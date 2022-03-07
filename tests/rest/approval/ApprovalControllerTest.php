<?php

namespace rest\approval;

use rest\organization\OrganizationControllerTest;
use rest\program\ProgramBuilder;
use rest\Request;
use rest\RestTestCase;

class ApprovalControllerTest extends RestTestCase
{
    public function testPostProgramStatus()
    {
        $programBody = ProgramBuilder::createProgram();
        $programId = $programBody->data->id;
        $statusVals = ApprovalUtils::getValidStatusVals();
        return $this->postEntity($programId, $statusVals->allowed, 'programs');
    }

    /**
     * @depends testPostProgramStatus
     */
    public function testGetProgramStatus($data)
    {
        $this->getEntityData($data);
    }

    /**
     * @depends testPostProgramStatus
     */
    public function testPatchProgramStatus($data)
    {
        $this->patchEntityData($data);
    }

    public function testApproveOrganization()
    {
        $newOrganization = new OrganizationControllerTest();
        $organizationId = $newOrganization->testCreateOrganization();
        $statusVals = ApprovalUtils::getValidStatusVals();
        return $this->postEntity($organizationId, $statusVals->allowed, 'organization');
    }

    public function testApproveOrganizationInFrench()
    {
        $newOrganization = new OrganizationControllerTest();
        $organizationId = $newOrganization->testCreateOrganization();
        $statusVals = ApprovalUtils::getValidStatusVals();
        return $this->postEntity($organizationId, $statusVals->allowed, 'organization', 'fr');
    }

    /**
     * @depends testApproveOrganization
     */
    public function testGetOrganizationStatus($data)
    {
        $this->getEntityData($data);
    }

    /**
     * @depends testApproveOrganization
     */
    public function testPatchOrganizationStatus($data)
    {
        $this->patchEntityData($data);
    }

    private function postEntity($entityId, $approvalStatus, $entityType, $uilang = 'en')
    {
        $response = ApprovalUtils::changeApprovalStatus($entityId, $approvalStatus, $entityType, $uilang);
        $body = json_decode($response->getBody());
        $statusCode = $response->getStatusCode();
        $approvalId = $body->data->id;
        $this->assertEquals(201, $statusCode, "Changing {$entityType} approval status return 201 response");
        $this->assertNotNull($approvalId, "An approval entity ID was returned in response body");
        return [
            "entityId" => $entityId,
            "approvalId" => $approvalId,
            "approvalStatus" => $approvalStatus,
            "entityType" => $entityType
        ];
    }

    private function getEntityData($data)
    {
        $entityType = $data['entityType'];
        $approvalStatus = $data['approvalStatus'];
        $approvalId = $data['approvalId'];
        $entityId = $data['entityId'];
        $userId = $this->globalAdministratorSession()->getUserId();
        $uri = "a/node/approval?filter[field_approval_entity.id]=$entityId&filter[field_user_entity.id]=$userId";
        $response = (new Request())
      ->uri($uri)
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $retrievedApprovalId = $body->data[0]->id;
        $retrievedStatus = $body->data[0]->attributes->field_status;
        $retrievedEntityId = $body->data[0]->relationships->field_approval_entity->data->id;
        $retrievedType = $body->data[0]->relationships->field_approval_entity->data->type;
        $this->assertEquals("node--{$entityType}", $retrievedType, "Retrieved entity type matched posted entity type");
        $this->assertEquals($entityId, $retrievedEntityId, "Retrieved {$entityType} ID matches posted {$entityType} ID");
        $this->assertEquals($approvalStatus, $retrievedStatus, "The approval status in response body matches the posted value");
        $this->assertEquals($approvalId, $retrievedApprovalId, "Retrieved approval ID matches expected approval ID");
        $this->assertTrue(in_array($retrievedStatus, (array)ApprovalUtils::getValidStatusVals()), "The entity status was a valid value");
    }

    /**
     * @depends testPostProgramStatus
     */
    public function patchEntityData($data)
    {
        $statusVals = ApprovalUtils::getValidStatusVals();
        $data['approvalStatus'] = $statusVals->suspended;
        $approvalId = $data['approvalId'];
        $patchData = ApprovalUtils::getApprovalData($data['entityId'], $data['approvalStatus'], $data['entityType']);
        $patchData['data']['id'] = $approvalId;
        $response = (new Request())
      ->uri("a/app/approval/{$approvalId}")
      ->method('PATCH')
      ->session($this->globalAdministratorSession())
      ->data($patchData)
      ->execute();
        $statusCode = $response->getStatusCode();
        $this->assertEquals(200, $statusCode, "PATCH approval returned status code 200");
        $this->getEntityData($data);
    }
}
