<?php

namespace rest\search;

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
}
