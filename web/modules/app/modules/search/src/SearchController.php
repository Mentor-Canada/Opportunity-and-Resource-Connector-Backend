<?php

namespace Drupal\app_search;

use Drupal\app\Utils\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;

class SearchController
{
    public function post()
    {
        $content = json_decode(\Drupal::request()->getContent(), true);
        $attributes = $content['data']['attributes'];

        $nationalEMentoring = $attributes['field_zip'] == "app-national";
        if (!$nationalEMentoring) {
            $location = Utils::geocode($attributes['field_zip']);
            $city = $this->getValue($location["address_components"], "locality");
            $state = $this->getValue($location["address_components"], "administrative_area_level_1");
        }
        $partnerId = SearchUtils::getPartnerId($attributes['field_partner_id']);

        $fields = [
            "partnerId" => $partnerId,
            "firstName" => $attributes['field_first_name'],
            "lastName" => $attributes['field_last_name'],
            "email" => $attributes['field_email'],
            "distance" => $attributes['field_distance'],
            "role" => $attributes['field_role'],
            "how" => "",
            "howOther" => "",
            "zip" => $attributes['field_zip'],
            "city" => $city,
            "state" => $state,
            "age" => json_encode([$attributes['field_youth_age']]),
            "grade" => json_encode([$attributes['field_youth_grade']]),
            "focus" => json_encode([$attributes['field_focus']]),
            "youth" => json_encode([$attributes['field_youth']]),
            "typeOfMentoring" => json_encode([$attributes['field_type_of_mentoring']]),
            "location" => json_encode($location),
            "siteBasedDelivery" => intval(in_array("siteBased", $attributes['delivery'])),
            "communityBasedDelivery" => intval(in_array("community", $attributes['delivery'])),
            "eMentoringDelivery" => intval(in_array("eMentoring", $attributes['delivery'])),
            "created" => \Drupal::time()->getRequestTime()
        ];

        $id = \Drupal::database()->insert("searches")
      ->fields($fields)
      ->execute()
    ;

        $uuid = \Drupal::database()->query("SELECT UUID() FROM searches WHERE id = :id", [
            ':id' => $id
        ])->fetchCol();
        $uuid = current($uuid);

        \Drupal::database()->update("searches")
      ->fields(["uuid" => $uuid])
      ->condition("id", $id)
      ->execute();

        return new JsonResponse(['data' => [
            'id' => $uuid
        ]]);
    }

    private function getValue($components, $type)
    {
        foreach ($components as $component) {
            if (in_array($type, $component["types"])) {
                return $component["long_name"];
            }
        }
    }

    public function patch($uuid)
    {
        $content = json_decode(\Drupal::request()->getContent(), true);
        $attributes = $content['data']['attributes'];

        $fields = [
            "distance" => $attributes['field_distance'],
            "age" => json_encode([$attributes['field_youth_age']]),
            "grade" => json_encode([$attributes['field_youth_grade']]),
            "focus" => json_encode([$attributes['field_focus']]),
            "youth" => json_encode([$attributes['field_youth']]),
            "typeOfMentoring" => json_encode([$attributes['field_type_of_mentoring']]),
            "siteBasedDelivery" => intval(in_array("siteBased", $attributes['delivery'])),
            "communityBasedDelivery" => intval(in_array("community", $attributes['delivery'])),
            "eMentoringDelivery" => intval(in_array("eMentoring", $attributes['delivery'])),
        ];

        \Drupal::database()->update("searches")
      ->fields($fields)
      ->condition("uuid", $uuid)
      ->execute()
    ;

        return new JsonResponse(['data' => [
            'id' => $uuid
        ]]);
    }

    public function get($uuid)
    {
        $row = \Drupal::database()->query("SELECT
       id,
       age,
       distance,
       grade,
       focus,
       typeOfMentoring,
       youth,
       siteBasedDelivery,
       communityBasedDelivery,
       eMentoringDelivery,
       zip = 'app-national' as nationWideEMentoring
      FROM searches WHERE uuid = :uuid", [
            ":uuid" => $uuid
        ])->fetchObject();

        return new JsonResponse(['data' => ['attributes' => $row]]);
    }
}
