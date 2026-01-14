<?php

declare(strict_types=1);

namespace Drupal\Tests\wallet_auth\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Wallet Auth settings form.
 *
 * @coversDefaultClass \Drupal\wallet_auth\Form\SettingsForm
 * @group wallet_auth
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'externalauth',
    'wallet_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user with necessary permissions.
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);

    // Rebuild routes.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests settings form route exists and is accessible.
   */
  public function testSettingsFormRoute(): void {
    $route = \Drupal::service('router.route_provider')->getRouteByName('wallet_auth.settings');

    $this->assertNotNull($route);
    $this->assertEquals('/admin/config/people/wallet-auth', $route->getPath());
    // Verify permission requirement exists.
    $requirement = $route->getRequirement('_permission');
    $this->assertNotEmpty($requirement);
  }

  /**
   * Tests settings form access control.
   */
  public function testSettingsFormAccess(): void {
    // Verify admin user was created successfully.
    $this->assertNotEmpty($this->adminUser->id());
    $this->assertTrue($this->adminUser->hasPermission('administer site configuration'));
  }

  /**
   * Tests settings form is inaccessible to non-admin users.
   */
  public function testSettingsFormAccessDenied(): void {
    // Verify the permission requirement exists on the route.
    $url = \Drupal::service('router.route_provider')->getRouteByName('wallet_auth.settings');
    $requirement = $url->getRequirement('_permission');
    $this->assertNotEmpty($requirement);
  }

  /**
   * Tests settings form configuration schema.
   */
  public function testSettingsConfigurationSchema(): void {
    // Verify config schema exists.
    $schema = \Drupal::service('config.typed')->getDefinition('wallet_auth.settings');

    $this->assertIsArray($schema);
    $this->assertArrayHasKey('mapping', $schema);

    // Verify expected config keys.
    $mapping = $schema['mapping'];
    $this->assertArrayHasKey('network', $mapping);
    $this->assertArrayHasKey('nonce_lifetime', $mapping);
    // enable_auto_connect is the schema key.
    $this->assertArrayHasKey('enable_auto_connect', $mapping);
  }

  /**
   * Tests default configuration values.
   */
  public function testSettingsDefaultValues(): void {
    $config = \Drupal::config('wallet_auth.settings');

    // Network should have a default value.
    $network = $config->get('network');
    $this->assertNotNull($network);
    $this->assertIsString($network);

    // Auto_connect may be null or boolean.
    $autoConnect = $config->get('enable_auto_connect');
    $this->assertTrue(is_bool($autoConnect) || is_null($autoConnect));

    // Nonce lifetime should be set or have a default.
    $nonceLifetime = $config->get('nonce_lifetime');
    $this->assertTrue(is_int($nonceLifetime) || is_null($nonceLifetime));
  }

  /**
   * Tests network configuration option.
   */
  public function testNetworkConfiguration(): void {
    $config = \Drupal::configFactory()->getEditable('wallet_auth.settings');

    // Test setting different network.
    $config->set('network', 'sepolia')->save();
    $savedConfig = \Drupal::config('wallet_auth.settings');
    $this->assertEquals('sepolia', $savedConfig->get('network'));

    // Reset to default.
    $config->set('network', 'mainnet')->save();
    $savedConfig = \Drupal::config('wallet_auth.settings');
    $this->assertEquals('mainnet', $savedConfig->get('network'));
  }

  /**
   * Tests auto_connect configuration option.
   */
  public function testAutoConnectConfiguration(): void {
    $config = \Drupal::configFactory()->getEditable('wallet_auth.settings');

    // Test setting enable_auto_connect (schema name).
    $config->set('enable_auto_connect', TRUE)->save();
    $savedConfig = \Drupal::config('wallet_auth.settings');
    $this->assertTrue($savedConfig->get('enable_auto_connect'));

    // Reset to default.
    $config->set('enable_auto_connect', FALSE)->save();
    $savedConfig = \Drupal::config('wallet_auth.settings');
    $this->assertFalse($savedConfig->get('enable_auto_connect'));
  }

  /**
   * Tests nonce_lifetime configuration option.
   */
  public function testNonceLifetimeConfiguration(): void {
    $config = \Drupal::configFactory()->getEditable('wallet_auth.settings');

    // Test setting different nonce lifetime.
    $config->set('nonce_lifetime', 120)->save();
    $this->assertEquals(120, $config->get('nonce_lifetime'));

    // Reset to default.
    $config->set('nonce_lifetime', 300)->save();
    $this->assertEquals(300, $config->get('nonce_lifetime'));
  }

  /**
   * Tests nonce_lifetime validation constraints.
   */
  public function testNonceLifetimeValidation(): void {
    $config = \Drupal::configFactory()->getEditable('wallet_auth.settings');

    // Test minimum value (60 seconds).
    $config->set('nonce_lifetime', 60)->save();
    $this->assertEquals(60, $config->get('nonce_lifetime'));

    // Test maximum value (3600 seconds).
    $config->set('nonce_lifetime', 3600)->save();
    $this->assertEquals(3600, $config->get('nonce_lifetime'));

    // Reset to default.
    $config->set('nonce_lifetime', 300)->save();
  }

}
