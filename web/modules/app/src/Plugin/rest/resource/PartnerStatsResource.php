<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Utils\Utils;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "partner_stats_resource",
 *   label = @Translation("Partner Stats Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/stats/partner"
 *   }
 * )
 */
class PartnerStatsResource extends ResourceBase
{
    public function get()
    {
//    $database = \Drupal::database();
//    $params = [];
//    $where = [];
//    if(isset($_REQUEST['title']) && $_REQUEST['title'] != 'null') {
//      // TODO: Escape string!
//      $title = $_REQUEST['title'];
//      $where[] = "node__field_display_title.field_display_title_value LIKE '%$title%'";
//    }
//    if(isset($_REQUEST['start']) && $_REQUEST['start'] != 'null') {
//      $where[] = "node_field_data.created >= :start";
//      $params[':start'] = $_REQUEST['start'];
//    }
//    if(isset($_REQUEST['end']) && $_REQUEST['end'] != 'null') {
//      $where[] = "node_field_data.created <= :end";
//      $params[':end'] = $_REQUEST['end'] + 24 * 60 * 60;
//    }
//    $where[] = "node.type = 'partner'";
//    $where = implode(" AND ", $where);
//    if($where != '') $where = "WHERE $where";
//    $q = "SELECT COUNT(*) FROM node
//    LEFT JOIN node_revision ON node.vid = node_revision.vid
//    LEFT JOIN node__field_country ON node.vid = node__field_country.revision_id
//    LEFT JOIN node__field_display_title ON node.nid = node__field_display_title.entity_id
//    LEFT JOIN node_field_data ON node.nid = node_field_data.nid
//    $where";
//    $result = $database->query($q, $params)->fetchAssoc();
//    $partners = $result['COUNT(*)'];

        $partners = '';

        $response = ['data' => [
            'partners' => $partners
        ]];
        return new ResourceResponse($response);
    }
}
