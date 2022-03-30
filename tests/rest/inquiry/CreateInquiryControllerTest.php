<?php

namespace rest\inquiry;

use rest\program\ProgramBuilder;
use rest\Request;
use rest\RestTestCase;

class CreateInquiryControllerTest extends RestTestCase
{
    public function testCreateMentorInquiry()
    {
        $params = InquiryUtils::getParams();

        $response = InquiryUtils::createInquiry($params);
        $this->assertPost($response, $params);
    }

    public function testCreateMentorInquiryInFrench()
    {
        $params = InquiryUtils::getParams();

        $response = InquiryUtils::createInquiry($params, "fr");
        $this->assertPost($response, $params);
    }

    public function testCreateMenteeInquiry()
    {
        $params = InquiryUtils::getAlternateParams();

        $response = InquiryUtils::createInquiry($params);
        $this->assertPost($response, $params);
    }

    public function testOrganizationAssociatedWithInquiryInAdminTableIsAccurate()
    {
        $params = InquiryUtils::getParams();
        $programBuilder = new ProgramBuilder();
        $programBuilder->addOrganization();
        $program = json_decode($programBuilder->execute()->getBody())->data;
        $organizationUUID = $program->relationships->field_organization_entity->data->id;
        $params->programId = $program->attributes->drupal_internal__nid;
        InquiryUtils::createInquiry($params);
        $inquiry = current(InquiryUtils::getInquiryCollection()->data);
        $retrievedOrganizationUUID = $inquiry->attributes->organization_uuid;
        $this->assertEquals($organizationUUID, $retrievedOrganizationUUID,
            "The organization uuid associated with an inquiry in admin inquires table was incorrect");
    }

    public function testGetInquiryByOrganizationFilter()
    {
        $params = InquiryUtils::getParams();
        $programBuilder = new ProgramBuilder();
        $programBuilder->addOrganization();
        $program = json_decode($programBuilder->execute()->getBody())->data;
        $organizationUUID = $program->relationships->field_organization_entity->data->id;
        $params->programId = $program->attributes->drupal_internal__nid;
        $inquiryUUID = InquiryUtils::createInquiry($params)->data->uuid;

        $uri = "a/app/inquiry?filter%5Binquiries.organization_id%5D=%22$organizationUUID%22";
        $response = (new Request())
            ->uri($uri)
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();

        $filterResults = json_decode($response->getBody())->data;
        $retrievedInquiry = current($filterResults);
        $retrievedInquiryUUID = $retrievedInquiry->attributes->uuid;
        $retrievedOrganizationUUID = $retrievedInquiry->attributes->organization_uuid;

        $this->assertEquals(1, count($filterResults),
            "The number of results retrieved through admin inquiry table organization filter was incorrect");
        $this->assertEquals($inquiryUUID, $retrievedInquiryUUID,
            "The inquiry retrieved through inquiry admin table organization filter was incorrect");
        $this->assertEquals($organizationUUID, $retrievedOrganizationUUID,
            "The organization retrieved through inquiry admin table organization filter was incorrect");
    }

    public function testCreateMenteeInquiryInFrench()
    {
        $params = InquiryUtils::getAlternateParams();

        $response = InquiryUtils::createInquiry($params, "fr");
        $this->assertPost($response, $params);
    }

    public function testProgramLinkInAdminInquiryTableIsAccurate()
    {
        $params = InquiryUtils::getParams();
        $program = ProgramBuilder::createProgram()->data;
        $programUUID = $program->id;
        $params->programId = $program->attributes->drupal_internal__nid;
        InquiryUtils::createInquiry($params);
        $inquiry = current(InquiryUtils::getInquiryCollection()->data);
        $retrievedProgramUUID = $inquiry->attributes->program_uuid;
        $this->assertEquals($programUUID, $retrievedProgramUUID,
            "The program uuid used for program link in inquires table did not match the expected program uuid"
        );
    }

    private function assertPost($response, $params)
    {
        $this->assertEquals($params->how, $response->data->how);
        $this->assertEquals($params->howOther, $response->data->howOther);
        $this->assertEquals($params->call, !!$response->data->voice);
        $this->assertEquals($params->sms, !!$response->data->sms);
    }
}
