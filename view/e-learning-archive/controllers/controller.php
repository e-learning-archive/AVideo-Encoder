<?php
session_start(); // need this in some of my code, and we're not using a framework here to do these things for us...

abstract class Controller {
    protected $output = '';

    protected function error($message, $code = 500)
    {
        http_response_code($code);
        echo $message;
        die;
    }

    protected function getCmdOutput() {
        return $this->output;
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
        echo "<script>jQuery('#cmd-output-{$hash}').scrollTop(jQuery('#cmd-output-{$hash}')[0].scrollHeight);disableModalButtons();</script>";
        echo "<pre style='height: 80vh; overflow: auto;' id='cmd-output-{$hash}'>";
        $this->output = '';
        while (!feof($handle)) {
            $line = fgets($handle, 1024);
            $this->output .= $line;

            echo $line;
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
        echo "<script>enableModalButtons();</script>";

        return intval($retCode);
    }

    abstract public function run();
}