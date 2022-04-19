<?php

namespace rest\filter;

use GuzzleHttp\RequestOptions;
use rest\program\ProgramBuilder;
use rest\Request;
use rest\Session;

class FilterUtils
{
    public static function getOrganizationFilterParams()
    {
        $filter = new OrganizationFilterParams();
        $filter->field_standing = "app-allowed";
        $filter->title = "test org";
        $filter->legal_name = "test legal name";
        $filter->description = "org description";
        $filter->type = "other";
        $filter->other_type = "other org";
        $filter->tax_status = "app-organization-tax-status-not-for-profit";
        $filter->other_tax_status = "other tax";
        $filter->first_name = "first_name";
        $filter->last_name = "last_name";
        $filter->position = "other";
        $filter->other_position = "other position";
        $filter->phone = "1234567";
        $filter->alt_phone = "7654321";
        $filter->email = "orgEmail@email.com";
        $filter->website = "webAdress";
        $filter->has_location = "yes";
        $filter->location = "100 street";
        return [
            RequestOptions::JSON => [
                "id" => null,
                "title" => "organization-filter",
                "type" => "organization",
                "data" => json_encode($filter)
            ]
        ];
    }

    public static function getProgramFilterParams()
    {
        $filter = new ProgramFilterParams();
        $filter->field_standing = "app-pending";
        $filter->title = "program name";
        $filter->programDescription = "program description";
        $filter->first_name = "first_name";
        $filter->last_name = "last_name";
        $filter->position = "position";
        $filter->email = "programEmail@email.com";
        $filter->phone = "1234567";
        $filter->delivery = "eMentoring";
        $filter->location = "anywhere";
        $filter->field_facebook = "facebook";
        $filter->field_website = "website";
        $filter->field_instagram = "instagram";
        $filter->field_twitter = "twitter";
        $filter->field_focus_area = "app-us-program-focus-academics";
        $filter->field_focus_area_other = "other focus";
        $filter->field_primary_meeting_location = "app-us-program-meeting-community";
        $filter->field_primary_meeting_loc_other = "other location";
        $filter->field_program_youth_per_year = "10";
        $filter->field_program_mentees_waiting_li = "5";
        $filter->field_types_of_mentoring = "app-type-of-mentoring-group";
        $filter->field_types_of_mentoring_other = "other mentoring";
        $filter->field_program_operated_through = "app-us-program-operated-business";
        $filter->field_program_operated_other = "operated other";
        $filter->field_program_how_are_meetings_s = "app-set-by-participants";
        $filter->field_program_how_other = "schedule other";
        $filter->field_program_genders_served = "app-us-two-spirit";
        $filter->field_program_genders_other = "other genders";
        $filter->field_program_ages_served = "app-us-15-18";
        $filter->field_program_ages_other = "other ages";
        $filter->field_program_family_served = "app-us-group-home";
        $filter->field_program_family_other = "other family";
        $filter->field_program_youth_served = "app-us-opportunity-youth";
        $filter->field_program_youth_other = "other youth";
        $filter->field_program_gender_mentor_targ = "app-us-female";
        $filter->field_program_gender_mentor_oth = "other mentor genders";
        $filter->field_program_ages_mentor_target = "app-us-age-35-49";
        $filter->field_ns_bg_check = "app-yes";
        $filter->field_ns_bg_fingerprint_type = "app-background-check-fingerprint-fbi";
        $filter->field_ns_bg_name_type = "app-background-check-name-state";
        $filter->field_ns_bg_other_types = "app-background-check-other-abuse";
        $filter->field_ns_bg_peer_type = "app-yes";
        $filter->field_ns_training = "app-yes";
        $filter->trainingDescription = "mentor training";
        $filter->mentorDescription = "mentor description";
        $filter->field_ns_bg_check_types = "app-background-check-type-ca-child-and-family";
        $filter->field_program_mentor_month_commi = "app-3-months";
        $filter->field_program_mentor_freq_commit = "app-frequency-monthly";
        $filter->field_program_mentor_freq_other = "other meeting frequency";
        $filter->field_program_mentor_hour_commit = "app-2-3-hours";
        $filter->field_program_mentor_hour_other = "other hours";
        $filter->altPhone = "7654321";
        return [
            RequestOptions::JSON => [
                "id" => null,
                "title" => "program-filter",
                "type" => "program",
                "data" => json_encode($filter)
            ]
        ];
    }

    public static function getInquiryFilterParams()
    {
        $program = ProgramBuilder::createProgram();
        $programId = $program->data->attributes->drupal_internal__nid;
        $data = [
            "inquiries.programId" => "{$programId}",
            "inquiries.role" => "mentor",
            "inquiries.status" => "app-un-contacted",
            "inquiries.how" => "app-us-hear-about-us-mentoring-partnership-in-my-state",
            "inquiries.howOther" => "how other",
            "inquiries.firstName" => "first_name",
            "inquiries.lastName" => "last_name",
            "inquiries.email" => "inquiry@email.com",
            "inquiries.phone" => "1234567",
            "inquiries.voice" => "app-yes",
            "inquiries.sms" => "app-no"
        ];
        return [
            RequestOptions::JSON => [
                "id" => null,
                "title" => "inquiry-filter",
                "type" => "inquiry",
                "data" => json_encode($data)
            ]
        ];
    }

    public static function createFilter($params)
    {
        $globalAdministrator = new Session();
        $globalAdministrator->signIn();

        $filterParams = $params['json'];
        $filterParams['title'] = "Filter-" . rand(1, 10000) . "-" . $filterParams['type'];
        $data['json'] = $filterParams;
        $response = $globalAdministrator->request('POST', 'a/app/filter', $data);
        $body = json_decode($response->getBody());
        $status = $body->status;
        $matchedFilter = self::getMatchedFilter($filterParams['title'], $data);
        return [
            "status" => $status,
            "filterParams" => $filterParams,
            "matchedFilter" => $matchedFilter
        ];
    }

    private function getMatchedFilter($newFilterTitle, $data)
    {
        $globalAdministrator = new Session();
        $globalAdministrator->signIn();

        $type = $data['json']['type'];
        $response = $globalAdministrator->request("GET", "a/app/filter?type={$type}", $data);
        $body = json_decode($response->getBody());
        $matchedFilter = null;
        foreach ($body as $filter) {
            if ($filter->title === $newFilterTitle) {
                $matchedFilter = $filter;
            }
        }
        return $matchedFilter;
    }

    public static function saveFilter(SaveFilterParams $filterParams)
    {
        $globalAdminSession = new Session();
        $globalAdminSession->signIn();
        $response = (new Request())
            ->uri("a/app/filter")
            ->method('POST')
            ->session($globalAdminSession)
            ->data($filterParams)
            ->execute();
        return json_decode($response->getBody())->data->id;
    }
}
