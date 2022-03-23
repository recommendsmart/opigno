<?php

namespace Drupal\eca_form\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_form\Event\FormBuild;
use Drupal\eca_form\Event\FormSubmit;
use Drupal\eca_form\Event\FormValidate;

/**
 * Plugin implementation of the ECA Events for the form API.
 *
 * @EcaEvent(
 *   id = "form",
 *   deriver = "Drupal\eca_form\Plugin\ECA\Event\FormEventDeriver"
 * )
 */
class FormEvent extends EventBase {

  public const FORMBUILD = 'eca.content_entity.formbuild';

  public const FORMVALIDATE = 'eca.content_entity.formvalidate';

  public const FORMSUBMIT = 'eca.content_entity.formsubmit';

  /**
   * @return array[]
   */
  public static function actions(): array {
    $actions = [];
    $actions['form_build'] = [
      'label' => 'Build form',
      'drupal_id' => self::FORMBUILD,
      'drupal_event_class' => FormBuild::class,
      'tags' => Tag::VIEW | Tag::RUNTIME | Tag::BEFORE,
    ];
    $actions['form_validate'] = [
      'label' => 'Validate form',
      'drupal_id' => self::FORMVALIDATE,
      'drupal_event_class' => FormValidate::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_submit'] = [
      'label' => 'Submit form',
      'drupal_id' => self::FORMSUBMIT,
      'drupal_event_class' => FormSubmit::class,
      'tags' => Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $fields = [];
    $fields[] = [
      'name' => 'form_id',
      'label' => 'Form ID',
      'type' => 'String',
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();
    return $configuration['form_id'] ?? '*';
  }

}
