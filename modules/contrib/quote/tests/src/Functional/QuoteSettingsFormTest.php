<?php

namespace Drupal\Tests\quote\Functional;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests settings form.
 *
 * @group quote
 */
class QuoteSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'quote'];

  /**
   * The configuration factory service.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Settings form url.
   */
  protected Url $settingsRoute;

  /**
   * User with correct permissions.
   */
  protected User $user;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer quote']);
    $this->configFactory = $this->container->get('config.factory');
    $this->settingsRoute = Url::fromRoute('quote.settings_form');
  }

  /**
   * Tests permissions to setting form.
   */
  public function testPermissionsToSettingsForm(): void {
    $this->drupalGet($this->settingsRoute);
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet($this->settingsRoute);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);
    $this->drupalGet($this->settingsRoute);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests settings save.
   */
  public function testSettingsSaving(): void {
    $types = ['page', 'article'];
    foreach ($types as $type_id) {
      $this->drupalCreateContentType(['type' => $type_id]);
    }

    $this->drupalLogin($this->user);
    $this->drupalGet($this->settingsRoute);

    $expected_values = $edit = [
      'quote_modes_quote_sel' => \random_int(0, 1),
      'quote_modes_quote_all' => \random_int(0, 1),
      'quote_modes_quote_reply_all' => \random_int(0, 1),
      'quote_modes_quote_reply_sel' => \random_int(0, 1),
      'quote_allow_comments' => \random_int(0, 1),
      'quote_selector' => $this->randomString(),
      'quote_limit' => \random_int(1, 999),
      'quote_selector_comment_quote_all' => $this->randomString(),
      'quote_selector_node_quote_all' => $this->randomString(),
    ];

    $allowed_types = \array_slice($types, 0, \random_int(0, 1));
    foreach ($allowed_types as $type_id) {
      $edit['quote_allow_types[' . $type_id . ']'] = $type_id;
      $expected_values['quote_allow_types'][$type_id] = $type_id;
    }

    $this->submitForm($edit, $this->t('Save configuration'));
    foreach ($expected_values as $field => $expected_value) {
      $actual_value = $this->config('quote.settings')->get($field);
      $this->assertEquals($expected_value, $actual_value);
    }
  }

}
