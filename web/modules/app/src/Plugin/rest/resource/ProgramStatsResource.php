<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Utils\Utils;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "program_stats_resource",
 *   label = @Translation("Program Stats Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/stats/program"
 *   }
 * )
 */
class ProgramStatsResource extends ResourceBase
{
    public function get()
    {
        $database = \Drupal::database();
        $params = [];
        $where = [];
        if (isset($_REQUEST['standing']) && $_REQUEST['standing'] != 'null') {
            if ($_REQUEST['standing'] == 'app-pending') {
                $where[] = "field_standing_value IS NULL";
            } else {
                $where[] = "field_standing_value = :standing";
                $params[':standing'] = $_REQUEST['standing'];
            }
        }
        if (isset($_REQUEST['title']) && $_REQUEST['title'] != 'null') {
            // TODO: Escape string!
            $title = $_REQUEST['title'];
            $where[] = "node_field_data.title LIKE '%$title%'";
        }
        if (isset($_REQUEST['start']) && $_REQUEST['start'] != 'null') {
            $where[] = "node_field_data.created >= :start";
            $params[':start'] = $_REQUEST['start'];
        }
        if (isset($_REQUEST['end']) && $_REQUEST['end'] != 'null') {
            $where[] = "node_field_data.created <= :end";
            $params[':end'] = $_REQUEST['end'] + 24 * 60 * 60;
        }
        $where[] = "node.type = 'programs'";
        $where[] = "node_field_data.langcode = 'en'";
        $where = implode(" AND ", $where);
        if ($where != '') {
            $where = "WHERE $where";
        }
        $q = "SELECT COUNT(*) FROM node
    LEFT JOIN node_field_data ON node.nid = node_field_data.nid
    LEFT JOIN node__field_standing ON node.nid = node__field_standing.entity_id
    $where";
        $result = $database->query($q, $params)->fetchAssoc();
        $programs = $result['COUNT(*)'];

        $response = ['data' => [
            'programs' => $programs
        ]];
        return new ResourceResponse($response);
    }
}
