<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "strings_resource",
 *   label = @Translation("Strings Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/strings"
 *   }
 * )
 */
class StringsResource extends ResourceBase
{
    public function get()
    {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $database = \Drupal::database();
        $q = "SELECT
            source, translation FROM locales_source
          LEFT JOIN locales_target ON locales_source.lid = locales_target.lid
          WHERE source LIKE 'app-%' AND locales_target.language = '$language'";
        $rows = $database->query($q)->fetchAll();
        $rows = array_map(function ($row) {
            return (array)$row;
        }, $rows);
        $response = ['status' => 'success', 'data' => $rows];
        return new ResourceResponse($response);
    }
}
