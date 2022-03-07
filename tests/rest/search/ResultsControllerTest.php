<?php

namespace rest\search;

use rest\approval\ApprovalUtils;
use rest\program\ProgramBuilder;
use rest\Request;
use rest\RestTestCase;

class ResultsControllerTest extends RestTestCase
{
    public function testPost()
    {
        $programBody = ProgramBuilder::createProgram();
        $programId = $programBody->data->id;
        ApprovalUtils::changeApprovalStatus($programId);
        $searchController = new SearchControllerTest();
        $searchId = $searchController->testPost();
        $response = (new Request())
      ->uri("a/app/search/results/list/{$searchId}?page%5Boffset%5D=0&page%5Blimit%5D=20")
      ->execute();
        $body = json_decode($response->getBody());
        $results = $body->data;
        $this->assertNotEmpty($results, "There were 0 results returned from search");
        return count($results);
    }
}
