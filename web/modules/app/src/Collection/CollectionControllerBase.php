<?php

namespace Drupal\app\Collection;

use Drupal\app\Controller\BaseController;
use Drupal\app\RequestAdapterBase;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class CollectionControllerBase extends BaseController
{
    protected $total;
    protected array $data;
    public ?RequestAdapterBase $adapter;

    private ?int $offset;
    private ?int $limit;

    abstract public function collectionTotal(): int;
    abstract public function collectionData(): array;
    abstract public function paginationOffset(): ?int;
    abstract public function paginationLimit(): ?int;

    public function collection(): JsonResponse
    {
        $this->total = $this->collectionTotal();
        $this->offset = $this->paginationOffset();
        $this->limit = $this->paginationLimit();
        $this->data = $this->collectionData();

        $documentBuilder = new JSONApiDocumentBuilder();
        $documentBuilder->total($this->total)
            ->data($this->data);

        if ($this->offset + $this->limit < $this->total) {
            $documentBuilder->next($this->offset + $this->limit);
        }
        if ($this->offset != 0) {
            $documentBuilder->prev($this->offset - $this->limit);
        }

        $document = $documentBuilder->execute();
        return new JsonResponse($document);
    }
}
