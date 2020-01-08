<?php

require_once __DIR__ . '/../../Config.php';
require_once __DIR__ . '/controller.php';

class SyllabusController extends CourseraController {

    protected function syllabusPath() {
        return $this->path($this->course . "-syllabus-parsed.json");
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
        echo "<div class='logical-form-group'>";
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
                    $count = count($files);
                    echo "<input name='syllabus[]' value='$value' id='$id' type='checkbox' data-video-count='$count' /><label for='$id'>$lecture_title_esc ($count videos)</label>";
                    echo '</li>';
                }
                echo '</ul>';
                echo '</li>';
            }
            echo '</ul>';

            echo '</li>';
        }
        echo '</ul>';
        echo '<div class=\'error-message\'></div>';
        echo '</div>';
        echo "<script>jQuery('#$id').checktree()</script>";
    }

    public function run() {
        $syllabus = $this->syllabusPath();
        if (file_exists($syllabus)) {
            $hour = 60 * 60;
            if (time() - filectime($syllabus) > 1 * $hour) {
                // delete syllabus if it's too old (it contains time-limited download links)
                unlink($syllabus);
            }
        }

        if (file_exists($syllabus)) {
            $this->showSyllabus();
        } else {
            $cmd = dirname(__DIR__) . '/../scripts/coursera/syllabus.sh ' . escapeshellarg($this->cauth) . ' ' . escapeshellarg($this->course);

            $retCode = $this->runCmd($cmd);

            if ($retCode === 0) {
                if (file_exists($syllabus)) {
                    echo "<script>jQuery('.cmd-output-container').empty()</script>";
                    $this->showSyllabus();
                }
            } else {
                $this->error("There was an error trying to get the syllabus. Program exited with code " . $retCode);
            }
        }
    }
}

(new SyllabusController())->run();