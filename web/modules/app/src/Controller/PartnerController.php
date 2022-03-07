<?php

namespace Drupal\app\Controller;

use Drupal\app\Utils\Utils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PartnerController extends ControllerBase implements ContainerInjectionInterface
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

    public function index()
    {
        $array = Utils::getSubRequestData('/a/node/partner', $this->httpKernel);

        $rows = [];
        $header = [];
        $header[] = strval(t('app-field-display-title'));
        $header[] = strval(t('app-field-id'));
        $header[] = strval(t('app-created'));

        $rows[] = $header;

        foreach ($array['data'] as $row) {
            $attributes = $row['attributes'];

            $item = [];

            $item[] = $attributes['field_display_title'];
            $item[] = $attributes['field_id'];
            $item[] = $attributes['created'];

            $rows[] = $item;
        }

        Utils::exporter($rows, 'partners.csv');

        exit;
    }
}
