<?php

namespace rest\inquiry;

use rest\program\ProgramBuilder;
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
