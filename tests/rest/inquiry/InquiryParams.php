<?php

namespace rest\inquiry;

class InquiryParams
{
    public string $role;
    public string $how;
    public string $firstName;
    public string $lastName;
    public string $email;
    public string $phone;
    public bool $call;
    public bool $sms;
    public string $searchId;
    public ?string $programId;
    public ?string $howOther;
}
