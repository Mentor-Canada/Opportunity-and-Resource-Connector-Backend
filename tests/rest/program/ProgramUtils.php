<?php

namespace rest\program;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use rest\organization\OrganizationControllerTest;
use rest\request_objects\GuzzleMultipartObject;
use rest\request_objects\InnerNode;
use rest\request_objects\RequestContents;
use rest\request_objects\RequestNode;

class ProgramUtils
{
    public static function getParams()
    {
        $params = new ProgramParams();
        $params->field_types_of_mentoring = [
            "app-type-of-mentoring-group",
            "app-type-of-mentoring-team"
        ];
        $params->field_types_of_mentoring_other = "other mentoring types";
        $params->field_program_operated_through = [
            "app-ca-program-operated-community-based organization"
        ];
        $params->field_program_operated_other = "other operation location";
        $params->field_facebook = "programFacebook";
        $params->field_twitter = "programTwitter";
        $params->field_website = "programWebsite";
        $params->field_instagram = "programInstagram";
        $params->field_focus_area = "app-ca-program-focus-sports-activities";
        $params->field_focus_area_other = "other focus area";
        $params->field_primary_meeting_location = "app-ca-program-meeting-school-academic-site";
        $params->field_primary_meeting_loc_other = "other meeting location";
        $params->field_program_how_are_meetings_s = [
            "app-set-by-participants"
        ];
        $params->field_program_how_other = "other meeting schedules";
        $params->field_program_genders_served = [
            "app-ca-female"
        ];
        $params->field_program_genders_other = "other genders";
        $params->field_program_ages_served = [
            "app-ca-8-11"
        ];
        $params->field_program_ages_other = "other ages";
        $params->field_program_family_served = [
            "other"
        ];
        $params->field_program_family_other = "other family";
        $params->field_program_youth_served = [
            "other",
            "app-ca-college-post-secondary-student"
        ];
        $params->field_program_youth_other = "other youth";
        $params->field_program_youth_per_year = "77";
        $params->field_program_mentees_waiting_li = "50";
        $params->field_program_gender_mentor_targ = [
            "app-ca-female"
        ];
        $params->field_program_gender_mentor_oth = "other mentor genders";
        $params->field_program_ages_mentor_target = [
            "app-ca-age-18-24"
        ];
        $params->field_program_age_mentor_other = "other mentor age";
        $params->field_program_mentor_month_commi = "app-5-months";
        $params->field_program_mentor_month_other = "other monthly";
        $params->field_program_mentor_freq_commit = "app-frequency-bi-weekly";
        $params->field_program_mentor_freq_other = "other frequency";
        $params->field_program_mentor_hour_commit = "app-2-3-hours";
        $params->field_program_mentor_hour_other = "other hourly";
        $params->field_program_screens_mentors = "";
        $params->field_program_screens_mentors_ho = [];
        $params->field_program_screens_mentees = "";
        $params->field_program_screens_mentees_ho = [];
        $params->field_program_trains_mentors = "";
        $params->field_program_trains_mentors_how = [];

        $params->field_program_must_train_mentors = "";
        $params->field_program_trains_mentees = "";
        $params->field_program_trains_mentees_how = [];
        $params->field_program_must_train_mentees = "";
        $params->field_program_matches_how = [];
        $params->field_program_matches_explain = "";

        $params->field_program_ongoing_support = "";
        $params->field_program_beginning_and_end = "";
        $params->field_program_has_specific_goals = "";
        $params->field_program_which_goals = [];
        $params->field_program_which_goals_other = "";
        $params->field_feedback = "";
        $params->field_program_accepting = [
            "app-program-accepting-mentors"
        ];
        $params->field_ns_bg_check = "app-yes";
        $params->field_ns_bg_check_types = [
            "app-background-check-type-ca-child-and-family",
            "app-background-check-type-ca-vulnerable-sector-check"
        ];
        $params->field_ns_bg_fingerprint_type = "app-background-check-fingerprint-fbi";
        $params->field_ns_bg_name_type = "app-background-check-name-state";
        $params->field_ns_bg_peer_type = "";
        $params->field_ns_bg_other_types = [
            "app-background-check-other-abuse",
            "app-background-check-other-offender"
        ];
        $params->field_ns_training = "app-yes";
        $params->field_program_grades_served = [
            "app-grade-k-5"
        ];
        $params->field_display_title = "Unit Test Program";
        $params->field_description = "program description";
        $params->field_mentor_role_description = "mentor description";
        $params->field_ns_training_description = "this is the training description";
        $params->title = "Test Program";

        if (self::getCountry() == 'us') {
            $params->field_focus_area = "app-us-program-focus-job-skills";
            $params->field_primary_meeting_location = "app-us-program-meeting-workplace";
            $params->field_program_operated_through= ["app-us-program-operated-community"];
            $params->field_program_ages_served = ["app-us-8-10"];
            $params->field_program_gender_mentor_targ = ["app-us-female"];
            $params->field_program_ages_mentor_target = ["app-us-age-18-24"];
            $params->field_program_genders_served = ["app-us-female"];
            $params->field_program_youth_served = [
                "other",
                "app-us-first-generation-college"
            ];
            $params->field_ns_bg_check_types = [
                "app-background-check-type-fingerprint",
                "app-background-check-type-name",
                "app-background-check-type-other"
            ];
        }
        return $params;
    }

