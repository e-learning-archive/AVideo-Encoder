<?php

trait DownloadTrait {


    /**
     * Shows the status of downloaded lectures.
     *
     * @param $success
     * @param $failure
     */
    protected function status($success, $failure) {
        echo "<div class='download-status'>";
        if (count($success) > 0 || count($failure) > 0) {
            echo "<h3>Download status</h3>";
        }
        if (count($success) > 0) {
            echo "<strong>Successfully downloaded</strong>";
            echo "<ul><li>" . implode('</li><li>', $success) . '</li></ul>';
        }
        if (count($failure) > 0) {
            echo "<strong>Error downloading</strong>";
            echo "<ul><li>" . implode('</li><li>', $failure) . '</li></ul>';
        }
        echo "</div>";
    }

    protected function getCategoryName($slug) {
        return ucfirst(strtolower(str_replace('-', ' ', $slug)));
    }

    protected function createCategory($slug, $name, $description) {

        $streamer = new Streamer(Login::getStreamerId());

        $postFields = array(
            "id" => "",
            "name" => $name,
            "clean_name" => $slug,
            "description" => $description,
            "nextVideoOrder" => "1",
            "private" => "1",
            "allow_download" => "1",
            "parentId" => "0",
            "type" => "2",
            "iconClass" => "fa fa-fw iconpicker-component",
        );

        $target = rtrim($streamer->getSiteURL(), '/') . '/objects/categoryAddNew.json.php';
        $cookie = [
            'user=' . $streamer->getUser(),
            'pass=' . $streamer->getPass(),
            'aVideoURL=' . $streamer->getSiteURL(),
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_COOKIE, implode('; ', $cookie));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($r, true);
        if (array_key_exists('status', $response)) {
            // re-login so that $_SESSION['login']->categories is repopulated
            // with the correct (new) categories.
            @Login::run($streamer->getUser(), $streamer->getPass(), $streamer->getSiteURL());

            return intval($response['status']);
        }

        return 0; // means: upload to 'default' category
    }

    protected function getCategoryId($slug, $category_name) {
        foreach ($_SESSION['login']->categories as $key => $value) {
            // this codebase is insane yo
            if ($value->name === $category_name) {
                return $value->id;
            }
        }

        return $this->createCategory($slug, $category_name, $category_name);
    }

    /**
     * Queues a file to be included on the video site.
     *
     * @param $file
     * @param $category_id
     * @return stdClass
     */
    protected function queue($file, $category_id) {
        $e = new Encoder(null);
        if (!($streamers_id = Login::getStreamerId())) {
            $this->error("There is no streamer site");
        }
        $pathinfo = pathinfo($file);

        $e->setStreamers_id($streamers_id);
        $s = new Streamer($streamers_id);
        $e->setFileURI($file);
        $e->setFilename($pathinfo['basename']);
        $e->setTitle(ucfirst(strtolower(str_replace('-', ' ', $pathinfo['filename']))));
        $e->setPriority($s->getPriority());

        $_POST["categories_id"] = $category_id;

        // we need to put stuff in the $_POST array to get decideFormatOrder() to work
        $_POST['webm'] = 'false';
        $_POST['audioOnly'] = 'false';
        $_POST['spectrum'] = 'false';
        $_POST['webm'] = 'false';
        $_POST['inputHLS'] = 'false';
        $_POST['inputLow'] = 'true';
        $_POST['inputSD'] = 'true';
        $e->setFormats_idFromOrder(decideFormatOrder());

        $f = new Format($e->getFormats_id());
        $format = $f->getExtension();

        $obj = new stdClass();
        $response = Encoder::sendFile('', 0, $format, $e);
        //var_dump($response);exit;
        if(!empty($response->response->video_id)){
            $obj->videos_id = $response->response->video_id;
        }
        $e->setReturn_vars(json_encode($obj));
        $id = $e->save();

        return $response;
    }

}