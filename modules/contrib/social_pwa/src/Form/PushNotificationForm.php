<?php

namespace Drupal\social_pwa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Configure Push Notifications form.
 */
class PushNotificationForm extends FormBase {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'push_notification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Check first if sending push notifications is enabled.
    $push_enabled = \Drupal::config('social_pwa.settings')->get('status.all');
    if (!$push_enabled) {
      $this->messenger()->addWarning($this->t('Sending push notifications is disabled.'));

      return $form;
    }

    // First we check if there are users on the platform that have a
    // subscription.
    // Retrieve all uid.
    $user_query = \Drupal::entityQuery('user');
    $user_query->condition('uid', 0, '>');
    $user_list = $user_query->execute();

    // Filter to check which users have subscription.
    foreach ($user_list as $key => &$value) {
      /** @var \Drupal\user\Entity\User $account */
      if ($account = User::load($key)) {
        $user_subscription = \Drupal::service('user.data')->get('social_pwa', $account->id(), 'subscription');
        if (isset($user_subscription)) {
          $value = $account->getDisplayName() . ' (' . $account->getAccountName() . ')';
          continue;
        }
        unset($user_list[$key]);
      }
    }

    // Check if the $user_list does have values.
    if (empty($user_list)) {
      $this->messenger()->addWarning($this->t('There are currently no users subscribed to receive push notifications.'));
    }
    else {
      // Start the form for sending push notifications.
      $form['push_notification'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Send a Push Notification'),
        '#open' => FALSE,
      ];
      $form['push_notification']['selected-user'] = [
        '#type' => 'select',
        '#title' => $this->t('To user'),
        '#description' => $this->t('This is a list of users that have given permission to receive notifications.'),
        '#options' => $user_list,
      ];
      $form['push_notification']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#size' => 47,
        '#default_value' => 'Open Social',
        '#disabled' => TRUE,
        '#description' => $this->t('This will be the <b>title</b> of the Push Notification. <i>(Static value for now)</i>'),
      ];
      $form['push_notification']['message'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Message'),
        '#size' => 47,
        '#maxlength' => 120,
        '#default_value' => 'Enter your message here...',
        '#description' => $this->t('This will be the <b>message</b> of the Push Notification.'),
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send Push Notification'),
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // The selected uid value of the form.
    $uid = $form_state->getValue('selected-user');

    if (!empty($uid)) {
      // Get subscription object of the selected user.
      $user_subscription = \Drupal::service('user.data')->get('social_pwa', $uid, 'subscription');

      // Prepare the payload with the message.
      $message = $form_state->getValue('message');
      $serialized_payload = json_encode(['message' => $message]);

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
    $this->messenger()->addStatus($this->t('Message was successfully sent!'));
  }

}
