<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Utils\Utils;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "country_stats_resource",
 *   label = @Translation("Country Stats Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/stats/country"
 *   }
 * )
 */
class CountryStatsResource extends ResourceBase
{
    public function get()
    {
        $database = \Drupal::database();
        $params = [];
        $where = [];
        if (isset($_REQUEST['title']) && $_REQUEST['title'] != 'null') {
            // TODO: Escape string!
            $title = $_REQUEST['title'];
            $where[] = "node_field_revision.title LIKE '%$title%'";
        }
        if (isset($_REQUEST['start']) && $_REQUEST['start'] != 'null') {
            $where[] = "node_revision.revision_timestamp >= :start";
            $params[':start'] = $_REQUEST['start'];
        }
        if (isset($_REQUEST['end']) && $_REQUEST['end'] != 'null') {
            $where[] = "node_revision.revision_timestamp <= :end";
            $params[':end'] = $_REQUEST['end'] + 24 * 60 * 60;
        }
        $where[] = "type = 'country'";
        $where = implode(" AND ", $where);
        if ($where != '') {
            $where = "WHERE $where";
        }
        $q = "SELECT COUNT(*) FROM node
    LEFT JOIN node_revision ON node.nid = node_revision.nid
    LEFT JOIN node_field_revision ON node.nid = node_field_revision.nid
    $where";
        $result = $database->query($q, $params)->fetchAssoc();
        $countries = $result['COUNT(*)'];

        $response = ['data' => [
            'countries' => $countries
        ]];
        return new ResourceResponse($response);
    }
}
