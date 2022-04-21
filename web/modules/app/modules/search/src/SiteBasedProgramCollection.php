<?php

namespace Drupal\app_search;

class SiteBasedProgramCollection
{
    public function __construct(SearchParamsInterface $adapter)
    {
        \Drupal::database()->query(
            "
CREATE TEMPORARY TABLE resultDistances
  SELECT
    postal_code,
    location->>'$.url' as url,
    programs_locations.entity_id,
    MIN(ST_Distance_Sphere(point(:lng,:lat), point(JSON_EXTRACT(location, '$.geometry.location.lng'), JSON_EXTRACT(location, '$.geometry.location.lat')))/1000) as distance
  FROM programs_locations
  LEFT JOIN programs ON programs_locations.entity_id = programs.entity_id
  WHERE
    type = 'siteBased'
    AND programs.siteBased = 1
  GROUP BY entity_id, postal_code, url
HAVING distance < :distance
ORDER BY entity_id, distance",
            [
                ":lng" => $adapter->lng(),
                ":lat" => $adapter->lat(),
                ":distance" => $adapter->distance()
            ]
        );
    }

    public function ids()
    {
        $rows = \Drupal::database()->query("SELECT entity_id FROM resultDistances")->fetchAll();
        return array_column($rows, "entity_id");
    }
}
