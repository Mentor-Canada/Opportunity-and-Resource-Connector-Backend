<?php

namespace rest\filter;

class InquiryFilterParams
{
    public array $inquiryFilterFields;

    public function __construct()
    {
        $this->inquiryFilterFields = [];
        $this->inquiryFilterFields["programFilter"] = '';
        $this->inquiryFilterFields["inquiries.programId"] = '';
        $this->inquiryFilterFields["inquiries.role"] = '';
        $this->inquiryFilterFields["inquiries.status"] = '';
        $this->inquiryFilterFields["inquiries.how"] = '';
        $this->inquiryFilterFields["inquiries.howOther"] = '';
        $this->inquiryFilterFields["inquiries.firstName"] = '';
        $this->inquiryFilterFields["inquiries.lastName"] = '';
        $this->inquiryFilterFields["inquiries.email"] = '';
        $this->inquiryFilterFields["inquiries.phone"] = '';
        $this->inquiryFilterFields["inquiries.voice"] = '';
        $this->inquiryFilterFields["inquiries.sms"] = '';
    }

}
