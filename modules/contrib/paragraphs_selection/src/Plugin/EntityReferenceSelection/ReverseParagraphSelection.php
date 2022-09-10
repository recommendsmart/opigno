<?php

namespace Drupal\paragraphs_selection\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "paragraph_reverse",
 *   label = @Translation("Paragraphs Selection"),
 *   group = "paragraph_reverse",
 *   entity_types = {"paragraph"},
 *   weight = 5,
 * )
 */
class ReverseParagraphSelection extends ParagraphSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $field = $form_state->getFormObject()->getEntity()->id();

    foreach ($this->getBundles() as $bundle) {
      $selection_configuration = $bundle->getThirdPartySetting('paragraphs_selection', 'fields');
      foreach ($selection_configuration ?? [] as $selection_field) {
        if (isset($selection_field['id']) && $selection_field['id'] === $field) {
          $form['target_bundles_drag_drop'][$bundle->id()]['enabled']['#default_value'] = !isset($this->configuration['negate']) || $this->configuration['negate'] !== '1';
          $form['target_bundles_drag_drop'][$bundle->id()]['weight']['#default_value'] = $selection_field['weight'];
        }
      }
    }

    return $form;
  }

  /**
   * Returns the sorted allowed types for the field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name
   *   with the following properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   */
  public function getSortedAllowedTypes() {
    if (isset($this->configuration, $this->configuration['self_field_id'])) {
      $field = $this->configuration['self_field_id'];
      $return_bundles = [];
      foreach ($this->getBundles() as $bundle) {
        $selection_configuration = $bundle->getThirdPartySetting('paragraphs_selection', 'fields');
        foreach ($selection_configuration ?? [] as $selection_field) {
          if (isset($selection_field['id']) && $selection_field['id'] === $field) {
            $return_bundles[$bundle->id()] = [
              'label' => $bundle->label(),
              'weight' => $selection_field['weight'] ?? 50,
            ];
          }
        }
      }

      // Sort bundle list by weight.
      $return_bundles = $this->sortBundles($return_bundles, 'weight');

      return $return_bundles;
    }

    return parent::getSortedAllowedTypes();

  }

  /**
   * Get all bundle types based on the target entity type.
   */
  protected function getBundles() {
    $target_type = $this->configuration['target_type'];

    $bundle_entity_type = $this->entityTypeManager->getDefinition($target_type)->getBundleEntityType();

    return $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();
  }

  /**
   * Sort bundles.
   */
  protected function sortBundles($a, $subkey) {
    foreach ($a as $k => $v) {
      $b[$k] = strtolower($v[$subkey]);
    }
    asort($b);
    foreach ($b as $key => $val) {
      $c[$key] = $a[$key];
    }
    return $c;
  }

}
