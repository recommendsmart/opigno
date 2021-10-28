<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests hook_post_update().
 *
 * @group Update
 */
class UpdatePostUpdateInstallProfileTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $connection = Database::getConnection();

    // Set the schema version.
    \Drupal::service('update.update_hook_registry')->setInstalledVersion('testing_post', 8000);

    // Update core.extension to add our testing_post module.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $extensions['module']['testing_post'] = 8000;
    $connection->update('config')
      ->fields([
        'data' => serialize($extensions),
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    // Mimic the behavior of ModuleInstaller::install() for removed post
    // updates. Don't include the actual post updates because we want them to
    // run and make sure they keep on running.
    $key_value = \Drupal::service('keyvalue');
    $existing_updates = $key_value->get('post_update')->get('existing_updates', []);
    $post_updates = [
      'testing_post_post_update_foo',
      'testing_post_post_update_bar',
      'testing_post_post_update_pub',
      'testing_post_post_update_baz',
    ];
    $key_value->get('post_update')->set('existing_updates', array_merge($existing_updates, $post_updates));
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    // Ensure that our testing_post hook_update_N() and
    // testing_post hook_update_dependencies are not shown as
    // testing hook_post_update_NAME().
    $this->assertSession()->responseNotContains('<li>Normal update_N() function.</li><li>Normal update_N() function for 8002.</li><li>Implements hook_update_dependencies().</li>');
    $this->assertSession()->responseContains('<li>8001 - Normal update_N() function.</li><li>8002 - Normal update_N() function for 8002.</li>');
  }

  /**
   * Tests hook_post_update_NAME().
   */
  public function testPostUpdate() {
    // Our updates should fire correctly.
    $this->runUpdates();
    $this->assertSession()->pageTextContains('First update');
    $this->assertSession()->pageTextContains('8001 update');

    // Test state value set by each post update.
    $updates = [
      'testing_post_post_update_8001',
      'testing_post_post_update_first',
    ];
    $this->assertSame($updates, \Drupal::state()->get('post_update_test_execution', []));

    // Check if the post_update key value store contains a list of the
    // hook_post_update_NAME() functions that have run from the testing_post module.
    $existing_updates = array_count_values(\Drupal::keyValue('post_update')->get('existing_updates'));
    // So we should be able to find our post_update_NAME() hooks.
    $expected_post_updates = [
      'testing_post_post_update_8001',
      'testing_post_post_update_first',
    ];
    foreach ($expected_post_updates as $expected_post_update) {
      $this->assertArrayHasKey($expected_post_update, $existing_updates, new FormattableMarkup("@expected_update exists in 'existing_updates' key and only appears once.", ['@expected_update' => $expected_post_update]));
    }

    // Check if the post_update key value store does not contain a list of the
    // hook_update_N() functions that have run from the testing_post module.
    $regular_updates = [
      'testing_post_update_8001',
      'testing_post_update_8002',
      'testing_post_update_dependencies',
    ];
    foreach ($regular_updates as $regular_update) {
      $this->assertArrayNotHasKey($regular_update, $existing_updates, new FormattableMarkup("@expected_update is not part of 'existing_updates' as it is not a post_update_NAME hook.", ['@expected_update' => $regular_update]));
    }

    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');
  }

}
