<?php

namespace Drupal\paragraphs_selection_paragraphs_sets_support\EventSubscriber;

use Drupal\paragraphs_sets_alter\Event\ParagraphsSetsAlterEvents;
use Drupal\paragraphs_sets_alter\Event\ParagraphsSetsAlterUseParagraphsSet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ParagraphsSelectionParagraphsSetsSupportUseParagraphsSetSubscriber
 *
 * @package Drupal\paragraphs_selection_paragraphs_sets_support\EventSubscriber
 */
class ParagraphsSelectionParagraphsSetsSupportUseParagraphsSetSubscriber implements EventSubscriberInterface {

  /**
   * @inheritdoc
   *
   * @return array|mixed
   */
  public static function getSubscribedEvents() {
    $events[ParagraphsSetsAlterEvents::USE_PARAGRAPHS_SET][] = ['useParagraphsSet'];
    return $events;
  }

  public function useParagraphsSet(ParagraphsSetsAlterUseParagraphsSet $event) {

    $context = $event->getContext();
    $field = isset($context['field']) ? $context['field'] : false;
    if ($field) {

      /** @var \Drupal\field\FieldConfigInterface $field_config */
      $field_config = \Drupal::service('entity_type.manager')
        ->getStorage('field_config')
        ->load($field);

      // Only proceed if in the context of a ReverseParagraphSelection selection handler.
      if ('paragraph_reverse' === $field_config->getSetting('handler')) {
        $selection_configuration = $event->getSet()->getThirdPartySetting('paragraphs_selection', 'selection');
        if ($selection_configuration['fields'] && count($selection_configuration['fields']) > 0) {
          $event->setUsable(FALSE);
          foreach ($selection_configuration['fields'] as $selection_field) {
            if (isset($selection_field['name']) && $selection_field['name'] === $field) {
              $event->setUsable(TRUE);
              $element = $event->getElement();
              $element['#weight'] = $selection_field['weight'];
              $event->setElement($element);
            }
          }
        } else {
          $event->setUsable(FALSE);
        }
      }

    }

  }

}
