<?php

namespace rest\organization;

use Exception;
use GuzzleHttp\RequestOptions;
use rest\Request;
use rest\RestTestCase;
use rest\Session;

class OrganizationControllerTest extends RestTestCase
{
    public function testCreateOrganization()
    {
        $body = OrganizationUtils::createOrganization();
        $this->assertNotNull($body->data->id);
        return $body->data->id;
    }

    /**
     * @depends testCreateOrganization
     */
    public function testSaveIntegrations($uuid)
    {
        $params = $this->getIntegrationParams();
        $response = (new Request())
      ->uri("a/app/organization/$uuid/integrations")
      ->data($params)
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $this->assertEquals($body->data->mentorCityEnabled, $params->mentorCityEnabled, "mentorCityEnabled value did not save");
        $this->assertEquals($body->data->bbbscEnabled, $params->bbbscEnabled, "bbbscEnabled value did not save");
    }

    /**
     * @depends testCreateOrganization
     */
    public function testDenySaveIntegrationsToAnonymousUser($uuid)
    {
        (new Request())
      ->uri("a/app/organization/$uuid/integrations")
      ->data($this->getIntegrationParams())
      ->expectedResponseCode(403)
      ->execute();
    }

    /**
     * @depends testCreateOrganization
     */
    public function testDenySaveIntegrationsToUnapprovedUser($uuid)
    {
        (new Request())
      ->uri("a/app/organization/$uuid/integrations")
      ->data($this->getIntegrationParams())
      ->session($this->authenticatedSession())
      ->expectedResponseCode(403)
      ->execute();
    }

    /**
     * @depends testCreateOrganization
     */
    public function testDenyPatchOrganizationToAnonymousUser($id)
    {
        $anonymousSession = new Session();
        $permissionDenied = null;
        try {
            $anonymousSession->request('POST', "a/app/organization/$id");
        } catch (Exception $e) {
            $permissionDenied = true;
        }
        $this->assertEquals(true, $permissionDenied, "Anonymous users cannot update organizations");
    }

    private function getIntegrationParams(): OrganizationIntegrationParams
    {
        $params = new OrganizationIntegrationParams();
        $params->bbbscEnabled = true;
        $params->mentorCityEnabled = false;
        return $params;
    }

    /**
     * @depends testCreateOrganization
     */
    public function testDenyPatchOrganizationToUnapprovedUser($uuid)
    {
        $innerData = OrganizationUtils::getDataObject();
        $newName = 'UNAUTHORISED PATCH';
        $innerData->contents->additional->contactFirstName = $newName;
        $data = $innerData->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/organization/{$uuid}")
      ->method('POST')
      ->data($data, true)
      ->session($this->authenticatedSession())
      ->expectedResponseCode([403, 500, 422, 0])
      ->execute();
        $this->assertnull($response, "Unauthorized user was able to patch organization");
    }

    /**
     * @depends testCreateOrganization
     */
    public function testSaveOrganizationAdministrator($uuid)
    {
        $data = [
            RequestOptions::JSON => [
                "uilang" => "en"
            ]
        ];
        $response  = (new Request())
      ->uri("a/app/organization/$uuid/administrator/authenticated@example.com")
      ->data($data)
      ->session($this->globalAdministratorSession())
      ->execute();
        $statusCode = $response->getStatusCode();
        $this->assertEquals(200, $statusCode, "New admin was not added to the organization");
    }

    /**
     * @depends testCreateOrganization
     */
    public function testApprovedAdminCanGetOrganization($uuid)
    {
        $response = (new Request())
      ->uri("a/app/organization/{$uuid}?include=field_administrators,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        $this->assertEquals($uuid, $body->data->id, "Expected organizations was not retrieved by its admin");
        $this->validateOrganizationFields($body);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testGlobalAdminCanGetOrganization($uuid)
    {
        $response = (new Request())
      ->uri("a/app/organization/{$uuid}?include=field_administrators,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $this->assertEquals($uuid, $body->data->id, "Expected organizations was not retrieved by global admin");
        $this->validateOrganizationFields($body);
    }

    public function testPostFrenchOrg()
    {
        $body = OrganizationUtils::createOrganization(true);
        $id = $body->data->id;
        $this->assertNotNull($id);
        return $id;
    }

    /**
   * @depends testPostFrenchOrg
   */
    public function testGetFrenchOrg($uuid)
    {
        $params = OrganizationUtils::getParams(true);
        $this->testSaveOrganizationAdministrator($uuid);
        $response = (new Request())
      ->uri("a/app/organization/{$uuid}?include=field_administrators,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        $this->validateOrganizationFields($body, $params);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testApprovedUserCanPatchOrganization($uuid)
    {
        $params = OrganizationUtils::getParams();
        $params->feedback = "this organization was patched";
        $params->legalName = "patched legal name";
        $params->type = "app-organization-type-school-academic-institution";
        $payload = OrganizationUtils::getDataObject();
        $payload->contents->additional = $params;
        $data = $payload->transformToDataArray();
        (new Request())
      ->uri("a/app/organization/{$uuid}")
      ->method('POST')
      ->data($data, true)
      ->session($this->authenticatedSession())
      ->execute();
        return $uuid;
    }

    /**
     * @depends testApprovedUserCanPatchOrganization
     */
    public function testGetPatchedOrganization($uuid)
    {
        $params = OrganizationUtils::getParams();
        $params->feedback = "this organization was patched";
        $params->legalName = "patched legal name";
        $params->type = "app-organization-type-school-academic-institution";
        $response = (new Request())
      ->uri("a/app/organization/{$uuid}?include=field_administrators,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        $this->validateOrganizationFields($body, $params);
    }

    private function validateOrganizationFields($body, $params = null)
    {
        $attributes = $body->data->attributes;
        $logo_id = $body->included[0]->id;
        if (!$params) {
            $params = OrganizationUtils::getParams();
        }
        $location = (object)$params->location;
        $this->assertEquals($params->contactEmail, $attributes->email);
        $this->assertEquals($params->title->en, $attributes->title->en);
        $this->assertEquals($params->title->fr, $attributes->title->fr);
        $this->assertEquals($params->contactFirstName, $attributes->first_name);
        $this->assertEquals($params->contactLastName, $attributes->last_name);
        $this->assertEquals($location->place_id, $attributes->location->place_id);
        $this->assertEquals($location->formatted_address, $attributes->location->formatted_address);
        for ($i = 0; $i < count($location->address_components) ; $i++) {
            $this->assertEquals($location->address_components[$i]['long_name'], $attributes->location->address_components[$i]->long_name);
        }
        $this->assertEquals($params->website, $attributes->website);
        $this->assertEquals($params->contactPhone, $attributes->phone);
        $this->assertEquals($params->contactAlternatePhone, $attributes->alt_phone);
        $this->assertEquals($params->legalName, $attributes->legal_name);
        $this->assertEquals($params->feedback, $attributes->feedback);
        $this->assertEquals($params->type, $attributes->type);
        $this->assertEquals($params->typeOther, $attributes->other_type);
        $this->assertEquals($params->taxStatus, $attributes->tax_status);
        $this->assertEquals($params->taxStatusOther, $attributes->other_tax_status);
        $this->assertEquals($params->contactPosition, $attributes->position);
        $this->assertEquals($params->contactPositionOther, $attributes->other_position);
        $this->assertEquals($params->description->en, $attributes->description->en);
        $this->assertEquals($params->description->fr, $attributes->description->fr);
        $this->assertEquals($params->hasLocation, $attributes->has_location === 1 ? 'yes' : 'no');
        $this->assertNotNull($logo_id);
    }
}
