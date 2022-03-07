<?php

namespace Drupal\app\Affiliates\Zip;

class ZipCollectionBuilder
{
    public $q;

    private $_limit;
    private $_offset;

    public function __construct()
    {
        $this->q = \Drupal::database()->select('zipCodes', 't');
        $this->q->addField('t', 'zip');
        $this->q->addField('t', 'state');
        $this->q->addField('t', 'city');
        $this->q->addField('t', 'county');
    }

    public function condition($key, $value, $operator = "="): ZipCollectionBuilder
    {
        if ($value) {
            $this->q->condition($key, $value, $operator);
        }
        return $this;
    }

    public function limit($value): ZipCollectionBuilder
    {
        $this->_limit = $value;
        return $this;
    }

    public function offset($value): ZipCollectionBuilder
    {
        $this->_offset = $value;
        return $this;
    }

    public function build(): ZipCollection
    {
        $total = $this->q->countQuery()->execute()->fetchField();
        $this->q->range($this->_offset, $this->_limit);
        $rows = $this->q->execute()->fetchAll();
        return new ZipCollection($rows, $total, $this->_limit, $this->_offset);
    }
}
