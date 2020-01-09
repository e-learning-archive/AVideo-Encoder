<?php

require_once __DIR__ . '/../../../../videos/configuration.php';
require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../../../../objects/Encoder.php';
require_once __DIR__ . '/../../../../objects/Streamer.php';
require_once __DIR__ . '/../../../../objects/Login.php';
require_once __DIR__ . '/../downloadTrait.php';

class EdxDownloadController extends EdxController {
    use DownloadTrait;

    protected $course_url;
    protected $course_title;
    protected $sections;

    public function __construct() {
        parent::__construct();
        $this->course_url = $_REQUEST['course_url'];
        $this->course_title = $_REQUEST['course_title'];
        $this->sections = $_REQUEST['sections'];
    }

    /**
     * Go through a list of selected (& downloaded) lectures and queue them to be encoded
     * so that they'll be available on the streaming website.
     *
     * @param $success_ids
     */
    protected function encode($success_ids) {
        echo "Encode " . print_r($success_ids, true);
        die;

    }

    protected function getDownloadFolder() {
        return rtrim(Config::get('downloads.edx'), '/') . '/';
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
        $base_cmd .= escapeshellarg($this->getDownloadFolder()) . ' ';
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
//                echo "<script>jQuery('#download-{$hash}').empty();</script>";
                $success[] = $title;
                $success_ids[] = $number;
            } else {
                $failure[] = $title;
            }
            echo "</div>";
        }
        echo $this->getCmdOutput();
        $this->status($success, $failure);

        // encode the videos we managed to obtain
        $this->encode($success_ids);
    }
}

(new EdxDownloadController())->run();