    public static function getAdditionalParams()
    {
        $email = getenv('EMAIL') ?: 'program@example.com';
        $params = new ProgramAdditionalParams();
        $params->first_name = "John";
        $params->last_name = "Smith";
        $params->position = "CEO";
        $params->phone = "1234567";
        $params->altPhone = "9999999";
        $params->email = $email;
        $params->delivery->community = true;
        $params->delivery->siteBased = false;
        $params->delivery->eMentoring = true;
        $params->delivery->nationWideEMentoring = true;
        $params->delivery->communityLocations = self::getLocations();
        $params->delivery->siteBasedLocations = [];
        $params->delivery->eMentoringLocations = [];
        $params->title->en = "Unit Test Program";
        $params->programDescription->en = "program description";
        $params->mentorDescription->en = "mentor description";
        $params->trainingDescription->en = "this is the training description";
        $params->adaSetting = true;
        $params->nqmsSetting = true;
        $params->source = "Mentor Connector";
        if (self::getCountry() == 'ca') {
            $params->title->fr = "French Program Title";
            $params->programDescription->fr = "description de program";
            $params->mentorDescription->fr = "description de menteur";
            $params->trainingDescription->fr = "description de entrainment de menteur";
        }
        return $params;
    }

    public static function getAlternateParams()
    {
        $programOperatedUs = [
            "app-us-program-operated-business",
            "app-us-program-operated-community",
            "app-us-program-operated-resident",
            "other",
            "app-us-program-operated-higher-education",
            "app-us-program-operated-faith",
            "app-us-program-operated-government",
            "app-us-program-operated-school"
        ];
        $programOperatedCanada = [
            "app-ca-program-operated-business",
            "app-ca-program-operated-school",
            "app-ca-program-operated-employment-agency",
            "app-ca-program-operated-higher-education-post-secondary-institution",
            "app-ca-program-operated-resident-treatment-facility",
            "app-ca-program-operated-faith-based-organization",
            "app-ca-program-operated-community-based organization",
            "app-ca-program-operated-foundation-or-philanthropic-organization",
            "app-ca-program-operated-government-agency",
            "app-ca-program-operated-correctional-facility",
            "other"
        ];
        $gendersServedUs = [
            "app-us-female",
            "app-us-two-spirit",
            "app-us-genderqueer",
            "other",
            "app-us-male",
            "app-us-non-binary",
        ];
        $gendersServedCanada = [
            "app-ca-female",
            "app-ca-two-spirit",
            "app-ca-genderqueer",
            "other",
            "app-ca-male",
            "app-ca-non-binary",
            "app-ca-transgender"
        ];
        $agesServedUs = [
            "app-us-7-and-under",
            "app-us-15-18",
            "app-us-8-10",
            "app-us-19-24",
            "app-us-11-14",
            "other"
        ];
        $agesServedCanada = [
            "app-ca-7-and-under",
            "app-ca-15-17",
            "app-ca-8-11",
            "app-ca-18-24",
            "app-ca-12-14",
            "app-ca-25-and-over",
            "other"
        ];
        $familyStructureUs = [
            "app-us-foster-care",
            "app-us-kinship-care",
            "other",
            "app-us-group-home",
            "app-us-single-parent-family",
            "app-us-two-parent-family",
            "app-us-guardian"
        ];
        $familyStructureCanada = [
            "app-ca-kinship-care",
            "app-ca-blended-family",
            "app-ca-foster-care",
            "app-ca-group-home",
            "app-ca-single-parent-family",
            "other",
            "app-ca-two-parent-family",
            "app-ca-guardian"
        ];
        $youthServedUs = [
            "app-us-college-post-secondary-student",
            "app-us-academically-at-risk",
            "app-us-gang-involved",
            "app-us-gifted-talented-academic-achiever",
            "app-us-foster-residential-or-kinship-care",
            "app-us-physical-disabilities-special-care-needs",
            "app-us-incarcerated-parent",
            "app-us-opportunity-youth",
            "app-us-low-income",
            "app-us-recent-immigrant-refugee",
            "app-us-single-parent-household",
            "app-us-youth-with-disabilities",
            "app-us-gang-at-risk",
            "app-us-first-generation-college",
            "app-us-adjudicated-court-involved",
            "app-us-general-youth-population",
            "app-us-homeless-runaway",
            "app-us-lgbtq-youth",
            "app-us-pregnant-parenting",
            "app-us-parent-involved-in-military",
            "app-us-mental-health-issues",
            "app-us-school-drop-out",
            "app-us-special-education",
            "other"
        ];
        $youthServedCanada = [
            "app-ca-academically-at-risk",
            "app-ca-gang-involved-or-gang-at-risk",
            "app-ca-low-income",
            "app-ca-recent-immigrant-refugee",
            "app-ca-emancipated",
            "app-ca-lgbtq-youth",
            "app-ca-pregnant-parenting",
            "other",
            "app-ca-college-post-secondary-student",
            "app-ca-foster-residential-or-kinship-care",
            "app-ca-incarcerated-parent",
            "app-ca-gifted-talented-academic-achiever",
            "app-ca-neet-opportunity-youth",
            "app-ca-physical-disabilities-special-care-needs",
            "app-ca-adjudicated-court-involved",
            "app-ca-single-parent-household",
            "app-ca-general-youth-population",
            "app-ca-homeless-living-in-a-shelter",
            "app-ca-mental-health-needs",
            "app-ca-parent-involved-in-military",
            "app-ca-special-education",
            "app-ca-school-drop-out"
        ];
        $mentorGendersUs = [
            "app-us-male",
            "app-us-non-binary",
            "app-us-two-spirit",
            "app-us-female",
            "app-us-genderqueer",
            "other"
        ];
        $mentorGendersCanada = [
            "app-ca-male",
            "app-ca-non-binary",
            "app-ca-female",
            "app-ca-two-spirit",
            "app-ca-genderqueer",
            "app-ca-transgender",
            "other"
        ];
        $mentorAgesUs = [
            "app-us-age-under-18",
            "app-us-age-35-49",
            "other",
            "app-us-age-50-65",
            "app-us-age-18-24",
            "app-us-age-25-34",
            "app-us-age-over-65"
        ];
        $mentorAgesCanada = [
            "other",
            "app-ca-age-35-49",
            "app-ca-age-under-18",
            "app-ca-age-50-65",
            "app-ca-age-18-24",
            "app-ca-age-25-34",
            "app-ca-age-over-65"
        ];
        $typesOfMentoring = [
            "app-type-of-mentoring-1-to-1",
            "app-type-of-mentoring-team",
            "other",
            "app-type-of-mentoring-group",
            "app-type-of-mentoring-peer",
            "app-type-of-mentoring-school",
            "app-type-of-mentoring-e-mentoring"
        ];

        $params = new ProgramParams();
        $params->field_types_of_mentoring = $typesOfMentoring;
        $params->field_types_of_mentoring_other = "other mentoring types";
        $params->field_program_operated_through = $programOperatedCanada;
        $params->field_program_operated_other = "other operation location";
        $params->field_facebook = "programFacebook";
        $params->field_twitter = "programTwitter";
        $params->field_website = "programWebsite";
        $params->field_instagram = "programInstagram";
        $params->field_focus_area = "other";
        $params->field_focus_area_other = "other focus area";
        $params->field_primary_meeting_location = "other";
        $params->field_primary_meeting_loc_other = "other meeting location";
        $params->field_program_how_are_meetings_s = [
            "other",
            "app-set-by-admin",
            "app-set-by-participants"
        ];
        $params->field_program_how_other = "other meeting schedules";
        $params->field_program_genders_served = $gendersServedCanada;
        $params->field_program_genders_other = "other genders";
        $params->field_program_ages_served = $agesServedCanada;
        $params->field_program_ages_other = "other ages";
        $params->field_program_family_served = $familyStructureCanada;
        $params->field_program_family_other = "other family";
        $params->field_program_youth_served = $youthServedCanada;
        $params->field_program_youth_other = "other youth";
        $params->field_program_youth_per_year = "50";
        $params->field_program_mentees_waiting_li = "40";
        $params->field_program_gender_mentor_targ = $mentorGendersCanada;
        $params->field_program_gender_mentor_oth = "other mentor genders";
        $params->field_program_ages_mentor_target = $mentorAgesCanada;
        $params->field_program_age_mentor_other = "other mentor ages";
        $params->field_program_mentor_month_commi = "app-3-months";
        $params->field_program_mentor_month_other = "";
        $params->field_program_mentor_freq_commit = "app-frequency-bi-weekly";
        $params->field_program_mentor_freq_other = "";
        $params->field_program_mentor_hour_commit = "app-2-3-hours";
        $params->field_program_mentor_hour_other = "";
        $params->field_program_screens_mentors = "";
        $params->field_program_screens_mentors_ho = [];
        $params->field_program_screens_mentees = "";
        $params->field_program_screens_mentees_ho = [];
        $params->field_program_trains_mentors = "";
        $params->field_program_trains_mentors_how = [];
        $params->field_program_must_train_mentors = "";
        $params->field_program_trains_mentees = "";
        $params->field_program_trains_mentees_how = [];
        $params->field_program_must_train_mentees = "";
        $params->field_program_matches_how = [];
        $params->field_program_matches_explain = "";
        $params->field_program_ongoing_support = "";
        $params->field_program_beginning_and_end = "";
        $params->field_program_has_specific_goals = "";
        $params->field_program_which_goals = [];
        $params->field_program_which_goals_other = "";
        $params->field_feedback = "";
        $params->field_program_accepting = [
            "app-program-accepting-mentees"
        ];
        $params->field_ns_bg_check = "app-background-check-type-peer";
        $params->field_ns_bg_check_types = [];
        $params->field_ns_bg_fingerprint_type = "";
        $params->field_ns_bg_name_type = "";
        $params->field_ns_bg_peer_type = "app-no";
        $params->field_ns_bg_other_types = [];
        $params->field_ns_training = "app-no";
        $params->field_program_grades_served = [
            "app-grade-k-5",
            "app-grade-6-8",
            "app-grade-9-12"
        ];
        $params->field_display_title = "Alternate unit test program";
        $params->field_description = "program description";
        $params->field_mentor_role_description = "mentor";
        $params->field_ns_training_description = "";
        $params->title = "Alternate unit test program";
        if (self::getCountry() == 'us') {
            $params->field_program_operated_through = $programOperatedUs;
            $params->field_program_genders_served = $gendersServedUs;
            $params->field_program_ages_served = $agesServedUs;
            $params->field_program_family_served = $familyStructureUs;
            $params->field_program_youth_served = $youthServedUs;
            $params->field_program_gender_mentor_targ = $mentorGendersUs;
            $params->field_program_ages_mentor_target = $mentorAgesUs;
        }
        return $params;
    }

