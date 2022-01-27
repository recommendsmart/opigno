<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\digital_signage_framework\Emergency;
use Drupal\digital_signage_framework\ScheduleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enable or disable emergency mode on devices.
 */
class EmergencyMode extends ActionBase {

  /**
   * @var \Drupal\digital_signage_framework\Emergency
   */
  protected $emergency;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, ScheduleManager $schedule_manager, Emergency $emergency) {
    $this->emergency = $emergency;
    parent::__construct($temp_store_factory, $entity_type_manager, $schedule_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('schedule.manager.digital_signage_platform'),
      $container->get('digital_signage_content_setting.emergency')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function id() {
    return 'digital_signage_device_emergency_mode';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Configure emergency mode on selected devices');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Set emergency mode');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $default_mode = FALSE;
    $default_entity = '';
    if (sizeof($this->devices) === 1) {
      $device = reset($this->devices);
      if ($entity = $device->getEmergencyEntity()) {
        $default_mode = TRUE;
        $default_entity = implode('/', [$entity->getReverseEntityType(), $entity->getReverseEntityId()]);
      }
    }
    $form['emergencymode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable emergency mode'),
      '#default_value' => $default_mode,
    ];
    $form['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#default_value' => $default_entity,
      '#options' => $this->emergency->allForSelect(),
      '#states' => [
        'visible' => [
          ':input[name="emergencymode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($form_state->getValue('confirm')) {
      if ($form_state->getValue('emergencymode')) {
        [$type, $id] = explode('/', $form_state->getValue('entity'));
        $entity = $this->entityTypeManager->getStorage($type)->load($id);
        $target_id = $entity->get('digital_signage')->getValue()[0]['target_id'];
        foreach ($this->devices as $device) {
          $device
            ->set('emergency_entity', $target_id)
            ->save();
        }
      }
      else {
        foreach ($this->devices as $device) {
          $device
            ->set('emergency_entity', NULL)
            ->save();
        }
      }
    }
  }

}
