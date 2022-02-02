<?php

namespace Drupal\digital_signage_framework;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for digital_signage_platform plugins.
 */
abstract class PlatformPluginBase extends PluginBase implements PlatformInterface, ContainerFactoryPluginInterface {

  protected const PREFIX_PLATFORM_LAST_SYNC = 'digital_signage_platform.last_sync.device.';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\digital_signage_framework\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $sharedTempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager, Messenger $messenger, ConfigFactoryInterface $config_factory, Renderer $renderer, TimeInterface $time, DateFormatterInterface $date_formatter, State $state, ClientFactory $client_factory, SharedTempStoreFactory $shared_temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
    $this->state = $state;
    $this->clientFactory = $client_factory;
    $this->sharedTempStore = $shared_temp_store_factory->get('digital_signage_platform');
    $this->init();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('digital_signage_framework.renderer'),
      $container->get('datetime.time'),
      $container->get('date.formatter'),
      $container->get('state'),
      $container->get('http_client_factory'),
      $container->get('tempstore.shared')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * @return string
   */
  private function getLastSyncKey(): string {
    return self::PREFIX_PLATFORM_LAST_SYNC . $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  final public function syncDevices() {
    /** @var \Drupal\digital_signage_framework\Entity\Device[] $existingDevices */
    $existingDevices = $this->entityTypeManager->getStorage('digital_signage_device')->loadByProperties([
      'bundle' => $this->getPluginId(),
    ]);

    $platformDevices = $this->getPlatformDevices();

    foreach ($platformDevices as $platformDevice) {
      $found = FALSE;
      foreach ($existingDevices as $key => $existingDevice) {
        if ($existingDevice->extId() === $platformDevice->extId()) {
          $found = TRUE;
          // Update existing device.
          $this->update($existingDevice, $platformDevice);

          // Remove from array.
          unset($existingDevices[$key]);
        }
      }
      if (!$found) {
        // Save new device.
        $platformDevice->scheduleUpdate();
      }
    }

    foreach ($existingDevices as $existingDevice) {
      if ($existingDevice->isEnabled()) {
        // Unpublish devices that no longer exist.
        $existingDevice->setStatus(FALSE)
          ->save();
      }
    }
    $this->state->set($this->getLastSyncKey(), $this->time->getRequestTime());
  }

  /**
   * Compares all field values and if at least one got changed, saves the
   * updated entity.
   *
   * @param \Drupal\digital_signage_framework\Entity\Device $existingDevice
   * @param \Drupal\digital_signage_framework\Entity\Device $platformDevice
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function update($existingDevice, $platformDevice) {
    $field_names = [
      'title',
      'description',
    ];
    $changed = FALSE;
    foreach ($field_names as $field_name) {
      if ($existingDevice->get($field_name)->getValue() !== $platformDevice->get($field_name)->getValue()) {
        $existingDevice->set($field_name, $platformDevice->get($field_name)->getValue());
        $changed = TRUE;
      }
    }
    if ($existingDevice->getWidth() !== $platformDevice->getWidth() || $existingDevice->getHeight() !== $platformDevice->getHeight()) {
      $existingDevice->set('size', $platformDevice->get('size')->getValue());
      $changed = TRUE;
    }
    foreach (array_diff($platformDevice->getSegmentIds(), $existingDevice->getSegmentIds()) as $newId) {
      if (($term = Term::load($newId)) && $existingDevice->addSegment($term->label())) {
        $changed = TRUE;
      }
    }
      if (!$existingDevice->isEnabled()) {
      $existingDevice->setStatus(TRUE);
      $changed = TRUE;
    }
    if ($changed) {
      $existingDevice->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function showSlideReport(DeviceInterface $device) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  final public function storeRecord($id, $value) {
    $this->sharedTempStore->set($id, $value);
  }

  /**
   * {@inheritdoc}
   */
  final public function deleteRecord($id) {
    $this->sharedTempStore->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  final public function getRecord($id) {
    return $this->sharedTempStore->get($id);
  }

}
