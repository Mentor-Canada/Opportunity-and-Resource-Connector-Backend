<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Search\SearchResultAdapter;
use Drupal\app\Utils\GooglePlaceUtils;
use Drupal\app\Utils\Utils;
use Drupal\node\Entity\Node;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "search_resource",
 *   label = @Translation("Search Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/search"
 *   }
 * )
 */
class SearchResource extends ResourceBase
{
    public function get()
    {
        if (!isset($_REQUEST['search'])) {
            throw new BadRequestHttpException("Missing search value.");
        }
        $node = Utils::getCurrentNode($_REQUEST['search']);
        $distance = $node->get('field_distance')->getValue()[0]['value'];

        $q = \Drupal::database()->select('node', 'n');
        $q->addField('n', 'UUID');
        $q->addField('n', 'nid');
        $q->addField('display_title', 'field_display_title_value', 'title_en');
        $q->addField('display_title_fr', 'field_display_title_value', 'title_fr');
        $q->addField('physical_locations', 'field_physical_locations_lat', 'lat');
        $q->addField('physical_locations', 'field_physical_locations_lng', 'lng');
        $q->addField('program_delivery', 'field_program_delivery_value');
        $q->leftJoin('node__field_display_title', 'display_title', "n.nid = display_title.entity_id AND display_title.langcode = 'en'");
        $q->leftJoin('node__field_display_title', 'display_title_fr', "n.nid = display_title_fr.entity_id AND display_title_fr.langcode = 'fr'");
        $q->leftJoin('node__field_physical_locations', 'physical_locations', "n.nid = physical_locations.entity_id");
        $q->leftJoin('node__field_program_delivery', 'program_delivery', "n.nid = program_delivery.entity_id");
        $q->leftJoin('node__field_program_ages_served', 'ages_served', "n.nid = ages_served.entity_id");
        $q->leftJoin('node__field_focus_area', 'focus_area', "n.nid = focus_area.entity_id");
        $q->leftJoin('node__field_program_youth_served', 'youth_served', "n.nid = youth_served.entity_id");
        $q->leftJoin('node__field_types_of_mentoring', 'types_of_mentoring', "n.nid = types_of_mentoring.entity_id");
        $q->leftJoin('node__field_standing', 'standing', "n.nid = standing.entity_id");

        // organization
        $q->leftJoin('node__field_organization_entity', 'organization', "n.nid = organization.entity_id");
        $q->leftJoin(
            'node__field_display_title',
            'organization_display_title_en',
            "organization.field_organization_entity_target_id = organization_display_title_en.entity_id AND organization_display_title_en.langcode = 'en'"
        );
        $q->leftJoin(
            'node__field_display_title',
            'organization_display_title_fr',
            "organization.field_organization_entity_target_id = organization_display_title_fr.entity_id AND organization_display_title_fr.langcode = 'fr'"
        );
        $q->addField('organization_display_title_en', 'field_display_title_value', 'organization_title_en');
        $q->addField('organization_display_title_fr', 'field_display_title_value', 'organization_title_fr');

        // organization is approved
        $q->leftJoin('node__field_organization_entity', 'organization_entity', "n.nid = organization_entity.entity_id");
        $q->leftJoin('node__field_standing', 'organization_standing_entity', "organization_entity.field_organization_entity_target_id = organization_standing_entity.entity_id");
        $q->addField('organization_entity', 'entity_id', 'organization_id');
        $q->addField('organization_standing_entity', 'field_standing_value', 'organization_standing');

        $orGroup = $q->orConditionGroup()
      ->isNull('organization_entity.entity_id')
      ->condition('organization_standing_entity.field_standing_value', 'app-allowed')
    ;
        $q->condition($orGroup);

        $q->condition('type', 'programs');
        $q->condition('standing.field_standing_value', 'app-allowed');

        $resultAdapter = new SearchResultAdapter($node);
        if (!empty($resultAdapter->age) && $resultAdapter->age != 'all') {
            $q->condition('field_program_ages_served_value', $resultAdapter->age);
        }
        if (!empty($resultAdapter->focus) && $resultAdapter->focus != 'all') {
            $q->condition('field_focus_area_value', $resultAdapter->focus);
        }
        if (!empty($resultAdapter->youth) && $resultAdapter->youth != 'all') {
            $q->condition('field_program_youth_served_value', $resultAdapter->youth);
        }
        if (!empty($resultAdapter->type) && $resultAdapter->type != 'all') {
            $q->condition('field_types_of_mentoring_value', $resultAdapter->type);
        }
        $rows = $q->distinct()->execute()->fetchAll();
        $rows = array_map(fn ($row) => (array) $row, $rows);

        $filtered = [];

        $grouped = [];
        foreach ($rows as $row) {
            $id = $row["UUID"];
            if (array_key_exists($id, $grouped)) {
                $grouped[$id]["locations"][] = [
                    "lat" => $row["lat"],
                    "lng" => $row["lng"]
                ];
            } else {
                $grouped[$id] = $row;
                $grouped[$id]["locations"] = [];
                $grouped[$id]["locations"][] = [
                    "lat" => $row["lat"],
                    "lng" => $row["lng"]
                ];
                unset($grouped[$id]["lat"]);
                unset($grouped[$id]["lng"]);
            }
        }

        $searchLocation = $node->get('field_physical_location')->getValue()[0];
        $lat = $searchLocation['lat'];
        $lng = $searchLocation['lng'];
        foreach ($grouped as $key => $row) {
            if ($row['field_program_delivery_value'] == 'app-program-delivery-e-mentoring') {
                $contains = false;
                $programNode = Node::load($row['nid']);
                foreach ($programNode->get('field_physical_locations') as $regionLocation) {
                    $contains = GooglePlaceUtils::contains($regionLocation, $searchLocation);
                    if ($contains) {
                        $row["distance"] = 0;
                        $filtered[] = $row;
                        continue 2;
                    }
                }
                if (!$contains) {
                    continue;
                }
            }

            $smallestDistance = 1000;
            foreach ($row["locations"] as &$location) {
                $locationLat = $location["lat"];
                $locationLng = $location["lng"];
                $d = Utils::latLongDist($lat, $lng, $locationLat, $locationLng);
                $location["distance"] = $d;
                if ($d < $smallestDistance) {
                    $smallestDistance = $d;
                    $row["distance"] = $d;
                }
            }

            if ($smallestDistance <= $distance) {
                $filtered[] = $row;
            }
        }

        $distances = [];
        foreach ($filtered as $key => $row) {
            $distances[$key] = $row['distance'];
        }
        array_multisort($distances, SORT_ASC, $filtered);

        $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
        foreach ($filtered as &$row) {
            $row['title'] = $row["title_{$lang}"];
            if ($lang != 'en' && empty($row['title'])) {
                $row['title'] = $row['title_en'];
            }
            $row['organization_title'] = $row["organization_title_${lang}"];
            if ($lang != 'en' && empty($row['organization_title'])) {
                $row['organization_title'] = $row['organization_title_en'];
            }
        }

        $response = ['status' => 'success', 'data' => $filtered];
        return new ResourceResponse($response);
    }
}
