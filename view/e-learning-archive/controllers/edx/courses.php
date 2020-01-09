<?php

require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/controller.php';

class EdxCourses extends EdxController {

    public function parseOutput($output) {
        $lines = explode("\n", $output);
        $courses = [];
        $course = $course_url = null;
        foreach ($lines as $line) {
            $matches = [];
            if (preg_match('/^[0-9]+\s\-\s([^\[]*)/', trim($line), $matches)) {
                $course = trim($matches[1]);
            }
            if (filter_var(trim($line), FILTER_VALIDATE_URL)) {
                $course_url = trim($line);
            }

            if (!is_null($course) && !is_null($course_url)) {
                $courses[] = ['title'=>$course, 'url'=>$course_url];
                $course = $course_url = null;
            }
        }
        return $courses;
    }

    public function enrollmentInfo($as_link = false) {
        $display = $as_link ? 'display:none;' : '';
        if ($as_link) {
            echo "<a href='javascript:void(0);' id='edx-explainer-toggle' onclick='jQuery(\"#ed-finding-courses-explainer\").show();jQuery(\"#edx-explainer-toggle\").hide();return false;'>Trouble finding courses? Click here.</a>";
        }
        echo "<div style='overflow:hidden;background-color:#efefef;padding:10px;$display' id='ed-finding-courses-explainer'>";
        echo "<img src='/view/e-learning-archive/assets/edx-audit.jpg' width='300' style='float:right;margin-left:10px;'>";
        echo "<h4>Finding courses</h4>";
        echo "<p>Go to <a href='https://www.edx.org/course?program=all' target='_blank'>edx.org</a> ";
        echo "to find and enroll in courses. You need to be signed in first.</p>";
        echo "<p>Once you've found a course you want to enroll in, click the 'enroll' button. ";
        echo "After enrolling in a course, there's usually a section that says ";
        echo "<strong>Audit This Course (No Certificate)</strong>. You can see this in the screenshot on the right. ";
        echo "Press the blue <strong>Audit This Course</strong> button in that section to enroll in the class for free.</p>";
        echo "<small>Beware: Not all courses might offer this option.</small>";
        echo "</div>";
    }

    public function renderCourses($courses) {
        if (count($courses) === 0) {
            echo "<h3>You have not yet enrolled in any courses</h3>";
            echo "<p>You can only download courses you are enrolled in.</p>";
            $this->enrollmentInfo();
            echo "<script>showOnlyCloseButtonOnModal()</script>";
            return;
        }
        echo "<strong>Please select the course you want to share this week</strong><br /><br />";
        echo "<ul class='course-selector'>";
        foreach ($courses as $course) {
            $url = htmlentities($course['url']);
            $title = htmlentities($course['title']);
            $id = md5($url);
            echo "<li><input type='radio' name='course_url' value='$url' id='$id'> <label for='$id' style='font-weight:normal;'>$title</label></li>";
        }
        echo "</ul>";
        $this->enrollmentInfo(true);
    }

    public function run()
    {
        if (empty($this->username) || empty($this->password)) {
            $this->error("You need to provide both username and password");
        }
        $cmd = dirname(__DIR__) . '/../scripts/edx/courses.sh ' . escapeshellarg($this->username) . ' ' . escapeshellarg($this->password);

        $retCode = $this->runCmd($cmd);

        if ($retCode === 0) {
            echo "<script>jQuery('.cmd-output-container').empty()</script>";
            $courses = $this->parseOutput($this->getCmdOutput());
            $this->renderCourses($courses);
        } else {
            $this->error("There was an error trying to get the list of courses. Program exited with code " . $retCode);
        }

    }
}

(new EdxCourses())->run();