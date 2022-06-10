<?php

namespace Drupal\node_singles\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_singles\Service\NodeSinglesInterface;

/**
 * Alters the node edit form.
 */
class NodeFormEventSubscriber {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The node singles service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesInterface
   */
  protected $singles;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route match.
   * @param \Drupal\node_singles\Service\NodeSinglesInterface $singles
   *   The node singles service.
   */
  public function __construct(RouteMatchInterface $current_route_match, NodeSinglesInterface $singles) {
    $this->currentRouteMatch = $current_route_match;
    $this->singles = $singles;
  }

  /**
   * Overrides the page title & hides irrelevant information.
   */
  public function formAlter(array &$form): void {
    if ($this->currentRouteMatch->getRouteName() !== 'entity.node.edit_form') {
      return;
    }

    $node = $this->currentRouteMatch->getParameter('node');
    if (empty($node) || !$this->singles->getSingleByBundle($node->bundle())) {
      return;
    }

    // Remove duplicate name from the page title.
    $form['#title'] = $this->t('Edit %type single', [
      '%type' => node_get_type_label($node),
    ]);

    // Hide authoring information since it's irrelevant.
    if (isset($form['meta']['author'])) {
      $form['meta']['author']['#access'] = FALSE;
    }

    // Hide publishing information since it's irrelevant.
    if (isset($form['meta']['published'])) {
      $form['meta']['author']['#access'] = FALSE;
    }
  }

}
