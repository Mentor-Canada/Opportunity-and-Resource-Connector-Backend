<?php

namespace rest\program;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use rest\organization\OrganizationControllerTest;
use rest\Request;
use rest\request_objects\GuzzleMultipartObject;
use rest\request_objects\InnerNode;
use rest\request_objects\RequestContents;

class ProgramBuilder
{
    public array $attributes;
    public array $additionalAttributes;
    public array $relationships = [];

    public function __construct()
    {
        $this->attributes = (array)ProgramUtils::getParams();
        $this->additionalAttributes = (array)ProgramUtils::getAdditionalParams();
    }

    public static function createProgram($alternateParams = false, $includeOrganization = false, $frenchOnly = false)
    {
        $client = new Client(['base_uri' => 'http://localhost/a']);
        $contents = ProgramUtils::getContents($alternateParams, $includeOrganization, $frenchOnly);
        $innerData = ProgramUtils::getDataObject();
        $innerData->contents = $contents;
        $data = $innerData->transformToDataArrayIncludingPhoto();
        $data = [RequestOptions::MULTIPART => $data];
        $response = $client->request('POST', 'a/app/program', $data);
        return json_decode($response->getBody());
    }

    public function addOrganization(): ProgramBuilder
    {
        $newOrganization = new OrganizationControllerTest();
        $organizationId = $newOrganization->testCreateOrganization();
        $this->relationships = [
            "field_organization_entity" => [
                "data" => [
                    "type" => "node--organization",
                    "id" => $organizationId
                ]
            ]
        ];
        return $this;
    }

    public function execute()
    {
        $payload = new GuzzleMultipartObject();
        $payload->contents = new RequestContents();
        $payload->contents->nodes->en = new InnerNode('programs', $this->attributes);
        $payload->contents->nodes->en->relationships = $this->relationships;
        $payload->contents->additional = $this->additionalAttributes;
        $data = $payload->transformToDataArray();
        $response = (new Request())
      ->uri("a/app/program")
      ->data($data, true)
      ->execute();
        return $response;
    }

    public function getBody()
    {
        $response = $this->execute();
        return json_decode($response->getBody());
    }
}
