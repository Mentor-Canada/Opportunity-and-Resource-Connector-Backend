<?php

namespace rest;

use Exception;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

class Request extends TestCase
{
    private array $data = [];
    private $uri;
    private $method = "POST";
    private Session $session;
    private $expectedResponseCodes = [200];
    private $dataIsMultipart = false;

    public function uri($uri): Request
    {
        $this->uri = $uri;
        return $this;
    }

    public function method($method): Request
    {
        $this->method = $method;
        return $this;
    }

    public function data($data, $dataIsMultiPart = false): Request
    {
        $this->data = (array) $data;
        $this->dataIsMultipart = $dataIsMultiPart;
        return $this;
    }

    public function session(Session $session): Request
    {
        $this->session = $session;
        return $this;
    }

    public function expectedResponseCode($code): Request
    {
        if (!is_array($code)) {
            $code = [$code];
        }
        $this->expectedResponseCodes = $code;
        return $this;
    }

    public function execute()
    {
        if (empty($this->session)) {
            $this->session = new Session();
        }
        if ($this->dataIsMultipart) {
            $data = [RequestOptions::MULTIPART => $this->data];
        } else {
            $data = [RequestOptions::JSON => $this->data];
        }
        $response = null;
        try {
            $response = $this->session->request($this->method, $this->uri, $data);
            $code = $response->getStatusCode();
            $this->assertContains($code, $this->expectedResponseCodes);
        } catch (Exception $e) {
            $code = $e->getCode();
            $this->assertContains($code, $this->expectedResponseCodes, "Response status code is incorrect.");
        }
        return $response;
    }
}
