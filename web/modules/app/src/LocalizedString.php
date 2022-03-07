<?php

namespace Drupal\app;

class LocalizedString
{
    public ?string $en;
    public ?string $fr;

    public function get($lang)
    {
        $result = $this->$lang;
        if (empty($result) && $lang != "en") {
            return $this->en;
        }
        return $this->$lang;
    }
}
