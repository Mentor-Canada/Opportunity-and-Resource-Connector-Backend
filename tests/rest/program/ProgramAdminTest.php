<?php

namespace rest\program;

use rest\organization\OrganizationBuilder;
use rest\organization\OrganizationUtils;
use rest\Request;
use rest\RestTestCase;
use rest\Session;
use rest\signin\SignInUtils;

class ProgramAdminTest extends RestTestCase
{

    private string $programAdmin = 'programAdministrator@example.com';
    private string $organizationAdmin = 'programOrganizationAdmin@example.com';
    private string $password = 'hello123';

    public function testGlobalAdminCanAddProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $newAdmin = "addedByGlobal@example.com";
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$newAdmin}")
            ->method('POST')
            ->session($this->globalAdministratorSession())
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertContains(
            $newAdmin,
            $programAdmins,
            "Global administrator failed to add a new program administrator"
        );
    }

    public function testProgramAdminCanAddNewProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->programAdmin;
        $this->createAuthenticatedUser($userParams);
        ProgramUtils::addProgramAdministrator($programId, $this->programAdmin);
        $programAdminSession = new Session();
        $programAdminSession->signIn($this->programAdmin, $this->password);

        $newAdmin = "addedByProgramAdmin@example.com";
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$newAdmin}")
            ->method('POST')
            ->session($programAdminSession)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertContains(
            $newAdmin,
            $programAdmins,
            "Program administrator failed to add a new program administrator"
        );
    }

    public function testProgramOrganizationAdminCanAddNewProgramAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $programBuilder = new ProgramBuilder();
        $programBuilder->programOrganizationId = $organizationId;
        $programId = $programBuilder->addOrganization()->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->organizationAdmin;
        $this->createAuthenticatedUser($userParams);
        OrganizationUtils::addOrganizationAdmin($organizationId, $this->organizationAdmin);
        $organizationAdminSession = new Session();
        $organizationAdminSession->signIn($this->organizationAdmin, $this->password);

        $newAdmin = "addedByProgramOrganizationAdmin@example.com";
        (new Request())
            ->uri("/a/app/program/{$programId}/administrator/{$newAdmin}")
            ->method('POST')
            ->session($organizationAdminSession)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertContains(
            $newAdmin,
            $programAdmins,
            "Program organization administrator failed to add a new program administrator"
        );
    }

    public function testAnonymousUserCanNotAddNewProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $newAdmin = "addedByAnonymous@example.com";
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$newAdmin}")
            ->method('POST')
            ->expectedResponseCode(403)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertNotContains(
            $newAdmin,
            $programAdmins,
            "Anonymous user was able to add a new program administrator"
        );
    }

    public function testAuthenticatedNonAdminUserCanNotAddNewProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $newAdmin = "addedByAuthenticatedNonAdmin@example.com";
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$newAdmin}")
            ->method('POST')
            ->session($this->authenticatedSession())
            ->expectedResponseCode(403)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertNotContains(
            $newAdmin,
            $programAdmins,
            "Authenticated non-admin user was able to add a new program administrator"
        );
    }

    public function testUnassociatedOrganizationAdminCanNotAddNewProgramAdmin()
    {
        $unassociatedOrgId = (new OrganizationBuilder())->getUuid();
        $programId = (new ProgramBuilder())->addOrganization()->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->organizationAdmin;
        $this->createAuthenticatedUser($userParams);
        OrganizationUtils::addOrganizationAdmin($unassociatedOrgId, $this->organizationAdmin);
        $unassociatedAdminSession = new Session();
        $unassociatedAdminSession->signIn($this->organizationAdmin, $this->password);

        $newAdmin = "addedByUnassociatedOrganizationAdmin@example.com";
        (new Request())
            ->uri("/a/app/program/{$programId}/administrator/{$newAdmin}")
            ->method('POST')
            ->session($unassociatedAdminSession)
            ->expectedResponseCode(403)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertNotContains(
            $newAdmin,
            $programAdmins,
            "An unassociated organization administrator was able to add a new program administrator"
        );
    }

    public function testGlobalAdminCanDeleteProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $adminToDelete = "adminToDelete@example.com";
        ProgramUtils::addProgramAdministrator($programId, $adminToDelete);
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($this->globalAdministratorSession())
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertNotContains(
            $adminToDelete,
            $programAdmins,
            "Global administrator failed to delete a program administrator"
        );
    }

    public function testProgramAdminCanDeleteProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->programAdmin;
        $this->createAuthenticatedUser($userParams);
        $adminToDelete = "adminToDelete@example.com";
        ProgramUtils::addProgramAdministrator($programId, $adminToDelete);
        ProgramUtils::addProgramAdministrator($programId, $this->programAdmin);
        $programAdminSession = new Session();
        $programAdminSession->signIn($this->programAdmin, $this->password);

        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($programAdminSession)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertNotContains(
            $adminToDelete,
            $programAdmins,
            "Program administrator failed to delete a program administrator"
        );
    }

    public function testProgramOrganizationAdminCanDeleteProgramAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $programBuilder = new ProgramBuilder();
        $programBuilder->programOrganizationId = $organizationId;
        $programId = $programBuilder->addOrganization()->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->organizationAdmin;
        $this->createAuthenticatedUser($userParams);
        $adminToDelete = "adminToDelete@example.com";
        ProgramUtils::addProgramAdministrator($programId, $adminToDelete);
        OrganizationUtils::addOrganizationAdmin($organizationId, $this->organizationAdmin);
        $organizationAdminSession = new Session();
        $organizationAdminSession->signIn($this->organizationAdmin, $this->password);

        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($organizationAdminSession)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertNotContains(
            $adminToDelete,
            $programAdmins,
            "Program organization administrator failed to delete a program administrator"
        );
    }

    public function testAnonymousUserCanNotDeleteProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $adminToDelete = "adminToDelete@example.com";
        ProgramUtils::addProgramAdministrator($programId, $adminToDelete);
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->expectedResponseCode(403)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertContains(
            $adminToDelete,
            $programAdmins,
            "Anonymous user was able to delete a program administrator"
        );
    }

    public function testAuthenticatedNonAdminUserCanNotDeleteProgramAdmin()
    {
        $programId = (new ProgramBuilder())->getUuid();
        $adminToDelete = "adminToDelete@example.com";
        ProgramUtils::addProgramAdministrator($programId, $adminToDelete);
        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($this->authenticatedSession())
            ->expectedResponseCode(403)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertContains(
            $adminToDelete,
            $programAdmins,
            "Authenticated non-admin user was able to delete a program administrator"
        );
    }

    public function testUnassociatedProgramOrganizationAdminCanNotDeleteProgramAdmin()
    {
        $unassociatedOrgId = (new OrganizationBuilder())->getUuid();
        $programId = (new ProgramBuilder())->addOrganization()->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->organizationAdmin;
        $this->createAuthenticatedUser($userParams);
        $adminToDelete = "adminToDelete@example.com";
        ProgramUtils::addProgramAdministrator($programId, $adminToDelete);
        OrganizationUtils::addOrganizationAdmin($unassociatedOrgId, $this->organizationAdmin);
        $unassociatedAminSession = new Session();
        $unassociatedAminSession->signIn($this->organizationAdmin, $this->password);

        (new Request())
            ->uri("/a/app/program/$programId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($unassociatedAminSession)
            ->expectedResponseCode(403)
            ->execute();
        $programAdmins = $this->getProgramAdmins($programId);
        $this->assertContains(
            $adminToDelete,
            $programAdmins,
            "An unassociated organization administrator was able to to delete a program administrator"
        );
    }

    private function getProgramAdmins($programId)
    {
        $response = (new Request())
            ->uri("a/app/program/{$programId}?include=field_administrators")
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        $body = json_decode($response->getBody());
        $programAdmins = $body->included;
        $adminNames = [];
        foreach ($programAdmins as $admin) {
            $adminNames[] = $admin->attributes->name;
        }
        return $adminNames;
    }
}
