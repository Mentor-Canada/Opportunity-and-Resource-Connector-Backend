<?php

namespace Drupal\app_inquiry;

class CreateInquiryRequestAdapter
{
    public $searchId;
    public $programId;

    public $firstName;
    public $lastName;
    public $email;
    public $phone;
    public $voice;
    public $sms;
    public $role;
    public $status;
    public $created;
    public $how;
    public $howOther;

    public $programTitle;
    public $uilang;
    public $uuid;

    public function __construct($content)
    {
        $this->searchId = $content->data->attributes->searchId;
        $this->programId = $content->data->attributes->programId;
        $this->email = $content->data->attributes->{ApplicationFields::email};
        $this->lastName = $content->data->attributes->{ApplicationFields::last_name};
        $this->firstName = $content->data->attributes->{ApplicationFields::first_name};
        $this->voice = $content->data->attributes->call ? 1 : 0;
        $this->sms = $content->data->attributes->{ApplicationFields::sms} ? 1 : 0;
        $this->phone = $content->data->attributes->{ApplicationFields::phone};
        $this->role = $content->data->attributes->{ApplicationFields::role};
        $this->how = $content->data->attributes->{ApplicationFields::how_did_you_hear_about_us};
        $this->howOther = $content->data->attributes->{ApplicationFields::how_did_you_hear_about_us_other};
        $this->created = \Drupal::time()->getRequestTime();

        $uilang = $content->uilang;
        if (!$uilang) {
            $uilang = 'en';
        }
        $this->uilang = $uilang;

        $q = "SELECT programs.title FROM node LEFT JOIN programs ON entity_id = node.nid WHERE node.nid = :id";
        $result = \Drupal::database()->query($q, [":id" => $this->programId])->fetchCol(0);
        $result = json_decode(current($result));
        $programTitle = $result->$uilang;
        if (empty($programTitle) && $uilang != 'en') {
            $programTitle = $result->en;
        }
        $this->programTitle = $programTitle;
    }
}
