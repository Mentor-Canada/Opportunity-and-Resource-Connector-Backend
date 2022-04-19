<?php

namespace rest\filter;

use rest\account\AccountUtils;
use rest\inquiry\InquiryUtils;
use rest\organization\OrganizationBuilder;
use rest\organization\OrganizationUtils;
use rest\program\ProgramBuilder;
use rest\program\ProgramUtils;
use rest\Request;
use rest\request_objects\UserBuilder;
use rest\RestTestCase;

class FilterCaseSensitivityTest extends RestTestCase
{

    public function testOrganizationFilterFieldsAreCaseInsensitive()
    {
        $organizationParams = $this->getCustomOrgFilterParams();
        $organizationBuilder = new OrganizationBuilder();
        $organizationBuilder->attributes = (array)$organizationParams;
        $organizationBuilder->execute();
        $filterParams = new OrganizationFilterParams();
        $filterParams->title = "filter org";
        $filterParams->location = "Rue Saint-Ambroise";
        $filterParams->legal_name = $organizationParams->legalName;
        $filterParams->description = $organizationParams->description->en;
        $filterParams->first_name = $organizationParams->contactFirstName;
        $filterParams->last_name = $organizationParams->contactLastName;
        $filterParams->other_type = $organizationParams->typeOther;
        $filterParams->other_tax_status = $organizationParams->taxStatusOther;
        $filterParams->other_position = $organizationParams->contactPositionOther;
        $filterParams->phone = $organizationParams->contactPhone;
        $filterParams->alt_phone = $organizationParams->contactAlternatePhone;
        $filterParams->email = $organizationParams->contactEmail;
        $filterParams->website = $organizationParams->website;

        foreach ($filterParams as $key => $val) {
            $regularQueryString = $this->getUrlEncodedQueryString($key, $val);
            $upperCaseQueryString = $this->getUrlEncodedQueryString($key, strtoupper($val));
            $regularUri = "a/app/organization?{$regularQueryString}&sort=-created";
            $upperCaseUri = "a/app/organization?{$upperCaseQueryString}&sort=-created";
            $regularBody = $this->fetchFilteredEntities($regularUri);
            $upperCaseBody = $this->fetchFilteredEntities($upperCaseUri);
            $this->assertNotEmpty(
                $regularBody->data,
                "'$key' in organization filter failed to fetch an expected organization before being tested for case sensitivity"
            );
            $this->assertNotEmpty(
                $upperCaseBody->data,
                "'$key' in organization filter was case sensitive"
            );
        }
    }

    public function testProgramFilterFieldsAreCaseInsensitive()
    {
        $programParams = ProgramUtils::getParams();
        $programAdditionalParams = ProgramUtils::getAdditionalParams();
        $programAdditionalParams->title->en = "filter program";
        $programBuilder = new ProgramBuilder();
        $programBuilder->attributes = (array)$programParams;
        $programBuilder->additionalAttributes = (array)$programAdditionalParams;
        $programBuilder->execute();
        $filterParams = new ProgramFilterParams();
        $filterParams->title = 'filter program';
        $filterParams->location = 'Chelsea';
        $filterParams->programDescription = $programAdditionalParams->programDescription->en;
        $filterParams->first_name = $programAdditionalParams->first_name;
        $filterParams->last_name = $programAdditionalParams->last_name;
        $filterParams->position = $programAdditionalParams->position;
        $filterParams->email = $programAdditionalParams->email;
        $filterParams->phone = $programAdditionalParams->phone;
        $filterParams->field_facebook = $programParams->field_facebook;
        $filterParams->field_website = $programParams->field_website;
        $filterParams->field_instagram = $programParams->field_instagram;
        $filterParams->field_twitter = $programParams->field_twitter;
        $filterParams->field_focus_area_other = $programParams->field_focus_area_other;
        $filterParams->field_primary_meeting_loc_other = $programParams->field_primary_meeting_loc_other;
        $filterParams->field_types_of_mentoring_other = $programParams->field_types_of_mentoring_other;
        $filterParams->field_program_operated_other = $programParams->field_program_operated_other;
        $filterParams->field_program_how_other = $programParams->field_program_how_other;
        $filterParams->field_program_genders_other = $programParams->field_program_genders_other;
        $filterParams->field_program_ages_other = $programParams->field_program_ages_other;
        $filterParams->field_program_family_other = $programParams->field_program_family_other;
        $filterParams->field_program_youth_other = $programParams->field_program_youth_other;
        $filterParams->field_program_gender_mentor_oth = $programParams->field_program_gender_mentor_oth;
        $filterParams->trainingDescription = $programParams->field_ns_training_description;
        $filterParams->mentorDescription = $programParams->field_mentor_role_description;
        $filterParams->field_program_mentor_freq_other = $programParams->field_program_mentor_freq_other;
        $filterParams->field_program_mentor_hour_other = $programParams->field_program_mentor_hour_other;
        $filterParams->altPhone = $programAdditionalParams->altPhone;

        foreach ($filterParams as $key => $val) {
            $regularQueryString = $this->getUrlEncodedQueryString($key, $val);
            $upperCaseQueryString = $this->getUrlEncodedQueryString($key, strtoupper($val));
            $regularUri = "a/app/program?{$regularQueryString}&sort=-created";
            $upperCaseUri = "a/app/program?{$upperCaseQueryString}&sort=-created";
            $regularBody = $this->fetchFilteredEntities($regularUri);
            $upperCaseBody = $this->fetchFilteredEntities($upperCaseUri);
            $this->assertNotEmpty(
                $regularBody->data,
                "'$key' in programs filter failed to fetch expected programs before being tested for case sensitivity"
            );
            $this->assertNotEmpty(
                $upperCaseBody->data,
                "'$key' in programs filter was case sensitive"
            );
        }
    }

