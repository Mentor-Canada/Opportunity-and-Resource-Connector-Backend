<?php

namespace rest\signin;

class UserBuilder
{
    public string $firstName;
    public string $lastName;
    public string $email;
    public string $password;

    public function build()
    {
        return [
            "field_first_name" => ["value" => $this->firstName],
            "field_last_name" => ["value" => $this->lastName],
            "field_global_administrator" => ["value" => "0"],
            "mail" => ["value" => $this->email],
            "name" => ["value" => $this->email],
            "pass" => ["value" => $this->password],
            "roles" => ["target_id" => "authenticated"],
            "status" => ["value" => 1],
        ];
    }
}
