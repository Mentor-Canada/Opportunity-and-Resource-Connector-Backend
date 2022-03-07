<?php

namespace rest;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

class Session extends TestCase
{
    private CookieJar $jar;

    public function __construct()
    {
        $this->jar = new CookieJar();
    }

    public function request($method, $uri, $data = [])
    {
        $data['cookies'] = $this->jar;
        $client = new Client(['base_uri' => 'http://localhost']);
        $response = $client->request($method, $uri, $data);
        return $response;
    }

    public function signIn($name = "admin@example.com", $pass = "admin")
    {
        $data = [
            RequestOptions::JSON => [
                "name" => $name,
                "pass" => $pass
            ]
        ];
        return $this->request('POST', '/user/login?_format=json', $data);
    }

    public function getUserId()
    {
        $userData = json_decode($this->request('GET', 'a/')->getBody());
        return $userData->meta->links->me->meta->id;
    }
}
