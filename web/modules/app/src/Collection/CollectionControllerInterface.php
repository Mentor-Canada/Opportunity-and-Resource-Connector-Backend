<?php

namespace Drupal\app\Collection;

interface CollectionControllerInterface
{
    public function collectionTotal(): int;
    public function collectionData(): array;
    public function paginationOffset(): ?int;
    public function paginationLimit(): ?int;
}
