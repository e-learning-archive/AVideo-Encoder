<?php

class CourseraController {
    protected $url;
    protected $cauth;

    public function __construct() {
        $this->url = $_GET["url"];
        $this->cauth = $_GET["cauth"];

        $this->course = $this->extractCourse($this->url);

        if (!$this->course) {
            $this->invalidUrl();
        }
    }

    protected function invalidUrl() {
        http_response_code(400);
        die("
Invalid URL.<br /><br />
This is either not a Coursera URL, or it is malformed.<br />
A valid URL looks something like this:\n\nhttps://www.coursera.org/learn/some-course-name/home/welcome
");
    }

    protected function extractCourse($url) {
        $re = '/https:\/\/(www\.)?coursera\.org\/learn\/([\w\-]+)\/?.*/m';

        preg_match_all($re, $url, $matches, PREG_SET_ORDER, 0);

        if (count($matches)>0) {
            $match = current($matches);
            return $match[2];
        }

        return false;
    }

    public function runCmd($cmd) {
        $retCode = null;
        // execute the shell script. The code below is basically 'passthru()' but without buffering.
        ob_end_flush();
        $handle = popen($cmd, 'r');

        // Continuously scroll to the bottom of the output element.
        // This PHP code is requested with an ajax call with a 'progress' handler, which renders all
        // of the output every time that it receives an update. Therefore, the javascript below is
        // executed many (many) times, not just once, so we don't have to do a setInterval or
        // setTimeout or something.
        echo "<script>jQuery('#cmd-output').scrollTop(jQuery('#cmd-output')[0].scrollHeight);</script>";
        echo "<pre style='height: 80vh; overflow: auto;' id='cmd-output'>";
        while (!feof($handle)) {
            echo fgets($handle, 256);
            ob_flush();
            flush();
        }
        echo "\n\n\nProgram ended.";
        echo "</pre>";
        $retCode = pclose($handle);

        if (intval($retCode) !== 0) {
            echo "<strong>Program ended with exit code $retCode</strong>";
        }
    }

    public function run() {
        $cmd = dirname(__DIR__) . '/scripts/coursera/syllabus.sh ' . escapeshellarg($this->cauth) . ' ' . escapeshellarg($this->course);

        $this->runCmd($cmd);
    }
}

(new CourseraController())->run();