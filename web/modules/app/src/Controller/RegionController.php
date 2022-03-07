<?php

namespace Drupal\app\Controller;

use Drupal\app\Builders\RequestBuilder;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegionController extends ControllerBase implements ContainerInjectionInterface
{
    protected $httpKernel;

    public function __construct($http_kernel)
    {
        $this->httpKernel = $http_kernel;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
        $container->get('http_kernel.basic')
    );
    }

    public function add()
    {
        $request = new RequestBuilder($this->httpKernel, "/a/regions");
        $response = $request->execute();
        return $response;
    }

    public function update($id)
    {
        $request = new RequestBuilder($this->httpKernel, "/a/regions/$id");
        $response = $request->method("PATCH")
      ->execute()
    ;
        return $response;
    }

    public function delete($id)
    {
        $request = new RequestBuilder($this->httpKernel, "/a/regions/$id");
        $response = $request->method("DELETE")
      ->execute()
      ;
        return $response;
    }
}
