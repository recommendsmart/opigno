<?php

namespace Drupal\arch_order\OrderMail;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Order mail interface.
 *
 * @package Drupal\arch_order\OrderMail
 */
interface OrderMailInterface extends PluginInspectionInterface {

  /**
   * Get available translation languages.
   *
   * @return array
   *   Language code array.
   */
  public function getLanguageList();

  /**
   * Get mail subject.
   *
   * @param string|null $langcode
   *   Language code.
   *
   * @return string
   *   Mail subject.
   */
  public function getSubject($langcode = NULL);

  /**
   * Get mail body.
   *
   * @param string|null $langcode
   *   Language code.
   *
   * @return array
   *   Mail body in formatted string array.
   */
  public function getBody($langcode = NULL);

  /**
   * Set mail subject.
   *
   * @param string $langcode
   *   Language code.
   * @param string $text
   *   Mail subject.
   */
  public function setSubject($langcode, $text);

  /**
   * Set mail body.
   *
   * @param string $langcode
   *   Language code.
   * @param array $text
   *   Mail body in formatted string array.
   */
  public function setBody($langcode, array $text);

  /**
   * Set specific translation.
   *
   * @param string $langcode
   *   Language code.
   * @param string $subject
   *   Mail subject.
   * @param array $body
   *   Mail body in formatted string array.
   */
  public function setTranslation($langcode, $subject, array $body);

  /**
   * Remove specific translation.
   *
   * @param string $langcode
   *   Language code.
   */
  public function removeTranslation($langcode);

  /**
   * Set mail status (enabled/disabled).
   *
   * @param bool $status
   *   Mail status.
   */
  public function setStatus($status);

  /**
   * To do or not to do.
   *
   * @return bool
   *   True if the mail should be sent.
   */
  public function isEnabled();

  /**
   * Customizable send-to value.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order object.
   *
   * @return string
   *   Email address.
   */
  public function sendTo(OrderInterface $order): string;

}
