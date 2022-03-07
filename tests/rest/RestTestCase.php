<?php

namespace rest;

use PHPUnit\Framework\TestCase;

class RestTestCase extends TestCase
{
    protected function globalAdministratorSession(): Session
    {
        $session = new Session();
        $session->signIn();
        return $session;
    }

    protected function authenticatedSession(): Session
    {
        $this->createAuthenticatedUser();
        $session = new Session();
        $session->signIn("authenticated@example.com", "hello123");
        return $session;
    }

    protected function createAuthenticatedUser()
    {
        (new Request())
      ->uri("a/user?_format=json")
      ->data([
          "field_first_name" => ["value" => "John"],
          "field_last_name" => ["value" => "Smith"],
          "field_global_administrator" => ["value" => "0"],
          "mail" => ["value" => "authenticated@example.com"],
          "name" => ["value" => "authenticated@example.com"],
          "pass" => ["value" => "hello123"],
          "roles" => ["target_id" => "authenticated"],
          "status" => ["value" => 1],
      ])
      ->session($this->globalAdministratorSession())
      ->expectedResponseCode([422, 201])
      ->execute();
    }
}