    public function testInquiryFilterFieldsAreCaseInsensitive()
    {
        $inquiryParams = InquiryUtils::getParams();
        $inquiryParams->howOther = "from other source";
        $inquiryParams->firstName = "John";
        $inquiryParams->lastName = "Smith";
        $inquiryParams->email = "email";
        $inquiryParams->phone = "phone";
        InquiryUtils::createInquiry($inquiryParams);

        $filterParams = new InquiryFilterParams();
        $filterParams->inquiryFilterFields["inquiries.howOther"] = $inquiryParams->howOther;
        $filterParams->inquiryFilterFields["inquiries.firstName"] = $inquiryParams->firstName;
        $filterParams->inquiryFilterFields["inquiries.lastName"] = $inquiryParams->lastName;
        $filterParams->inquiryFilterFields["inquiries.email"] = $inquiryParams->email;
        $filterParams->inquiryFilterFields["inquiries.phone"] = $inquiryParams->phone;

        foreach ($filterParams->inquiryFilterFields as $key => $val) {
            if (!$val) {
                continue;
            }
            $regularQueryString = $this->getUrlEncodedQueryString($key, $val);
            $upperCaseQueryString = $this->getUrlEncodedQueryString($key, strtoupper($val));
            $regularUri = "a/app/inquiry?{$regularQueryString}&sort=-created";
            $upperCaseUri = "a/app/inquiry?{$upperCaseQueryString}&sort=-created";
            $regularBody = $this->fetchFilteredEntities($regularUri);
            $upperCaseBody = $this->fetchFilteredEntities($upperCaseUri);
            $this->assertNotEmpty(
                $regularBody->data,
                "'$key' in inquiry filter failed to fetch expected inquiries before being tested for case sensitivity"
            );
            $this->assertNotEmpty(
                $upperCaseBody->data,
                "'$key' in inquiry filter was case sensitive"
            );
        }
    }

    public function testAccountFilterIsCaseInsensitive()
    {
        $userBuilder = new UserBuilder();
        $userBuilder->firstName = 'John';
        $userBuilder->lastName = 'Smith';
        $userBuilder->email = 'accountFilterTest@example.com';
        $userBuilder->password = 'hello123';
        AccountUtils::createAccount($userBuilder->build());

        $filterParams = new AccountFilterParams();
        $filterParams->firstName = $userBuilder->firstName;
        $filterParams->lastName = $userBuilder->lastName;
        $filterParams->mail = $userBuilder->email;

        foreach ($filterParams as $key => $val) {
            $upperCaseVal = strtoupper($val);
            if ($key === 'mail') {
                $regularQueryString = $this->getUrlEncodedQueryStringForMailField($key, $val);
                $upperCaseQueryString = $this->getUrlEncodedQueryStringForMailField($key, strtoupper($val));
            } else {
                $regularQueryString = urlencode("filter[$key]") . '=' . urlencode("$val");
                $upperCaseQueryString = urlencode("filter[$key]") . '=' . urlencode("{$upperCaseVal}");
            }
            $regularUri = "a/app/accounts?{$regularQueryString}&sort=mail";
            $upperCaseUri = "a/app/accounts?{$upperCaseQueryString}&sort=mail";
            $regularBody = $this->fetchFilteredEntities($regularUri);
            $upperCaseBody = $this->fetchFilteredEntities($upperCaseUri);
            $this->assertNotEmpty(
                $regularBody->data,
                "'$key' in accounts filter failed to fetch expected accounts before being tested for case sensitivity"
            );
            $this->assertNotEmpty(
                $upperCaseBody->data,
                "'$key' in accounts filter was case sensitive"
            );
        }
    }

    private function getUrlEncodedQueryString($key, $val): string
    {
        return urlencode("filter[$key]") . '=' . urlencode('"') . urlencode("$val") . urlencode('"');
    }

    private function getUrlEncodedQueryStringForMailField($key, $val): string
    {
        return urlencode("filter[$key][value]") . '=' . urlencode("$val") . '&' . urlencode(
                "filter[$key][operator]"
            ) . '=' . 'CONTAINS';
    }

    private function getCustomOrgFilterParams()
    {
        $organizationParams = OrganizationUtils::getParams();
        $timeCreated = date_format(date_create(), "D/M/d - h:i:s");
        $organizationParams->title->en = "filter org - {$timeCreated}";
        $organizationParams->type = "other";
        $organizationParams->typeOther = "other type";
        $organizationParams->taxStatus = "other";
        $organizationParams->taxStatusOther = "other tax";
        $organizationParams->contactPosition = "other";
        $organizationParams->contactPositionOther = "other contact";
        return $organizationParams;
    }

    private function fetchFilteredEntities($uri)
    {
        $response = (new Request())
            ->uri($uri)
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        return json_decode($response->getBody());
    }

}
