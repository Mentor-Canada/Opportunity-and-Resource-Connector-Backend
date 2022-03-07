<?php

namespace Drupal\app_account;

class Account
{
    public string $id;
    public string $mail;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $globalAdministrator;
    public array $affiliates = [];
    public array $organizations = [];
    public array $programs = [];
    public int $created;
}
