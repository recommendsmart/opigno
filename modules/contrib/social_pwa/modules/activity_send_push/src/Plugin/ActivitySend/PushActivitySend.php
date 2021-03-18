<?php

namespace Drupal\activity_send_push\Plugin\ActivitySend;

use Drupal\activity_send\Plugin\ActivitySendBase;
use Drupal\Core\Url;
use Drupal\message\Entity\Message;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Drupal\file\Entity\File;

/**
 * Provides a 'PushActivitySend' activity action.
 *
 * @ActivitySend(
 *  id = "push_activity_send",
 *  label = @Translation("Action that is triggered when a entity is created"),
 * )
 */
class PushActivitySend extends ActivitySendBase {

  /**
   * {@inheritdoc}
   */
  public function create($activity) {
    // If push is disabled then we don't do anything.
    $push_enabled = \Drupal::config('social_pwa.settings')->get('status.all');
    if (!$push_enabled) {
      return;
    }

    $uid = $activity->field_activity_recipient_user->target_id;

    // If there is no recipient user for this activity then we can't send a push
    // notification to anyone.
    if (empty($uid)) {
      // We log this as a debug message since it doesn't make sense to use push
      // notifications for things that don't affect users. Detecting these
      // scenarios helps us improve the notification system.
      \Drupal::logger('activity_send_push')->debug('Tried to send push notification for activity from message (%mid) that did not have a user recipient.', ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }

    // Get subscription object of the selected user, if the user does not have
    // push notifications enabled then we're done.
    $user_subscription = \Drupal::service('user.data')->get('social_pwa', $uid, 'subscription');
    if (empty($user_subscription)) {
      return;
    }

    // Prepare the payload with the message.
    $message_loaded = Message::load($activity->field_activity_message->target_id);
    $message = $message_loaded->getText();
    if (empty($message[0])) {
      \Drupal::logger('activity_send_push')->error('Tried to send an empty push notification for mid: %mid', ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }
    $message_to_send = $message[0];

    $url = $activity->getRelatedEntityUrl();
    // If the related entity does not have a canonical URL then we don't have
    // anywhere for the user to go to when they click the push notification so
    // we shouldn't send a notification at all.
    if (!($url instanceof Url)) {
      \Drupal::logger('activity_send_push')->error("Tried to send push notification for mid: %mid but the target entity doesn't have a canonical url", ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }

    $pwa_settings = \Drupal::config('social_pwa.settings');

    // Set fields for payload.
    $message_to_send = html_entity_decode($message_to_send);
    $payload = [
      'message' => strip_tags($message_to_send),
      'site_name' => $pwa_settings->get('name'),
      'url' => $url->toString(),
    ];

    $icon = $pwa_settings->get('icons.icon');
    if (!empty($icon)) {
      // Get the file id and path.
      $fid = $icon[0];
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($fid);
      $path = $file->createFileUrl(FALSE);

      $payload['icon_url'] = file_url_transform_relative($path);
    }

    // Encode payload.
    $serialized_payload = json_encode($payload);

    // Get the VAPID keys that were generated before.
    $vapid_keys = \Drupal::state()->get('social_pwa.vapid_keys');

    $auth = [
      'VAPID' => [
        'subject' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
        'publicKey' => $vapid_keys['public'],
        'privateKey' => $vapid_keys['private'],
      ],
    ];
    $webPush = new WebPush($auth);

    foreach ($user_subscription as $subscription_data) {
      $subscription = new Subscription(
        $subscription_data['endpoint'],
        $subscription_data['key'],
        $subscription_data['token']
      );
      $webPush->queueNotification(
        $subscription,
        $serialized_payload
      );
    }

    $removed = FALSE;
    // Send each notification and check the results.
    // flush() returns a generator that won't actually send all batches until
    // we've consumed all the results of the previous batch.
    /** @var \Minishlink\WebPush\MessageSentReport $push_result */
    foreach ($webPush->flush() as $push_result) {
      // If we had any results back that we're unsuccessful, we should act and
      // remove the push subscription endpoint.
      if (!$push_result->isSuccess()) {
        // Loop through the users subscriptions.
        foreach ($user_subscription as $key => $subscription) {
          // Remove from list of subscriptions, as the endpoint is no longer
          // being used.
          if ($subscription['endpoint'] === $push_result->getEndpoint()) {
            unset($user_subscription[$key]);
            $removed = TRUE;
          }
        }
      }
    }

    // Update the users subscriptions if we removed something from the list.
    if ($removed) {
      \Drupal::service('user.data')->set('social_pwa', $uid, 'subscription', $user_subscription);
    }
  }

}
