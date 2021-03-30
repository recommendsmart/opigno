<?php

namespace Drupal\if_then_else\core;

use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class defined to have common functions for ifthenelse rules processing.
 */
class IfthenelseUtilities extends DefaultPluginManager implements IfthenelseUtilitiesInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $bundleInfo
   *   The entityTypeManager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager,
                                EntityTypeBundleInfo $bundleInfo,
                                EntityFieldManagerInterface $entityFieldManager,
                                ClassResolverInterface $class_resolver,
                                FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entity_manager;
    $this->bundleInfo = $bundleInfo;
    $this->entityFieldManager = $entityFieldManager;
    $this->classResolver = $class_resolver;
    $this->formBuilder = $form_builder;
  }

  /**
   * Check if form class is valid.
   *
   * @param string $form_class
   *   Form Class name to be validated.
   *
   * @return bool
   *   Return if form class is valid or not.
   */
  public static function validateFormClass($form_class) {
    if (is_string($form_class) && class_exists($form_class)) {
      // Generating class object from class string name to compare
      // if it is instance of FormInterface.
      $other_form_class = \Drupal::classResolver($form_class);
      if (is_object($other_form_class) && ($other_form_class instanceof FormInterface)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get content entities and bundles.
   *
   * @return array
   *   Return array of Content entities and their bundles
   */
  public function getContentEntitiesAndBundles() {
    static $content_entity_types = [];

    if (empty($content_entity_types)) {
      // Fetching all entities.
      $entity_type_definitions = $this->entityTypeManager->getDefinitions();
      $bundle_info = $this->bundleInfo->getAllBundleInfo();
      /* @var $definition EntityTypeInterface */
      foreach ($entity_type_definitions as $definition) {

        // Checking if the entity is of type content.
        if ($definition instanceof ContentEntityType) {
          $entity_id = $definition->id();

          $content_entity_types[$entity_id]['entity_id'] = $entity_id;
          $content_entity_types[$entity_id]['label'] = $definition->getLabel()->__toString();
          if (!empty($bundle_info[$entity_id])) {
            // Fetching all bundles of entity.
            $entity_bundles = $bundle_info[$entity_id];

            // Getting label of each bundle of an entity.
            foreach ($entity_bundles as $bundle_id => $bundle) {
              if (is_object($bundle['label'])) {
                $content_entity_types[$entity_id]['bundles'][$bundle_id]['label'] = $bundle['label']->__toString();
              }
              elseif (!is_object($bundle['label']) && !is_array($bundle['label'])) {
                $content_entity_types[$entity_id]['bundles'][$bundle_id]['label'] = $bundle['label'];
              }
              $content_entity_types[$entity_id]['bundles'][$bundle_id]['bundle_id'] = $bundle_id;
            }
          }
        }
      }
    }

    return $content_entity_types;
  }

  /**
   * Get list of fields by entity and bundle id.
   *
   * @param array $content_entity_types
   *   List of content entities and bundles.
   * @param string $return_type
   *   Type of field.
   *
   * @return array
   *   List of fields associated with bundle.
   */
  public function getFieldsByEntityBundleId(array $content_entity_types, $return_type = 'field') {
    static $all_fields_list = [];
    static $listFields = [];
    static $field_type = [];
    static $field_cardinality = [];
    static $extra_fields_name = ['title', 'status', 'uid'];

    if (empty($listFields)) {
      $entity_field_manager = $this->entityFieldManager;

      foreach ($content_entity_types as $entity) {
        $entity_id = $entity['entity_id'];
        if ($entity_id == 'user') {
          $extra_fields_name = array_merge($extra_fields_name, ['name', 'mail']);
        }
        if (!empty($entity['bundles'])) {
          foreach ($entity['bundles'] as $bundle_id => $bundle) {
            $fields = $entity_field_manager->getFieldDefinitions($entity_id, $bundle_id);
            foreach ($fields as $field_name => $field_definition) {
              if (!empty($field_definition->getTargetBundle()) || in_array($field_name, $extra_fields_name)) {
                // List of all fields in an entity bundle.
                $listFields[$entity_id][$bundle_id]['fields'][$field_name]['name'] = $field_definition->getLabel();
                if (is_object($listFields[$entity_id][$bundle_id]['fields'][$field_name]['name'])) {
                  $listFields[$entity_id][$bundle_id]['fields'][$field_name]['name'] = $listFields[$entity_id][$bundle_id]['fields'][$field_name]['name']->__toString();
                }
                if ($field_name == 'status') {
                  $listFields[$entity_id][$bundle_id]['fields'][$field_name]['name'] = $this->t('Status');
                }
                $listFields[$entity_id][$bundle_id]['fields'][$field_name]['code'] = $field_name;
                $all_fields_list[$field_name]['code'] = $field_name;
                $all_fields_list[$field_name]['name'] = $listFields[$entity_id][$bundle_id]['fields'][$field_name]['name'];

                $field_type[$entity_id][$field_name] = $field_definition->getType();
                $field_cardinality[$entity_id][$field_name] = $field_definition->getFieldStorageDefinition()->getCardinality();
              }
            }
          }
        }
      }
    }

    if ($return_type == 'field') {
      return $listFields;
    }
    elseif ($return_type == 'field_type') {
      return $field_type;
    }elseif ($return_type == 'field_cardinality') {
      return $field_cardinality;
    }
    elseif($return_type == 'all'){
      // Converting it to non associative array for working with
      // Vuejs multiselect.
      $listFieldsAssoc = $all_fields_list;
      $all_fields_list = [];
      $i = 0;
      foreach ($listFieldsAssoc as $field) {
        if ($field['name'] == 'Menu link title') {
          $field['name'] = 'Title';
        }
        $all_fields_list[$i]['name'] = $field['name'];
        $all_fields_list[$i]['code'] = $field['code'];
        $i++;
      }
      return $all_fields_list;
    }
  }

  /**
   * Get a specific field by entity, bundle id and field id.
   *
   * @param string $entity_id
   *   Content Entity id.
   * @param string $bundle_id
   *   Bundle id whose fields to be fetched.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Field definition and info for a specific field.
   */
  public function getFieldInfoByEntityBundleId($entity_id, $bundle_id, $field_name) {

    // Get field definitation.
    $field = FieldConfig::loadByName($entity_id, $bundle_id, $field_name);
    $field_type = $field->getType();

    // List text type of field.
    if ($field_type == 'list_string') {
      $field_settings = $field->getFieldStorageDefinition()->getSettings();
      foreach ($field_settings['allowed_values'] as $key => $value) {
        $field_value[] = [
          'key' => $key,
          'name' => $value,
        ];
      }
      $field_type = 'select-input';
      $field_label = $field->getLabel();
    }

    // Plain text type of field.
    if ($field_type == 'string') {
      $field_value = '';
      $field_type = 'text-input';
      $field_label = $field->getLabel();
    }

    // Plain text type of field.
    if ($field_type == 'text_with_summary') {
      $field_value = '';
      $field_type = 'textarea-input';
      $field_label = $field->getLabel();
    }

    // Date field.
    if ($field_type == 'datetime') {
      $field_value = '';
      $field_type = 'textdate-input';
      $field_label = $field->getLabel();
    }

    // Entity reference field.
    if ($field_type == 'entity_reference') {
      $target_entity = $field->getSettings()['target_type'];
      $bundles = $field->getSettings()['handler_settings']['target_bundles'];

      $list_query = $this->entityTypeManager->getStorage($target_entity)
        ->getQuery()->condition('status', 1);
      if ($target_entity == 'taxonomy_term') {
        $list_query->condition('vid', $bundles, "IN");
      }
      elseif ($target_entity == 'node') {
        $list_query->condition('type', $bundles, 'IN');
      }

      $nids = $list_query->execute();

      $entities = $this->entityTypeManager->getStorage($target_entity)->loadMultiple($nids);
      $field_value = [];
      $i = 0;
      foreach ($entities as $entity) {
        $field_value[$i]['key'] = $entity->id();
        if ($target_entity == 'taxonomy_term') {
          $field_value[$i]['name'] = $entity->getName();
        }
        elseif ($target_entity == 'node') {
          $field_value[$i]['name'] = $entity->getTitle();
        }
        elseif ($target_entity == 'user') {
          $field_value[$i]['name'] = $entity->getAccountName();
        }
        $i++;
      }

      if ($target_entity == 'node') {
        $field_type = 'contentreference-input';
      }
      elseif ($target_entity == 'taxonomy_term') {
        $field_type = 'taxonomyreference-input';
      }
      elseif ($target_entity == 'user') {
        $field_type = 'userreference-input';
      }
      $field_label = $field->getLabel();
    }

    // Boolean type of field.
    if ($field_type == 'boolean') {
      $field_value = '';
      $field_type = 'boolean-input';
      $field_label = $field->getLabel();
    }

    // Boolean type of field.
    if ($field_type == 'email') {
      $field_value = '';
      $field_type = 'email-input';
      $field_label = $field->getLabel();
    }

    // Boolean type of field.
    if ($field_type == 'link') {
      $field_value = '';
      $field_type = 'text-input';
      $field_label = $field->getLabel();
    }

    $field_info['type'] = $field_type;
    $field_info['field_name'] = $field_name;
    $field_info['value'] = $field_value;
    $field_info['field_label'] = $field_label;

    // Cardinality of field.
    $cardinality = $field->getFieldStorageDefinition()->getCardinality();
    $field_info['cardinality'] = $cardinality;

    return $field_info;
  }

  /**
   * Get form fields by form class.
   *
   * @param string $form_class
   *   Form class name which extends FormInterface class.
   *
   * @return array
   *   List of fields in form.
   */
  public function getFieldsByFormClass($form_class) {
    $listFields = [];
    if (is_string($form_class) && class_exists($form_class)) {
      // Generating class object from class string name to compare
      // if it is instance of FormInterface.
      $other_form_class = $this->classResolver->getInstanceFromDefinition($form_class);
      if (!is_object($other_form_class) || !($other_form_class instanceof FormInterface)) {
        // @todo
        // exception if the form class entered is wrong.
      }
      else {
        $other_form = $this->formBuilder->getForm($form_class);

        // Iterate all keys of form array.
        foreach ($other_form as $field_name => $field) {
          // Skip all keys which starts with #. they are not fields.
          if (strpos($field_name, '#') === FALSE) {
            if ($field['#type'] == 'hidden' || $field['#type'] == 'token' || $field['#type'] == 'actions' || $field['#type'] == 'details' ||$field['#type'] == 'vertical_tabs') {
              // Skip all keys which can't be made required.
              continue;
            }

            if ($field['#type'] == 'container') {
              if (isset($field['widget'])) {
                foreach ($field['widget'] as $k => $value) {
                  if (strpos($k, '#') !== FALSE) {
                    // Skip all keys which have #.
                    continue;
                  }

                  // If title is translatable object.
                  if (is_object($field['widget'][$k]['#title'])) {
                    $listFields[$field_name]['name'] = $field['widget'][$k]['#title']->__toString();
                  }
                  elseif (is_string($field['widget'][$k]['#title'])) {
                    $listFields[$field_name]['name'] = $field['widget'][$k]['#title'];
                  }
                  $listFields[$field_name]['code'] = $field_name;
                }
              }
            }
            else {
              if (is_object($field['#title'])) {
                $listFields[$field_name]['name'] = $field['#title']->__toString();
              }
              elseif (is_string($field['#title'])) {
                $listFields[$field_name]['name'] = $field['#title'];
              }
              $listFields[$field_name]['code'] = $field_name;
            }
          }
        }
      }
    }

    return $listFields;
  }

  /**
   * Get views name and Display ID list.
   */
  public function getViewsNameAndDisplay() {
    $query = $this->entityTypeManager->getStorage('view')
      ->getQuery()->condition('status', TRUE);
    $views_ids = $query->execute();
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple($views_ids);
    static $views_lists = [];
    foreach ($views as $view) {
      $views_lists[$view->id()]['id'] = $view->id();
      $views_lists[$view->id()]['label'] = $view->label();

      foreach ($view->get('display') as $dislay) {

        $views_lists[$view->id()]['display'][$dislay['id']]['id'] = $dislay['id'];
        $views_lists[$view->id()]['display'][$dislay['id']]['label'] = $dislay['display_title'];
      }
    }
    return $views_lists;
  }

}
