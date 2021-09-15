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
class ViewController extends ControllerBase implements ContainerInjectionInterface {

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
   * Plugin ID.
   *
   * @var string
   */
  private $pluginId;

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
   * Details view.
   *
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return array
   *   Page render array.
   */
  public function main($plugin_id = NULL) {
    $mail = $this->mailManager->get($plugin_id);
    if (!$mail) {
      throw new NotFoundHttpException();
    }

    $this->pluginId = $plugin_id;

    $content = [
      'label' => ['#markup' => '<h3>' . $mail->getPluginDefinition()['label'] . '</h3>'],
      'addressee' => [
        '#type' => 'container',
        'label' => [
          '#prefix' => '<b>',
          '#markup' => $this->t('Addressee'),
          '#suffix' => ':</b> ',
        ],
        'value' => ['#markup' => $mail->getPluginDefinition()['sendTo']],
      ],
      'status' => [
        'text' => [
          '#type' => 'container',
          'label' => [
            '#prefix' => '<b>',
            '#markup' => $this->t('Status'),
            '#suffix' => ':</b> ',
          ],
          'value' => ['#markup' => $mail->isEnabled() ? $this->t('Enabled') : $this->t('Disabled')],
          'button' => [
            '#type' => 'link',
            '#title' => $mail->isEnabled() ? $this->t('Disable') : $this->t('Enable'),
            '#url' => Url::fromRoute('arch_order_mail.change_status', ['plugin_id' => $plugin_id]),
            '#attributes' => [
              'class' => 'button button--danger',
            ],
          ],
        ],
      ],
      'description' => ['#markup' => $mail->getPluginDefinition()['description']],
      'table' => [],
    ];
    $content['table'] = [
      '#type' => 'table',
      '#header' => $this->buildTableHeader(),
      '#empty' => $this->t(
        'There are no translation for the "@mail_label" mail yet.',
        ['@mail_label' => $mail->getPluginDefinition()['label']],
        ['context' => 'arch_order_mail']
      ),
    ];

    foreach ($mail->getLanguageList() as $langcode) {
      $content['table'][] = $this->buildTableRow($langcode);
    }

    return $content;
  }

  /**
   * Change mail status (enabled/disabled).
   *
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back.
   */
  public function changeStatus($plugin_id) {
    $mail = $this->mailManager->get($plugin_id);
    if (!$mail) {
      throw new NotFoundHttpException();
    }

    $mail->setStatus(!$mail->isEnabled());
    if ($mail->isEnabled()) {
      $message = $this->t(
        'The mail has been successfully enabled.',
        [],
        ['context' => 'arch_order_mail']
      );
    }
    else {
      $message = $this->t(
        'The mail has been successfully disabled.',
        [],
        ['context' => 'arch_order_mail']
      );
    }

    $this->messenger->addMessage($message);

    return $this->redirect('arch_order_mail.view', ['plugin_id' => $plugin_id]);
  }

  /**
   * Build table header.
   *
   * @return array
   *   Render array.
   */
  private function buildTableHeader() {
    $header = [
      'label' => $this->t('Language'),
      'operations' => $this->t('Operations'),
    ];

    return $header;
  }

  /**
   * Build table row.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   Render array.
   */
  private function buildTableRow($langcode) {
    $row = [];

    $row['label'] = [
      '#markup' => $this->languageManager->getLanguage($langcode)->getName(),
    ];
    $row['operations'] = [
      '#type' => 'operations',
      '#links' => $this->getOperations($langcode),
    ];

    return $row;
  }

  /**
   * Builds a renderable list of operation links for the mail translation.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   A renderable array of operation links.
   */
  private function getOperations($langcode) {
    $operations = [];

    $edit_url = Url::fromRoute(
      'arch_order_mail.edit_translation',
      [
        'plugin_id' => $this->pluginId,
        'lang_code' => $langcode,
      ]
    );

    $delete_url = Url::fromRoute(
      'arch_order_mail.delete_translation',
      [
        'plugin_id' => $this->pluginId,
        'lang_code' => $langcode,
      ]
    );

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => $edit_url,
    ];

    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'url' => $delete_url,
    ];

    return $operations;
  }

}
