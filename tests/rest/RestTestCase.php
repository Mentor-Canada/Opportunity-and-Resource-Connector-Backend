<?php

namespace rest;

use PHPUnit\Framework\TestCase;
use rest\signin\SignInUtils;
use rest\signin\UserBuilder;
use rest\signin\UserParams;

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

    protected function createAuthenticatedUser(UserParams $userParams = null)
    {
        if (!$userParams) {
            $userParams = SignInUtils::getUserParams();
        }
        $builder = new UserBuilder();
        $builder->firstName = $userParams->firstName;
        $builder->lastName = $userParams->lastName;
        $builder->email = $userParams->email;
        $builder->password = $userParams->password;
        $payload = $builder->build();
        (new Request())
            ->uri("a/user?_format=json")
            ->data($payload)
            ->session($this->globalAdministratorSession())
            ->expectedResponseCode([422, 201])
            ->execute();
    }
}
