<?php

namespace Drupal\arch_order\OrderMail;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Utility\Token;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Order mail manager service.
 *
 * @package Drupal\arch_order\OrderMail
 */
class OrderMailManager extends DefaultPluginManager implements OrderMailManagerInterface {

  /**
   * List of mails.
   *
   * @var \Drupal\arch_order\OrderMail\OrderMailInterface[]
   */
  protected $mails;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager,
    ConfigFactoryInterface $config_factory,
    Token $token,
    MailManagerInterface $mail_manager,
    RequestStack $requestStack
  ) {
    parent::__construct(
      'OrderMail/Plugin',
      $namespaces,
      $module_handler,
      '\Drupal\arch_order\OrderMail\OrderMailInterface',
      '\Drupal\arch_order\OrderMail\Annotation\OrderMail'
    );

    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->mailManager = $mail_manager;
    $this->requestStack = $requestStack;

    $this->alterInfo('arch_order_mail');
  }

  /**
   * {@inheritdoc}
   */
  public function get($plugin_id) {
    if (!$this->hasDefinition($plugin_id)) {
      return NULL;
    }

    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() : array {
    if (!isset($this->mails)) {
      $list = [];
      foreach ($this->getDefinitions() as $definition) {
        $mail = $this->createInstance(
          $definition['id'],
          []
        );
        $list[$mail->getPluginId()] = $mail;
      }

      $this->mails = $list;
    }

    return $this->mails;
  }

  /**
   * {@inheritdoc}
   */
  public function send($plugin_id, OrderInterface $order) {
    if (!$this->hasDefinition($plugin_id)) {
      return FALSE;
    }

    $context = [
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      'shop_name' => $this->configFactory->get('system.site')->get('name'),
      'shop_address' => $this->configFactory->get('system.site')->get('mail'),
      'order' => $order,
      'plugin' => $this->get($plugin_id),
    ];

    if (!$context['plugin']->isEnabled()) {
      return FALSE;
    }

    switch ($context['plugin']->getPluginDefinition()['sendTo']) {
      case 'user':
        $to = $order->getOwner()->getEmail();
        break;

      case 'shop':
        $to = $context['shop_address'];
        break;

      case 'method':
        $to = (string) $context['plugin']->sendTo($order);
        if (empty($to)) {
          $to = $context['shop_address'];
        }
        break;

      default:
        return FALSE;
    }

    $token_params = [
      'order' => $order,
    ];

    $this->moduleHandler->alter('arch_order_mail_params', $token_params, $context);

    $subject = $context['plugin']->getSubject($context['langcode']);
    $subject = $this->token->replace($subject, $token_params, ['clear' => TRUE]);

    if (empty($subject)) {
      return FALSE;
    }

    $content = $context['plugin']->getBody($context['langcode']);
    $body = $this->token->replace($content['value'], $token_params, ['clear' => TRUE]);
    if (empty($body)) {
      return FALSE;
    }

    $body = check_markup($body, !empty($content['format']) ? $content['format'] : 'basic_html');
    $body = Html::transformRootRelativeUrlsToAbsolute($body, $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost());

    $params = [
      'from' => $context['shop_name'] . '<' . $context['shop_address'] . '>',
      'subject' => $subject,
      'message' => $body,
    ];
    $module = 'arch_order';
    $key = 'arch_order_mail_manager';
    $result = $this->mailManager->mail($module, $key, $to, $context['langcode'], $params, NULL, TRUE);

    return $result['result'] === TRUE;
  }

}
