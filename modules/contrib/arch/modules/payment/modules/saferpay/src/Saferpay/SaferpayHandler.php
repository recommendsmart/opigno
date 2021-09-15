<?php

namespace Drupal\arch_payment_saferpay\Saferpay;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Stream;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Saferpay handler.
 *
 * @package Drupal\arch_payment_saferpay\Saferpay
 */
class SaferpayHandler implements SaferpayHandlerInterface, ContainerInjectionInterface {

  const CONFIG_NAME = 'arch_payment_saferpay.settings';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Order entity.
   *
   * @var \Drupal\arch_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * SaferpayHandler constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    RequestStack $request_stack,
    StateInterface $state,
    LanguageManagerInterface $language_manager,
    ClientInterface $http_client
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->request = $request_stack->getCurrentRequest();
    $this->state = $state;
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('state'),
      $container->get('language_manager')
    );
  }

  /**
   * Get safer payment setting.
   *
   * @return array
   *   Settings array with keys:
   *   - customer_id
   *   - terminal_id
   *   - username
   *   - password
   *   - end_point.
   */
  private function getSettings() {
    $settings = $this->configFactory->get(self::CONFIG_NAME);
    if ($this->state->get('arch_payment_saferpay_test', TRUE)) {
      // @todo Maybe these should be configurable.
      $config = [
        'customer_id' => '401860',
        'terminal_id' => '17795278',
        'username' => 'API_401860_80003225',
        'password' => 'C-y*bv8346Ze5-T8',
        'end_point' => 'https://test.saferpay.com/api',
      ];
    }
    else {
      $config = [
        'customer_id' => $settings->get('customer_id'),
        'terminal_id' => $settings->get('terminal_id'),
        'username' => $settings->get('username'),
        'password' => $settings->get('password'),
        'end_point' => 'https://www.saferpay.com/api',
      ];
    }
    $config['spec_version'] = $settings->get('spec_version');
    $config['force_sca'] = $settings->get('force_sca');
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder($order_id) {
    /** @var \Drupal\arch_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('order')->load($order_id);
    if (
      empty($order)
      || 'completed' == $order->get('status')->getString()
      || empty($order->get('currency')->getString())
      || empty($order->get('subtotal_gross')->getString())
    ) {
      return NULL;
    }
    $this->order = $order;
    return $this->order;
  }

  /**
   * Proxy for saferpay's requests.
   *
   * @param string $url
   *   API url.
   * @param array $payload
   *   A multidimensional array, that assembles the JSON structure.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   Response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function processUrl($url, array $payload) {
    $settings = $this->getSettings();
    $username = $settings['username'];
    $password = $settings['password'];
    $full_url = $settings['end_point'] . $url;

    $return = FALSE;
    try {
      $options = [];
      $options['headers']['Content-Type'] = 'application/json';
      $options['headers']['Accept'] = 'application/json; charset=utf-8';
      $options['auth'] = [
        $username,
        $password,
      ];
      $options['timeout'] = 100;
      $options['json'] = $payload;

      $return = $this->httpClient->request(
        'POST',
        $full_url,
        $options
      );

      if ($return->getStatusCode() !== 200) {
        throw new \Exception(var_export($return->getHeaders(), TRUE));
      }

      if ($return->getBody() instanceof Stream) {
        $return->getBody()->rewind();
      }
    }
    catch (RequestException $exception) {
      $this->logError($exception);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function callInitialize() {
    if (empty($this->order)) {
      return NULL;
    }
    try {
      $currency_code = $this->order->get('currency')->getString();

      $price = $this->order->get('grandtotal_gross')->getString();
      $currency = $this->entityTypeManager->getStorage('currency')->load($currency_code);
      // More info: Drupal\currency\Entity\Currency->formatAmount();
      $price = bcmul(round(bcdiv($price, $currency->getRoundingStep(), 6)), $currency->getRoundingStep(), 6);
      $price = $price * 100;

      $domain = $this->request->getSchemeAndHttpHost();

      $order_id = $this->order->id();
      $order_number = $this->order->get('order_number')->getString();

      $settings = $this->getSettings();
      $lang = $this->languageManager->getCurrentLanguage()->getId();

      $url_options = [
        'absolute' => TRUE,
        'query' => [
          'order' => $order_id,
        ],
      ];

      $succes_url = Url::fromRoute('arch_payment_saferpay.success', [], $url_options)->toString(TRUE)->getGeneratedUrl();
      $error_url = Url::fromRoute('arch_payment_saferpay.error', [], $url_options)->toString(TRUE)->getGeneratedUrl();
      $cancel_url = Url::fromRoute('arch_payment_saferpay.cancel', [], $url_options)->toString(TRUE)->getGeneratedUrl();

      $payload = [
        'RequestHeader' => [
          'SpecVersion' => $settings['spec_version'],
          'CustomerId' => $settings['customer_id'],
          'RequestId' => sha1($order_id . time()),
          'RetryIndicator' => 0,
          'ClientInfo' => [
            'ShopInfo' => $domain,
          ],
        ],
        'TerminalId' => $settings['terminal_id'],
        'Payment' => [
          'Amount' => [
            'Value' => (int) $price,
            'CurrencyCode' => $currency_code,
          ],
          'OrderId' => $order_number,
          'Description' => $order_number,
        ],
        'Payer' => [
          'LanguageCode' => $lang,
        ],
        'ReturnUrls' => [
          'Success' => $succes_url,
          'Fail' => $error_url,
          'Abort' => $cancel_url,
        ],
      ];
      if ($settings['force_sca']) {
        $payload['Authentication'] = [
          'ThreeDsChallenge' => 'FORCE',
        ];
      }
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->processUrl('/Payment/v1/PaymentPage/Initialize', $payload);
      if (!empty($response)) {
        $body = (array) Json::decode($response->getBody());
        if (!empty($body['Token'])) {
          $this->order->setDataKey('saferpay_token', $body['Token']);
          $this->order->save();
        }
      }
    }
    catch (RequestException $exception) {
      $this->logError($exception);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function callAssert() {
    if (empty($this->order)) {
      return NULL;
    }
    try {
      $token = $this->order->getDataKey('saferpay_token');
      $domain = $this->request->getSchemeAndHttpHost();

      $order_id = $this->order->id();

      $settings = $this->getSettings();

      $response = $this->processUrl(
        '/Payment/v1/PaymentPage/Assert',
        [
          'RequestHeader' => [
            'SpecVersion' => $settings['spec_version'],
            'CustomerId' => $settings['customer_id'],
            'RequestId' => sha1($order_id . time()),
            'RetryIndicator' => 0,
            'ClientInfo' => [
              'ShopInfo' => $domain,
            ],
          ],
          'Token' => $token,
        ]
      );

      $body = (array) Json::decode($response->getBody());
      if (!empty($body['Transaction'])) {
        $this->order->setDataKey('saferpay_transaction', $body['Transaction']);
        $this->order->save();
      }
    }
    catch (RequestException $exception) {
      $this->logError($exception);
    }

    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function callCapture($transaction_id) {
    if (empty($this->order)) {
      return NULL;
    }
    try {
      $order_id = $this->order->id();

      $settings = $this->getSettings();

      $response = $this->processUrl(
        '/Payment/v1/Transaction/Capture',
        [
          'RequestHeader' => [
            'SpecVersion' => $settings['spec_version'],
            'CustomerId' => $settings['customer_id'],
            'RequestId' => sha1($order_id . time()),
            'RetryIndicator' => 0,
          ],
          'TransactionReference' => [
            'TransactionId' => $transaction_id,
          ],
        ]
      );

      // @todo Add body to order history.
      $body = (array) Json::decode($response->getBody());
      if (!empty($body['Transaction'])) {
        $this->order->setDataKey('saferpay_capture', $body);
        $this->order->save();
      }
    }
    catch (\Exception $exception) {
      $this->logError($exception);
    }

    return $response;
  }

  /**
   * Log exception.
   */
  private function logError($exception) {
    $response = $exception->getResponse();
    $response_info = $response->getBody()->getContents();
    $message = new FormattableMarkup(
      'API error. Error details are as follows:<pre>@response</pre>',
      ['@response' => print_r(json_decode($response_info), TRUE)]
    );
    watchdog_exception('Saferpay API error', $exception, $message);
  }

}
