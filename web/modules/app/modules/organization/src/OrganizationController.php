<?php

namespace Drupal\app_organization;

use Drupal\app\GroupControllerBase;
use Drupal\app\Utils\GooglePlaceUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class OrganizationController extends GroupControllerBase
{
    private Organization $organization;

    public function getOrganization($uuid)
    {
        $sub_request = Request::create("/a/node/organization/$uuid", "GET", $_REQUEST);
        $subResponse = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        if (!$subResponse->getStatusCode() == 200) {
            return $subResponse;
        }
        $content = json_decode($subResponse->getContent());
        $entityId = $content->data->attributes->drupal_internal__nid;
        $additionalAttributes = \Drupal::database()->query("SELECT * FROM organizations WHERE entity_id = :entity_id", [
            ":entity_id" => $entityId
        ])->fetchObject();

        foreach ($additionalAttributes as $key => $value) {
            $decoded = json_decode($value);
            if ($decoded) {
                $value = $decoded;
            }
            $content->data->attributes->$key = $value;
        }
        return new JsonResponse($content);
    }

    public function getSubmittedOrganization($uuid)
    {
        $lang = self::getUiLang();
        $node = \Drupal::service('entity.repository')->loadEntityByUuid('node', $uuid);
        $sql = "SELECT title->>'$.$lang' as title, email from organizations WHERE entity_id = :entity_id";
        $q = \Drupal::database()->query($sql, [":entity_id" => $node->id()]);
        $result = $q->fetchAssoc();
        return new JsonResponse([
            "data" => $result
        ]);
    }

    private function getUiLang(): string
    {
        $languages = ["en", "fr"];
        $index = array_search($_REQUEST['uilang'], $languages);
        if ($index !== false) {
            return $languages[$index];
        }
        return "en";
    }

    public function post()
    {
        $response = parent::post();
        $this->organization()->onInsert($this->content->uilang);
        return $response;
    }

    public function deleteOrganization($uuid)
    {
        $organization = Organization::createFromUuid($uuid);
        $organization->delete();
        return new JsonResponse(["status" => "success"]);
    }

    public function patch($uuid)
    {
        parent::patch($uuid);
        $this->organization()->save();
        return new JsonResponse(["data" => $this->organization]);
    }

    public function saveIntegrations($uuid)
    {
        $organization = Organization::createFromUuid($uuid);
        $postData = json_decode(\Drupal::request()->getContent());
        $organization->mentorCityEnabled = $postData->mentorCityEnabled;
        $organization->bbbscEnabled = $postData->bbbscEnabled;
        $organization->saveIntegrations();
        return new JsonResponse(["data" => $organization]);
    }

    public function organization(): Organization
    {
        if (empty($this->organization)) {
            $this->organization = Organization::createFromData($this->content->additional);
            $this->organization->nid = $this->nid;
            $this->organization->id = $this->uuid;
        }
        return $this->organization;
    }

    protected function getContent()
    {
        $content = $_POST['entityData'];
        $content = json_decode($content);
        foreach ($content->nodes as &$node) {
            $googleLocationData = $node->attributes->field_physical_location;
            if ($googleLocationData) {
                $node->attributes->field_physical_location = $this->googleLocationDataToLocationField($googleLocationData);
            }
        }
        return $content;
    }

    private function googleLocationDataToLocationField($data): LocationField
    {
        $location = new LocationField();
        $location->place_id = $data->place_id;
        $location->name = $data->formatted_address;
        $location->type = $data->types[0];
        $location->postal_code = GooglePlaceUtils::getComponent('postal_code', $data->address_components);
        $location->lat = $data->geometry->location->lat;
        $location->lng = $data->geometry->location->lng;
        $location->country = GooglePlaceUtils::getComponent('country', $data->address_components);
        $location->province = GooglePlaceUtils::getComponent('administrative_area_level_1', $data->address_components);
        $location->components = json_encode($data->address_components);
        return $location;
    }
}
