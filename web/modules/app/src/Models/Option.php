<?php

namespace Drupal\app\Models;

class Option
{
    public string $name;
    public string $value;

    public function __construct($value)
    {
        $this->name = $value;
        $this->value = $value;
    }
}
