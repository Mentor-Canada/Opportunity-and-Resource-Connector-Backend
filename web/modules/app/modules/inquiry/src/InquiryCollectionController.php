<?php

namespace Drupal\app_inquiry;

use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\CSVBuilder;
use Drupal\app\SearchFields;
use Drupal\app\Utils\ProgramUtils;
use Drupal\app\Utils\Security;
use Drupal\app_filter\FilterCollectionBuilder;
use Drupal\app_program\ProgramRequestAdapter;

class InquiryCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $this->adapter = new InquiryRequestAdapter(\Drupal::request());

        $builder = (new InquiryCollectionBuilder())
      ->start($this->adapter->filter['start_date'])
      ->end($this->adapter->filter['end_date'])
      ->orderBy($this->adapter->sortField, $this->adapter->sortDirection);

        if (!Security::globalAdministrator()) {
            $userPrograms = ProgramUtils::programsForUser();
            $applications = $this->applicationsForPrograms($userPrograms);
            if (!count($applications)) {
                $this->total = 0;
                $this->data = [];
                return;
            }
            $builder->ids($applications);
        }

        foreach ($this->adapter->filter as $key => $value) {
            if (in_array($key, ['start_date', 'end_date'])) {
                continue;
            }
            if ($key == ApplicationFields::programFilter) {
                $ids = $this->adapter->getFilter($key);
                $filters = (new FilterCollectionBuilder())->ids($ids)->execute();
                $entityIds = [];
                foreach ($filters as $filter) {
                    $data = json_decode($filter->data, true);
                    $entityIds = array_merge($entityIds, $this->getIdsFromFilter($data));
                }
                $builder->filter(ApplicationFields::program, $entityIds);
                continue;
            } elseif ($key == ApplicationFields::organization) {
                $builder->organization($value);
                continue;
            }
            $builder->filter($key, $value);
        }

        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();
    }

    private function getIdsFromFilter($filter)
    {
        $adapter = new ProgramRequestAdapter();
        if (isset($filter['delivery'])) {
            $delivery = $filter['delivery'];
            unset($filter['delivery']);
            $filter[$delivery] = 1;
        }
        foreach($filter as $key => $value) {
            $filter[$key] = json_encode($value);
        }
        $adapter->filter = $filter;
        $builder = \Drupal\app_program\ProgramUtils::getFilterBuilder($adapter);
        $result = $builder->execute();
        return array_map(fn ($a) => $a->nid, $result);
    }

    public function csv()
    {
        (new CSVBuilder($this->data, "inquiries.csv"))
      ->header('app-partner-id')->text(ApplicationFields::partnerId)
      ->header('app-partner-title')->text(ApplicationFields::partnerTitle)
      ->header('app-program')->text(ApplicationFields::programTitle)
      ->header('app-organization')->text(ApplicationFields::organizationTitle)
      ->header('app-first-name')->text(ApplicationFields::first_name)
      ->header('app-last-name')->text(ApplicationFields::last_name)
      ->header('app-role')->text(ApplicationFields::role, true)
      ->header('app-field-status')->text(ApplicationFields::status, true)
      ->header('app-recipient-email')->json(ApplicationFields::recipientEmail)
      ->header('app-relay-email')->callback(ApplicationFields::uuid, function ($value, $row) {
          return "{$value}@{$_ENV['RELAY_HOST']}";
      })
      ->header('app-email')->callback(ApplicationFields::email, function ($value, $row) {
          return $row->{ApplicationFields::status} == 'app-contacted' ? $row->{ApplicationFields::email} : strval(t('app-hidden'));
      })
      ->header('app-contact-phone')->callback(ApplicationFields::phone, function ($value, $row) {
          return $row->{ApplicationFields::status} == 'app-contacted' ? $row->{ApplicationFields::phone} : strval(t('app-hidden'));
      })
      ->header('app-voice')->callback(ApplicationFields::call, function ($value, $row) {
          return $row->{ApplicationFields::call} == 1 ? strval(t("app-yes")) : "";
      })
      ->header('app-sms')->callback(ApplicationFields::sms, function ($value, $row) {
          return $row->{ApplicationFields::sms} == 1 ? strval(t("app-yes")) : "";
      })
      ->header('app-how-did-you-hear-about-us')->text(ApplicationFields::how_did_you_hear_about_us, true)
      ->header('app-how-did-you-hear-about-us-other')->text(SearchFields::how_did_you_hear_about_us_other)
      ->header('app-zip')->callback(SearchFields::zip, function ($value, $row) {
          return $row->{SearchFields::zip} === 'app-national' ? t('app-national') : $row->{SearchFields::zip};
      })
      ->header('app-city')->text(SearchFields::city)
      ->header('app-state')->text(SearchFields::state)
      ->header("app-created")->callback('created', fn ($value) => date('Y-m-d H:i:s', $value))
      ->render();
    }

    private function applicationsForPrograms($programs)
    {
        if (!count($programs)) {
            return [];
        }
        $in = implode(',', $programs);
        $q = "SELECT id FROM inquiries
            WHERE programId IN ($in)
            ";
        $database = \Drupal::database();
        $query = $database->query($q);
        $result = $query->fetchAll();
        $ids = array_map(function ($a) {
            return $a->id;
        }, $result);
        return $ids;
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
