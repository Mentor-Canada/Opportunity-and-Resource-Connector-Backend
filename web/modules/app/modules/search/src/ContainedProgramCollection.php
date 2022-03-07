<?php

namespace Drupal\app_search;

use Drupal\app\Utils\GooglePlaceUtils;

class ContainedProgramCollection
{
    private $rows = [];

    public function __construct($searchLocation, $type)
    {
        $q = "
SELECT programs_locations.* FROM node
LEFT JOIN programs_locations ON node.nid = programs_locations.entity_id
LEFT JOIN programs ON programs_locations.entity_id = programs.entity_id
WHERE programs_locations.type = '$type' AND programs.eMentoring = 1
    ";
        $rows = \Drupal::database()->query($q)->fetchAll();

        foreach ($rows as $row) {
            $location = json_decode($row->location);
            $contains = $this->contains($location, $searchLocation);
            if ($contains) {
                $this->rows[] = $row;
            }
        }

        if ($type == 'eMentoring') {
            $nationalRows = \Drupal::database()->query("SELECT entity_id FROM programs WHERE eMentoring = 1 AND nationWideEmentoring = 1")->fetchAll();
            foreach ($nationalRows as $nationalRow) {
                $this->rows[] = $nationalRow;
            }
        }
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
