<?php

require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/controller.php';

class EdxSections extends EdxController {

    protected $course_url;
    protected $course_title;

    public function __construct() {
        parent::__construct();
        $this->course_url = $_REQUEST['course_url'];
        $this->course_title = $_REQUEST['course_title'];
    }

    public function parseOutput($output) {
        $lines = explode("\n", $output);
        $sections = [];

        foreach ($lines as $line) {
            $matches = [];
            if (preg_match('/^\s*?([0-9]+)\s\-\sDownload (.+) videos/', trim($line), $matches)) {
                $section_id = intval($matches[1]);
                $section_title = $matches[2];

                $sections[] = [
                    'id' => $section_id,
                    'title' => $section_title
                ];
            }
        }
        return $sections;
    }

    public function renderSections($sections) {
        echo "<strong>Please select the sections you want to share this week</strong><br /><br />";
        echo "<ul class='section-selector'>";
        foreach ($sections as $section) {
            $nr = htmlentities($section['id']);
            $title = htmlentities($section['title']);
            $id = md5($title.$nr);
            echo "<li>";
            echo "<input type='checkbox' name='section[]' value='$nr' id='$id'> ";
            echo "<label for='$id' style='font-weight:normal;'>$title</label>";
            echo "</li>";
        }
        echo "</ul>";
    }

    public function run()
    {
        if (empty($this->username) || empty($this->password) || empty($this->course_url)) {
            $this->error("You need to provide both username, password, and course URL");
        }
        $cmd = dirname(__DIR__) . '/../scripts/edx/sections.sh ' .
            escapeshellarg($this->username) . ' ' .
            escapeshellarg($this->password) . ' ' .
            escapeshellarg($this->course_url);

        $retCode = $this->runCmd($cmd);

        if ($retCode === 0) {
            echo "<script>jQuery('.cmd-output-container').empty()</script>";
            $courses = $this->parseOutput($this->getCmdOutput());
            $this->renderSections($courses);
        } else {
            $this->error("There was an error trying to get the list of courses. Program exited with code " . $retCode);
        }

    }
}

(new EdxSections())->run();