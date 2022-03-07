<?php

namespace Drupal\app\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class VersionController extends ControllerBase implements ContainerInjectionInterface
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

    public function get()
    {
        return new JsonResponse(['data' => [
            'version' => $_ENV['VERSION']
        ]]);
    }
}