    public static function getAlternateAdditionalParams()
    {
        $email = getenv('EMAIL') ?: 'program@example.com';
        $params = new ProgramAdditionalParams();
        $params->first_name = "John";
        $params->last_name = "Smith";
        $params->position = "CEO";
        $params->phone = "1234567";
        $params->altPhone = "9999999";
        $params->email = $email;
        $params->delivery->community = false;
        $params->delivery->siteBased = true;
        $params->delivery->eMentoring = true;
        $params->delivery->nationWideEMentoring = false;
        $params->delivery->siteBasedLocations = self::getLocations();
        $params->delivery->eMentoringLocations = self::getLocations();
        $params->title->en = "Alternate Unit Test Program";
        $params->programDescription->en = "program description";
        $params->mentorDescription->en = "mentor description";
        $params->trainingDescription->en = "this is the training description";
        $params->adaSetting = false;
        $params->nqmsSetting = false;
        $params->source = "Mentor Connector";
        if (self::getCountry() == 'ca') {
            $params->title->fr = "French Program Title";
            $params->programDescription->fr = "description de program";
            $params->mentorDescription->fr = "description de menteur";
            $params->trainingDescription->fr = "description de entrainment de menteur";
        }
        return $params;
    }

    public static function getLocations()
    {
        return [
            [
                "address_components" => [
                    [
                        "long_name" => "02150",
                        "short_name" => "02150",
                        "types" => [
                            "postal_code"
                        ]
                    ],
                    [
                        "long_name" => "Chelsea",
                        "short_name" => "Chelsea",
                        "types" => [
                            "locality",
                            "political"
                        ]
                    ],
                    [
                        "long_name" => "Suffolk County",
                        "short_name" => "Suffolk County",
                        "types" => [
                            "administrative_area_level_2",
                            "political"
                        ]
                    ],
                    [
                        "long_name" => "Massachusetts",
                        "short_name" => "MA",
                        "types" => [
                            "administrative_area_level_1",
                            "political"
                        ]
                    ],
                    [
                        "long_name" => "United States",
                        "short_name" => "US",
                        "types" => [
                            "country",
                            "political"
                        ]
                    ]
                ],
                "adr_address" => "<span class=\"locality\">Chelsea</span>, <span class=\"region\">MA</span> <span class=\"postal-code\">02150</span>, <span class=\"country-name\">USA</span>",
                "formatted_address" => "Chelsea, MA 02150, USA",
                "geometry" => [
                    "location" => [
                        "lat" => 42.4000656,
                        "lng" => -71.0319478
                    ],
                    "viewport" => [
                        "south" => 42.38320900250412,
                        "west" => -71.05740397699788,
                        "north" => 42.41422593445861,
                        "east" => -71.00758899435277
                    ]
                ],
                "icon" => "https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/geocode-71.png",
                "icon_background_color" => "#7B9EB0",
                "icon_mask_base_uri" => "https://maps.gstatic.com/mapfiles/place_api/icons/v2/generic_pinlet",
                "name" => "02150",
                "place_id" => "ChIJBxHiZrJx44kRo0DsrmIYHAM",
                "reference" => "ChIJBxHiZrJx44kRo0DsrmIYHAM",
                "types" => [
                    "postal_code"
                ],
                "url" => "https://maps.google.com/?q=02150&ftid=0x89e371b266e21107:0x31c1862aeec40a3",
                "utc_offset" => -240,
                "vicinity" => "Chelsea",
                "html_attributions" => [],
                "utc_offset_minutes" => -240
            ],
            [
                "address_components" => [
                    [
                        "long_name" => "02116",
                        "short_name" => "02116",
                        "types" => [
                            "postal_code"
                        ]
                    ],
                    [
                        "long_name" => "Boston",
                        "short_name" => "Boston",
                        "types" => [
                            "locality",
                            "political"
                        ]
                    ],
                    [
                        "long_name" => "Suffolk County",
                        "short_name" => "Suffolk County",
                        "types" => [
                            "administrative_area_level_2",
                            "political"
                        ]
                    ],
                    [
                        "long_name" => "Massachusetts",
                        "short_name" => "MA",
                        "types" => [
                            "administrative_area_level_1",
                            "political"
                        ]
                    ],
                    [
                        "long_name" => "United States",
                        "short_name" => "US",
                        "types" => [
                            "country",
                            "political"
                        ]
                    ]
                ],
                "adr_address" => "<span class=\"locality\">Boston</span>, <span class=\"region\">MA</span> <span class=\"postal-code\">02116</span>, <span class=\"country-name\">USA</span>",
                "formatted_address" => "Boston, MA 02116, USA",
                "geometry" => [
                    "location" => [
                        "lat" => 42.353068,
                        "lng" => -71.0765188
                    ],
                    "viewport" => [
                        "south" => 42.31601603236506,
                        "west" => -71.1017700596542,
                        "north" => 42.35801802455438,
                        "east" => -71.06257502459124
                    ]
                ],
                "icon" => "https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/geocode-71.png",
                "icon_background_color" => "#7B9EB0",
                "icon_mask_base_uri" => "https://maps.gstatic.com/mapfiles/place_api/icons/v2/generic_pinlet",
                "name" => "02116",
                "place_id" => "ChIJxbyObAx644kR5j-mO84Tats",
                "reference" => "ChIJxbyObAx644kR5j-mO84Tats",
                "types" => [
                    "postal_code"
                ],
                "url" => "https://maps.google.com/?q=02116&ftid=0x89e37a0c6c8ebcc5:0xdb6a13ce3ba63fe6",
                "utc_offset" => -240,
                "vicinity" => "Boston",
                "html_attributions" => [],
                "utc_offset_minutes" => -240
            ]
        ];
    }

