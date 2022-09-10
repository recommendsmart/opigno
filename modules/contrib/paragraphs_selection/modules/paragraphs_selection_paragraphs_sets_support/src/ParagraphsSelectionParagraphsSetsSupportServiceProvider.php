<?php

namespace Drupal\paragraphs_selection_paragraphs_sets_support;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\paragraphs_selection_paragraphs_sets_support\EventSubscriber\ParagraphsSelectionParagraphsSetsSupportUseParagraphsSetSubscriber;

/**
 * Registers services in the container.
 */
class ParagraphsSelectionParagraphsSetsSupportServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    $container
      ->register('paragraphs_selection_paragraphs_sets_support.use_paragraphs_set_subscriber', ParagraphsSelectionParagraphsSetsSupportUseParagraphsSetSubscriber::class)
      ->addTag('event_subscriber');

  }

}
