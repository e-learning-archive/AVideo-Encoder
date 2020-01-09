<?php

require_once __DIR__ . '/../../../../videos/configuration.php';
require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../downloadTrait.php';
require_once __DIR__ . '/../../../../objects/Encoder.php';
require_once __DIR__ . '/../../../../objects/Streamer.php';
require_once __DIR__ . '/../../../../objects/Login.php';

class DownloadController extends CourseraController {
    use DownloadTrait;

    protected $lectures = [];

    public function __construct() {
        parent::__construct();

        $this->lectures = $_POST['syllabus'];

        if (count($this->lectures) === 0) {
            $this->error('You did not select any lectures to download');
        }
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
                    $category_id = $this->getCategoryId($this->course, $this->getCategoryName($this->course));
                    $result = $this->queue($file->getPathname(), $category_id);
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