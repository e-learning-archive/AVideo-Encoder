<?php
require __DIR__ . '/../Config.php';

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


    public function run() {
        $cmd = dirname(__DIR__) . '/scripts/coursera/syllabus.sh "' . $this->cauth . '" "' . $this->course . '"';

        echo $cmd;
        $output = [];
        $retCode = null;
        exec($cmd, $output, $retCode);

        var_dump($retCode);
        var_dump($output);
    }
}

(new CourseraController())->run();