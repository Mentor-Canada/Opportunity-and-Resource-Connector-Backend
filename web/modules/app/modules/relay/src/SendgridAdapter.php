<?php

namespace Drupal\app_relay;

class SendgridAdapter
{
    public string $inquiryId;

    public string $toEmail;
    public string $fromEmail;

    public string $subject;
    public string $text;
    public string $html;
    public string $from;

    public array $files;

    public function __construct()
    {
        $envelope = json_decode($_REQUEST['envelope']);
        $this->toEmail = $envelope->to[0];
        $this->fromEmail = $envelope->from;

        $toComponents = explode("@", $this->toEmail);
        $this->inquiryId = $toComponents[0];

        $this->subject = $_REQUEST['subject'];
        $this->text = $_REQUEST['text'];
        $this->html = $_REQUEST['html'];
        $this->fromName = preg_replace("/ <.*>/", "", $_REQUEST['from']);
        $this->files = $_FILES;
    }
}
