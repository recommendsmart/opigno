<?php

/**
 * @file
 * This file contains ProfileStatSenderInterface.
 */

namespace Drupal\profile_stat_sender;

/**
 * Provides an interface for sending profile information to server.
 */
interface ProfileStatSenderInterface {

  /**
   * Makes http POST request to server.
   *
   * @return numeric
   *   Returns response code from server.
   */
  public function sendData();

}
