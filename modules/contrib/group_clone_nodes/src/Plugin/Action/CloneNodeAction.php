<?php

namespace Drupal\group_clone_nodes\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Clone nodes bulk operations listing page.
 *
 * @Action(
 *   id = "group_clone_nodes_clone_node_action",
 *   label = @Translation("Group clone nodes"),
 *   type = "node",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "Group clone nodes: Manage access of clone node listing
 *     page",
 *     "_custom_access" = TRUE,
 *   },
 * )
 */
class CloneNodeAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * Get all groups.
   */
  public function getAllGroups() {
    $database = \Drupal::database();
    $groups_query = $database->select('groups_field_data', 'gfd');
    $groups_query->fields('gfd', ['label', 'id']);
    $groups_arr = $groups_query->execute()->fetchAll();
    $groups = [];
    if (count($groups_arr) > 0) {
      foreach ($groups_arr as $key => $value) {
        $groups[$value->id] = $value->label;
      }
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $all_groups = $this->getAllGroups();
    $all_groups[NULL] = '--Select--';
    asort($all_groups);
    $form['select_group'] = [
      '#type' => 'select',
      '#title' => 'Group',
      '#options' => $all_groups,
      '#weight' => -1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['select_group'] = $form_state->getValue('select_group');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $gid = $this->configuration['select_group'];
    if (empty($gid)) {
      \Drupal::messenger()->addError(t('Invalid group or empty group.'));
      return $this->t('No nodes cloned.');
    }
    $node = Node::load($entity->id());

    if (is_object($node)) {
      $clonednode = $node->createDuplicate();
      // Loop over entity fields and duplicate nested paragraphs.
      foreach ($clonednode->getFields() as $field) {
        if ($field->getFieldDefinition()->getType() == 'entity_reference_revisions') {
          if ($field->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type') == "paragraph") {
            $paragraphs = [];
            foreach ($field as $item) {
              $paragraphs[] = $item->entity->createDuplicate();
            }
            $fieldname = $field->getFieldDefinition()->getName();
            $clonednode->$fieldname = $paragraphs;
          }
        }
      }

      // Set group language and group for the cloned content.
      $entity_type_manager = \Drupal::service('entity_type.manager');
      $group = $entity_type_manager->getStorage('group')->load($gid);

      $clonednode->changed->value = time();

      // Append cloned in the title.
      $clonednode->set('title', 'Cloned - ' . $node->getTitle());

      // Cloned node should be unpublished.
      $clonednode->set('status', 0);

      $clonednode->save();

      $group->addContent($clonednode, 'group_node:' . $clonednode->getType());
      $group->save();

      return $this->t('No. of cloned nodes.');
    }
    else {
      return $this->t('No nodes cloned.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against
    // them as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }

}
