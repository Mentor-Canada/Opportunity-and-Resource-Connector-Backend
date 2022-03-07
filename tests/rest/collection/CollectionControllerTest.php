<?php

namespace rest\collection;

use rest\feedback\FeedbackControllerTest;
use rest\inquiry\InquiryUtils;
use rest\organization\OrganizationControllerTest;
use rest\program\ProgramBuilder;
use rest\Request;
use rest\RestTestCase;
use rest\search\SearchControllerTest;

class CollectionControllerTest extends RestTestCase
{
    public function testOrganizationDuplicates()
    {
        $organizations = $this->getEntityCollection();
        if (!count($organizations)) {
            $newOrganization = new OrganizationControllerTest();
            $newOrganization->testCreateOrganization();
            $organizations = $this->getEntityCollection();
        }
        $this->checkDuplicates($organizations);
    }

    public function testProgramDuplicates()
    {
        $programs = $this->getEntityCollection(CollectionEntityTypes::$program);
        if (!count($programs)) {
            ProgramBuilder::createProgram();
            $programs = $this->getEntityCollection(CollectionEntityTypes::$program);
        }
        $this->checkDuplicates($programs);
    }

    public function testInquiryDuplicates()
    {
        $inquiries = $this->getEntityCollection(CollectionEntityTypes::$inquiry);
        if (!count($inquiries)) {
            InquiryUtils::createInquiry();
            $inquiries = $this->getEntityCollection(CollectionEntityTypes::$inquiry);
        }
        foreach ($inquiries as $inquiry) {
            $inquiry->id = $inquiry->attributes->uuid;
        }
        $this->checkDuplicates($inquiries);
    }

    public function testFeedbackDuplicates()
    {
        $feedback = $this->getEntityCollection(CollectionEntityTypes::$feedback);
        if (!count($feedback)) {
            $newFeedback = new FeedbackControllerTest();
            $newFeedback->testPost();
            $feedback = $this->getEntityCollection(CollectionEntityTypes::$feedback);
        }
        $this->checkDuplicates($feedback);
    }

    public function testSearchDuplicates()
    {
        $searches = $this->getEntityCollection(CollectionEntityTypes::$search);
        if (!count($searches)) {
            $newSearch = new SearchControllerTest();
            $newSearch->testPost();
            $searches = $this->getEntityCollection(CollectionEntityTypes::$search);
        }
        foreach ($searches as $search) {
            $search->id = $search->attributes->created . $search->attributes->email;
        }
        $this->checkDuplicates($searches);
    }

    public function testPartnerDuplicates()
    {
        $partners = $this->getEntityCollection(CollectionEntityTypes::$partner, 'node');
        $this->checkDuplicates($partners);
    }

    public function testAccountDuplicates()
    {
        $accounts = $this->getEntityCollection(CollectionEntityTypes::$accounts);
        $this->checkDuplicates($accounts);
    }

    private function checkDuplicates($entityCollection)
    {
        $entityIds = [];
        $duplicateFound = false;
        foreach ($entityCollection as $entity) {
            if (in_array($entity->id, $entityIds)) {
                $duplicateFound = true;
                break;
            }
            array_push($entityIds, $entity->id);
        }
        $this->assertNotEmpty($entityCollection, "A collection of at least 1 item was returned");
        $this->assertFalse($duplicateFound, "No duplicate entity Id was found in collection");
    }

    private function getEntityCollection($entityType = 'organization', $routePrefix = 'app')
    {
        $response = (new Request())
      ->uri("a/$routePrefix/$entityType")
      ->method('GET')
      ->session($this->globalAdministratorSession())
      ->execute();
        $body = json_decode($response->getBody());
        return $body->data;
    }
}
