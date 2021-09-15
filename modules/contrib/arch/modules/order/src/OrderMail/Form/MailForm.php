<?php

namespace Drupal\arch_order\OrderMail\Form;

use Drupal\arch_order\OrderMail\OrderMailInterface;
use Drupal\arch_order\OrderMail\OrderMailManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Mail form.
 *
 * @package Drupal\arch_order\Form
 */
class MailForm extends FormBase {

  /**
   * Mail manager service.
   *
   * @var \Drupal\arch_order\OrderMail\OrderMailManagerInterface
   */
  protected $mailManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $renderer;

  /**
   * Available site language list.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  private $languageList;

  /**
   * Site default language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  private $defaultLanguage;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * File usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * MailForm constructor.
   *
   * @param \Drupal\arch_order\OrderMail\OrderMailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   Entity repository service.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   File usage service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   */
  public function __construct(
    OrderMailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    MessengerInterface $messenger,
    RendererInterface $renderer,
    EntityRepositoryInterface $entityRepository,
    FileUsageInterface $fileUsage,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
    $this->renderer = $renderer;

    $this->languageList = $this->languageManager->getLanguages();
    $this->defaultLanguage = $this->languageManager->getDefaultLanguage();

    $this->entityRepository = $entityRepository;
    $this->fileUsage = $fileUsage;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_order_mail'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('file.usage'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_order_mail';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, $lang_code = NULL) {
    if (!$plugin_id) {
      throw new NotFoundHttpException();
    }

    if (isset($lang_code)) {
      $lang_code = strtolower(trim($lang_code));
      if (!isset($this->languageList[$lang_code])) {
        throw new NotFoundHttpException();
      }
    }

    $mail = $this->mailManager->get($plugin_id);
    if (!$mail) {
      throw new NotFoundHttpException();
    }
    if ($lang_code && !$mail->translationIsExists($lang_code)) {
      throw new NotFoundHttpException();
    }

    $languageList = [];
    foreach ($this->languageList as $lang) {
      if ($mail && $lang->getId() !== $lang_code && $mail->translationIsExists($lang->getId())) {
        continue;
      }

      $languageList[$lang->getId()] = $lang->getName();
    }

    if (empty($languageList)) {
      $message = $this->t(
        'Translation is already available in all languages.',
        [],
        ['context' => 'arch_order_mail']
      );
      $this->messenger->addMessage($message, Messenger::TYPE_WARNING);

      return $this->redirect('arch_order_mail.view', ['plugin_id' => $plugin_id]);
    }

    $token_tree = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['order'],
    ];

    $form['plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $plugin_id,
    ];

    $form['lang_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $languageList,
      '#default_value' => $lang_code ? $lang_code : $this->defaultLanguage->getId(),
      '#attributes' => $lang_code ? ['disabled' => 'disabled'] : [],
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#default_value' => $mail ? $mail->getSubject($lang_code) : '',
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $mail ? $mail->getBody($lang_code)['value'] : '',
      '#required' => TRUE,
      '#format' => 'basic_html',
      '#allowed_formats' => ['basic_html'],
      '#description' => $this->t(
        'This field supports tokens. @browse_tokens_link',
        ['@browse_tokens_link' => $this->renderer->render($token_tree)]
      ),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pluginId = $form_state->getValue('plugin_id');
    $langcode = $form_state->getValue('lang_code');
    $subject = $form_state->getValue('subject');
    $body = $form_state->getValue('body');
    // Makes it safe for DB and prevent unnecessary newlines.
    $body['value'] = preg_replace('/\s\s+/', '', $body['value']);

    $mail = $this->mailManager->get($pluginId);
    if (!$mail) {
      throw new NotFoundHttpException();
    }

    $mail->setTranslation($langcode, $subject, $body);

    $this->handleManagedFiles($body, $mail, $form_state);

    $message = $this->t(
      '@language translation have been saved.',
      ['@language' => $this->languageManager->getLanguage($langcode)->getName()],
      ['context' => 'arch_order_mail']
    );
    $this->messenger->addMessage($message);

    $form_state->setRedirect('arch_order_mail.view', ['plugin_id' => $pluginId]);
  }

  /**
   * Handles managed files.
   *
   * @param array $body
   *   Array of body field. Keys are 'value', 'format'.
   * @param \Drupal\arch_order\OrderMail\OrderMailInterface $orderMail
   *   Loaded order mail plugin currently editing.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state data.
   */
  protected function handleManagedFiles(array $body, OrderMailInterface $orderMail, FormStateInterface $formState) {
    if (!$this->moduleHandler->moduleExists('editor')) {
      return;
    }

    $module = 'arch_order';
    $type = 'order_mail';
    $plugin_id = $formState->getValue('plugin_id');
    $translation_langcode = $formState->getValue('lang_code');
    $id = substr($translation_langcode . '__' . $plugin_id, 0, 64);

    try {
      $original_uuids = _editor_parse_file_uuids($orderMail->getBody($translation_langcode)['value']);
      $uuids = _editor_parse_file_uuids($body['value']);

      // Adds new files.
      foreach ($uuids as $uuid) {
        if ($file = $this->entityRepository->loadEntityByUuid('file', $uuid)) {
          /** @var \Drupal\file\FileInterface $file */
          if ($file->isTemporary()) {
            $file->setPermanent();
            $file->save();
          }

          $usages = $this->fileUsage->listUsage($file);
          if (!isset($usages[$module][$type][$id])) {
            $this->fileUsage->add($file, $module, $type, $id);
          }
        }
      }

      // Deletes possibly removed image(s) from file_usage table.
      $removed_files = array_diff($original_uuids, $uuids);
      foreach ($removed_files as $uuid) {
        if ($file = $this->entityRepository->loadEntityByUuid('file', $uuid)) {
          $this->fileUsage->delete($file, $module, $type, $id, 1);
        }
      }
    }
    catch (\Exception $e) {
      $this->messenger->addWarning($this->t('Failed to set image to permanent.'));
    }
  }

}
