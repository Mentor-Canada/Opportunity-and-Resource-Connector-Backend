<?php

namespace rest\request_objects;

class LangCode
{
    public string $selectedLanguage;

    public function __construct()
    {
        $this->selectedLanguage = 'en';
    }

    public function setToEnglish()
    {
        $this->selectedLanguage = 'en';
        return $this;
    }

    public function setToFrench()
    {
        $this->selectedLanguage = 'fr';
        return $this;
    }
}
