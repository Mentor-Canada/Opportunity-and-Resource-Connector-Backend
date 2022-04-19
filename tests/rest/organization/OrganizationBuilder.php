<?php

namespace rest\organization;

use GuzzleHttp\RequestOptions;
use rest\request_objects\GuzzleMultipartObject;
use rest\request_objects\InnerNode;
use rest\request_objects\RequestNode;
use rest\Session;

class OrganizationBuilder
{
    public array $attributes;
    public string $language;

    public function __construct()
    {
        $this->language = 'en';
        $this->attributes = (array)OrganizationUtils::getParams();
    }

    public function getUuid()
    {
        $body = $this->createOrganization();
        return $body->data->id;
    }

    public function execute()
    {
        $organizationDataObject = $this->getDataObject()->transformToDataArrayIncludingPhoto();
        $payload = [RequestOptions::MULTIPART => $organizationDataObject];
        $response = (new Session())->request('POST', 'a/app/organization', $payload);
        return $response;
    }

    public function updateAttributesToLanguage()
    {
        $isFrenchOrganization = $this->language === 'fr';
        $this->attributes = (array)OrganizationUtils::getParams($isFrenchOrganization);
    }

    private function createOrganization()
    {
        $anonymousSession = new Session();
        $innerData = $this->getDataObject();
        $payload = $innerData->transformToDataArrayIncludingPhoto();
        $payload = [RequestOptions::MULTIPART => $payload];
        $response = $anonymousSession->request('POST', 'a/app/organization', $payload);
        return json_decode($response->getBody());
    }

    private function getDataObject()
    {
        $innerData = new GuzzleMultipartObject();
        $innerData->contents->nodes = new RequestNode();
        $innerData->contents->nodes->en = new InnerNode("node--organization");
        $innerData->contents->nodes->fr = new InnerNode("node--organization");
        $innerData->contents->uilang = $this->language;
        $innerData->contents->additional = $this->attributes;
        return $innerData;
    }
}
