<?php

require_once __DIR__ . '/../controller.php';

abstract class EdxController extends Controller
{
    protected $username = null;
    protected $password = null;

    public function __construct()
    {
        $this->username = $_REQUEST["username"];
        $this->password = $_REQUEST["password"];

        // store username and password in the session so that on the next visit
        // they can be pre-filled in the 'username'/'password' form
        $_SESSION['edx-username'] = $this->username;
        $_SESSION['edx-password'] = $this->password;
    }

}