<?php

namespace Drupal\app_search;

use Drupal\app\Utils\GooglePlaceUtils;

class CommunityBasedProgramCollection
{
    private $rows = [];

    public function __construct(SearchParamsInterface $adapter)
    {
        $q = "
SELECT programs_locations.* FROM node
LEFT JOIN programs_locations ON node.nid = programs_locations.entity_id
LEFT JOIN programs ON node.nid = programs.entity_id
WHERE programs_locations.type = 'communityBased' AND programs.communityBased = 1
    ";
        $rows = \Drupal::database()->query($q)->fetchAll();

        /**
         * Match programs contained within the service area.
         * Example: zip is 02150 service area is Massachusetts.
         */
        foreach ($rows as $row) {
            $location = json_decode($row->location);
            $contains = $this->contains($location, $adapter->location());
            if ($contains) {
                $this->rows[] = $row;
            }
        }

        /**
         * Match programs within search distance of the service area.
         * Example: zip is 02150 service area is 02140.
         */
        \Drupal::database()->query(
            "
CREATE TEMPORARY TABLE communityDistances
  SELECT
    programs_locations.entity_id,
    MIN(ST_Distance_Sphere(point(:lng,:lat), point(JSON_EXTRACT(location, '$.geometry.location.lng'), JSON_EXTRACT(location, '$.geometry.location.lat')))/1000) as distance
  FROM programs_locations
  LEFT JOIN programs ON programs_locations.entity_id = programs.entity_id
  WHERE
    type = 'communityBased'
    AND programs.communityBased = 1
  GROUP BY entity_id
HAVING distance < :distance
ORDER BY entity_id, distance",
            [
                ":lng" => $adapter->lng(),
                ":lat" => $adapter->lat(),
                ":distance" => $adapter->distance()
            ]
        );
        $rows = \Drupal::database()->query("SELECT entity_id FROM communityDistances")->fetchAll();
        $this->rows = array_merge($this->rows, $rows);
    }

    private function contains($region, $location): bool
    {
        $type = $region->types[0];
        $value = GooglePlaceUtils::getComponent($type, $region->address_components);
        if (!$location) {
            return false;
        }

        foreach ($location->address_components as $component) {
            if (in_array($type, $component->types)) {
                if ($component->long_name == $value) {
                    return true;
                }
            }
        }
        return false;
    }

    public function ids()
    {
        return array_column($this->rows, "entity_id");
    }
}
