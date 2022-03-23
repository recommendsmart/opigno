<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentEntityPrepareForm
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityPrepareForm extends ContentEntityBaseEntity {

  /**
   * @var string
   */
  protected string $operation;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected FormStateInterface $formState;

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $operation
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function __construct(ContentEntityInterface $entity, string $operation, FormStateInterface $form_state) {
    parent::__construct($entity);
    $this->operation = $operation;
    $this->formState = $form_state;
  }

  /**
   * @return string
   */
  public function getOperation(): string {
    return $this->operation;
  }

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

}
