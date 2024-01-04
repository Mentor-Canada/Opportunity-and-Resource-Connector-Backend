<?php

namespace Drupal\app\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class CatalogueController extends BaseController
{
    public function get()
    {
$sql = <<<END
SELECT
    node.uuid as id,
    programs.title,
    programs.programDescription as description,
    programs.email,
    programs.phone,
    node__field_facebook.field_facebook_value as facebook,
    node__field_twitter.field_twitter_value as twitter,
    node__field_website.field_website_value as website,
    node__field_instagram.field_instagram_value as instagram,
    organizations.title AS organization,
    node__field_physical_location.field_physical_location_name AS address,
    UPPER(node__field_physical_location.field_physical_location_province) AS province,
    node__field_physical_location.field_physical_location_lat AS lat,
    node__field_physical_location.field_physical_location_lng AS lng
FROM
    node as node
    LEFT JOIN programs ON programs.entity_id = node.nid
    LEFT JOIN node__field_facebook ON node__field_facebook.entity_id = node.nid
    LEFT JOIN node__field_twitter ON node__field_twitter.entity_id = node.nid
    LEFT JOIN node__field_website ON node__field_website.entity_id = node.nid
    LEFT JOIN node__field_instagram ON node__field_instagram.entity_id = node.nid
    LEFT JOIN node__field_organization_entity ON node__field_organization_entity.entity_id = node.nid
    LEFT JOIN organizations ON node__field_organization_entity.field_organization_entity_target_id = organizations.entity_id
    LEFT JOIN node__field_physical_location ON node__field_physical_location.entity_id = organizations.entity_id
WHERE
    node.type = 'programs'
    AND (SELECT field_standing_value FROM node__field_standing WHERE node.nid = node__field_standing.entity_id AND node__field_standing.bundle = 'programs') = 'app-allowed'
    ORDER BY LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(programs.title, '$.en'))))
END;
        $rows = \Drupal::database()->query($sql)->fetchAll();

        $provinces = [
          "NEWFOUNDLAND AND LABRADOR" => "NL",
          "PRINCE EDWARD ISLAND" => "PE",
          "NOVA SCOTIA" => "NS",
          "NEW BRUNSWICK" => "NB",
          "QUEBEC" => "QC",
          "QUÉBEC" => "QC",
          "ONTARIO" => "ON",
          "MANITOBA" => "MB",
          "SASKATCHEWAN" => "SK",
          "ALBERTA" => "AB",
          "BRITISH COLUMBIA" => "BC",
          "YUKON" => "YT",
          "NORTHWEST TERRITORIES" => "NT",
          "NUNAVUT" => "NU",
        ];


        foreach($rows as &$row) {
          $row->title = json_decode($row->title);
          $row->description = json_decode($row->description);
          $row->organization = json_decode($row->organization);
          $row->province = $provinces[$row->province];
          $row->slug = [
            "en" => $row->id,
            "fr" => $row->id
          ];
        }
        return new JsonResponse(['programs' => $rows]);
    }
}
