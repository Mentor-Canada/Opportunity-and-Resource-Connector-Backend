<?php

namespace Drupal\app;

use GuzzleHttp\Exception\GuzzleException;

class ServicesRequest
{
    public static function request($path, $method = 'GET')
    {
        $url = "{$_ENV['SERVICES_API_URL']}{$path}";
        $client = \Drupal::httpClient();
        try {
            $request = $client->request($method, $url, [
              'headers' => ['x-api-key' => $_ENV['SERVICES_API_KEY']]
            ]);
        } catch (GuzzleException $e) {
            return false;
        }
        $response = $request->getBody();
        return $response;
    }
}
