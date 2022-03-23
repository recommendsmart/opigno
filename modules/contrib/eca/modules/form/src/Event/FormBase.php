<?php

namespace Drupal\eca_form\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\FormEventInterface;

/**
 * Class FormBase
 *
 * @package Drupal\eca_form\Event
 */
abstract class FormBase extends Event implements ConditionalApplianceInterface, FormEventInterface {

  /**
   * @var array
   */
  protected array $form;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected FormStateInterface $formState;

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function __construct(array $form, FormStateInterface $form_state) {
    $this->form = $form;
    $this->formState = $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $form = $this->getForm();
    return (($wildcard === '*') || (isset($form['#form_id']) && $wildcard === $form['#form_id']));
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $form = $this->getForm();
    return isset($form['#form_id'], $arguments['form_id']) && $form['#form_id'] === $arguments['form_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(): array {
    return $this->form;
  }

  /**
   * {@inheritdoc}
   */
  public function setForm(array $form): void {
    $this->form = $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

}
