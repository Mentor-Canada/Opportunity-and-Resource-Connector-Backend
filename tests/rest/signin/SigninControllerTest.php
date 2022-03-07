<?php

namespace rest\signin;

use GuzzleHttp\Client;
use rest\Request;
use rest\RestTestCase;

class SigninControllerTest extends RestTestCase
{
    public function testSignIn()
    {
        $name = "admin@example.com";
        $pass = "admin";
        $data = [
            "name" => $name,
            "pass" => $pass
        ];
        $response = (new Request())
      ->uri('/user/login?_format=json')
      ->data($data)
      ->execute();
        $body = json_decode($response->getBody());
        $responseHeaders = $response->getHeaders();
        $cookie = $responseHeaders['Set-Cookie'][0];
        $authenticated = $body->current_user->roles[0];
        $currentUserName = $body->current_user->name;
        $uid = $body->current_user->uid;
        $logOutToken = $body->logout_token;
        $this->assertEquals('authenticated', $authenticated, "User role was not set to 'authenticated'");
        $this->assertEquals($name, $currentUserName, "The expected user name was not returned");
        $this->assertNotNull($uid, "The expected user ID was null instead");
        return [
            'cookie' => $cookie,
            'logOutToken' => $logOutToken
        ];
    }

    /**
     * @depends testSignIn
     */
    public function testDenyDuplicateSignIn()
    {
        $name = "admin@example.com";
        $pass = "admin";
        $data = [
            "name" => $name,
            "pass" => $pass
        ];
        $response = (new Request())
      ->uri('/user/login?_format=json')
      ->data($data)
      ->session($this->globalAdministratorSession())
      ->expectedResponseCode(403)
      ->execute();
    }

    /**
     * @depends testSignIn
     */
    public function testGetSignoutToken($items)
    {
        $client = new Client(['base_uri' => 'http://localhost']);
        $data = [
            'headers' => [
                "Cookie" => $items['cookie']
            ]
        ];
        $response = $client->request('GET', '/session/logouttoken?_format=json', $data);
        $body = json_decode($response->getBody());
        $retrievedToken = $body->logout_token;
        $this->assertEquals($items['logOutToken'], $retrievedToken, "The expected logout token was not retrieved");
    }

    /**
     * @depends testSignIn
     */
    public function testSignOut($items)
    {
        $client = new Client(['base_uri' => 'http://localhost']);
        $data = [
            'headers' => [
                "Cookie" => $items['cookie']
            ]
        ];
        $response = $client->request('POST', '/user/logout?_format=json&token=' . $items['logOutToken'], $data);
        $status = $response->getStatusCode();
        $this->assertEquals(204, $status, "The status code expected on logout was not retrieved");
    }
}
