<?php

class Config {
    protected static function getConfig() {
        static $config;
        if (empty($config)) {
            $config = json_decode(file_get_contents(__DIR__ . '/config.json'));
        }

        return $config;
    }

    public static function get($key = '') {
        $config = self::getConfig();

        $parts = explode(".", $key);
        $current = $config;

        while ($section = array_shift($parts)) {
            if (is_object($current) && property_exists($current, $section)) {
                $current = $current->$section;
            } else {
                return null;
            }
        }

        return $current;
    }
}