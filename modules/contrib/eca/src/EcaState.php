<?php

namespace Drupal\eca;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\State;

/**
 * Key/Value store for ECA only.
 */
class EcaState extends State {

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * ECA State constructor.
   *
   * This extends Drupal core's state service with an ECA related store.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *
   * @noinspection MagicMethodsValidityInspection
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, TimeInterface $time) {
    $this->keyValueStore = $key_value_factory->get('eca');
    $this->time = $time;
  }

  /**
   * Stores the request time in ECA's key/value store.
   *
   * @param string $key
   *   The identifier for the timestamp.
   *
   * @return $this
   */
  public function setTimestamp(string $key): EcaState {
    $this->set($this->timestampKey($key), $this->getCurrentTimestamp());
    return $this;
  }

  /**
   * Receive a stored timestamp from the ECA's key/value store.
   *
   * @param string $key
   *   The identifier for the timestamp.
   *
   * @return int
   */
  public function getTimestamp(string $key): int {
    return $this->get($this->timestampKey($key), 0);
  }

  /**
   * Receive current timestamp.
   *
   * @return int
   */
  public function getCurrentTimestamp(): int {
    return $this->time->getRequestTime();
  }

  /**
   * @param string $key
   *   The identifier for the timestamp.
   * @param int $timeout
   *   Elapsed time in seconds after which the identified timestamp is
   *   considered to have timed-out.
   *
   * @return bool
   *   TRUE if the difference between current time and the identified and
   *   stored timestamp (default: 0) is greater than the given timeout period.
   *   FALSE otherwise.
   */
  public function hasTimestampExpired(string $key, int $timeout): bool {
    return ($this->getCurrentTimestamp() - $this->getTimestamp($key) > $timeout);
  }

  /**
   * Builds an identifier for timestamps related to a given key.
   *
   * @param string $key
   *   The identifier for the timestamp.
   *
   * @return string
   *   A unique key to identify a timestamp in the Key/Value store related to
   *   the given key.
   */
  protected function timestampKey(string $key): string {
    return implode('.', ['timestamp', $key]);
  }

}
