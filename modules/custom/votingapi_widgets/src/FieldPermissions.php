<?php

namespace Drupal\votingapi_widgets;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements custom permissions for votingapi_widgets fields.
 */
class FieldPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a FieldPermissions instance.
   */
  public function __construct(EntityFieldManagerInterface $field_manager) {
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_field.manager'));
  }

  /**
   * Implements permissions callback for field_permissions.permissions.yml.
   *
   * @return array
   *   Add custom permissions.
   */
  public function permissions() {
    $map = $this->fieldManager->getFieldMapByFieldType('voting_api_field');
    $perms = [];
    foreach ($map as $entity_type => $info) {
      foreach ($info as $field_name => $field_info) {
        foreach ($field_info['bundles'] as $bundle) {
          $perms['vote on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('Vote on type @type from bundle @bundle in field @field', [
              '@type' => $entity_type,
              '@bundle' => $bundle,
              '@field' => $field_name,
            ]),
          ];
          $perms['edit own vote on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('Edit vote on type @type from bundle @bundle in field @field', [
              '@type' => $entity_type,
              '@bundle' => $bundle,
              '@field' => $field_name,
            ]),
          ];
          $perms['clear own vote on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('Clear vote on type @type from bundle @bundle in field @field', [
              '@type' => $entity_type,
              '@bundle' => $bundle,
              '@field' => $field_name,
            ]),
          ];
          $perms['edit voting status on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('Open or close voting on type @type from bundle @bundle in field @field', [
              '@type' => $entity_type,
              '@bundle' => $bundle,
              '@field' => $field_name,
            ]),
          ];
        }
      }
    }
    return $perms;
  }

}
