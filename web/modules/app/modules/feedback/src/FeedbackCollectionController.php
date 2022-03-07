<?php

namespace Drupal\app_feedback;

use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\CSVBuilder;
use Drupal\app\RequestAdapterBase;

class FeedbackCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $this->adapter = new RequestAdapterBase(\Drupal::request());

        $builder = (new FeedbackCollectionBuilder())
      ->createdStart($this->adapter->createdStartDate)
      ->createdStop($this->adapter->createdEndDate)
      ->orderBy($this->adapter->sortField, $this->adapter->sortDirection);

        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();

        foreach ($this->data as &$row) {
            $row->field_text = nl2br($row->field_text);
        }
    }

    public function csv()
    {
        (new CSVBuilder($this->data, "feedback.csv"))
      ->header("app-email")->text(FeedbackFields::email)
      ->header("app-url")->text(FeedbackFields::url)
      ->header("app-message")->text(FeedbackFields::text)
      ->header("app-created")->callback('created', fn ($value) => date('Y-m-d H:i:s', $value))
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
