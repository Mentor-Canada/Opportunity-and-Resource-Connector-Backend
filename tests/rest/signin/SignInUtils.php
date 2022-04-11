<?php

namespace rest\signin;

class SignInUtils
{
    public static function getUserParams()
    {
        $params = new UserParams();
        $params->firstName = "John";
        $params->lastName = "Smith";
        $params->email = "authenticated@example.com";
        $params->password = "hello123";
        return $params;
    }
}
