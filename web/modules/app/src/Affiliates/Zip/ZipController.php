<?php

namespace Drupal\app\Affiliates\Zip;

use Drupal\app\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ZipController extends BaseController
{
    public static function index(): JsonResponse
    {
        $zipsQ = \Drupal::database()->select('node__field_zips', 't');
        $zipsQ->addField('t', 'field_zips_value');

        $params = new ZipCollectionRequestParams();

        $collection = (new ZipCollectionBuilder())
      ->condition('abrv', $params->filter['state'])
      ->condition('county', "%{$params->filter['county']}%", 'LIKE')
      ->condition('city', "%{$params->filter['city']}%", 'LIKE')
      ->condition('zip', "%{$params->filter['zip']}%", 'LIKE')
      ->condition('zip', $zipsQ, 'NOT IN')
      ->offset($params->offset)
      ->limit($params->limit)
      ->build();

        return new JsonResponse($collection);
    }
}
