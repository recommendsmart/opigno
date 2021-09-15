<?php

namespace Drupal\arch_order\OrderMail\Controller;

use Drupal\arch_order\OrderMail\OrderMailManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for order routes.
 */
class DeleteController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs an MailController object.
   *
   * @param \Drupal\arch_order\OrderMail\OrderMailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    OrderMailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    MessengerInterface $messenger
  ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_order_mail'),
      $container->get('language_manager'),
      $container->get('messenger')
    );
  }

  /**
   * Confirmation screen.
   *
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $lang_code
   *   Language code.
   *
   * @return array
   *   Page render array.
   */
  public function confirm($plugin_id = NULL, $lang_code = NULL) {
    $mail = $this->mailManager->get($plugin_id);
    if (!$mail || !$this->languageManager->getLanguage($lang_code)) {
      throw new NotFoundHttpException();
    }

    $page = [];

    $page['question'] = [
      '#markup' => $this->t(
        'Do you really want to delete the @language translation on the "@mail" order mail?',
        [
          '@language' => $this->languageManager->getLanguage($lang_code)->getName(),
          '@mail' => $mail->getPluginDefinition()['label'],
        ]
      ),
    ];

    $page['actions'] = [
      '#type' => 'actions',
    ];
    $page['actions']['yes'] = [
      '#type' => 'link',
      '#title' => $this->t('Yes'),
      '#url' => Url::fromRoute(
        'arch_order_mail.delete_translation_confirmed',
        [
          'plugin_id' => $plugin_id,
          'lang_code' => $lang_code,
        ]
      ),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];
    $page['actions']['no'] = [
      '#type' => 'link',
      '#title' => $this->t('No'),
      '#url' => Url::fromRoute(
        'arch_order_mail.view',
        ['plugin_id' => $plugin_id]
      ),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $page;
  }

  /**
   * Delete method.
   *
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $lang_code
   *   Language code.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to the plugin view page.
   */
  public function delete($plugin_id = NULL, $lang_code = NULL) {
    $mail = $this->mailManager->get($plugin_id);
    if (!$mail) {
      throw new NotFoundHttpException();
    }

    $mail->removeTranslation($lang_code);

    $message = $this->t(
      '@language translation have been deleted.',
      ['@language' => $this->languageManager->getLanguage($lang_code)->getName()],
      ['context' => 'arch_order_mail']
    );
    $this->messenger->addMessage($message);

    return $this->redirect('arch_order_mail.view', ['plugin_id' => $plugin_id]);
  }

}
