<?php

namespace Drupal\eca\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Service\Modellers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Import a model from a previous export.
 */
class Import extends FormBase {

  /**
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->modellerService = $container->get('eca.service.modeller');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eca_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['model'] = [
      '#type' => 'file',
      '#title' => $this->t('File containing the exported model'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $all_files = \Drupal::request()->files->get('files', []);
    if (empty($all_files)) {
      $form_state->setErrorByName('model', 'No file provided.');
      return;
    }
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = reset($all_files);
    $filename = $file->getRealPath();
    if (!file_exists($filename)) {
      $form_state->setErrorByName('model', 'Something went wrong during upload.');
      return;
    }
    [$name, ] = explode('.', $file->getClientOriginalName());
    [$modellerId, $id] = explode('-', $name);
    $modeller = $this->modellerService->getModeller($modellerId);
    if (!$modeller) {
      $form_state->setErrorByName('model', 'The required modeller is not available.');
      return;
    }
    $form_state->setValue('model', [
      'filename' => $filename,
      'modellerId' => $modellerId,
      'id' => $id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $model = $form_state->getValue('model');
    if ($modeller = $this->modellerService->getModeller($model['modellerId'])) {
      $modeller->save(file_get_contents($model['filename']));
    }
    $form_state->setRedirect('entity.eca.collection');
  }

}
