<?php

namespace Drupal\app\Builders;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestBuilder
{
    private $httpKernel;
    private $_uri;
    private $_method = "POST";
    private $_body;

    public function __construct($httpKernel, $uri)
    {
        $this->httpKernel = $httpKernel;
        $this->_uri = $uri;
    }

    public function method($method): RequestBuilder
    {
        $this->_method = $method;
        return $this;
    }

    public function body($body, $isJSON = false): RequestBuilder
    {
        if (!$isJSON) {
            $this->_body = json_encode($body);
        } else {
            $this->_body = $body;
        }
        return $this;
    }

    public function execute()
    {
        if (!$this->_body) {
            $this->_body = \Drupal::request()->getContent();
        }
        $request = Request::create(
            $this->_uri,
            $this->_method,
            [],
            [],
            [],
            [],
            $this->_body
        );
        $request->headers->set('content-type', 'application/vnd.api+json');
        return $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);
    }
}
