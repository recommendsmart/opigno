<?php

namespace Drupal\eca_form\Event;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormBuild
 *
 * @package Drupal\eca_form\Event
 */
class FormBuild extends FormBase {

  /**
   * @var string
   */
  protected string $formId;

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param string $form_id
   */
  public function __construct(array $form, FormStateInterface $form_state, string $form_id) {
    parent::__construct($form, $form_state);
    $this->formId = $form_id;
  }

  /**
   * @return string
   */
  public function getFormId(): string {
    return $this->formId;
  }

}
