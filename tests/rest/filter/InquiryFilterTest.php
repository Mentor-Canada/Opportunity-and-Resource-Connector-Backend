<?php

namespace rest\filter;

use rest\inquiry\InquiryUtils;
use rest\program\ProgramBuilder;
use rest\program\ProgramDelivery;
use rest\program\ProgramStrings;
use rest\program\ProgramUtils;
use rest\Request;
use rest\RestTestCase;

class InquiryFilterTest extends RestTestCase
{

    public function testFilterInquiriesBySiteBasedPrograms()
    {
        $siteBasedProgramInquiry = $this->createInquiryWithProgramDeliveryType(ProgramStrings::$programSiteBased);
        $communityBasedProgramInquiry = $this->createInquiryWithProgramDeliveryType(
            ProgramStrings::$programCommunityBased
        );
        $eMentoringProgramInquiry = $this->createInquiryWithProgramDeliveryType(ProgramStrings::$programEMentoring);

        $retrievedInquiryIds = $this->getFilteredInquriyIds(ProgramStrings::$programSiteBased);

        $this->assertContains(
            $siteBasedProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with site based program was not loaded with site based program inquiry filter"
        );
        $this->assertNotContains(
            $communityBasedProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with community based program was loaded with site based program only inquiry filter"
        );
        $this->assertNotContains(
            $eMentoringProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with eMentoring program was loaded with site based program only inquiry filter"
        );
    }

    public function testFilterInquiriesByCommunityBasedPrograms()
    {
        $siteBasedProgramInquiry = $this->createInquiryWithProgramDeliveryType(ProgramStrings::$programSiteBased);
        $communityBasedProgramInquiry = $this->createInquiryWithProgramDeliveryType(
            ProgramStrings::$programCommunityBased
        );
        $eMentoringProgramInquiry = $this->createInquiryWithProgramDeliveryType(ProgramStrings::$programEMentoring);

        $retrievedInquiryIds = $this->getFilteredInquriyIds(ProgramStrings::$programCommunityBased);

        $this->assertContains(
            $communityBasedProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with community based program was not loaded with community based program inquiry filter"
        );
        $this->assertNotContains(
            $siteBasedProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with site based program was loaded with community based program only inquiry filter"
        );
        $this->assertNotContains(
            $eMentoringProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with eMentoring program was loaded with community based program only inquiry filter"
        );
    }

    public function testFilterInquiriesByEMentoringPrograms()
    {
        $siteBasedProgramInquiry = $this->createInquiryWithProgramDeliveryType(ProgramStrings::$programSiteBased);
        $communityBasedProgramInquiry = $this->createInquiryWithProgramDeliveryType(
            ProgramStrings::$programCommunityBased
        );
        $eMentoringProgramInquiry = $this->createInquiryWithProgramDeliveryType(ProgramStrings::$programEMentoring);

        $retrievedInquiryIds = $this->getFilteredInquriyIds(ProgramStrings::$programEMentoring);

        $this->assertContains(
            $eMentoringProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with eMentoring program was not loaded with eMentoring program inquiry filter"
        );
        $this->assertNotContains(
            $siteBasedProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with site based program was loaded with eMentoring program only inquiry filter"
        );
        $this->assertNotContains(
            $communityBasedProgramInquiry,
            $retrievedInquiryIds,
            "Inquiry with community based program was loaded with eMentoring program only inquiry filter"
        );
    }

    private function getFilteredInquriyIds($deliveryType)
    {
        $programFilterParams = new ProgramFilterParams();
        $programFilterParams->delivery = $deliveryType;
        $deliveryFilter = new SaveFilterParams();
        $deliveryFilter->type = 'program';
        $deliveryFilter->title = "{$deliveryType}-Program-Filter";
        $deliveryFilter->data = json_encode($programFilterParams);
        $filterId = FilterUtils::saveFilter($deliveryFilter);

        $filteredInquiries = $this->getInquiriesByProgramDeliveryFilter($filterId)->data;
        $retrievedInquiryIds = [];
        foreach ($filteredInquiries as $inquiry) {
            $retrievedInquiryIds[] = $inquiry->attributes->uuid;
        }
        return $retrievedInquiryIds;
    }

    private function getInquiriesByProgramDeliveryFilter($programFilterId)
    {
        $response = (new Request())
            ->uri("a/app/inquiry?filter%5BprogramFilter%5D=%22{$programFilterId}%22")
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        return json_decode($response->getBody());
    }

    private function createInquiryWithProgramDeliveryType($deliveryType)
    {
        $programAdditionalParams = (object)ProgramUtils::getAdditionalParams();
        $programDelivery = new ProgramDelivery();
        if ($deliveryType === 'communityBased') {
            $programDelivery->community = $deliveryType;
        } else {
            $programDelivery->{$deliveryType} = $deliveryType;
        }
        $programAdditionalParams->delivery = $programDelivery;
        $timeCreated = date_format(date_create(), "D/M/d - h:i:s");
        $programAdditionalParams->title->en = "{$deliveryType} - Program - {$timeCreated}";
        $InquiryParams = InquiryUtils::getParams();
        $programBuilder = new ProgramBuilder();
        $programBuilder->additionalAttributes = (array)$programAdditionalParams;
        $program = json_decode($programBuilder->execute()->getBody())->data;
        $InquiryParams->programId = $program->attributes->drupal_internal__nid;
        InquiryUtils::createInquiry($InquiryParams);
        $inquiry = current(InquiryUtils::getInquiryCollection()->data);
        return $inquiry->attributes->uuid;
    }
}
