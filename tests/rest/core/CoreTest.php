<?php

namespace rest\core;

use rest\Request;
use rest\RestTestCase;

class CoreTests extends RestTestCase
{
    public function testBootstrap()
    {
        $response = (new Request())
            ->uri("a/app/bootstrap")
            ->execute();
        $body = json_decode($response->getBody());

        $this->assertIsObject($body);
    }
}
