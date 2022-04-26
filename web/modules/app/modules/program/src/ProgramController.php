<?php

namespace Drupal\app_program;

use Drupal\app\GroupControllerBase;
use Drupal\app\Utils\GooglePlaceUtils;
use Drupal\app\Utils\Security;
use Drupal\app\Utils\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ProgramController extends GroupControllerBase
{
    public function getProgram($uuid)
    {
        $sub_request = Request::create("/a/node/programs/$uuid", "GET", $_REQUEST);
        $subResponse = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        if (!$subResponse->getStatusCode() == 200) {
            return $subResponse;
        }
        $content = json_decode($subResponse->getContent());
        $entityId = $content->data->attributes->drupal_internal__nid;

        $additionalAttributes = \Drupal::database()->query("SELECT * FROM programs WHERE entity_id = :entity_id", [
            ":entity_id" => $entityId
        ])->fetchObject();

        foreach ($additionalAttributes as $key => $value) {
            $decoded = json_decode($value);
            if ($decoded) {
                $value = $decoded;
            }
            $content->data->attributes->$key = $value;
        }

        $locations = \Drupal::database()->query("SELECT type, JSON_ARRAYAGG(location) as locations FROM programs_locations WHERE entity_id = :entity_id GROUP BY type", [
            ":entity_id" => $entityId
        ])->fetchAll();

        foreach ($locations as $location) {
            $key = "{$location->type}Locations";
            $locationData = json_decode($location->locations);
            $content->data->attributes->$key = $locationData;
        }

        \Drupal::moduleHandler()->invokeAll("mentor_connector_program_alter", ['content' => $content]);

        return new JsonResponse($content);
    }

    public function post()
    {
        $response = parent::post();
        $program = Program::createWithNid($this->nid, $this->content->uilang);
        $program->setStanding();
        $program->sendNotifications();
        $program->sendAdminNotifications();
        $program->setInitialAdministrator();
        $program->save();
        return $response;
    }

    public function patch($uuid)
    {
        $response = parent::patch($uuid);
        $data = $this->content->additional;
        $fields = [
            ':first_name' => $data->first_name,
            ':last_name' => $data->last_name,
            ':position' => $data->position,
            ':phone' => $data->phone,
            ':altPhone' => $data->altPhone,
            ':email' => $data->email,
            ':title' => json_encode($data->title),
            ':programDescription' => json_encode($data->programDescription),
            ':mentorDescription' => json_encode($data->mentorDescription),
            ':trainingDescription' => json_encode($data->trainingDescription),
            ':communityBased' => intval($data->delivery->community),
            ':siteBased' => intval($data->delivery->siteBased),
            ':eMentoring' => intval($data->delivery->eMentoring),
            ':nationWideEMentoring' => intval($data->delivery->nationWideEMentoring),
            ':source' => $data->source,
        ];
        \Drupal::moduleHandler()->invokeAll("mentor_connector_program_presave", [
          'data' => $data,
          'fields' => &$fields
        ]);
        \Drupal::database()
          ->update("programs")
          ->fields($fields)
          ->condition("entity_id", $this->node->id())
          ->execute()
        ;

        // save locations
        \Drupal::database()
          ->delete("programs_locations")
          ->condition("entity_id", $this->node->id())
          ->execute()
        ;
        if ($data->delivery->siteBased) {
            $this->insertLocation($this->node->id(), "siteBased", $data->delivery->siteBasedLocations);
        }
        if ($data->delivery->community) {
            $this->insertLocation($this->node->id(), "communityBased", $data->delivery->communityLocations);
        }
        if ($data->delivery->eMentoring && !$data->delivery->nationWideEMentoring) {
            $this->insertLocation($this->node->id(), "eMentoring", $data->delivery->eMentoringLocations);
        }

        return $response;
    }

    private function insertLocation($entityId, $type, $locations)
    {
        $q = \Drupal::database()->insert("programs_locations")->fields(["entity_id", "type", "location", "postal_code"]);
        foreach ($locations as $row) {
            $postalCode = GooglePlaceUtils::getComponent("postal_code", $row->address_components);
            $q->values([
                "entity_id" => $entityId,
                "type" => $type,
                "location" => json_encode($row),
                "postal_code" => $postalCode
            ]);
        }
        $q->execute();
    }

    public function deleteProgram($uuid)
    {
        $node = Utils::loadNodeByUUid($uuid);
        if ($node) {
            $id = $node->id();
            $node->delete();
            \Drupal::database()->query("DELETE FROM programs WHERE entity_id = $id")->execute();
        }
        return new JsonResponse();
    }

    public function saveSettings($uuid)
    {
        $id = \Drupal::database()->query("SELECT nid FROM node WHERE uuid = :uuid", [
            ":uuid" => $uuid
        ])->fetchCol();
        $id = current($id);
        $content = json_decode(\Drupal::request()->getContent());

        $values = [
            ':id' => $id,
            ':bbbsc' => $content->bbbsc ? "1" : "0",
            ':bbbscInquiryProgramOfInterest' => $content->bbbscInquiryProgramOfInterest,
            ':bbbscProgramType' => $content->bbbscProgramType,
            ':bbbscSystemUser' => $content->bbbscSystemUser
        ];

        \Drupal::database()->query("UPDATE programs
      SET bbbsc = :bbbsc,
          bbbscInquiryProgramOfInterest = :bbbscInquiryProgramOfInterest,
          bbbscProgramType = :bbbscProgramType,
          bbbscSystemUser = :bbbscSystemUser
      WHERE entity_id = :id", $values);
        return new JsonResponse();
    }

    public function getProgramSources()
    {
        $sources = \Drupal::database()->query(
            "SELECT DISTINCT source FROM programs WHERE NOT source IS NULL ORDER BY source ASC"
        )->fetchCol();
        return new JsonResponse($sources);
    }
}
