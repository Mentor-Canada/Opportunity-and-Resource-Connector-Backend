<?php

namespace rest\search;

use rest\approval\ApprovalUtils;
use rest\program\ProgramBuilder;
use rest\program\ProgramUtils;
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

    public function testProgramFocusAreaMultipleCategorySelectionHasCorrectSearchResults()
    {
        $params = ProgramUtils::getParams();
        $params->field_focus_area = 'app-ca-program-focus-arts';
        $builder = new ProgramBuilder();
        $builder->attributes = (array)$params;
        $artsFocusProgram = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($artsFocusProgram);

        $params->field_focus_area = 'app-ca-program-focus-culture';
        $builder->attributes = (array)$params;
        $cultureFocusProgram = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($cultureFocusProgram);

        $params->field_focus_area = 'app-ca-program-focus-sports-activities';
        $builder->attributes = (array)$params;
        $sportsFocusProgram = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($sportsFocusProgram);

        $focusAreaUrl = "/a/app/search/results/02150?focus=app-ca-program-focus-arts,app-ca-program-focus-culture&grade=all";
        $response = (new Request())
            ->uri($focusAreaUrl)
            ->method('GET')
            ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgramIds = array_map(fn($program) => $program->UUID, $body->data);
        $this->assertContains(
            $artsFocusProgram,
            $retrievedProgramIds,
            "A program with the expected focus area was not retrieved in the results"
        );
        $this->assertContains(
            $cultureFocusProgram,
            $retrievedProgramIds,
            "A program with the expected focus area was not retrieved in the results"
        );
        $this->assertNotContains(
            $sportsFocusProgram,
            $retrievedProgramIds,
            "A program with the wrong focus area was retrieved in the results"
        );
    }

    public function testProgramAgesServedMultipleCategorySelectionHasCorrectSearchResults()
    {
        $params = ProgramUtils::getParams();
        $params->field_program_ages_served = ['app-ca-25-and-over'];
        $builder = new ProgramBuilder();
        $builder->attributes = (array)$params;
        $agesTwentyFiveAndOver = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($agesTwentyFiveAndOver);

        $params->field_program_ages_served = ['app-ca-15-17', 'app-ca-18-24'];
        $builder->attributes = (array)$params;
        $agesFifteenToTwentyFour = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($agesFifteenToTwentyFour);

        $params->field_program_ages_served = ['app-ca-7-and-under'];
        $builder->attributes = (array)$params;
        $agesSevenAndUnder = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($agesSevenAndUnder);

        $agesServedUrl = "/a/app/search/results/02150?age=app-ca-25-and-over,app-ca-18-24&grade=all";
        $response = (new Request())
            ->uri($agesServedUrl)
            ->method('GET')
            ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgramIds = array_map(fn($program) => $program->UUID, $body->data);
        $this->assertContains(
            $agesTwentyFiveAndOver,
            $retrievedProgramIds,
            "A program with the expected ages served was not retrieved in the results"
        );
        $this->assertContains(
            $agesFifteenToTwentyFour,
            $retrievedProgramIds,
            "A program with the expected ages served was not retrieved in the results"
        );
        $this->assertNotContains(
            $agesSevenAndUnder,
            $retrievedProgramIds,
            "A program with the wrong ages served was retrieved in the results"
        );
    }

    public function testProgramYouthServedMultipleCategorySelectionHasCorrectSearchResults()
    {
        $params = ProgramUtils::getParams();
        $params->field_program_youth_served = ['app-ca-emancipated'];
        $builder = new ProgramBuilder();
        $builder->attributes = (array)$params;
        $emancipatedYouthServed = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($emancipatedYouthServed);

        $params->field_program_youth_served = ['app-ca-low-income', 'app-ca-school-drop-out'];
        $builder->attributes = (array)$params;
        $lowIncomeAndDropoutYouthServed = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($lowIncomeAndDropoutYouthServed);

        $params->field_program_youth_served = ['app-ca-mental-health-needs'];
        $builder->attributes = (array)$params;
        $mentalHealthNeedsYouthServed = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($mentalHealthNeedsYouthServed);

        $agesServedUrl = "/a/app/search/results/02150?youth=app-ca-low-income,app-ca-emancipated&grade=all";
        $response = (new Request())
            ->uri($agesServedUrl)
            ->method('GET')
            ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgramIds = array_map(fn($program) => $program->UUID, $body->data);
        $this->assertContains(
            $emancipatedYouthServed,
            $retrievedProgramIds,
            "A program with the expected youth served was not retrieved in the results"
        );
        $this->assertContains(
            $lowIncomeAndDropoutYouthServed,
            $retrievedProgramIds,
            "A program with the expected youth served was not retrieved in the results"
        );
        $this->assertNotContains(
            $mentalHealthNeedsYouthServed,
            $retrievedProgramIds,
            "A program with the wrong youth served was retrieved in the results"
        );
    }

    public function testProgramMentoringTypeMultipleCategorySelectionHasCorrectSearchResults()
    {
        $params = ProgramUtils::getParams();
        $params->field_types_of_mentoring = ['app-type-of-mentoring-group'];
        $builder = new ProgramBuilder();
        $builder->attributes = (array)$params;
        $groupMentoring = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($groupMentoring);

        $params->field_types_of_mentoring = ['app-type-of-mentoring-peer', 'app-type-of-mentoring-team'];
        $builder->attributes = (array)$params;
        $peerAndTeamMentoring = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($peerAndTeamMentoring);

        $params->field_types_of_mentoring = ['app-type-of-mentoring-1-to-1'];
        $builder->attributes = (array)$params;
        $oneToOneMentoring = $builder->getUuid();
        ApprovalUtils::changeApprovalStatus($oneToOneMentoring);

        $mentoringTypeUrl = "/a/app/search/results/02150?type=app-type-of-mentoring-team,app-type-of-mentoring-group&grade=all";
        $response = (new Request())
            ->uri($mentoringTypeUrl)
            ->method('GET')
            ->execute();
        $body = json_decode($response->getBody());
        $retrievedProgramIds = array_map(fn($program) => $program->UUID, $body->data);
        $this->assertContains(
            $groupMentoring,
            $retrievedProgramIds,
            "A program with the expected mentoring type was not retrieved in the results"
        );
        $this->assertContains(
            $peerAndTeamMentoring,
            $retrievedProgramIds,
            "A program with the expected mentoring type was not retrieved in the results"
        );
        $this->assertNotContains(
            $oneToOneMentoring,
            $retrievedProgramIds,
            "A program with the wrong mentoring type was retrieved in the results"
        );
    }
}
