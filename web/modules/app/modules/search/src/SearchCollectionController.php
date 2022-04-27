<?php

namespace Drupal\app_search;

use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\CSVBuilder;
use Drupal\app\SearchFields;

class SearchCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);

        ini_set('memory_limit', '4096M');
        ini_set('max_execution_time', '120');

        $this->adapter = new SearchRequestAdapter(\Drupal::request());

        $builder = (new SearchCollectionBuilder())
      ->partner($this->adapter->partnerNid)
      ->createdStart($this->adapter->createdStartDate)
      ->createdStop($this->adapter->createdEndDate)
      ->orderBy($this->adapter->sortField, $this->adapter->sortDirection);

        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();
    }

    public function csv()
    {
        (new CSVBuilder($this->data, "searches.csv"))
      ->header('app-partner')->text(SearchFields::partnerTitle)
      ->header('app-field-role')->text(SearchFields::role, true)
      ->header('app-field-zip')->callback(SearchFields::zip, function ($value, $row) {
          return $row->{SearchFields::zip} === 'app-national' ? t('app-national') : $row->{SearchFields::zip};
      })
      ->header('app-city')->text(SearchFields::city)
      ->header('app-state')->text(SearchFields::state)

      ->header('app-search-focus-of-mentoring')->json(SearchFields::focus, true)
      ->header('app-search-age-of-youth-served')->json(SearchFields::age, true)
      ->header('app-search-grade-of-youth-served')->json(SearchFields::grade, true)
      ->header('app-youth-served')->json(SearchFields::youth, true)
      ->header('app-field-type-of-mentoring')->json(SearchFields::type_of_mentoring, true)

      ->header('app-field-how-did-you-hear-about-us')->text(SearchFields::how_did_you_hear_about_us, true)
      ->header('app-other')->text(SearchFields::how_did_you_hear_about_us_other)
      ->header("app-created")->callback('created', fn ($value) => date('Y-m-d H:i:s', $value))

      ->render();
        ;
        exit;
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
