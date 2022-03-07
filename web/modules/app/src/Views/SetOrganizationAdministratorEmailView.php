<?php

namespace Drupal\app\Views;

use Drupal\user\Entity\User;

class SetOrganizationAdministratorEmailView
{
    public $otp;
    public $langcode;
    public User $account;
    public $client_url;
    public $organizationTitle;
    public $organizationLink;
    public $addProgramsLink;
    public $accountIsNew;

    public function __construct()
    {
        $this->client_url = $_ENV['CLIENT_URL'];
    }
}
