<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "organization_stats_resource",
 *   label = @Translation("Organization Stats Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/stats/organization"
 *   }
 * )
 */
class OrganizationStatsResource extends ResourceBase
{
    public function get()
    {
        $db = \Drupal::database();

        $q = $db->select('node', 'node');
        $q->addField('node', 'nid');
        $q->condition('node.type', 'organization');
        $q->condition('data.langcode', 'en');

        if ($_REQUEST['title'] != 'null') {
            $q->leftJoin('node__field_display_title', 'title', 'node.nid = title.entity_id');
            $q->condition('title.field_display_title_value', "%{$_REQUEST['title']}%", 'LIKE');
            $q->condition('title.langcode', 'en');
        }

        if (isset($_REQUEST['standing']) && $_REQUEST['standing'] != 'null') {
            $q->leftJoin('node__field_standing', 'standing', 'node.nid = standing.entity_id');
            if ($_REQUEST['standing'] == 'app-pending') {
                $q->isNull('standing.field_standing_value');
            } else {
                $q->condition('standing.field_standing_value', $_REQUEST['standing']);
                $q->condition('standing.langcode', 'en');
            }
        }

        $q->leftJoin('node_field_data', 'data', 'node.nid = data.nid');
        if (isset($_REQUEST['start']) && $_REQUEST['start'] != 'null') {
            $q->condition('data.created', $_REQUEST['start'], '>=');
        }
        if (isset($_REQUEST['end']) && $_REQUEST['end'] != 'null') {
            $q->condition('data.created', $_REQUEST['end'] + 24 * 60 * 60, '<=');
        }

        if (!empty($_REQUEST['province'])) {
            $q->leftJoin('node__field_physical_location', 'location', 'node.nid = location.entity_id');
            $q->leftJoin('node__field_has_location', 'has_location', 'node.nid = has_location.entity_id');
            if ($_REQUEST['province'] == 'app-unknown') {
                $q->isNull('location.field_physical_location_province');
                $q->condition('has_location.field_has_location_value', 'yes');
            } elseif ($_REQUEST['province'] == 'app-without-physical-location') {
                $q->isNull('location.field_physical_location_province');
                $q->condition('has_location.field_has_location_value', 'no');
            } else {
                $q->condition('location.field_physical_location_province', $_REQUEST['province']);
            }
        }

        $count = $q->countQuery()->execute()->fetchField();

        $response = ['data' => [
            'organizations' => $count
        ]];
        return new ResourceResponse($response);
    }
}
