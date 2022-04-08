<?php

namespace rest\inquiry;

use rest\program\ProgramBuilder;
use rest\program\ProgramUtils;
use rest\Request;
use rest\request_objects\LangCode;
use rest\RestTestCase;

class InquiryCSVTest extends RestTestCase
{
    private int $columnCount = 20;
    private string $firstEmail = "firstAdmin@example.com";
    private string $secondEmail = "secondAdmin@example.com";

    public function testGetInquiryCsv()
    {
        $csvContent = InquiryUtils::downloadInquiryCSV();
        $csvRows = explode("\r\n", $csvContent);
        $csvHeaders = explode(',', current($csvRows));
        $this->assertNotEmpty($csvRows, "Failed to download CSV rows");
        $this->assertCount($this->columnCount, $csvHeaders, "The number of CSV columns did not match expected amount");
    }

    public function testInquiryRecipientEmailsCorrectlyStoredAndRetrieved()
    {
        $program = $this->createCustomInquiryProgram();
        $programNid = $program->data->attributes->drupal_internal__nid;
        $inquiryParams = InquiryUtils::getParams();
        $inquiryParams->programId = $programNid;
        $inquiryUUID = InquiryUtils::createInquiry($inquiryParams)->data->uuid;

        $retrievedInquiries = (new Request())
            ->uri("a/app/inquiry?sort=-created")
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();

        $retrievedInquiriesBody = json_decode($retrievedInquiries->getBody());
        $inquiryAttributes = $retrievedInquiriesBody->data[0]->attributes;
        $expectedEmails = [$this->firstEmail, $this->secondEmail];

        $this->assertEquals(
            $inquiryAttributes->uuid,
            $inquiryUUID,
            "Wrong inquiry was retrieved or original inquiry failed to post correctly"
        );
        $this->assertEquals(
            json_decode($inquiryAttributes->recipient_email),
            $expectedEmails,
            "Inquiry email recipients were not retrieved correctly"
        );
    }

    public function testEmailRecipientsAppearInEnglishInquiryCSV()
    {
        $this->testInquiryRecipientEmailsCorrectlyStoredAndRetrieved();
        $recipientColumnIndex = 8;
        $csvContent = InquiryUtils::downloadInquiryCSV();
        $csvRows = explode("\r\n", $csvContent);
        $csvHeaders = explode(',', current($csvRows));
        $latestInquiry = $csvRows[count($csvRows) - 2];
        $this->assertEquals(
            'Recipients',
            $csvHeaders[$recipientColumnIndex],
            "The recipient column index or header translation was incorrect in inquiry english CSV"
        );
        $this->assertStringContainsString(
            "{$this->firstEmail}, {$this->secondEmail}",
            $latestInquiry,
            "The expected email addresses were not found in the latest inquiry CSV column"
        );
    }

    public function testEmailRecipientsAppearInFrenchInquiryCSV()
    {
        $this->testInquiryRecipientEmailsCorrectlyStoredAndRetrieved();
        $recipientColumnIndex = 8;
        $langCode = (new LangCode())->setToFrench();
        $csvContent = InquiryUtils::downloadInquiryCSV($langCode);
        $csvRows = explode("\r\n", $csvContent);
        $csvHeaders = explode(',', current($csvRows));
        $latestInquiry = $csvRows[count($csvRows) - 2];
        $this->assertEquals(
            'Destinataires',
            $csvHeaders[$recipientColumnIndex],
            "The recipient column index or header translation was incorrect in inquiry french CSV"
        );
        $this->assertStringContainsString(
            "{$this->firstEmail}, {$this->secondEmail}",
            $latestInquiry,
            "The expected email addresses were not found in the latest inquiry CSV column"
        );
    }

    private function createCustomInquiryProgram()
    {
        $programBuilder = new ProgramBuilder();
        $programAttributes = ProgramUtils::getAdditionalParams();
        $programAttributes->email = $this->firstEmail;
        $programBuilder->additionalAttributes = (array)$programAttributes;
        $response = json_decode($programBuilder->execute()->getBody());
        $programUUID = $response->data->id;
        ProgramUtils::addProgramAdministrator($programUUID, $this->secondEmail);
        return $response;
    }
}
