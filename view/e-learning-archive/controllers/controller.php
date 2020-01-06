<?php

abstract class CourseraController {

    protected $course = false;
    protected $cauth;
    protected $url;

    public function __construct()
    {
        $this->url = $_REQUEST["url"];
        $this->cauth = $_REQUEST["cauth"];

        $this->course = $this->extractCourse($this->url);

        if (!$this->course) {
            $this->invalidUrl();
        }
    }

    public function runCmd($cmd)
    {
        $retCode = null;
        // execute the shell script. The code below is basically 'passthru()' but without buffering.
        ob_end_flush();
        $handle = popen($cmd, 'r');

        $hash = md5($cmd);

        // Continuously scroll to the bottom of the output element.
        // This PHP code is requested with an ajax call with a 'progress' handler, which renders all
        // of the output every time that it receives an update. Therefore, the javascript below is
        // executed many (many) times, not just once, so we don't have to do a setInterval or
        // setTimeout or something.
        echo "<div class='cmd-output-container'>";
        echo "<script>jQuery('#cmd-output-{$hash}').scrollTop(jQuery('#cmd-output-{$hash}')[0].scrollHeight);</script>";
        echo "<pre style='height: 80vh; overflow: auto;' id='cmd-output-{$hash}'>";
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
        echo "</div>";

        return intval($retCode);
    }

    protected function extractCourse($url)
    {
        $re = '/https:\/\/(www\.)?coursera\.org\/learn\/([\w\-]+)\/?.*/m';

        preg_match_all($re, $url, $matches, PREG_SET_ORDER, 0);

        if (count($matches) > 0) {
            $match = current($matches);
            return $match[2];
        }

        return false;
    }

    protected function path($file)
    {
        return rtrim(Config::get('downloads.coursera'), '/') . '/' . $file;
    }

    protected function invalidUrl()
    {
        http_response_code(400);
        die("
Invalid URL.<br /><br />
This is either not a Coursera URL, or it is malformed.<br />
A valid URL looks something like this:\n\nhttps://www.coursera.org/learn/some-course-name/home/welcome
");
    }

    protected function error($message, $code = 500)
    {
        http_response_code($code);
        echo $message;
        die;
    }

    abstract public function run();
}