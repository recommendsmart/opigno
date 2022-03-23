<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\eca\Service\Conditions;
use Drupal\eca_form\Event\FormBase;

/**
 * Require a form field.
 *
 * @Action(
 *   id = "eca_form_field_require",
 *   label = @Translation("Form field: require"),
 *   type = "form"
 * )
 */
class FormFieldRequire extends FormFieldActionBase {

  /**
   * Helper function to set all nested form field levels to required or not.
   *
   * @param array $element
   *   The form field that should be set to required or not.
   * @param bool $flag
   *   TRUE, if that field should be required, FALSE otherwise.
   */
  protected function setRequired(array &$element, bool $flag): void {
    foreach (Element::children($element, TRUE) as $key) {
      if (isset($element[$key]) && $element[$key]) {
        $this->setRequired($element[$key], $flag);
      }
    }
    $element['#required'] = $flag;
    if (!$flag && isset($element['#type']) && $element['#type'] === 'textfield' && $element['#default_value'] === NULL) {
      $element['#default_value'] = '-';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($this->event instanceof FormBase) {
      $form_state = $this->event->getFormState();
      if (!$form_state->isProcessingInput()) {
        $form = $this->event->getForm();
        $this->setRequired($form[$this->configuration['field_name']], ($this->configuration['flag'] === Conditions::OPTION_YES));
        $this->event->setForm($form);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'flag' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    parent::buildConfigurationForm($form, $form_state);
    $form['flag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->configuration['flag'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['flag'] = $form_state->getValue('flag');
    parent::submitConfigurationForm($form, $form_state);
  }

}
