<?php

namespace Drupal\arch_product\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assigns ownership of a product to a user.
 *
 * @Action(
 *   id = "product_assign_owner_action",
 *   label = @Translation("Change the creator of product", context = "arch_product"),
 *   type = "product"
 * )
 */
class AssignOwnerProduct extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->connection = $connection;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $entity */
    $entity->setOwnerId($this->configuration['owner_uid'])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'owner_uid' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $description = $this->t('The username of the user to which you would like to assign ownership.');
    $count = $this->connection->query("SELECT COUNT(*) FROM {users}")->fetchField();

    // Use dropdown for fewer than 200 users; textbox for more than that.
    if (intval($count) < 200) {
      $options = [];
      $result = $this->connection->query("SELECT uid, name FROM {users_field_data} WHERE uid > 0 AND default_langcode = 1 ORDER BY name");
      foreach ($result as $data) {
        $options[$data->uid] = $data->name;
      }
      $form['owner_uid'] = [
        '#type' => 'select',
        '#title' => $this->t('Username'),
        '#default_value' => $this->configuration['owner_uid'],
        '#options' => $options,
        '#description' => $description,
      ];
    }
    else {
      $form['owner_uid'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Username'),
        '#target_type' => 'user',
        '#selection_setttings' => [
          'include_anonymous' => FALSE,
        ],
        '#default_value' => $this->userStorage->load($this->configuration['owner_uid']),
        // Validation is done in static::validateConfigurationForm().
        '#validate_reference' => FALSE,
        '#size' => '6',
        '#maxlength' => '60',
        '#description' => $description,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $exists = (bool) $this->connection->queryRange('SELECT 1 FROM {users_field_data} WHERE uid = :uid AND default_langcode = 1', 0, 1, [':uid' => $form_state->getValue('owner_uid')])->fetchField();
    if (!$exists) {
      $form_state->setErrorByName('owner_uid', $this->t('Enter a valid username.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['owner_uid'] = $form_state->getValue('owner_uid');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->getOwner()->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
