<?php

namespace rest\search;

use rest\RestTestCase;

class SearchCSVTest extends RestTestCase
{

    public function testGetSearchCsv()
    {
        $csvContent = SearchUtils::downloadSearchCSV();
        $csvRows = explode("\r\n", $csvContent);
        $this->assertNotEmpty($csvRows, "Failed to download search CSV rows");
    }

}
