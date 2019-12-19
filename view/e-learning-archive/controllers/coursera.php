<?php

require_once __DIR__ . '/../Config.php';

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

    protected function path($file) {
        return rtrim(Config::get('downloads.coursera'), '/') . '/' . $file;
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
        echo "<div id='cmd-output-container'>";
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
        echo "</div>";

        return intval($retCode);
    }

    protected function syllabusPath() {
        return $this->path($this->course . "-syllabus-parsed.json");
    }

    protected function error($message, $code = 500) {
        http_response_code($code);
        echo $message;
        die;
    }

    protected function parseSyllabus() {
        $path = $this->syllabusPath();

        if (file_exists($path)) {
            $syllabus = [];
            try {
                $syllabus = json_decode(file_get_contents($path), true, 100, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                $this->error("The downloaded syllabus cannot be parsed.");
            }
        }

        // OMFG I could have really used something like jq here.
        $output = [];

        foreach ($syllabus as $module) {
            foreach ($module as $m=>$module_def) {
                if ($m === 0) {
                    $module_title = $module_def;
                    $output[$module_title] = [];
                    continue;
                }
                if ($m === 1) {
                    $sections = $module_def;
                    foreach ($sections as $s=>$section) {
                        foreach ($section as $sd=>$section_def) {
                            if ($sd === 0) {
                                $section_title = $section_def;
                                $output[$module_title][$section_title] = [];
                                continue;
                            }
                            if ($sd === 1) {
                                $lectures = $section_def;

                                foreach ($lectures as $l=>$lecture) {
                                    foreach ($lecture as $ld=>$lecture_def) {
                                        if ($ld === 0) {
                                            $lecture_title = $lecture_def;
                                            $output[$module_title][$section_title][$lecture_title] = [];
                                            continue;
                                        }
                                        if ($ld === 1) {
                                            $lecture_contents = $lecture_def;
                                            foreach ($lecture_contents as $type => $content) {
                                                if (strtolower($type) === 'mp4') {
                                                    foreach ($content as $file_def) {
                                                        foreach ($file_def as $file) {
                                                            if (strlen(trim($file)) > 0) {
                                                                $output[$module_title][$section_title][$lecture_title][] = trim($file);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }

    protected function showSyllabus() {
        $syllabus = $this->parseSyllabus();
        $id = 'tree_' . mt_rand(10000, 99999);
        echo "<strong>Please select the courses you want to share this week</strong><br /><br />";
        echo "<ul class='checktree' id='$id'>";
        foreach ($syllabus as $module_title=>$module) {
            echo '<li>';

            $module_title_esc = htmlentities($module_title);
            echo "<input name='syllabus[]' value='$module_title_esc' id='$module_title_esc' type='checkbox' /><label for='$module_title_esc'>$module_title_esc</label>";

            echo '<ul>';
            foreach ($module as $section_title=>$section) {
                $video_count = array_sum(array_map(function($lecture) { return count($lecture);}, $section));
                if ($video_count === 0) {
                    continue;
                }

                $section_title_esc = htmlentities($section_title);

                echo '<li>';
                echo "<input name='syllabus[]' value='$module_title_esc/$section_title_esc' id='$section_title_esc' type='checkbox' /><label for='$section_title_esc'>$section_title_esc</label>";

                echo '<ul>';
                foreach ($section as $lecture_title=>$files) {
                    if (count($files) === 0) {
                        continue;
                    }
                    echo '<li>';
                    $lecture_title_esc = htmlentities("$lecture_title");
                    $value = "$module_title_esc/$section_title_esc/$lecture_title_esc";
                    $id = md5($value);
                    echo "<input name='syllabus[]' value='$value' id='$id' type='checkbox' /><label for='$id'>$lecture_title_esc</label>";
                    echo '</li>';
                }
                echo '</ul>';
                echo '</li>';
            }
            echo '</ul>';

            echo '</li>';
        }
        echo '</ul>';
        echo "<script>jQuery('#$id').checktree()</script>";
    }

    public function run() {
        $syllabus = $this->syllabusPath();
        if (file_exists($syllabus)) {
            $this->showSyllabus();
        } else {
            $cmd = dirname(__DIR__) . '/scripts/coursera/syllabus.sh ' . escapeshellarg($this->cauth) . ' ' . escapeshellarg($this->course);

            $retCode = $this->runCmd($cmd);

            if ($retCode === 0) {
                if (file_exists($syllabus)) {
                    echo "<script>jQuery('#cmd-output-container').empty()</script>";
                    $this->showSyllabus();
                }
            } else {
                $this->error("There was an error trying to get the syllabus. Program exited with code " . $retCode);
            }
        }
    }
}

(new CourseraController())->run();