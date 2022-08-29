<?php

namespace Drupal\ckeditor_bgimage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Component\Utility\Bytes;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Environment;

/**
 * Provides a link dialog for text editors.
 */
class EditorFileDialog extends FormBase implements BaseFormIdInterface {

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   */
  public function __construct(EntityStorageInterface $file_storage) {
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_bgimage_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    // Use the EditorLinkDialog form id to ease alteration.
    return 'editor_bgimage_link_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {

    $file_element = $form_state->get('file_element') ?: [];
    if (isset($form_state->getUserInput()['editor_object'])) {
      $file_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('file_element', $file_element);
      $form_state->setCached(TRUE);
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-bgimage-dialog-form">';
    $form['#suffix'] = '</div>';

    $editor = editor_load($filter_format->id());
    $file_upload = $editor->getThirdPartySettings('ckeditor_bgimage');
    $max_filesize = min(Bytes::toInt($file_upload['max_size']), Environment::getUploadMaxSize());

    $existing_file = isset($file_element['data-entity-uuid']) ? $this->loadEntityByUuid('file', $file_element['data-entity-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $ext = (!empty($file_upload['extensions'])) ? [$file_upload['extensions']] : ['jpg',
      'jpeg', 'png',
    ];

    $form['fid'] = [
      '#title' => $this->t('Background Image'),
      '#type' => 'managed_file',
      '#upload_location' => $file_upload['scheme'] . '://' . $file_upload['directory'],
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_validators' => [
        'file_validate_extensions' => $ext,
        'file_validate_size' => [$max_filesize],
      ],
      '#required' => TRUE,
      '#access' => TRUE,
    ];

    $form['attributes']['href'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($file_element['href']) ? $file_element['href'] : '',
      '#maxlength' => 2048,
      '#required' => TRUE,
      '#access' => TRUE,
    ];

    $form['background_color'] = [
      '#title' => $this->t('Background Color'),
      '#type' => 'color',
      '#default_value' => '#ffffff',
      '#description' => $this->t('Select a Color'),
      '#maxlength' => 10,
    ];

    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Set a value width in (px)'),
      '#maxlength' => 4,
      '#required' => FALSE,
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Heigth'),
      '#description' => $this->t('Set a value height in (px)'),
      '#maxlength' => 4,
      '#required' => FALSE,
    ];

    $form['background_aling'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Position'),
      '#options' => [
        'left top' => $this->t('Left Top'),
        'left center' => $this->t('Left Center'),
        'left bottom' => $this->t('Left Bottom'),
        'right top' => $this->t('Right Top'),
        'right center' => $this->t('Right Center'),
        'right bottom' => $this->t('Right Bottom'),
        'center top' => $this->t('Center Top'),
        'center center' => $this->t('Center Center'),
        'center bottom' => $this->t('Center Bottom'),
      ],
    ];

    if ($file_upload['status']) {
      $form['attributes']['href']['#access'] = FALSE;
      $form['attributes']['href']['#required'] = FALSE;
    }
    else {
      $form['fid']['#access'] = FALSE;
      $form['fid']['#required'] = FALSE;
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $form_state->setValue(['attributes', 'idModal'], rand(1000000, 99999999));
    $fid = $form_state->getValue(['fid', 0]);

    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface */
      $file = $this->fileStorage->load($fid);
      $file_url = file_create_url($file->getFileUri());
      $file_url = file_url_transform_relative($file_url);
      $form_state->setValue(['attributes', 'image'], $file_url);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-bgimage-dialog-form', $form));
      return $response;
    }

    $response->addCommand(new EditorDialogSave($form_state->getValues()));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
