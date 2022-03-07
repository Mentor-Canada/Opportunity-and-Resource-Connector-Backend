<?php

namespace Drupal\app\Collection;

abstract class CollectionBuilderBase
{
    public $q;
    protected $alias;

    public function __construct($alias = 'node')
    {
        $db = \Drupal::database();
        $this->alias = $alias;
        $this->q = $db->select('node', $alias);
        $this->q->addField('node', 'nid');
        $this->q->leftJoin('node_field_data', 'data', "$alias.nid = data.nid");
        $this->q->addField('data', 'created');
        $this->q->condition('data.langcode', 'en');
        $this->q->addField($alias, 'uuid', 'id');
    }

    protected function addField($fieldName, $langcode = null, $join = true, $column = 'value', $joinTable = 'node')
    {
        $name = str_replace("field_", '', $fieldName);
        if ($join) {
            if ($langcode) {
                $this->q->leftJoin("node__field_$name", "$name", "$joinTable.nid = $name.entity_id AND ($name.langcode IS NULL OR $name.langcode = '$langcode')");
            } else {
                $this->q->leftJoin("node__field_$name", "$name", "$joinTable.nid = $name.entity_id");
            }
        }
        $this->q->addField($name, "field_{$name}_${column}", "field_$name");
    }

    public function start($start)
    {
        if (!empty($start)) {
            $this->q->condition('data.created', $start, '>=');
        }
        return $this;
    }

    public function end($end)
    {
        if (!empty($end)) {
            $this->q->condition('data.created', $end + 24 * 60 * 60, '<=');
        }
        return $this;
    }

    protected function addMultipleValueField($field, $column = 'value')
    {
        $q = \Drupal::database()->select("node__$field", $field);
        $q->addExpression("entity_id", "entity_id");
        $q->addExpression("JSON_ARRAYAGG({$field}_$column)", $field);
        $q->groupBy('entity_id');
        $this->q->leftJoin($q, $field, "{$this->alias}.nid = $field.entity_id");
        $this->q->addField($field, $field, $field);
    }

    public function range($offset, $limit): CollectionBuilderBase
    {
        $this->q->range($offset, $limit);
        return $this;
    }

    public function createdStart($value): CollectionBuilderBase
    {
        if ($value) {
            $this->q->condition('created', $value, '>=');
        }
        return $this;
    }

    public function createdStop($value): CollectionBuilderBase
    {
        if ($value) {
            $this->q->condition('created', $value, '<=');
        }
        return $this;
    }

    public function orderBy($value, $direction): CollectionBuilderBase
    {
        if ($value) {
            $this->q->orderBy($value, $direction);
        }
        return $this;
    }

    public function filter($field, $table = null, $column = 'value', $value = null)
    {
        if (!$value) {
            $value = $this->adapter->getFilter($field);
        }
        if (!empty($value)) {
            $filterMethod = "filter" . ucfirst($field);
            if (method_exists($this, $filterMethod)) {
                return $this->$filterMethod($value);
            }

            if (!is_array($value)) {
                $column = "{$field}_$column";
                if ($table) {
                    $column = "$table.$column";
                }
                if ($value == 'IS NULL') {
                    $this->q->isNull($column);
                } else {
                    $q = \Drupal::database()->select("node__$field", $field);
                    $q->addField($field, "entity_id");
                    $q->condition($column, "%{$value}%", 'LIKE');
                    $result = $q->execute()->fetchCol();
                    if (count($result)) {
                        $this->q->condition('node.nid', $result, 'IN');
                    } else {
                        $this->q->isNull('node.nid');
                    }
                }
            } else {
                $q = \Drupal::database()->select("node__$field", $field);
                $q->addField($field, "entity_id");
                $q->condition("$field.{$field}_$column", $value, "IN");
                $result = $q->execute()->fetchCol();
                if (count($result)) {
                    $this->q->condition('node.nid', $result, 'IN');
                } else {
                    $this->q->isNull('node.nid');
                }
            }
        }
        return $this;
    }

    public function filterMultiple($field, $table = null, $column = 'value', $value = null)
    {
        if (!$value) {
            $value = $this->adapter->getFilter($field);
        }
        if (!empty($value)) {
            $filterMethod = "filter" . ucfirst($field);
            if (method_exists($this, $filterMethod)) {
                return $this->$filterMethod($value);
            }

            if (!is_array($value)) {
                $column = "{$field}_$column";
                if ($table) {
                    $column = "$table.$column";
                }
                if ($value == 'IS NULL') {
                    $this->q->isNull($column);
                } else {
                    $q = \Drupal::database()->select("node__$field");
                    $q->addField("node__$field", "entity_id");
                    $q->condition("{$field}_value", "%{$value}%", 'LIKE');
                    $result = $q->execute()->fetchCol();
                    if (count($result)) {
                        $this->q->condition('node.nid', $result, 'IN');
                    } else {
                        $this->q->isNull('node.nid');
                    }
                }
            } else {
                $q = \Drupal::database()->select("node__$field", $field);
                $q->addField($field, "entity_id");
                $q->condition("$field.{$field}_$column", $value, "IN");
                $result = $q->execute()->fetchCol();
                if (count($result)) {
                    $this->q->condition('node.nid', $result, 'IN');
                } else {
                    $this->q->isNull('node.nid');
                }
            }
        }
        return $this;
    }
}
