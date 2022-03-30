<?php

namespace Drupal\app_account;

use Drupal\app\RequestAdapterBase;
use Symfony\Component\HttpFoundation\Request;

class AccountCollectionRequestAdapter extends RequestAdapterBase
{
    public ?string $mail = null;
    public ?string $accountType = null;
    public ?string $mentorCity = null;
    public ?string $firstName = null;
    public ?string $lastName = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if (!empty($this->filter['mail']['value'])) {
            $this->mail = $this->filter['mail']['value'];
        }
        if (!empty($this->filter['accountType'])) {
            $this->accountType = $this->filter['accountType'];
        }
        if (!empty($this->filter['mentorCity'])) {
            $this->mentorCity = $this->filter['mentorCity'];
        }
        if (!empty($this->filter['firstName'])) {
            $this->firstName = $this->filter['firstName'];
        }
        if (!empty($this->filter['lastName'])) {
            $this->lastName = $this->filter['lastName'];
        }
    }
}
