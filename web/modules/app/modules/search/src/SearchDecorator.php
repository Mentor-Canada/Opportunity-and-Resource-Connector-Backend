<?php

namespace Drupal\app_search;

use Drupal\node\Entity\Node;

class SearchDecorator
{
    public const TYPE = 'search';

    public Node $node;
}
