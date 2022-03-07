<?php

namespace rest\request_objects;

class InnerNode
{
    public string $type;
    public $attributes;
    public $relationships = [];

    public function __construct($type, $attributes = [])
    {
        $this->type = $type;
        $this->attributes = $attributes;
    }
}
