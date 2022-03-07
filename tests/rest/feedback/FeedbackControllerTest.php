<?php

namespace rest\feedback;

use rest\Request;
use rest\RestTestCase;

class FeedbackControllerTest extends RestTestCase
{
    private $location = "/en/a/test";
    private $email = "testEmail@email.com";
    private $message = "Unit test feedback message";

    public function testPost()
    {
        $params = new FeedbackParams();
        $params->location = $this->location;
        $params->email = $this->email;
        $params->message = $this->message;
        $response = (new Request())
      ->uri('a/app/feedback')
      ->data($params)
      ->execute();
        $body = json_decode($response->getBody());
        $status = $body->data->status;
        $this->assertEquals("success", $status);
        return $status;
    }

    public function testGet()
    {
        $response = (new Request())
      ->uri('a/app/feedback')
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        $latestEntry = $body->data[0];
        $this->assertEquals($latestEntry->attributes->field_url, $this->location);
        $this->assertEquals($latestEntry->attributes->field_email, $this->email);
        $this->assertEquals($latestEntry->attributes->field_text, $this->message);
    }
}
