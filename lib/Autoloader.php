<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {

            //class directories
            $directories = array(
                DIRECTORY_SEPARATOR,
                '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR,
            );

            foreach ($directories as $directory) {
                $file = $directory . $class . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }

            throw new Exception('Could not autoregister file: %s', $file);
        });
    }
}