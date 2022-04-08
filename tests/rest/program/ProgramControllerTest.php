<?php

namespace rest\program;

use rest\Request;
use rest\RestTestCase;
use rest\Session;

class ProgramControllerTest extends RestTestCase
{
    public function testPost()
    {
        $body = (new ProgramBuilder())->getBody();
        $id = $body->data->id;
        $this->assertNotNull($id);
        return $id;
    }

    /**
     * @depends testPost
     */
    public function testGet($id, $langCode = 'en')
    {
        $response = (new Request())
      ->uri("/$langCode/a/app/program/{$id}?include=field_administrators,field_organization_entity,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session(new Session())
      ->execute();
        return json_decode($response->getBody());
    }

    /**
     * @depends testPost
     */
    public function validateStandardFields($id, $langCode = 'en')
    {
        $body = $this->testGet($id);
        $this->validateProgram($body);
    }

    /**
     * @depends testPost
     */
    public function testDenySaveSettingsToAnonymousUser($id)
    {
        $settings = ProgramUtils::getProgramSettings();
        $response = (new Request())
      ->uri("a/app/program/$id/settings")
      ->data($settings)
      ->expectedResponseCode(403)
      ->execute();
    }

    /**
     * @depends testPost
     */
    public function testDenySaveSettingsToUnauthorizedUser($id)
    {
        $settings = ProgramUtils::getProgramSettings();
        $response = (new Request())
      ->uri("a/app/program/$id/settings")
      ->data($settings)
      ->session($this->authenticatedSession())
      ->expectedResponseCode(403)
      ->execute();
        $body = $this->testGet($id);
        $attributes = $body->data->attributes;
        $this->assertNotEquals($settings->bbbscInquiryProgramOfInterest, $attributes->bbbscInquiryProgramOfInterest);
        $this->assertNotEquals($settings->bbbscProgramType, $attributes->bbbscProgramType);
        $this->assertNotEquals($settings->bbbscSystemUser, $attributes->bbbscSystemUser);
        $this->assertEquals(false, $attributes->bbbsc);
    }

    /**
     * @depends testPost
     */
    public function testGlobalAdminCanSaveSettings($id)
    {
        $settings = ProgramUtils::getProgramSettings();
        $settings->bbbscInquiryProgramOfInterest = "other_program";
        $response = (new Request())
      ->uri("a/app/program/$id/settings")
      ->data($settings)
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = $this->testGet($id);
        $attributes = $body->data->attributes;
        $this->assertEquals($settings->bbbscInquiryProgramOfInterest, $attributes->bbbscInquiryProgramOfInterest);
        $this->assertEquals($settings->bbbscProgramType, $attributes->bbbscProgramType);
        $this->assertEquals($settings->bbbscSystemUser, $attributes->bbbscSystemUser);
        $this->assertEquals($settings->bbbsc ? 1 : 0, $attributes->bbbsc);
    }

    /**
     * @depends testPost
     */
    public function testGlobalAdminCanPatch($id)
    {
        $payload = $this->setUpProgramPatchData('PATCHED by GLOBAL');
        $attributes = $payload->contents->nodes->en->attributes;
        $additionalAttributes = $payload->contents->additional;
        $data = $payload->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/program/$id")
      ->session($this->globalAdministratorSession())
      ->data($data, true)
      ->execute();
        $body = $this->testGet($id);
        $this->validateProgram($body, $attributes, $additionalAttributes);
    }

    /**
     * @depends testPost
     */
    public function testDenyPatchToAnonymousUser($id)
    {
        $newName = 'PATCHED by ANONYMOUS';
        $payload = $this->setUpProgramPatchData($newName);
        $data = $payload->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/program/$id")
      ->data($data, true)
      ->expectedResponseCode(403)
      ->execute();
        $body = $this->testGet($id);
        $programTitle = $body->data->attributes->title->en;
        $this->assertNotEquals($newName, $programTitle, "Server returned 403 but Anonymous user was able to patch program");
    }

    /**
     * @depends testPost
     */
    public function testDenyPatchToUnauthorizedUser($id)
    {
        $newName = 'PATCHED by UNAUTHORIZED';
        $payload = $this->setUpProgramPatchData('PATCHED by UNAUTHORIZED');
        $data = $payload->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/program/$id")
      ->session($this->authenticatedSession())
      ->data($data, true)
      ->expectedResponseCode(403)
      ->execute();
        $body = $this->testGet($id);
        $programTitle = $body->data->attributes->title->en;
        $this->assertNotEquals($newName, $programTitle, "Server returned 403 but unauthorized user was able to patch program");
    }

    public function testPostAlternate()
    {
        $body = (new ProgramBuilder())::createProgram(true);
        $id = $body->data->id;
        $this->assertNotNull($id);
        return $id;
    }

    /**
     * @depends testPostAlternate
     */
    public function testGetAlternate($id)
    {
        $body = $this->testGet($id);
        $alternateParams = ProgramUtils::getAlternateParams();
        $alternateAdditionalParams = ProgramUtils::getAlternateAdditionalParams();
        $this->validateProgram($body, $alternateParams, $alternateAdditionalParams);
    }

    /**
     * @depends testPostAlternate
     */
    public function testDeleteAlternate($id)
    {
        $this->testDelete($id);
    }

    public function testDenyGetProgramCollectionToAnonymousUser()
    {
        (new Request())
      ->uri("a/app/program?sort=-created&page%5Blimit%5D=20&page%5Boffset%5D=0")
      ->method('GET')
      ->session(new Session())
      ->expectedResponseCode(403)
      ->execute();
    }

    /**
     * @depends testPost
     */
    public function testDenyGetFullProgramCollectionToUnapprovedUser($programId)
    {
        $response = (new Request())
      ->uri("a/app/program?sort=-created&page%5Blimit%5D=20&page%5Boffset%5D=0")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        if (!empty($body->data)) {
            $retrievedProgram = $body->data[0];
            $retrievedProgramId = $retrievedProgram->id;
            $this->assertNotEquals($programId, $retrievedProgramId, "Unauthorized user was allowed to retrieve a program not associated with them");
        } else {
            $this->assertEmpty($body->data);
        }
    }

    /**
     * @depends testPost
     */
    public function testGlobalAdminCanAccessProgramCollection($programId)
    {
        $response = (new Request())
      ->uri("a/app/program?sort=-created&page%5Blimit%5D=20&page%5Boffset%5D=0")
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgram = $body->data[0];
        $retrievedProgramId = $retrievedProgram->id;
        $this->assertEquals($programId, $retrievedProgramId, "Global admin failed to get the expected program in collection");
    }

    /**
     * @depends testPost
     */
    public function testSaveProgramAdministrator($uuid)
    {
        $response  = ProgramUtils::addProgramAdministrator($uuid, "authenticated@example.com");
        $statusCode = $response->getStatusCode();
        $this->assertEquals(200, $statusCode, "New admin was not added to the organization");
        return $uuid;
    }

    /**
     * @depends testPost
     */
    public function testProgramAdminCanSaveSettings($id)
    {
        $settings = ProgramUtils::getProgramSettings();
        $settings->bbbscInquiryProgramOfInterest = "updated by admin";
        $response = (new Request())
      ->uri("a/app/program/$id/settings")
      ->data($settings)
      ->session($this->authenticatedSession())
      ->execute();
        $body = $this->testGet($id);
        $attributes = $body->data->attributes;
        $this->assertEquals($settings->bbbscInquiryProgramOfInterest, $attributes->bbbscInquiryProgramOfInterest);
        $this->assertEquals($settings->bbbscProgramType, $attributes->bbbscProgramType);
        $this->assertEquals($settings->bbbscSystemUser, $attributes->bbbscSystemUser);
        $this->assertEquals($settings->bbbsc ? 1 : 0, $attributes->bbbsc);
    }

    /**
     * @depends testSaveProgramAdministrator
     */
    public function testApprovedUserCanGetProgramInCollection($programId)
    {
        $response = (new Request())
      ->uri("a/app/program?sort=-created&page%5Blimit%5D=20&page%5Boffset%5D=0")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgram = $body->data[0];
        $retrievedProgramId = $retrievedProgram->id;
        $this->assertEquals($programId, $retrievedProgramId, "Authorized user was unable to get a program associated with them");
    }

    /**
     * @depends testSaveProgramAdministrator
     */
    public function testApprovedUserCanPatchProgram($id)
    {
        $payload = $this->setUpProgramPatchData('PATCHED by AUTHORIZED');
        $attributes = $payload->contents->nodes->en->attributes;
        $additionalAttributes = $payload->contents->additional;
        $data = $payload->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/program/$id")
      ->session($this->authenticatedSession())
      ->data($data, true)
      ->execute();
        $body = $this->testGet($id);
        $this->validateProgram($body, $attributes, $additionalAttributes);
    }

    public function testPostIncludingOrganization()
    {
        $request = new ProgramBuilder();
        $request->addOrganization();
        $response = $request->execute();
        $body = json_decode($response->getBody());
        $programId = $body->data->id;
        $organizationId = $body->data->relationships->field_organization_entity->data->id;
        $this->assertNotNull($programId, "Posted program returned an ID as response");
        $this->assertNotNull($organizationId, "Posted program returned an included organization ID as response");
        return [
            "programId" => $programId,
            "organizationId" => $organizationId
        ];
    }

    /**
     * @depends testPostIncludingOrganization
     */
    public function testApprovedUserCanGetProgramCollectionIncludingOrganization($entityIds)
    {
        $programId = $entityIds['programId'];
        $organizationId = $entityIds['organizationId'];
        $this->testSaveProgramAdministrator($programId);
        $response = (new Request())
      ->uri("a/app/program?sort=-created&page%5Blimit%5D=20&page%5Boffset%5D=0")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgram = $body->data[0];
        $retrievedProgramId = $retrievedProgram->id;
        $retrievedOrganizationId = $retrievedProgram->attributes->organization_uuid;
        $this->assertEquals($programId, $retrievedProgramId, "Authorized user was unable to get a program including organization associated with them in a collection");
        $this->assertEquals($organizationId, $retrievedOrganizationId, "Authorized user was unable to get a program including associated with them in a collection");
    }

    public function testGetProgramIncludingOrganization()
    {
        $langCode = 'en';
        $body = (new ProgramBuilder())->addOrganization()->getBody();
        $programId = $body->data->id;
        $organizationId = $body->data->relationships->field_organization_entity->data->id;

        $response = (new Request())
      ->uri("/$langCode/a/app/program/{$programId}?include=field_administrators,field_organization_entity,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgramId = $body->data->id;
        $retrievedOrganizationId = $body->data->relationships->field_organization_entity->data->id;
        $this->assertEquals($programId, $retrievedProgramId, "Authorized user was unable to load program details");
        $this->assertEquals($organizationId, $retrievedOrganizationId, "Authorized user was unable to load program details");
    }

    public function testGetProgramIncludingOrganizationAsProgramAdministrator()
    {
        $langCode = 'en';
        $body = (new ProgramBuilder())->addOrganization()->getBody();
        $programUuid = $body->data->id;
        $organizationId = $body->data->relationships->field_organization_entity->data->id;

        $response  = (new Request())
      ->uri("a/app/program/$programUuid/administrator/authenticated@example.com")
      ->session($this->globalAdministratorSession())
      ->execute();

        $response = (new Request())
      ->uri("/$langCode/a/app/program/{$programUuid}?include=field_administrators,field_organization_entity,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->authenticatedSession())
      ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgramId = $body->data->id;
        $retrievedOrganizationId = $body->data->relationships->field_organization_entity->data->id;
        $this->assertEquals($programUuid, $retrievedProgramId, "Authorized user was unable to load program details");
        $this->assertEquals($organizationId, $retrievedOrganizationId, "Authorized user was unable to load program details");
    }

    public function testPostFrenchProgram($settings = null)
    {
        if (!$settings) {
            $settings = new ProgramCreationSettings();
            $settings->frenchOnly = true;
        }
        $body = ProgramBuilder::createProgram($settings->useAlternateParams, $settings->includeOrganization, $settings->frenchOnly);
        $id = $body->data->id;
        $this->assertNotNull($id);
        $settings->id = $id;
        return $settings;
    }

    /**
     * @depends testPostFrenchProgram
     */
    public function testGetFrenchProgram($settings)
    {
        $contents = ProgramUtils::getContents($settings->useAlternateParams, $settings->includeOrganization, $settings->frenchOnly);
        $params = $contents->nodes->fr->attributes;
        $additionalParams = $contents->additional;
        $body = $this->testGet($settings->id, 'fr');
        $this->validateProgram($body, $params, $additionalParams);
    }

    public function testPostFrenchProgramAlternate()
    {
        $settings = new ProgramCreationSettings();
        $settings->useAlternateParams = true;
        $settings->includeOrganization = true;
        $settings->frenchOnly = true;
        return $this->testPostFrenchProgram($settings);
    }

    /**
     * @depends testPostFrenchProgramAlternate
     */
    public function testGetFrenchProgramAlternate($settings)
    {
        $contents = ProgramUtils::getContents($settings->useAlternateParams, $settings->includeOrganization, $settings->frenchOnly);
        $params = $contents->nodes->fr->attributes;
        $additionalParams = $contents->additional;
        $response = (new Request())
      ->uri("/fr/a/app/program/{$settings->id}?include=field_administrators,field_organization_entity,field_logo&fields[file--file]=uri,url")
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $this->validateProgram($body, $params, $additionalParams);
    }

    /**
     * @depends testPost
     */
    public function testDelete($id)
    {
        $innerData = ProgramUtils::getDataObject();
        $data = $innerData->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/program/$id")
      ->session($this->globalAdministratorSession())
      ->method("DELETE")
      ->data($data)
      ->execute();
        $responseStatus = $response->getStatusCode();
        $this->assertEquals($responseStatus, 200);
    }

    public function setUpProgramPatchData($newTitle = 'PATCHED')
    {
        $params = ProgramUtils::getParams();
        $additionalParams = ProgramUtils::getAdditionalParams();
        $params->field_facebook = "new-facebook";
        $additionalParams->title->en = $newTitle;
        $payload = ProgramUtils::getDataObject();
        $payload->contents->nodes->en->attributes = $params;
        $payload->contents->nodes->fr->attributes = $params;
        unset($payload->contents->nodes->fr);
        $payload->contents->additional = $additionalParams;
        return $payload;
    }

    public function validateProgram($body, $params = null, $additionalParams = null)
    {
        $attributes = $body->data->attributes;
        $logo_id = $body->included[0]->id;
        if (!$params) {
            $params = ProgramUtils::getParams();
        }
        if (!$additionalParams) {
            $additionalParams = ProgramUtils::getAdditionalParams();
        }
        $country = $_ENV['COUNTRY'] == null ? 'ca' : $_ENV['COUNTRY'];
        $this->assertEquals($additionalParams->first_name, $attributes->first_name);
        $this->assertEquals($additionalParams->last_name, $attributes->last_name);
        $this->assertEquals($additionalParams->position, $attributes->position);
        $this->assertEquals($additionalParams->phone, $attributes->phone);
        $this->assertEquals($additionalParams->altPhone, $attributes->altPhone);
        $this->assertEquals($additionalParams->email, $attributes->email);
        $this->assertEquals($additionalParams->delivery->community == true ? 1 : 0, $attributes->communityBased);
        $this->assertEquals($additionalParams->delivery->siteBased == true ? 1 : 0, $attributes->siteBased);
        $this->assertEquals($additionalParams->delivery->eMentoring == true ? 1 : 0, $attributes->eMentoring);
        $this->assertEquals($additionalParams->source, $attributes->source);
        $this->assertEquals($additionalParams->delivery->nationWideEMentoring == true ? 1 : 0, $attributes->nationWideEMentoring);
        if ($additionalParams->delivery->community) {
            $this->assertEquals($additionalParams->delivery->communityLocations[0]['address_components'][1]['long_name'], $attributes->communityBasedLocations[0]->vicinity);
            $this->assertEquals($additionalParams->delivery->communityLocations[1]['address_components'][1]['long_name'], $attributes->communityBasedLocations[1]->vicinity);
        }
        if ($additionalParams->delivery->eMentoring && !$additionalParams->delivery->nationWideEMentoring) {
            $this->assertEquals($additionalParams->delivery->eMentoringLocations[0]['address_components'][1]['long_name'], $attributes->eMentoringLocations[0]->vicinity);
            $this->assertEquals($additionalParams->delivery->eMentoringLocations[1]['address_components'][1]['long_name'], $attributes->eMentoringLocations[1]->vicinity);
        }
        if ($additionalParams->delivery->siteBased) {
            $this->assertEquals($additionalParams->delivery->siteBasedLocations[0]['address_components'][1]['long_name'], $attributes->siteBasedLocations[0]->vicinity);
            $this->assertEquals($additionalParams->delivery->siteBasedLocations[1]['address_components'][1]['long_name'], $attributes->siteBasedLocations[1]->vicinity);
        }
        $this->assertEquals($additionalParams->title->en, $attributes->title->en);
        $this->assertEquals($additionalParams->programDescription->en, $attributes->programDescription->en);
        $this->assertEquals($additionalParams->mentorDescription->en, $attributes->mentorDescription->en);
        $this->assertEquals($additionalParams->trainingDescription->en, $attributes->trainingDescription->en);

        if ($country == 'ca') {
            $this->assertEquals($additionalParams->title->fr, $attributes->title->fr);
            $this->assertEquals($additionalParams->programDescription->fr, $attributes->programDescription->fr);
            $this->assertEquals($additionalParams->mentorDescription->fr, $attributes->mentorDescription->fr);
            $this->assertEquals($additionalParams->trainingDescription->fr, $attributes->trainingDescription->fr);
        }

        $this->assertEquals($params->field_facebook, $attributes->field_facebook);
        $this->assertEquals($params->field_focus_area, $attributes->field_focus_area);
        $this->assertEquals($params->field_instagram, $attributes->field_instagram);
        $this->assertEquals($params->field_ns_bg_check, $attributes->field_ns_bg_check);
        $this->assertEquals($params->field_ns_bg_check_types, $attributes->field_ns_bg_check_types);
        $this->assertEquals($params->field_ns_bg_fingerprint_type, $attributes->field_ns_bg_fingerprint_type);
        $this->assertEquals($params->field_ns_bg_name_type, $attributes->field_ns_bg_name_type);
        $this->assertEquals($params->field_ns_bg_other_types, $attributes->field_ns_bg_other_types);
        $this->assertEquals($params->field_ns_bg_peer_type, $attributes->field_ns_bg_peer_type);
        $this->assertEquals($params->field_ns_training, $attributes->field_ns_training);
        $this->assertEquals($params->field_ns_training_description, $attributes->field_ns_training_description);
        $this->assertEquals($params->field_primary_meeting_location, $attributes->field_primary_meeting_location);
        $this->assertEquals($params->field_program_accepting, $attributes->field_program_accepting);
        $this->assertEquals($params->field_program_ages_mentor_target, $attributes->field_program_ages_mentor_target);
        $this->assertEquals($params->field_program_ages_served, $attributes->field_program_ages_served);
        $this->assertEquals($params->field_program_family_other, $attributes->field_program_family_other);
        $this->assertEquals($params->field_program_family_served, $attributes->field_program_family_served);
        $this->assertEquals($params->field_program_genders_served, $attributes->field_program_genders_served);
        $this->assertEquals($params->field_program_gender_mentor_targ, $attributes->field_program_gender_mentor_targ);
        $this->assertEquals($params->field_program_grades_served, $attributes->field_program_grades_served);
        $this->assertEquals($params->field_program_how_are_meetings_s, $attributes->field_program_how_are_meetings_s);
        $this->assertEquals($params->field_program_mentees_waiting_li, $attributes->field_program_mentees_waiting_li);
        $this->assertEquals($params->field_program_mentor_freq_commit, $attributes->field_program_mentor_freq_commit);
        $this->assertEquals($params->field_program_mentor_hour_commit, $attributes->field_program_mentor_hour_commit);
        $this->assertEquals($params->field_program_mentor_month_commi, $attributes->field_program_mentor_month_commi);
        $this->assertEquals($params->field_program_operated_through, $attributes->field_program_operated_through);
        $this->assertEquals($params->field_program_youth_other, $attributes->field_program_youth_other);
        $this->assertEquals($params->field_program_youth_per_year, $attributes->field_program_youth_per_year);
        $this->assertEquals($params->field_program_youth_served, $attributes->field_program_youth_served);
        $this->assertEquals($params->field_twitter, $attributes->field_twitter);
        $this->assertEquals($params->field_types_of_mentoring, $attributes->field_types_of_mentoring);
        $this->assertEquals($params->field_types_of_mentoring_other, $attributes->field_types_of_mentoring_other);
        $this->assertEquals($params->field_website, $attributes->field_website);
        $this->assertEquals($params->field_program_operated_other, $attributes->field_program_operated_other);
        $this->assertEquals($params->field_focus_area_other, $attributes->field_focus_area_other);
        $this->assertEquals($params->field_primary_meeting_loc_other, $attributes->field_primary_meeting_loc_other);
        $this->assertEquals($params->field_program_how_other, $attributes->field_program_how_other);
        $this->assertEquals($params->field_program_genders_other, $attributes->field_program_genders_other);
        $this->assertEquals($params->field_program_ages_other, $attributes->field_program_ages_other);
        $this->assertEquals($params->field_program_family_other, $attributes->field_program_family_other);
        $this->assertEquals($params->field_program_youth_other, $attributes->field_program_youth_other);
        $this->assertEquals($params->field_program_gender_mentor_oth, $attributes->field_program_gender_mentor_oth);
        $this->assertEquals($params->field_program_age_mentor_other, $attributes->field_program_age_mentor_other);
        $this->assertEquals($params->field_program_mentor_month_other, $attributes->field_program_mentor_month_other);
        $this->assertEquals($params->field_program_mentor_freq_other, $attributes->field_program_mentor_freq_other);
        $this->assertEquals($params->field_program_mentor_hour_other, $attributes->field_program_mentor_hour_other);
        $this->assertNotNull($logo_id);
    }
}
