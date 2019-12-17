<?php

class Config {
    protected static function getConfig() {
        static $config;
        if (empty($config)) {
            $config = json_decode(file_get_contents(__DIR__ . '/config.json'));
        }

        return $config;
    }

    public static function get($key) {
        $config = self::getConfig();
        if (property_exists($config, $key)) {
            return $config->$key;
        }

        return null;
    }
}