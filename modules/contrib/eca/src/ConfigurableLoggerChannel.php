<?php

namespace Drupal\eca;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorator that applies currently active logging settings.
 */
class ConfigurableLoggerChannel extends LoggerChannel {

  /**
   * The logger channel that is being decorated by this service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  /**
   * The maximum allowed RFC log level.
   *
   * @var int
   */
  protected int $maximumLogLevel;

  /**
   * The ConfigurableLoggerChannel constructor.
   *
   * @param string $channel_name
   *   The name of the logger channel that is being decorated.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   The logger channel that is being decorated by this service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(string $channel_name, LoggerChannelInterface $loggerChannel, ConfigFactoryInterface $configFactory) {
    parent::__construct($channel_name);
    $this->loggerChannel = $loggerChannel;
    $this->updateLogLevel((int) $configFactory->get('eca.settings')->get('log_level'));
  }

  /**
   * Set the ECA log level.
   *
   * @param int $level
   *   The RfcLogLevel:: level which should be configured for ECA.
   *
   * @return void
   */
  public function updateLogLevel(int $level): void {
    $this->maximumLogLevel = $level;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    if (is_string($level)) {
      // Convert to integer equivalent for consistency with RFC 5424.
      $level = $this->levelTranslation[$level];
    }
    if ($level <= $this->maximumLogLevel) {
      $this->loggerChannel->log($level, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestStack(RequestStack $requestStack = NULL): void {
    $this->loggerChannel->setRequestStack($requestStack);
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user = NULL): void {
    $this->loggerChannel->setCurrentUser($current_user);
  }

  /**
   * {@inheritdoc}
   */
  public function setLoggers(array $loggers): void {
    $this->loggerChannel->setLoggers($loggers);
  }

  /**
   * {@inheritdoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0): void {
    $this->loggerChannel->addLogger($logger, $priority);
  }

}
