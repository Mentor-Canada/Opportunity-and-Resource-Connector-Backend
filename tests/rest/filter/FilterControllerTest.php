<?php

namespace rest\filter;

use rest\RestTestCase;

class FilterControllerTest extends RestTestCase
{
    public function testPostOrganizationFilter()
    {
        return $this->validateFilterPost(FilterUtils::getOrganizationFilterParams());
    }

    public function testPostProgramFilter()
    {
        return $this->validateFilterPost(FilterUtils::getProgramFilterParams());
    }

    public function testPostInquiryFilter()
    {
        return $this->validateFilterPost(FilterUtils::getInquiryFilterParams());
    }

    private function validateFilterPost($filterType)
    {
        $results = FilterUtils::createFilter($filterType);
        $status = $results['status'];
        $matchedFilter = $results['matchedFilter'];
        $expectedTitle = $results['filterParams']['title'];
        $expectedType = $results['filterParams']['type'];
        $actualTitle = $matchedFilter ? $results['matchedFilter']->title : 'wrong-title';
        $actualType = $matchedFilter ? $results['matchedFilter']->type : 'wrong-type';

        $this->assertEquals("success", $status, "server did not respond with 'success'");
        $this->assertNotNull($matchedFilter, "No filter matching the latest saved filter was found in retrieved filters, Filter did not save");
        $this->assertEquals($expectedTitle, $actualTitle, "The 'title' of the latest filter does not match saved filter");
        $this->assertEquals($expectedType, $actualType, "They 'type' property of the latest filter does not match saved filter");
        return $status;
    }
}
