<?php

namespace Drupal\app\Affiliates;

use Drupal\app\Affiliates\Zip\ZipCollectionBuilder;
use Drupal\app\Affiliates\Zip\ZipCollectionRequestParams;
use Drupal\app\Controller\BaseController;
use Laminas\Diactoros\Response\JsonResponse;

class AffiliateController extends BaseController
{
    public static function addZip($uuid, $zip)
    {
        $affiliate = AffiliateNodeDecorator::withUUID($uuid);
        $affiliate->addZip($zip);
        return new JsonResponse(["status" => "success"]);
    }

    public static function addZips($uuid)
    {
        $affiliate = AffiliateNodeDecorator::withUUID($uuid);
        $params = new ZipCollectionRequestParams();
        $zipsQ = \Drupal::database()->select('node__field_zips', 't');
        $zipsQ->addField('t', 'field_zips_value');
        $builder = (new ZipCollectionBuilder())
      ->condition('abrv', $params->filter['state'])
      ->condition('county', "%{$params->filter['county']}%", 'LIKE')
      ->condition('city', "%{$params->filter['city']}%", 'LIKE')
      ->condition('zip', "%{$params->filter['zip']}%", 'LIKE')
      ->condition('zip', $zipsQ, 'NOT IN');
        $rows = $builder->q->execute()->fetchCol(0);
        $affiliate->addZips($rows);
        return new JsonResponse(["status" => "success"]);
    }

    public static function removeZips($uuid)
    {
        $affiliate = AffiliateNodeDecorator::withUUID($uuid);

        $params = new ZipCollectionRequestParams();
        $builder = (new ZipCollectionBuilder())
      ->condition('abrv', $params->filter['state'])
      ->condition('county', "%{$params->filter['county']}%", 'LIKE')
      ->condition('city', "%{$params->filter['city']}%", 'LIKE')
      ->condition('zip', "%{$params->filter['zip']}%", 'LIKE');
        $rows = $builder->q->execute()->fetchCol(0);
        $affiliate->removeZips($rows);
        return new JsonResponse(["status" => "success"]);
    }

    public static function removeZip($uuid, $zip)
    {
        $affiliate = AffiliateNodeDecorator::withUUID($uuid);
        $affiliate->removeZip($zip);
        return new JsonResponse(["status" => "success"]);
    }

    public static function zipIndex($uuid)
    {
        $affiliate = AffiliateNodeDecorator::withUUID($uuid);

        $zipsQ = \Drupal::database()->select('node__field_zips', 't');
        $zipsQ->addField('t', 'field_zips_value');
        $zipsQ->condition('t.bundle', 'region');
        $zipsQ->condition('t.entity_id', $affiliate->node->id());

        $params = new ZipCollectionRequestParams();

        $collection = (new ZipCollectionBuilder())
      ->condition('abrv', $params->filter['state'])
      ->condition('county', "%{$params->filter['county']}%", 'LIKE')
      ->condition('city', "%{$params->filter['city']}%", 'LIKE')
      ->condition('zip', "%{$params->filter['zip']}%", 'LIKE')
      ->condition('zip', $zipsQ, 'IN')
      ->offset($params->offset)
      ->limit($params->limit)
      ->build();

        return new \Symfony\Component\HttpFoundation\JsonResponse($collection);
    }
}
