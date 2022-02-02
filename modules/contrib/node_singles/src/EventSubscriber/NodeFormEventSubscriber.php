<?php

namespace Drupal\node_singles\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_singles\Service\NodeSinglesInterface;

class NodeFormEventSubscriber
{
    use StringTranslationTrait;

    /** @var RouteMatchInterface */
    protected $currentRouteMatch;
    /** @var NodeSinglesInterface */
    protected $wmSingles;

    public function __construct(
        RouteMatchInterface $currentRouteMatch,
        NodeSinglesInterface $wmSingles
    ) {
        $this->currentRouteMatch = $currentRouteMatch;
        $this->wmSingles = $wmSingles;
    }

    public function formAlter(array &$form): void
    {
        if ($this->currentRouteMatch->getRouteName() !== 'entity.node.edit_form') {
            return;
        }

        $node = $this->currentRouteMatch->getParameter('node');
        if (empty($node) || !$this->wmSingles->getSingleByBundle($node->bundle())) {
            return;
        }

        // Remove duplicate name from the page title
        $form['#title'] = $this->t('Edit %type single', [
            '%type' => node_get_type_label($node),
        ]);

        // Hide authoring information since it's irrelevant
        if (isset($form['meta']['author'])) {
            $form['meta']['author']['#access'] = false;
        }

        // Hide publishing information since it's irrelevant
        if (isset($form['meta']['published'])) {
            $form['meta']['author']['#access'] = false;
        }
    }
}
