<?php

namespace rest\inquiry;

use rest\program\ProgramBuilder;
use rest\Request;
use rest\request_objects\InnerNode;
use rest\RestTestCase;

class InquiryUtils extends RestTestCase
{
    public static function getParams()
    {
        $email = getenv('EMAIL') ?: "inquiry-email@example.com";
        $params = new InquiryParams();
        $params->role = "mentor";
        $params->how = "app-us-hear-about-us-mentor-web-site";
        $params->firstName = "first_name";
        $params->lastName = "last_name";
        $params->email = $email;
        $params->phone = "1111231234";
        $params->call = true;
        $params->sms = true;
        $params->searchId = "0";
        $params->programId = null;
        $params->howOther = null;

        return $params;
    }

    public static function getAlternateParams()
    {
        $params = self::getParams();
        $params->role = 'mentee';
        $params->how = "other";
        $params->howOther = "googleSearch";
        $params->call = false;
        $params->sms = false;
        return $params;
    }

    public static function createInquiry($inquiryParams = null, $language = "en")
    {
        $program = ProgramBuilder::createProgram();
        $programId = $program->data->attributes->drupal_internal__nid;
        if (!$inquiryParams) {
            $inquiryParams = InquiryUtils::getParams();
        }
        $inquiryParams->programId = $programId;
        $innerData = (array)new InnerNode("node--application", $inquiryParams);
        $data = [
            "data" => $innerData,
            "uilang" => $language
        ];
        $response = (new Request())
      ->uri("a/app/inquiry")
      ->data($data)
      ->execute();
        return json_decode($response->getBody());
    }
}
