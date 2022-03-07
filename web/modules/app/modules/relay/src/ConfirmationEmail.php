<?php

namespace Drupal\relay;

use Drupal\app_inquiry\Inquiry;

class ConfirmationEmail
{
    public Inquiry $inquiry;
    public string $langcode;
    public string $phoneLabel;

    public function setLangcode($langcode)
    {
        $this->langcode = $langcode;
        $labels = [];
        if ($this->inquiry->voice) {
            $labels[] = $this->t('app-voice');
        }
        if ($this->inquiry->sms) {
            $labels[] = $this->t('app-sms');
        }
        $this->phoneLabel = implode(", ", $labels);
    }

    private function t($s)
    {
        return t($s, [], ["langcode" => $this->langcode]);
    }
}
