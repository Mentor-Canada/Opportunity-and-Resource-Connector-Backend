<?php

namespace rest\request_objects;

class RequestContents
{
    public RequestNode $nodes;
    public $additional;
    public $uilang;

    public function __construct()
    {
        $this->nodes = new RequestNode();
        $this->additional = [];
        $this->uilang = "en";
    }
}
