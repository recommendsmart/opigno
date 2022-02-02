<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\digital_signage_framework\PlatformPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class SyncDevices extends ConfirmFormBase {

  /**
   * @var \Drupal\digital_signage_framework\PlatformPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(PlatformPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.digital_signage_platform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'digital_signage_device_sync_devices';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to sync all devices?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.digital_signage_device.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->pluginManager->syncDevices();
    $this->messenger()->addStatus($this->t('Device synchronisation completed!'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
