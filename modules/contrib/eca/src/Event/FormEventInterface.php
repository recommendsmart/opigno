<?php

namespace Drupal\eca\Event;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for form API related events.
 */
interface FormEventInterface {

  /**
   * Gets the form array which was involved in the form event.
   *
   * @return array
   */
  public function getForm(): array;

  /**
   * Sets the form array for this event.
   *
   * This is needed, if the receiver of the form array had to modify the array
   * and needs to hand over the changes to the subsequent process.
   *
   * @todo: Analyse if we could change getForm() into a method that returns
   * the form array by reference.
   *
   * @param array $form
   */
  public function setForm(array $form): void;

  /**
   * Gets the form state object which was involved in the form event.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getFormState(): FormStateInterface;

}
