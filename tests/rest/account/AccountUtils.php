<?php

namespace rest\account;

use rest\Request;
use rest\RestTestCase;
use rest\Session;

class AccountUtils extends RestTestCase
{

    public static function createAccount($user)
    {
        $globalAdminSession = new Session();
        $globalAdminSession->signIn();
        (new Request())
            ->uri("a/user?_format=json")
            ->data($user)
            ->session($globalAdminSession)
            ->expectedResponseCode([422, 201])
            ->execute();
    }
}
