<?php

namespace Drupal\app\Views;

class OrganizationReadyEmailView
{
    public $langcode;
    public $firstName;
    public $client_url;
    public $organizationTitle;
    public $addProgramsLink;

    public function __construct()
    {
        $this->client_url = $_ENV['CLIENT_URL'];
    }
}
