<?php

namespace rest\search;

use rest\Request;
use rest\request_objects\LangCode;
use rest\Session;

class SearchUtils
{
    public static function params()
    {
        $params = new SearchParams();

        $params->field_zip = "02150";
        $params->field_first_name = "Henry";
        $params->field_last_name = "Harper";
        $params->field_email = "example@example.com";
        $params->field_distance = "25";
        $params->field_type_of_mentoring = "all";
        $params->field_youth_age = "all";
        $params->field_youth = "all";
        $params->field_role = "mentor";
        $params->field_focus = "all";
        $params->field_partner_id = "1";
        $params->field_youth_grade = "all";
        $params->delivery = [
            "siteBased",
            "community",
            "eMentoring"
        ];

        return $params;
    }

    public static function downloadSearchCSV(LangCode $langCode = null)
    {
        if (!$langCode) {
            $langCode = new LangCode();
        }
        $language = $langCode->selectedLanguage;
        $globalAdminSession = new Session();
        $globalAdminSession->signIn();
        $response = (new Request())
            ->method("GET")
            ->uri("{$language}/a/app/search/csv")
            ->session($globalAdminSession)
            ->execute();
        ob_start();
        echo $response->getBody();
        return ob_get_clean();
    }
}
