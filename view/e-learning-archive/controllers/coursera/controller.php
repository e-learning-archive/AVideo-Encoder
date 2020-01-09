<?php
require_once __DIR__ . '/../controller.php';

abstract class CourseraController extends Controller {

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

        // store cauth and url in the session so that on the next visit
        // it can be pre-filled in the form
        $_SESSION['coursera-cauth'] = $this->cauth;
        $_SESSION['coursera-url'] = $this->url;
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

}