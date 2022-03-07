<?php

namespace Drupal\app_inquiry;

use Drupal\node\Entity\Node;

class FilterDecorator
{
    public $id;
    public $created;

    public $title;
    public $start_time;
    public $end_time;
    public $entityId;
    public $date_mode;
    public $type;

    private $node;

    public function save()
    {
        $data = [
            'type' => 'filter',
            FilterFields::title => $this->title,
            FilterFields::start_time => $this->start_time,
            FilterFields::end_time => $this->end_time,
            FilterFields::filter_entity => $this->entityId,
            FilterFields::date_mode => $this->date_mode,
            FilterFields::type => $this->type
        ];
        $this->node = Node::create($data);
        $this->node->save();

        $this->id = $this->node->uuid();
        $this->created = $this->node->getCreatedTime();
    }
}
