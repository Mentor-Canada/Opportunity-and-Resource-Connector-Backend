<?php

namespace rest\filter;

class SaveFilterParams
{
    public ?string $id;
    public string $title;
    public string $type;
    public string $data;

    public function __construct()
    {
        $this->id = null;
    }
}
