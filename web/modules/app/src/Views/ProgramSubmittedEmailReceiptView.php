<?php

namespace Drupal\app\Views;

class ProgramSubmittedEmailReceiptView
{
    public $firstName;
    public $lastName;
    public $programName;
    public $langcode;
    public $baseUrl;

    public $customTextA;
    public $customTextB;
    public $affiliateName;

    public function __construct()
    {
        $this->baseUrl = $GLOBALS['base_url'];
    }
}