    public static function getContents($alternateParams, $includeOrganization, $frenchOnly = false)
    {
        $attributes = $alternateParams ? self::getAlternateParams() : self::getParams();
        $contents = new RequestContents();
        $contents->nodes->en = new InnerNode('programs', $attributes);
        $contents->nodes->en->relationships = self::getOrganizationData($includeOrganization);
        $contents->additional = $alternateParams ? self::getAlternateAdditionalParams() : self::getAdditionalParams();
        if ($frenchOnly) {
            $contents = self::transformToFrenchOnlyProgram($contents, $attributes);
        }
        return $contents;
    }

    public static function transformToFrenchOnlyProgram($contents, $attributes)
    {
        $frenchName = 'Nom en Francais';
        $engAttributes = $contents->nodes->en->attributes;
        $freProgramDescription = "Description de program en Francais";
        $freMentorDescription = "Description de role de mentor en Francais";
        $engAttributes->field_display_title = $frenchName;
        $engAttributes->field_description = '';
        $engAttributes->field_mentor_role_description = '';
        $engAttributes->title = $frenchName;
        $contents->nodes->en->attributes = $engAttributes;
        $contents->nodes->fr = new InnerNode('programs', $attributes);
        $frenchAttributes = $contents->nodes->fr->attributes;
        unset($frenchAttributes->field_display_title);
        unset($frenchAttributes->field_description);
        unset($frenchAttributes->field_mentor_role_description);
        unset($frenchAttributes->title);
        $contents->nodes->fr->attributes = $frenchAttributes;
        $additional = $contents->additional;
        $additional->title->fr =$frenchName;
        $additional->title->en = $frenchName;
        $additional->programDescription->fr = $freProgramDescription;
        $additional->programDescription->en = '';
        $additional->mentorDescription->fr = $freMentorDescription;
        $additional->mentorDescription->en = '';
        $additional->trainingDescription->fr = '';
        $contents->additional = $additional;
        $contents->uilang = 'fr';
        return $contents;
    }

