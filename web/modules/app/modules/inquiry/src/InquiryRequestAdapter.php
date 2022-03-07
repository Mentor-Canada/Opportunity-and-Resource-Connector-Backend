<?php

namespace Drupal\app_inquiry;

use Drupal\app\RequestAdapterBase;
use Symfony\Component\HttpFoundation\Request;

class InquiryRequestAdapter extends RequestAdapterBase
{
    public $request;
    public $filter;

    public $sortField;
    public $sortDirection;
    public $createdStartDate;
    public $createdEndDate;
    public $limit;
    public $offset;
    public $programId;
    public $role;
    public $status;
    public $lead;
    public $leadOther;
    public $firstName;
    public $lastName;
    public $email;
    public $phone;
    public $voice;
    public $sms;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->filter = $this->request->query->get('filter');
        $this->setSort();
        $this->setDate();
        $this->limit = $this->request->query->get('page')['limit'];
        $this->offset = $this->request->query->get('page')['offset'];
        $this->programId = $this->filter['program'];
        $this->role = $this->filter['role'];
        $this->status = $this->filter['status'];
        $this->lead = $this->filter['lead'];
        $this->leadOther = $this->filter['leadOther'];
        $this->firstName = $this->filter['firstName'];
        $this->lastName = $this->filter['lastName'];
        $this->email = $this->filter['email'];
        $this->phone = $this->filter['phone'];
        $this->voice = $this->filter['voice'];
        $this->sms = $this->filter['sms'];
    }

    protected function setSort()
    {
        $field = $this->request->query->get('sort');
        $direction = 'ASC';
        if (mb_substr($field, 0, 1) == '-') {
            $field = substr($field, 1);
            $direction = 'DESC';
        }
        $this->sortField = $field;
        $this->sortDirection = $direction;
    }

    protected function setDate()
    {
        if ($startDateFilter = $this->filter['start-date-filter']) {
            $this->createdStartDate = $startDateFilter['condition']['value'];
        }
        if ($endDateFilter = $this->filter['end-date-filter']) {
            $this->createdEndDate = $endDateFilter['condition']['value'];
        }
    }

    public function getFilter($field)
    {
        $value = $this->filter[$field];
        if ($value) {
            $decoded = json_decode($value);
            $error = json_last_error();
        }
        return $error ? $value : $decoded;
    }
}
