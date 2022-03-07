<?php

namespace Drupal\app\Utils;

class RequestUtils
{
    public static function postData($assoc = false)
    {
        $postBody = \Drupal::request()->getContent();
        return json_decode($postBody, $assoc);
    }
}
