<?php

namespace rest\organization;

class OrganizationParams
{
    public LocalizedString $title;
    public string $contactFirstName;
    public string $contactLastName;
    public string $contactEmail;
    public array $location;
    public string $website;
    public string $contactPhone;
    public string $contactAlternatePhone;
    public string $legalName;
    public string $feedback;
    public string $type;
    public string $typeOther;
    public string $taxStatus;
    public string $taxStatusOther;
    public string $contactPosition;
    public string $contactPositionOther;
    public LocalizedString $description;
    public string $hasLocation = "yes";

    public function __construct()
    {
        $this->title = new LocalizedString();
        $this->description = new LocalizedString();
    }
}
