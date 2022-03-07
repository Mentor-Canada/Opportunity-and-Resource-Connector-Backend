<?php

namespace Drupal\app_inquiry;

class Inquiry
{
    public ?string $id = null;
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $email = null;
    public ?string $voice = null;
    public ?string $sms = null;
    public ?string $phone = null;
    public ?string $status = null;
    public ?string $role = null;
    public ?string $how = null;
    public ?string $howOther = null;
    public ?bool $filter = false;
    public $created;

    public $searchId;
    public $programId;

    public function __construct($uuid)
    {
        $rows = \Drupal::database()->query("SELECT inquiries.*, programs.title->>'$.en' as programTitle FROM inquiries LEFT JOIN programs ON inquiries.programId = programs.entity_id WHERE uuid = :uuid", [
            ":uuid" => $uuid
        ])->fetchAll();
        if (!count($rows)) {
            \Drupal::logger('relay')->error("Relay error: " . json_encode($_REQUEST));
            throw new \Exception("Invalid inquiry $uuid");
        }
        $row = current($rows);

        $this->id = $row->{ApplicationFields::uuid};
        $this->firstName = $row->{ApplicationFields::first_name};
        $this->lastName = $row->{ApplicationFields::last_name};
        $this->email = $row->{ApplicationFields::email};
        $this->voice = $row->{ApplicationFields::call};
        $this->sms = $row->{ApplicationFields::sms};
        $this->phone = $row->{ApplicationFields::phone};
        $this->status = $row->{ApplicationFields::status};
        $this->role = $row->{ApplicationFields::role};
        $this->how = $row->{ApplicationFields::how_did_you_hear_about_us};
        $this->howOther = $row->{ApplicationFields::how_did_you_hear_about_us_other};
        $this->created = $row->{ApplicationFields::created};
        $this->searchId = $row->searchId;
        $this->programId = $row->programId;
        $this->programTitle = $row->programTitle;
    }

    public function serialize($langcode): array
    {
        $data = [];
        $data['firstName'] = $this->firstName;
        $data['lastName'] = $this->lastName;
        $data['relayEmail'] = "{$this->id}@{$_ENV['RELAY_HOST']}";
        $data['email'] = $this->status == "app-contacted" ? $this->email : "";
        $data['phone'] = $this->status == "app-contacted" ? $this->phone : "";
        $data['voice'] = $this->voice;
        $data['sms'] = $this->sms;
        $data['status'] = $this->status;
        $data['role'] = ucfirst($this->role);
        $data['how'] = $this->how == 'other' ? t('app-other', [], ['langcode' => $langcode]) : $this->how;
        $data['howOther'] = $this->howOther;
        $data['created'] = $this->created;
        $data['programTitle'] = $this->programTitle;
        return $data;
    }
}
