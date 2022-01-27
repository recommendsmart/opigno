<?php

namespace Drupal\node_singles\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\node_singles\Service\NodeSinglesInterface;

class NodeTypeUpdateEventSubscriber
{
    /** @var NodeSinglesInterface */
    private $wmSingles;

    public function __construct(
        NodeSinglesInterface $wmSingles
    ) {
        $this->wmSingles = $wmSingles;
    }

    public function checkForSingles(EntityInterface $entity): void
    {
        if ($entity instanceof NodeTypeInterface) {
            $this->wmSingles->checkSingle($entity);
        }
    }
}
