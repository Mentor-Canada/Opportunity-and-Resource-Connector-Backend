<?php

namespace rest\organization;

class OrganizationBuilder
{
    public function getUuid()
    {
        $body = OrganizationUtils::createOrganization();
        return $body->data->id;
    }
}
