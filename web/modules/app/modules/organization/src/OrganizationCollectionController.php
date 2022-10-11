<?php

namespace Drupal\app_organization;

use Drupal;
use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\CSVBuilder;
use Drupal\app_program\ProgramRequestAdapter;

class OrganizationCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $this->adapter = ProgramRequestAdapter::createFromRequest(Drupal::request());

        $builder = OrganizationUtils::getFilterBuilder($this->adapter);

        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();
    }

    public function csv()
    {
        (new CSVBuilder($this->data, "organizations.csv"))
            ->header("app-organization-name-en")->text(OrganizationFields::displayTitle)
            ->header("app-organization-name-fr")->text("title_fr")
            ->header("app-organization-description-en")->text("description_en")
            ->header("app-organization-description-fr")->text("description_fr")
            ->header("app-organization-legal-name")->text(OrganizationFields::legalName)
            ->header("app-organization-type")->text(OrganizationFields::type, true)
            ->header("app-organization-type-other")->text(OrganizationFields::typeOther)
            ->header("app-organization-tax-status")->text(OrganizationFields::taxStatus, true)
            ->header("app-organization-tax-status-other")->text(OrganizationFields::taxStatusOther)
            ->header("app-first-name")->text(OrganizationFields::firstName)
            ->header("app-last-name")->text(OrganizationFields::lastName)
            ->header("app-contact-position")->text(OrganizationFields::position, true)
            ->header("app-contact-position-other")->text(OrganizationFields::positionOther)
            ->header("app-contact-phone")->text(OrganizationFields::phone)
            ->header("app-contact-alternate-phone")->text(OrganizationFields::altPhone)
            ->header("app-email")->text(OrganizationFields::email)
            ->header("app-web-address")->text(OrganizationFields::webAddress)
            ->header("app-organization-has-physical-location")->callback(OrganizationFields::hasLocation, fn ($value) => $value = $value === '1' ? 'yes' : 'no')
            ->header("app-address")->callback(OrganizationFields::physicalLocation, fn ($value) => json_decode($value)->formatted_address)
            ->header("app-feedback-label")->text(OrganizationFields::feedback)
            ->render()
        ;
    }

    public function collectionTotal(): int
    {
        return $this->total;
    }

    public function collectionData(): array
    {
        return $this->data;
    }

    public function paginationOffset(): ?int
    {
        return $this->adapter->offset;
    }

    public function paginationLimit(): ?int
    {
        return $this->adapter->limit;
    }
}
