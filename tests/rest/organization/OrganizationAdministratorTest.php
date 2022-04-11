<?php

namespace rest\organization;

use rest\Request;
use rest\RestTestCase;
use rest\Session;
use rest\signin\SignInUtils;

class OrganizationAdministratorTest extends RestTestCase
{

    private string $organizationAdmin = 'organizationAdministrator@example.com';
    private string $password = 'hello123';

    public function testGlobalAdminCanAddNewOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $newAdmin = "addedByGlobalAdmin@example.com";
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$newAdmin}")
            ->method('POST')
            ->session($this->globalAdministratorSession())
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertContains(
            $newAdmin,
            $organizationAdmins,
            "Global administrator failed to add a new organization administrator"
        );
    }

    public function testOrganizationAdminCanAddNewOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->organizationAdmin;
        $this->createAuthenticatedUser($userParams);
        OrganizationUtils::addOrganizationAdmin($organizationId, $this->organizationAdmin);
        $organizationAdminSession = new Session();
        $organizationAdminSession->signIn($this->organizationAdmin, $this->password);

        $newAdmin = "addedByOrganizationAdmin@example.com";
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$newAdmin}")
            ->method('POST')
            ->session($organizationAdminSession)
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertContains(
            $newAdmin,
            $organizationAdmins,
            "Organization administrator failed to add a new organization administrator"
        );
    }

    public function testAnonymousUserCanNotAddNewOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $newAdmin = "addedByAnonymous@example.com";
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$newAdmin}")
            ->method('POST')
            ->expectedResponseCode(403)
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertNotContains(
            $newAdmin,
            $organizationAdmins,
            "Anonymous user was able to add a new organization administrator"
        );
    }

    public function testAuthenticatedNonAdminUserCanNotAddNewOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $newAdmin = "addedByAuthenticatedNonAdmin@example.com";
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$newAdmin}")
            ->method('POST')
            ->session($this->authenticatedSession())
            ->expectedResponseCode(403)
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertNotContains(
            $newAdmin,
            $organizationAdmins,
            "Authenticated non-admin user was able to add a new organization administrator"
        );
    }

    public function testGlobalAdminCanDeleteOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $adminToDelete = "adminToDelete@example.com";
        OrganizationUtils::addOrganizationAdmin($organizationId, $adminToDelete);
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($this->globalAdministratorSession())
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertNotContains(
            $adminToDelete,
            $organizationAdmins,
            "Global administrator failed to delete an organization administrator"
        );
    }

    public function testOrganizationAdminCanDeleteOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $userParams = SignInUtils::getUserParams();
        $userParams->email = $this->organizationAdmin;
        $this->createAuthenticatedUser($userParams);
        $adminToDelete = "adminToDelete@example.com";
        OrganizationUtils::addOrganizationAdmin($organizationId, $adminToDelete);
        OrganizationUtils::addOrganizationAdmin($organizationId, $this->organizationAdmin);
        $organizationAdminSession = new Session();
        $organizationAdminSession->signIn($this->organizationAdmin, $this->password);

        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($organizationAdminSession)
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertNotContains(
            $adminToDelete,
            $organizationAdmins,
            "Organization administrator failed to delete an organization administrator"
        );
    }

    public function testAnonymousUserCanNotDeleteOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $adminToDelete = "adminToDelete@example.com";
        OrganizationUtils::addOrganizationAdmin($organizationId, $adminToDelete);
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->expectedResponseCode(403)
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertContains(
            $adminToDelete,
            $organizationAdmins,
            "Anonymous user was able to delete an organization administrator"
        );
    }

    public function testAuthenticatedNonAdminUserCanNotDeleteOrganizationAdmin()
    {
        $organizationId = (new OrganizationBuilder())->getUuid();
        $adminToDelete = "adminToDelete@example.com";
        OrganizationUtils::addOrganizationAdmin($organizationId, $adminToDelete);
        (new Request())
            ->uri("/a/app/organization/$organizationId/administrator/{$adminToDelete}")
            ->method('DELETE')
            ->session($this->authenticatedSession())
            ->expectedResponseCode(403)
            ->execute();
        $organizationAdmins = $this->getOrganizationAdmins($organizationId);
        $this->assertContains(
            $adminToDelete,
            $organizationAdmins,
            "Authenticated non-admin user was able to delete an organization administrator"
        );
    }

    private function getOrganizationAdmins($organizationUuid)
    {
        $response = (new Request())
            ->uri("a/app/organization/{$organizationUuid}?include=field_administrators")
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        $body = json_decode($response->getBody());
        $organizationAdmins = $body->included;
        $adminNames = [];
        foreach ($organizationAdmins as $admin) {
            $adminNames[] = $admin->attributes->name;
        }
        return $adminNames;
    }
}
