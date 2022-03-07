<?php

namespace rest\program;

use rest\organization\LocalizedString;

class ProgramAdditionalParams
{
    public string $first_name;
    public string $last_name;
    public string $position;
    public string $phone;
    public string $altPhone;
    public string $email;
    public ProgramDelivery $delivery;
    public LocalizedString $title;
    public LocalizedString $programDescription;
    public LocalizedString $mentorDescription;
    public LocalizedString $trainingDescription;
    public bool $nqmsSetting;
    public bool $adaSetting;
    public string $source;

    public function __construct()
    {
        $this->delivery = new ProgramDelivery();
        $this->title = new LocalizedString();
        $this->programDescription = new LocalizedString();
        $this->mentorDescription = new LocalizedString();
        $this->trainingDescription = new LocalizedString();
    }
}
