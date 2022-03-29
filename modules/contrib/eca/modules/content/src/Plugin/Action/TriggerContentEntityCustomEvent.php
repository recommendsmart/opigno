<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca_content\Event\ContentEntityCustomEvent;
use Drupal\eca_content\Event\ContentEntityEvents;

/**
 * Trigger a content entity custom event.
 *
 * @Action(
 *   id = "eca_trigger_content_entity_custom_event",
 *   label = @Translation("Trigger a custom event (entity-aware)"),
 *   type = "entity"
 * )
 */
class TriggerContentEntityCustomEvent extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $event_id = $this->tokenServices->replaceClear($this->configuration['event_id']);
    $event = new ContentEntityCustomEvent($entity, $event_id, ['event' => $this->event]);
    $event->addTokenNamesFromString($this->configuration['tokens']);
    \Drupal::service('event_dispatcher')->dispatch($event, ContentEntityEvents::CUSTOM);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'event_id' => '',
      'tokens' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event ID'),
      '#default_value' => $this->configuration['event_id'],
      '#weight' => -10,
    ];
    $form['tokens'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tokens to forward'),
      '#default_value' => $this->configuration['tokens'],
      '#description' => $this->t('Comma separated list of token names from the current context, that will be forwarded to the triggered event and its process.'),
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['event_id'] = $form_state->getValue('event_id');
    $this->configuration['tokens'] = $form_state->getValue('tokens');
    parent::submitConfigurationForm($form, $form_state);
  }

}
