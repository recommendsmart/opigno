<?php

namespace Drupal\eca_user\Plugin\Action;

use Drupal\eca\Plugin\Action\ActionBase;

/**
 * Load the currently logged in user into the token environment.
 *
 * @Action(
 *   id = "eca_token_load_user_current",
 *   label = @Translation("Token: load currently logged in user")
 * )
 */
class TokenLoadUserCurrent extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id())) {
      $token_type = $this->tokenServices->getTokenType($user);
      $this->tokenServices->addTokenData($token_type, $user);
    }
  }

}
