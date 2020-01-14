<?php

require_once __DIR__ . '/../../../../videos/configuration.php';
require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../downloadTrait.php';

class EdxDownloadController extends EdxController {
    use DownloadTrait;

    protected $course_url;
    protected $course_title;
    protected $sections;
    protected $slug;

    public function __construct() {
        parent::__construct();
        $this->course_url = $_REQUEST['course_url'];
        $this->course_title = $_REQUEST['course_title'];
        $this->sections = $_REQUEST['sections'];

        // this is the folder in which edx-dl downloads the course
        $this->slug = $this->createSlug($this->course_title);
    }

    protected function createSlug($course_title) {
        // translated to PHP from
        // https://github.com/coursera-dl/edx-dl/blob/master/edx_dl/utils.py#L113
        $unescaped = html_entity_decode(urldecode($course_title));
        $stripped = str_replace(':', '-', $unescaped);
        $stripped = str_replace('/', '-', $stripped);
        $stripped = str_replace(chr(0), '-', $stripped);
        $stripped = str_replace("\n", '-', $stripped);
        $stripped = str_replace('(', '', $stripped);
        $stripped = str_replace(')', '', $stripped);
        $stripped = rtrim($stripped, '.');

        $stripped = str_replace(' ', '_', $stripped);

        $stripped = preg_replace('/[^a-zA-Z0-9\-_\.\(\)]/', '', $stripped);

        return $stripped;
    }

    /**
     * Go through a list of selected (& downloaded) lectures and queue them to be encoded
     * so that they'll be available on the streaming website.
     *
     * @param $success_ids
     */
    protected function encode($success_ids) {
        $download_folder = rtrim(Config::get('downloads.edx'), '/') . '/' . $this->slug . '/';
        $rdi = new RecursiveDirectoryIterator(
            $download_folder,
            FilesystemIterator::KEY_AS_PATHNAME |
            FilesystemIterator::CURRENT_AS_FILEINFO |
            FilesystemIterator::SKIP_DOTS
        );
        $rii = new RecursiveIteratorIterator($rdi);

        $category_id = $this->getCategoryId(
            str_replace('_', '-', $this->slug),
            $this->course_title
        );

        $success_ids = array_map('intval', $success_ids);

        foreach ($rii as $file) {
            // $file is something like
            // /var/www/ed-x/Anthropology_of_Current_World_Issues/01-Getting_started/01-Episode 0 - Getting Started.mp4
            //
            // we transform it so that $relative_path is something like
            // 01-Getting_started/01-Episode 0 - Getting Started.mp4
            $relative_path = trim(trim(str_replace($download_folder, '', $file)), '/');

            // now we misuse PHP's intval() function to convert $relative_path into
            // a number, which it does by converting everything until it reads a non-numeric character
            // -> so in our example, $section_id would be '1'
            $section_id = intval($relative_path);

            // we only import this file if that section is in the 'success_ids'
            if (!in_array($section_id, $success_ids)) {
                echo "<li>Not importing $relative_path</li>";
                continue;
            }

            if ($this->isVideo($file->getPathname())) {
                // we only import video files
                $result = $this->queue($file->getPathname(), $category_id);
                if ($result->error) {
                    $this->error($result->msg);
                }
            }
        }
    }

    /**
     * Tries to download the selected edX lectures, and will then add them
     * to the video streamer website.
     */
    public function run()
    {
        $base_cmd = dirname(__DIR__) . '/../scripts/edx/download.sh ';
        $base_cmd .= escapeshellarg($this->username) . ' ';
        $base_cmd .= escapeshellarg($this->password) . ' ';
        $base_cmd .= escapeshellarg($this->course_url) . ' ';

        $success = [];
        $failure = [];
        $success_ids = [];
        foreach ($this->sections as $number=>$title) {
            $hash = md5($number.$title);
            echo "<div id='download-{$hash}'>";
            $this->status($success, $failure);

            $cmd = $base_cmd . ' ';
            $cmd .= escapeshellarg($number); // for the record, '$number' is not necessarily a number (we don't check or enforce)
            $retCode = $this->runCmd($cmd);

            if ($retCode === 0) {
                echo "<script>jQuery('#download-{$hash}').empty();</script>";
                $success[] = $title;
                $success_ids[] = $number;
            } else {
                $failure[] = $title;
            }
            echo "</div>";
        }
        $this->status($success, $failure);

        // encode the videos we managed to obtain
        $this->encode($success_ids);
    }
}

(new EdxDownloadController())->run();