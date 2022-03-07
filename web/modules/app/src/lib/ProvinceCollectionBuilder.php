<?php

namespace Drupal\app\lib;

use Drupal\app\Models\Option;

class ProvinceCollectionBuilder
{
    private array $rows;

    public function query($q): ProvinceCollectionBuilder
    {
        $db = \Drupal::database();
        $this->rows = $db->query($q)->fetchAll();
        return $this;
    }

    public function execute(): array
    {
        $result = [];
        $emptyProvince = false;
        foreach ($this->rows as $row) {
            if (empty($row->province)) {
                $emptyProvince = true;
                continue;
            }
            $result[] = new Option($row->province);
        }
        usort($result, fn ($a, $b) => strcmp($a->name, $b->name));
        if ($emptyProvince) {
            $result[] = new Option('app-unknown');
        }
        return $result;
    }
}
