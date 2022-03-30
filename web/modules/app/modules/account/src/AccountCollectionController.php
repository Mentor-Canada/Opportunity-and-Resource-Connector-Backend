<?php

namespace Drupal\app_account;

use Drupal;
use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\CSVBuilder;

class AccountCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    private AccountCollectionBuilder $builder;

    public function __construct($http_kernel)
    {
        $this->adapter = new AccountCollectionRequestAdapter(Drupal::request());

        $this->builder = (new AccountCollectionBuilder())
      ->mail($this->adapter->mail)
      ->accountType($this->adapter->accountType)
      ->mentorCity($this->adapter->mentorCity)
      ->firstName($this->adapter->firstName)
      ->lastName($this->adapter->lastName)
      ->created($this->adapter->createdStartDate, $this->adapter->createdEndDate)
      ->orderBy($this->adapter->sortField, $this->adapter->sortDirection)
    ;

        parent::__construct($http_kernel);
    }

    public function collectionData(): array
    {
        $this->builder->range($this->adapter->limit, $this->adapter->offset);
        return $this->builder->build();
    }

    public function collectionTotal(): int
    {
        return $this->builder->total();
    }

    public function paginationOffset(): ?int
    {
        return $this->adapter->offset;
    }

    public function paginationLimit(): ?int
    {
        return $this->adapter->limit;
    }

    public function csv()
    {
        $data = $this->collectionData();
        (new CSVBuilder($data, "accounts.csv"))
      /** General */
      ->header("app-email")->text("mail")
      ->header("app-field-first-name")->text("firstName")
      ->header("app-field-last-name")->text("lastName")
      ->header("app-field-global-administrator")->callback("globalAdministrator", fn ($value) => $value ? t("app-yes") : t("app-no"))
      ->header("app-affiliate-administrator")->callback("affiliates", function ($value) {
          if (count($value)) {
              $names = array_column($value, "name");
              return implode(", ", $names);
          }
      })
      ->header("app-organization-administrator")->callback("organizations", function ($value) {
          if (count($value)) {
              $names = array_column($value, "name");
              return implode(", ", $names);
          }
      })
      ->header("app-program-administrator")->callback("programs", function ($value) {
          if (count($value)) {
              $names = array_column($value, "name");
              return implode(", ", $names);
          }
      })
      ->header("app-created")->callback('created', fn ($value) => date('Y-m-d H:i:s', $value / 1000))
      ->render()
    ;
    }
}
