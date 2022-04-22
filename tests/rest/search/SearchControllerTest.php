<?php

namespace rest\search;

use rest\Request;
use rest\RestTestCase;

class SearchControllerTest extends RestTestCase
{
    public function testPost()
    {
        $builder = new SearchBuilder();
        $response = $builder->execute();
        $body = json_decode($response->getBody());
        $id = $body->data->id;
        $this->assertNotNull($id);
        return $id;
    }

    /**
     * @depends testPost
     */
    public function testGet($id, $params = null)
    {
        $response = (new Request())
      ->uri("a/app/search/{$id}")
      ->method('GET')
      ->execute();
        $body = json_decode($response->getBody());
        $attributes = $body->data->attributes;
        if (!$params) {
            $params = SearchUtils::params();
        }
        $this->assertEquals($params->field_youth_age, json_decode($attributes->age)[0]);
        $this->assertEquals($params->field_youth_grade, json_decode($attributes->grade)[0]);
        $this->assertEquals($params->field_focus, json_decode($attributes->focus)[0]);
        $this->assertEquals($params->field_type_of_mentoring, json_decode($attributes->typeOfMentoring)[0]);
        $this->assertEquals($params->field_youth, json_decode($attributes->youth)[0]);
        $this->assertEquals($params->field_distance, $attributes->distance);
    }

    /**
     * @depends testPost
     */
    public function testPatch($id)
    {
        $params = SearchUtils::params();
        $params->field_focus = "app-us-program-focus-academics";
        $searchData = new SearchOuterDataObject();
        $searchData->data = new SearchInnerDataObject();
        $searchData->data->attributes = $params;
        $data = (array)$searchData;
        $response = (new Request())
      ->uri("a/app/search/{$id}")
      ->method('PATCH')
      ->data($data)
      ->execute();
        $this->testGet($id, $params);
    }
}
