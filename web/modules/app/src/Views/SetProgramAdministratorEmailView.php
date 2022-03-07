<?php

namespace Drupal\app\Views;

use Drupal\user\Entity\User;

class SetProgramAdministratorEmailView
{
    public $otp;
    public $langcode;
    public User $account;
    public $client_url;
    public $programTitle;
    public $programLink;
    public $accountIsNew;
    public $approved;

    public function __construct()
    {
        $this->client_url = $_ENV['CLIENT_URL'];
    }
}
