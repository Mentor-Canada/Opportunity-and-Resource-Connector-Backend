<?php

namespace Drupal\app\Views;

use Drupal\user\Entity\User;

class CompleteRegistrationEmailView
{
    public string $otp;
    public string $langcode = 'en';
    public User $account;
    public string $client_url;

    public function __construct()
    {
        $this->client_url = $_ENV['CLIENT_URL'] ?: "http://localhost:8080";
    }
}
