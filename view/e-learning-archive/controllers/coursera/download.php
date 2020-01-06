<?php

require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/../controller.php';

class DownloadController extends CourseraController {
    protected $lectures = [];

    public function __construct() {
        parent::__construct();

        $this->lectures = $_POST['syllabus'];

        if (count($this->lectures) === 0) {
            $this->error('You did not select any lectures to download');
        }
    }

    protected function status($lectures, $failure) {
        echo "<div class='download-status'>";
        if (count($lectures) > 0 || count($failure) > 0) {
            echo "<h3>Download status</h3>";
        }
        if (count($lectures) > 0) {
            echo "<strong>Successfully downloaded</strong>";
            echo "<ul><li>" . implode('</li><li>', $lectures) . '</li></ul>';
        }
        if (count($failure) > 0) {
            echo "<strong>Error downloading</strong>";
            echo "<ul><li>" . implode('</li><li>', $failure) . '</li></ul>';
        }
        echo "</div>";
    }

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
    }
}

(new DownloadController())->run();