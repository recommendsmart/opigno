<?php

namespace Drupal\crm_core_user_sync\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin looks up relations of users to CRM individuals.
 *
 * @codingStandardsIgnoreStart
 * Example:
 * @code
 *   field_crm_id:
 *     plugin: crm_core_user_relation_lookup
 *     source: id
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 *
 * @MigrateProcessPlugin(
 *   id = "crm_core_user_relation_lookup",
 *   handle_multiples = TRUE
 * )
 */
class RelationLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The selection plugin.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * User Sync Relation.
   *
   * @var \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface
   */
  protected $crmCoreUserSyncRelation;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    MigrationInterface $migration,
    CrmCoreUserSyncRelationInterface $crmCoreUserSyncRelation
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->migration = $migration;
    $this->crmCoreUserSyncRelation = $crmCoreUserSyncRelation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition,
    MigrationInterface $migration = NULL
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $migration,
      $container->get('crm_core_user_sync.relation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform(
    $value,
    MigrateExecutableInterface $migrateExecutable,
    Row $row,
    $destinationProperty
  ) {
    // If the source data is an empty array, return the same.
    if (is_array($value) && count($value) === 0) {
      return [];
    }
    return $this->query($value);
  }

  /**
   * Lookup value.
   *
   * @param mixed $value
   *   The value to query.
   *
   * @return mixed|null
   *   Entity id if the queried entity exists. Otherwise NULL.
   */
  protected function query($value) {
    $multiple = is_array($value);
    $results = [];

    if ($multiple) {
      foreach ($value as $id) {
        $results[] = $this->crmCoreUserSyncRelation->getIndividualIdFromUserId($id);
      }
    } else {
      $results[] = $this->crmCoreUserSyncRelation->getIndividualIdFromUserId($value);
    }
    return $multiple ? array_values($results) : reset($results);
  }

}
