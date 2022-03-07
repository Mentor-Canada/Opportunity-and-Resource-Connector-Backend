<?php

namespace Drupal\app\Affiliates;

use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;

class AffiliateCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);

        $this->adapter = new AffiliateRequestAdapter(\Drupal::request());
        $builder = (new AffiliateCollectionBuilder())
      ->createdStart($this->adapter->createdStartDate)
      ->createdStop($this->adapter->createdEndDate)
      ->title($this->adapter->title)
    ;
        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();
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
