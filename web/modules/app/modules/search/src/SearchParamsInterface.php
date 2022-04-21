<?php

namespace Drupal\app_search;

interface SearchParamsInterface
{
    public function distance(): ?string;
    public function lat(): ?string;
    public function lng(): ?string;
    public function location(): ?object;
    public function postalCode(): ?string;
    public function limit(): ?int;
    public function offset(): ?int;
    public function communityBased(): ?string;
    public function siteBased(): ?string;
    public function eMentoring(): ?string;

    public function grade(): ?array;
    public function focus(): ?array;
    public function age(): ?array;
    public function youth(): ?array;
    public function type(): ?array;
}
