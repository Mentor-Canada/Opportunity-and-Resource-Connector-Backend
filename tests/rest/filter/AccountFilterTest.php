<?php

namespace rest\filter;

use rest\account\AccountUtils;
use rest\Request;
use rest\request_objects\UserBuilder;
use rest\RestTestCase;

class AccountFilterTest extends RestTestCase
{

    public function testFilterAccountByFirstName()
    {
        $this->createAccountsForFilter();
        $filterUrl = "en/a/app/accounts?filter%5BfirstName%5D=bruce&sort=mail&page%5Blimit%5D=20&page%5Boffset%5D=0";
        $response = (new Request())
            ->uri($filterUrl)
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        $body = json_decode($response->getBody());
        $firstResult = $body->data[0]->attributes;
        $secondResult = $body->data[1]->attributes;
        $this->assertEquals(2, count($body->data),
            'first name account filter returned an unexpected number of results');
        $this->assertEquals('bruce', $firstResult->firstName,
            'first name account  filter failed to fetch 1st of 2 expected results correctly');
        $this->assertEquals('bruce', $secondResult->firstName,
            'first name account filter failed to fetch 2nd of 2 expected results correctly');
    }

    public function testFilterAccountByLastName()
    {
        $this->createAccountsForFilter();
        $filterUrl = "en/a/app/accounts?filter%5BlastName%5D=banner&sort=mail&page%5Blimit%5D=20&page%5Boffset%5D=0";
        $response = (new Request())
            ->uri($filterUrl)
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        $body = json_decode($response->getBody());
        $firstResult = $body->data[0]->attributes;
        $secondResult = $body->data[1]->attributes;
        $this->assertEquals(2, count($body->data),
            'last name account filter returned an unexpected number of results');
        $this->assertEquals('banner', $firstResult->lastName,
            'last name account filter failed to fetch 1st of 2 expected results correctly');
        $this->assertEquals('banner', $secondResult->lastName,
            'last name account filter failed to fetch 2nd of 2 expected results correctly');
    }

    public function testFilterAccountByFirstAndLastName()
    {
        $this->createAccountsForFilter();
        $filterUrl = "en/a/app/accounts?filter%5BfirstName%5D=joe&filter%5BlastName%5D=banner&sort=mail&page%5Blimit%5D=20&page%5Boffset%5D=0";
        $response = (new Request())
            ->uri($filterUrl)
            ->method('GET')
            ->session($this->globalAdministratorSession())
            ->execute();
        $body = json_decode($response->getBody());
        $result = $body->data[0]->attributes;
        $this->assertEquals(1, count($body->data),
            'first and last name account filter returned an unexpected number of results');
        $this->assertEquals('joe', $result->firstName,
            'first and last name account filter failed to match first name correctly');
        $this->assertEquals('banner', $result->lastName,
            'first and last name account filter failed to match last name correctly');
    }

    private function createAccountsForFilter()
    {
        $userBuilder = new UserBuilder();
        $userBuilder->firstName = 'bruce';
        $userBuilder->lastName = 'wayne';
        $userBuilder->email = 'batman@example.com';
        $userBuilder->password = 'hello123';
        AccountUtils::createAccount($userBuilder->build());
        $userBuilder = new UserBuilder();
        $userBuilder->firstName = 'bruce';
        $userBuilder->lastName = 'banner';
        $userBuilder->email = 'hulk@example.com';
        $userBuilder->password = 'hello123';
        AccountUtils::createAccount($userBuilder->build());
        $userBuilder = new UserBuilder();
        $userBuilder->firstName = 'joe';
        $userBuilder->lastName = 'banner';
        $userBuilder->email = 'averagejoe@example.com';
        $userBuilder->password = 'hello123';
        AccountUtils::createAccount($userBuilder->build());
    }
}
