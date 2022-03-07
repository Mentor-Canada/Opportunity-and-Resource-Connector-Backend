<?php

namespace Drupal\app\Commands;

use Drupal\app\Utils\GooglePlaceUtils;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;

class UpdateCommands extends DrushCommands
{
    /**
     * @command app:v1tov2
     */
    public function v1tov2()
    {
        $this->migrateSearches();
        $this->updateSearchLocations();
        $this->migrateInquiries();
        $this->cleanup();
    }

    private function migrateSearches()
    {
        $q = "CREATE TABLE IF NOT EXISTS `searches` (
    `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(128) DEFAULT NULL,
  `importId` int DEFAULT NULL,
  `partnerId` int DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `age` json DEFAULT NULL,
  `distance` varchar(255) DEFAULT NULL,
  `firstName` varchar(255) DEFAULT NULL,
  `grade` json DEFAULT NULL,
  `how` varchar(255) DEFAULT NULL,
  `howOther` varchar(255) DEFAULT NULL,
  `location` json DEFAULT NULL,
  `focus` json DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `typeOfMentoring` json DEFAULT NULL,
  `youth` json DEFAULT NULL,
  `created` int DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `siteBasedDelivery` tinyint(1) DEFAULT NULL,
  `communityBasedDelivery` tinyint(1) DEFAULT NULL,
  `eMentoringDelivery` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `searches_importId_uindex` (`importId`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

        \Drupal::database()->query($q);

        $q = "REPLACE INTO searches(
                      id,
                      uuid,
                      created,
                      partnerId,
                      distance,
                      zip,
                      how,
                      howOther,
                      age,
                      focus,
                      role,
                      typeOfMentoring,
                      siteBasedDelivery,
                      communityBasedDelivery,
                      eMentoringDelivery
                      )
  (SELECT
    node.nid as id,
    node.uuid,
    ANY_VALUE(node_field_data.created),
    ANY_VALUE(field_partner_entity_target_id),
    ANY_VALUE(field_distance_value),
    ANY_VALUE(field_zip_value),
    ANY_VALUE(field_how_did_you_hear_about_us_value),
    ANY_VALUE(field_how_did_you_hear_other_value),
    JSON_ARRAYAGG(field_youth_age_value),
    JSON_ARRAYAGG(field_focus_value),
    ANY_VALUE(field_role_value),
    JSON_ARRAYAGG(field_type_of_mentoring_value),
    1,
    1,
    1
  FROM node
  LEFT JOIN node_field_data ON node.nid = node_field_data.nid
  LEFT JOIN node__field_partner_entity ON node.nid = node__field_partner_entity.entity_id
  LEFT JOIN node__field_distance ON node.nid = node__field_distance.entity_id
  LEFT JOIN node__field_zip ON node.nid = node__field_zip.entity_id
  LEFT JOIN node__field_how_did_you_hear_about_us ON node.nid = node__field_how_did_you_hear_about_us.entity_id
  LEFT JOIN node__field_how_did_you_hear_other ON node.nid = node__field_how_did_you_hear_other.entity_id
  LEFT JOIN node__field_youth_age ON node.nid = node__field_youth_age.entity_id
  LEFT JOIN node__field_focus ON node.nid = node__field_focus.entity_id
  LEFT JOIN node__field_role ON node.nid = node__field_role.entity_id
  LEFT JOIN node__field_type_of_mentoring ON node.nid = node__field_type_of_mentoring.entity_id
  LEFT JOIN node__field_youth ON node.nid = node__field_youth.entity_id
  WHERE node.type = 'search'
    GROUP BY node.nid
    )
";

        \Drupal::database()->query($q);
    }

    private function updateSearchLocations()
    {
        $q = "SELECT id, zip FROM searches";
        $rows = \Drupal::database()->query($q)->fetchAll();
        foreach ($rows as $row) {
            $response = GooglePlaceUtils::getWithAddress($row->zip);
            $location = $response->results[0];
            if ($location) {
                $city = GooglePlaceUtils::getComponent("locality", $location->address_components);
                $state = GooglePlaceUtils::getComponent("administrative_area_level_1", $location->address_components);
                \Drupal::database()->query("INSERT INTO searches(id, location, city, state) VALUE(:entity_id, :value, :city, :state) ON DUPLICATE KEY UPDATE location = :value, city = :city, state = :state", [
                    ":value" => json_encode($location),
                    ":entity_id" => $row->id,
                    ":city" => $city,
                    ":state" => $state
                ]);
            }
        }
    }

    public static function deleteNodes($type)
    {
        $rows = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->execute();
        foreach ($rows as $row) {
            $node = Node::load($row);
            $node->delete();
        }
    }

    private function migrateInquiries()
    {
        $q = "
  CREATE TABLE IF NOT EXISTS `inquiries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(128) DEFAULT NULL,
  `importId` int DEFAULT NULL,
  `searchId` int DEFAULT NULL,
  `programId` int DEFAULT NULL,
  `firstName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `voice` tinyint(1) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `sms` tinyint(1) DEFAULT NULL,
  `how` varchar(255) DEFAULT NULL,
  `howOther` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inquiries_importId_uindex` (`importId`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  ";
        \Drupal::database()->query($q);

        $q = "REPLACE INTO inquiries(
            id,
            uuid,
            created,
            searchId,
            programId,
            firstName,
            lastName,
            email,
            phone,
            voice,
            role,
            sms,
            how,
            howOther,
            status
                      )
  (SELECT
    node.nid as id,
    node.uuid,
    node_field_data.created,
    field_search_target_id,
    field_program_target_id,
    field_first_name_value,
    field_last_name_value,
    field_email_value,
    field_phone_value,
    field_call_value,
    field_role_value,
    field_sms_value,
    field_how_did_you_hear_about_us_value,
    field_how_did_you_hear_other_value,
    field_status_value
  FROM node
  LEFT JOIN node_field_data ON node.nid = node_field_data.nid
  LEFT JOIN node__field_email ON node.nid = node__field_email.entity_id
  LEFT JOIN node__field_first_name ON node.nid = node__field_first_name.entity_id
  LEFT JOIN node__field_last_name ON node.nid = node__field_last_name.entity_id
  LEFT JOIN node__field_call ON node.nid = node__field_call.entity_id
  LEFT JOIN node__field_sms ON node.nid = node__field_sms.entity_id
  LEFT JOIN node__field_how_did_you_hear_about_us ON node.nid = node__field_how_did_you_hear_about_us.entity_id
  LEFT JOIN node__field_how_did_you_hear_other ON node.nid = node__field_how_did_you_hear_other.entity_id
  LEFT JOIN node__field_phone ON node.nid = node__field_phone.entity_id
  LEFT JOIN node__field_program ON node.nid = node__field_program.entity_id
  LEFT JOIN node__field_role ON node.nid = node__field_role.entity_id
  LEFT JOIN node__field_search ON node.nid = node__field_search.entity_id
  LEFT JOIN node__field_status ON node.nid = node__field_status.entity_id
  WHERE node.type = 'application'
    )
";

        \Drupal::database()->query($q);
    }

    private function cleanup()
    {
        self::deleteNodes("search");
        self::deleteNodes("application");
    }
}
