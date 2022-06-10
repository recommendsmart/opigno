<?php

namespace Drupal\eca_form\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_form\Event\FormAfterBuild;
use Drupal\eca_form\Event\FormBuild;
use Drupal\eca_form\Event\FormEvents;
use Drupal\eca_form\Event\FormProcess;
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

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];
    $actions['form_build'] = [
      'label' => 'Build form',
      'event_name' => FormEvents::BUILD,
      'event_class' => FormBuild::class,
      'tags' => Tag::VIEW | Tag::RUNTIME | Tag::BEFORE,
    ];
    $actions['form_process'] = [
      'label' => 'Process form',
      'event_name' => FormEvents::PROCESS,
      'event_class' => FormProcess::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_after_build'] = [
      'label' => 'After build form',
      'event_name' => FormEvents::AFTER_BUILD,
      'event_class' => FormAfterBuild::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_validate'] = [
      'label' => 'Validate form',
      'event_name' => FormEvents::VALIDATE,
      'event_class' => FormValidate::class,
      'tags' => Tag::READ | Tag::RUNTIME | Tag::AFTER,
    ];
    $actions['form_submit'] = [
      'label' => 'Submit form',
      'event_name' => FormEvents::SUBMIT,
      'event_class' => FormSubmit::class,
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
      'label' => 'Restrict by form ID',
      'type' => 'String',
      'description' => 'The form ID can be mostly found in the HTML &lt;form&gt; element as "id" attribute.',
    ];
    $fields[] = [
      'name' => 'entity_type_id',
      'label' => 'Restrict by entity type ID',
      'type' => 'String',
      'description' => 'Example: <em>node, taxonomy_term, user</em>',
    ];
    $fields[] = [
      'name' => 'bundle',
      'label' => 'Restrict by entity bundle',
      'type' => 'String',
      'description' => 'Example: <em>article, tags</em>',
    ];
    $fields[] = [
      'name' => 'operation',
      'label' => 'Restrict by operation',
      'type' => 'String',
      'description' => 'Example: <em>default, save, delete</em>',
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();

    $wildcard = '';
    $form_ids = [];
    if (!empty($configuration['form_id'])) {
      foreach (explode(',', $configuration['form_id']) as $form_id) {
        $form_id = strtolower(trim(str_replace('-', '_', $form_id)));
        if ($form_id !== '') {
          $form_ids[] = $form_id;
        }
      }
    }
    if ($form_ids) {
      $wildcard .= implode(',', $form_ids);
    }
    else {
      $wildcard .= '*';
    }

    $wildcard .= ':';
    $entity_type_ids = [];
    if (!empty($configuration['entity_type_id'])) {
      foreach (explode(',', $configuration['entity_type_id']) as $entity_type_id) {
        $entity_type_id = strtolower(trim($entity_type_id));
        if ($entity_type_id !== '') {
          $entity_type_ids[] = $entity_type_id;
        }
      }
    }
    if ($entity_type_ids) {
      $wildcard .= implode(',', $entity_type_ids);
    }
    else {
      $wildcard .= '*';
    }

    $wildcard .= ':';
    $bundles = [];
    if (!empty($configuration['bundle'])) {
      foreach (explode(',', $configuration['bundle']) as $bundle) {
        $bundle = strtolower(trim($bundle));
        if ($bundle !== '') {
          $bundles[] = $bundle;
        }
      }
    }
    if ($bundles) {
      $wildcard .= implode(',', $bundles);
    }
    else {
      $wildcard .= '*';
    }

    $wildcard .= ':';
    $operations = [];
    if (!empty($configuration['operation'])) {
      foreach (explode(',', $configuration['operation']) as $operation) {
        $operation = trim($operation);
        if ($operation !== '') {
          $operations[] = $operation;
        }
      }
    }
    if ($operations) {
      $wildcard .= implode(',', $operations);
    }
    else {
      $wildcard .= '*';
    }

    return $wildcard;
  }

}
