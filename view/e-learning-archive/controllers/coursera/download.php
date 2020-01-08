<?php

require_once __DIR__ . '/../../../../videos/configuration.php';
require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/../controller.php';
require_once __DIR__ . '/../../../../objects/Encoder.php';
require_once __DIR__ . '/../../../../objects/Streamer.php';
require_once __DIR__ . '/../../../../objects/Login.php';

class DownloadController extends CourseraController {
    protected $lectures = [];

    public function __construct() {
        parent::__construct();

        $this->lectures = $_POST['syllabus'];

        if (count($this->lectures) === 0) {
            $this->error('You did not select any lectures to download');
        }
    }

    /**
     * Shows the status of downloaded lectures.
     *
     * @param $success
     * @param $failure
     */
    protected function status($success, $failure) {
        echo "<div class='download-status'>";
        if (count($success) > 0 || count($failure) > 0) {
            echo "<h3>Download status</h3>";
        }
        if (count($success) > 0) {
            echo "<strong>Successfully downloaded</strong>";
            echo "<ul><li>" . implode('</li><li>', $success) . '</li></ul>';
        }
        if (count($failure) > 0) {
            echo "<strong>Error downloading</strong>";
            echo "<ul><li>" . implode('</li><li>', $failure) . '</li></ul>';
        }
        echo "</div>";
    }

    protected function getCategoryName($slug = null) {
        if (is_null($slug)) {
            $slug = $this->course;
        }
        return ucfirst(strtolower(str_replace('-', ' ', $slug)));
    }

    protected function createCategory($slug, $name, $description) {

        $streamer = new Streamer(Login::getStreamerId());

        $postFields = array(
            "id" => "",
            "name" => $name,
            "clean_name" => $slug,
            "description" => $description,
            "nextVideoOrder" => "1",
            "private" => "1",
            "allow_download" => "1",
            "parentId" => "0",
            "type" => "2",
            "iconClass" => "fa fa-fw iconpicker-component",
        );

        $target = rtrim($streamer->getSiteURL(), '/') . '/objects/categoryAddNew.json.php';
        $cookie = [
            'user=' . $streamer->getUser(),
            'pass=' . $streamer->getPass(),
            'aVideoURL=' . $streamer->getSiteURL(),
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_COOKIE, implode('; ', $cookie));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($r, true);
        if (array_key_exists('status', $response)) {
            // re-login so that $_SESSION['login']->categories is repopulated
            // with the correct (new) categories.
            @Login::run($streamer->getUser(), $streamer->getPass(), $streamer->getSiteURL());

            return intval($response['status']);
        }

        return 0; // means: upload to 'default' category
    }

    protected function getCategoryId($slug, $category_name) {
        foreach ($_SESSION['login']->categories as $key => $value) {
            // this codebase is insane yo
            if ($value->name === $category_name) {
                return $value->id;
            }
        }

        return $this->createCategory($slug, $category_name, $category_name);
    }

    /**
     * Queues a file to be included on the video site.
     *
     * @param $file
     */
    protected function queue($file) {
        $e = new Encoder(null);
        if (!($streamers_id = Login::getStreamerId())) {
            $this->error("There is no streamer site");
        }
        $pathinfo = pathinfo($file);

        $e->setStreamers_id($streamers_id);
        $s = new Streamer($streamers_id);
        $e->setFileURI($file);
        $e->setFilename($pathinfo['basename']);
        $e->setTitle(ucfirst(strtolower(str_replace('-', ' ', $pathinfo['filename']))));
        $e->setPriority($s->getPriority());

        $_POST["categories_id"] = $this->getCategoryId($this->course, $this->getCategoryName($this->course));

        // we need to put stuff in the $_POST array to get decideFormatOrder() to work
        $_POST['webm'] = 'false';
        $_POST['audioOnly'] = 'false';
        $_POST['spectrum'] = 'false';
        $_POST['webm'] = 'false';
        $_POST['inputHLS'] = 'false';
        $_POST['inputLow'] = 'true';
        $_POST['inputSD'] = 'true';
        $e->setFormats_idFromOrder(decideFormatOrder());

        $f = new Format($e->getFormats_id());
        $format = $f->getExtension();

        $obj = new stdClass();
        $response = Encoder::sendFile('', 0, $format, $e);
        //var_dump($response);exit;
        if(!empty($response->response->video_id)){
            $obj->videos_id = $response->response->video_id;
        }
        $e->setReturn_vars(json_encode($obj));
        $id = $e->save();
    }

    /**
     * Go through a list of selected (& downloaded) lectures and queue them to be encoded
     * so that they'll be available on the streaming website.
     *
     * @param $paths
     */
    protected function encode($paths) {
        $download_folder = rtrim(Config::get('downloads.coursera'), '/') . '/' . $this->course . '/';
        $rdi = new RecursiveDirectoryIterator(
            $download_folder,
            FilesystemIterator::KEY_AS_PATHNAME |
            FilesystemIterator::CURRENT_AS_FILEINFO |
            FilesystemIterator::SKIP_DOTS
        );
        $rii = new RecursiveIteratorIterator($rdi);

        $files = array();

        foreach ($rii as $file) {

            $this_file = substr($file->getPathname(), strlen($download_folder));
            // e.g. '01_week-1-fundamentals-of-imagemaking/03_imagemaking-techniques/02_techniques-of-imagemaking-2.mp4'

            foreach ($paths as $p) {
                // $p is e.g. 'week-1-fundamentals-of-imagemaking/imagemaking-techniques/techniques-of-imagemaking-2'
                list($p_module, $p_section, $p_lecture) = explode('/', $p);
                list($f_module, $f_section, $f_lecture) = explode('/', $this_file);

                if (
                    (strpos($f_module, $p_module) !== false) &&
                    (strpos($f_section, $p_section) !== false) &&
                    (strpos($f_lecture, $p_lecture) !== false)
                ) {
                    // $p and $this_file have the same module, section, and lecture
                    // -> queue the file to be encoded
                    $result = $this->queue($file->getPathname());
                    if ($result->error) {
                        $this->error($result->msg);
                    }
                }
            }
        }
    }

    /**
     * Tries to download the selected Coursera lectures, and will then add them
     * to the video streamer website.
     */
    public function run()
    {
        $base_cmd = dirname(__DIR__) . '/../scripts/coursera/download.sh ';
        $base_cmd .= escapeshellarg($this->cauth) . ' ';
        $base_cmd .= escapeshellarg($this->course);

        $success = [];
        $failure = [];
        foreach ($this->lectures as $path) {
            $hash = md5($path);
            echo "<div id='download-{$hash}'>";
            $this->status($success, $failure);

            list($module, $section, $lecture) = explode('/', $path);
            $cmd = $base_cmd . ' ';
            $cmd .= escapeshellarg($section) . ' ';
            $cmd .= escapeshellarg($lecture) . ' ';
            $retCode = $this->runCmd($cmd);

            if ($retCode === 0) {
                echo "<script>jQuery('#download-{$hash}').empty();</script>";
                $success[] = $path;
            } else {
                $failure[] = $path;
            }
            echo "</div>";
        }
        $this->status($success, $failure);

        // encode the videos we managed to obtain
        $this->encode($success);
    }
}

(new DownloadController())->run();