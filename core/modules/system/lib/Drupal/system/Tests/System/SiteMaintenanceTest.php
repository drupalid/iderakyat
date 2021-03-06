<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\SiteMaintenanceTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests site maintenance functionality.
 */
class SiteMaintenanceTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Site maintenance mode functionality',
      'description' => 'Test access to site while in maintenance mode.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();

    // Configure 'node' as front page.
    \Drupal::config('system.site')->set('page.front', 'node')->save();

    // Create a user allowed to access site in maintenance mode.
    $this->user = $this->drupalCreateUser(array('access site in maintenance mode'));
    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration', 'access site in maintenance mode'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Verify site maintenance mode functionality.
   */
  function testSiteMaintenance() {
    // Turn on maintenance mode.
    $edit = array(
      'maintenance_mode' => 1,
    );
    $this->drupalPostForm('admin/config/development/maintenance', $edit, t('Save configuration'));

    $admin_message = t('Operating in maintenance mode. <a href="@url">Go online.</a>', array('@url' => url('admin/config/development/maintenance')));
    $user_message = t('Operating in maintenance mode.');
    $offline_message = t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => \Drupal::config('system.site')->get('name')));

    $this->drupalGet('');
    $this->assertRaw($admin_message, 'Found the site maintenance mode message.');

    // Logout and verify that offline message is displayed.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertText($offline_message);
    $this->drupalGet('node');
    $this->assertText($offline_message);
    $this->drupalGet('user/register');
    $this->assertText($offline_message);

    // Verify that user is able to log in.
    $this->drupalGet('user');
    $this->assertNoText($offline_message);
    $this->drupalGet('user/login');
    $this->assertNoText($offline_message);

    // Log in user and verify that maintenance mode message is displayed
    // directly after login.
    $edit = array(
      'name' => $this->user->getUsername(),
      'pass' => $this->user->pass_raw,
    );
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->assertText($user_message);

    // Log in administrative user and configure a custom site offline message.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/development/maintenance');
    $this->assertNoRaw($admin_message, 'Site maintenance mode message not displayed.');

    $offline_message = 'Sorry, not online.';
    $edit = array(
      'maintenance_mode_message' => $offline_message,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Logout and verify that custom site offline message is displayed.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertRaw($offline_message, 'Found the site offline message.');

    // Verify that custom site offline message is not displayed on user/password.
    $this->drupalGet('user/password');
    $this->assertText(t('Username or e-mail address'), 'Anonymous users can access user/password');

    // Submit password reset form.
    $edit = array(
      'name' => $this->user->getUsername(),
    );
    $this->drupalPostForm('user/password', $edit, t('E-mail new password'));
    $mails = $this->drupalGetMails();
    $start = strpos($mails[0]['body'], 'user/reset/'. $this->user->id());
    $path = substr($mails[0]['body'], $start, 66 + strlen($this->user->id()));

    // Log in with temporary login link.
    $this->drupalPostForm($path, array(), t('Log in'));
    $this->assertText($user_message);
  }
}
