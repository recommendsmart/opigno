<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca_form\Event\FormBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Validate a entity reference form field for leaf taxonomy term.
 *
 * @Action(
 *   id = "eca_form_field_validate_ref_leaf_terms",
 *   label = @Translation("Validate form field: referenced taxonomy terms should not have children"),
 *   type = "form"
 * )
 */
class FormFieldValidateRefLeafTerms extends FormFieldValidateActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (!$this->event instanceof FormBase) {
      return;
    }

    $formState = $this->event->getFormState();
    $formObject = $formState->getFormObject();

    // @todo: can we find out the target type without referring to an entity?
    $field_name = $this->configuration['field_name'];
    $field = NULL;
    if ($formObject instanceof EntityFormInterface) {
      $entity = $formObject->getEntity();
      if (($entity instanceof FieldableEntityInterface) && $entity->hasField($field_name)) {
        /** @var FieldItemListInterface $field */
        $field = $entity->get($field_name);
      }
    }

    if (!($field instanceof EntityReferenceFieldItemList) || $field->isEmpty()) {
      return;
    }

    $target_type = $field->getSetting('target_type');
    if ($target_type !== 'taxonomy_term') {
      return;
    }

    if (!$formState->hasValue($field_name)) {
      return;
    }

    $tids = array_column($formState->getValue($field_name), 'target_id');
    /** @var TermStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($target_type);
    $terms = $storage->loadMultiple($tids);
    if (!$terms) {
      $this->messenger()
        ->addWarning($this->t('Unable to load referenced taxonomy terms.'));
      return;
    }

    $prefixes = (empty($this->configuration['term_prefixes']))
      ? []
      : array_map('trim', explode(',', $this->configuration['term_prefixes']));
    /** @var TermInterface $term */
    foreach ($terms as $term) {
      $name = $term->getName();
      if ($this->startsWith($prefixes, $name)) {
        continue;
      }

      $children = $storage->getChildren($term);
      if ($children) {
        $this->setError($this->t('Referenced term (@name) must not have children.', [
          '@name' => $name,
        ]));
      }
    }
  }

  /**
   * Check if term name starts with prefix.
   *
   * @param array $prefixes
   * @param string $name
   *
   * @return bool
   */
  protected function startsWith(array $prefixes, string $name): bool {
    if (count($prefixes)) {
      foreach ($prefixes as $prefix) {
        if (mb_strpos($name, $prefix) === 0) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
        'term_prefixes' => [],
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['term_prefixes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skip validation for term with prefix'),
      '#description' => $this->t('Enter prefixes comma separated.'),
      '#default_value' => $this->configuration['term_prefixes'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['term_prefixes'] = $form_state->getValue('term_prefixes');
    parent::submitConfigurationForm($form, $form_state);
  }

}
