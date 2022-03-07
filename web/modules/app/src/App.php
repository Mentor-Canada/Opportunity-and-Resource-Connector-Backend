<?php

namespace Drupal\app;

class App
{
    private static App $instance;

    public $uilang;

    public static function getInstance(): App
    {
        if (!isset(self::$instance)) {
            self::$instance = new App();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->uilang = $this->uilang();
    }

    private function uilang(): string
    {
        $postData = $_REQUEST['entityData'] ?: \Drupal::request()->getContent();
        $postBody = json_decode($postData);
        return $postBody->uilang ?? 'en';
    }
}
