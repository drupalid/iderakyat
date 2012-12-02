<?php

/**
 * @file
 * Contains \Drupal\toolbar\Tests\ToolbarHookToolbarTest.
 */

namespace Drupal\toolbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the toolbar tab and tray registration.
 */
class ToolbarHookToolbarTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'toolbar_test', 'test_page_test');

  public static function getInfo() {
    return array(
      'name' => 'Toolbar hook_toolbar',
      'description' => 'Tests the implementation of hook_toolbar() by a module.',
      'group' => 'Toolbar',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->admin_user = $this->drupalCreateUser(array('access toolbar'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests for a tab and tray provided by a module implementing hook_toolbar().
   */
  function testHookToolbar() {
    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    // Assert that the tab registered by toolbar_test is present.
    $this->assertRaw('id="toolbar-tab-testing"');

    // Assert that the tray registered by toolbar_test is present.
    $this->assertRaw('id="toolbar-tray-testing"');
  }

}