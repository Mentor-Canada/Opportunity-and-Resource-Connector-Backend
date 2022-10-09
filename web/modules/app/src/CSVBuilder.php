<?php

namespace Drupal\app;

use Drupal\app\Utils\Utils;

class CSVBuilder
{
    private array $data = [];
    private array $headers = [];
    private array $callbacks = [];
    private $filename;

    public function __construct($data, $filename)
    {
        $this->data = $data;
        $this->filename = $filename;
    }

    public function header($header): CSVBuilder
    {
        $this->headers[] = strval(t($header));
        return $this;
    }

    public function text($key, $localized = false): CSVBuilder
    {
        $this->callbacks[] = function ($row) use ($key, $localized) {
            $value = $row->$key;
            if (!$value) {
                return "";
            }
            if ($value == "null") {
                return "";
            }
            if ($localized && !empty($value)) {
                return t($value);
            }
            return $value;
        };
        return $this;
    }

    public function callback($key, $callback): CSVBuilder
    {
        $this->callbacks[] = function ($row) use ($key, $callback) {
            return $callback($row->$key, $row);
        };
        return $this;
    }

    public function json($key, $localized = false): CSVBuilder
    {
        $this->callbacks[] = function ($row) use ($key, $localized) {
            $values = json_decode($row->$key);
            if (!is_array($values)) {
                return "";
            }
            if ($localized) {
                $values = array_map(fn ($a) => !empty($a) ? t($a) : $a, $values);
            }
            return implode(", ", $values);
        };
        return $this;
    }

    public function render()
    {
        $csv = [$this->headers];
        foreach ($this->data as $record) {
            $columns = [];
            foreach ($this->callbacks as $callback) {
                $columns[] = $callback($record);
            }
            $csv[] = $columns;
        }
        Utils::exporter($csv, $this->filename);
        exit;
    }
}
