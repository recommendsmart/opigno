<?php

namespace Drupal\profile_stat_sender;

use \Drupal\Component\Utility\Crypt;

/**
 * @file
 * This file contains ProfileStatSender service implementation.
 */

/**
 * Provides ProfileStatSender service implementation.
 */
class ProfileStatSender implements ProfileStatSenderInterface {

  protected $siteKey;

  /**
   * Constructs Drupal\profile_stat_sender\ProfileStatSender object.
   */
  public function __construct() {
    $config = \Drupal::configFactory()->getEditable('profile_stat_sender.key');
    $key = $config->get('key');
    if (!$key) {
      // Generate site key from hash because all keys must have same length.
      $key = Crypt::hmacBase64($_SERVER['SERVER_ADDR'], \Drupal::service('private_key')->get());
      $config->set('key', $key)->save();
    }
    $this->siteKey = $key;
  }

  /**
   * Makes http POST request to server.
   *
   * @return numeric | NULL
   *   Returns response code from server or NULL if client isn't able to connect
   *   to the server.
   */
  public function sendData() {
    $client = \Drupal::httpClient();
    $data = $this->fetchData();
    $data = http_build_query($data);
    $options = [
      'headers' => [
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'body' => $data,
    ];
    $server_url = $this->getServerUrl();
    try {
      $response = $client->request('POST', $server_url, $options);

      return $response->getStatusCode();
    }
    // We don't need to do anything with connection problems.
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Fetches data about site into associative array.
   *
   * @return array
   *   Associative array containing data about this site.
   */
  protected function fetchData() {
    $data = array(
      'site_key' => $this->siteKey,
      'name' => \Drupal::config('system.site')->get('name'),
      'profile' => drupal_get_profile(),
      'url' => $this->getClientUrl(),
    );

    return $data;
  }

  /**
   * Returns server url from datafile.
   *
   * @return string
   *   Returns decrypted server url.
   */
  protected function getServerUrl() {
    $path = drupal_get_path('module', 'profile_stat_sender');
    $server_url = file_get_contents($path . '/includes/profile_stat_sender_data.inc');
    $server_url = base64_decode(str_pad(strtr($server_url, '-_', '+/'), strlen($server_url) % 4, '=', STR_PAD_RIGHT));

    return $server_url;
  }

  /**
   * Builds site URL.
   *
   * @return string
   *   Current site URL.
   */
  protected function getClientUrl() {
    $is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
    $http_protocol = $is_https ? 'https' : 'http';
    $url = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
    if ($dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/')) {
      $url .= $dir;
    }

    return $url;
  }

}
