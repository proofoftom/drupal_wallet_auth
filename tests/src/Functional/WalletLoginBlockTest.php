<?php

declare(strict_types=1);

namespace Drupal\Tests\wallet_auth\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Wallet Login block functionality.
 *
 * @coversDefaultClass \Drupal\wallet_auth\Plugin\Block\WalletLoginBlock
 * @group wallet_auth
 */
class WalletLoginBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'externalauth',
    'wallet_auth',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Rebuild routes and containers.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests that Wallet Login block can be created.
   */
  public function testBlockCreation(): void {
    // Verify block plugin exists.
    $blockManager = \Drupal::service('plugin.manager.block');
    $plugin = $blockManager->getDefinition('wallet_login_block');

    $this->assertIsArray($plugin);
    $this->assertArrayHasKey('admin_label', $plugin);
  }

  /**
   * Tests block configuration options.
   */
  public function testBlockConfiguration(): void {
    $blockManager = \Drupal::service('plugin.manager.block');
    $plugin = $blockManager->getDefinition('wallet_login_block');

    $this->assertIsArray($plugin);
    $this->assertArrayHasKey('category', $plugin);
    $this->assertArrayHasKey('class', $plugin);
    $this->assertEquals('Drupal\wallet_auth\Plugin\Block\WalletLoginBlock', $plugin['class']);
  }

  /**
   * Tests block library registration.
   */
  public function testBlockLibrary(): void {
    $libraries = \Drupal::service('library.discovery')->getLibrariesByExtension('wallet_auth');

    $this->assertIsArray($libraries);
    $this->assertArrayHasKey('wallet_auth_connector', $libraries);
    $this->assertArrayHasKey('wallet_auth_ui', $libraries);

    // Verify library dependencies.
    $connectorLib = $libraries['wallet_auth_connector'];
    $this->assertIsArray($connectorLib['js']);
    $this->assertIsArray($connectorLib['dependencies']);

    $uiLib = $libraries['wallet_auth_ui'];
    $this->assertIsArray($uiLib['js']);
    $this->assertIsArray($uiLib['css']);
  }

  /**
   * Tests block template exists.
   */
  public function testBlockTemplate(): void {
    // Verify the theme hook is registered.
    $themeRegistry = \Drupal::service('theme.registry')->get();

    $this->assertArrayHasKey('wallet_login_button', $themeRegistry);

    $hook = $themeRegistry['wallet_login_button'];
    $this->assertArrayHasKey('path', $hook);
    $this->assertStringContainsString('templates', $hook['path']);
    // Template is stored without .html.twig extension in the registry.
    $this->assertStringContainsString('wallet-login-button', $hook['template']);
  }

}
