<?php

namespace Drupal\app_notify;

use Drupal\node\Entity\Node;

class NotifyDecorator
{
    public $node;

    public $email;
    public $searchId;
    public $firstName;
    public $lastName;
    public $created;

    public function save()
    {
        $data = [
            'type' => 'notify',
            NotifyFields::email => $this->email,
            NotifyFields::searchId => $this->searchId,
            NotifyFields::firstName => $this->firstName,
            NotifyFields::lastName => $this->lastName
        ];
        if ($this->created) {
            $data['created'] = $this->created;
        }
        $this->node = Node::create($data);
        $this->node->save();
    }
}