    public static function createProgram($alternateParams = false, $includeOrganization = false, $frenchOnly = false)
    {
        $client = new Client(['base_uri' => 'http://localhost/a']);
        $contents = self::getContents($alternateParams, $includeOrganization, $frenchOnly);
        $innerData = self::getDataObject();
        $innerData->contents = $contents;
        $data = $innerData->transformToDataArrayIncludingPhoto();
        $data = [RequestOptions::MULTIPART => $data];
        $response = $client->request('POST', 'a/app/program', $data);
        return json_decode($response->getBody());
    }

    public static function getOrganizationData($sendBackFullData)
    {
        $data =[];
        if ($sendBackFullData) {
            $newOrganization = new OrganizationControllerTest();
            $organizationId = $newOrganization->testCreateOrganization();
            $data = [
                "type" => "node--organization",
                "id" => $organizationId
            ];
        }
        return [
            "field_administrators" => [
                "data" => []
            ],
            "field_organization_entity" => [
                "data" => $data
            ]
        ];
    }

    public static function getDataObject($frenchOnly = false)
    {
        $innerData = new GuzzleMultipartObject();
        $innerData->contents->nodes = new RequestNode();
        $innerData->contents->nodes->en = new InnerNode("programs");
        $innerData->contents->nodes->en->attributes = self::getParams();
        $innerData->contents->nodes->fr = new InnerNode("programs");
        $innerData->contents->nodes->fr->attributes = self::getParams();
        $innerData->contents->additional = self::getAdditionalParams();
        return $innerData;
    }

    public static function getProgramSettings()
    {
        $settings = new ProgramBBBSCSettings();
        $settings->bbbsc = false;
        $settings->bbbscInquiryProgramOfInterest = "program_of_intrest";
        $settings->bbbscSystemUser = "system_user";
        $settings->bbbscProgramType = "program_type";
        return $settings;
    }

    private function getCountry()
    {
        return $_ENV['COUNTRY'] == null ? 'ca' : $_ENV['COUNTRY'];
    }
}
