<?php

declare(strict_types=1);

namespace Drupal\Tests\wallet_auth\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\wallet_auth\Plugin\Block\WalletLoginBlock;

/**
 * Tests Wallet Login block build output.
 *
 * @coversDefaultClass \Drupal\wallet_auth\Plugin\Block\WalletLoginBlock
 * @group wallet_auth
 */
class WalletLoginBlockTest extends KernelTestBase {

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
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['wallet_auth']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests that block build includes chainId in drupalSettings.
   *
   * @covers ::build
   * @covers ::getChainId
   */
  public function testBlockBuildIncludesChainId(): void {
    $block = $this->createBlock();
    $build = $block->build();

    $this->assertArrayHasKey('#attached', $build);
    $this->assertArrayHasKey('drupalSettings', $build['#attached']);
    $this->assertArrayHasKey('walletAuth', $build['#attached']['drupalSettings']);
    $this->assertArrayHasKey('chainId', $build['#attached']['drupalSettings']['walletAuth']);

    // Default network is mainnet, chainId should be 1.
    $this->assertEquals(1, $build['#attached']['drupalSettings']['walletAuth']['chainId']);
  }

  /**
   * Tests chainId mapping for all supported networks.
   *
   * @covers ::build
   * @covers ::getChainId
   *
   * @dataProvider networkChainIdProvider
   */
  public function testChainIdForNetwork(string $network, int $expectedChainId): void {
    // Update config to use the specified network.
    $this->config('wallet_auth.settings')
      ->set('network', $network)
      ->save();

    $block = $this->createBlock();
    $build = $block->build();

    $settings = $build['#attached']['drupalSettings']['walletAuth'];

    $this->assertEquals($network, $settings['network']);
    $this->assertEquals($expectedChainId, $settings['chainId']);
  }

  /**
   * Data provider for network to chainId mapping.
   *
   * @return array
   *   Test cases with network name and expected chain ID.
   */
  public static function networkChainIdProvider(): array {
    return [
      'mainnet' => ['mainnet', 1],
      'sepolia' => ['sepolia', 11155111],
      'polygon' => ['polygon', 137],
      'bsc' => ['bsc', 56],
      'arbitrum' => ['arbitrum', 42161],
      'optimism' => ['optimism', 10],
    ];
  }

  /**
   * Tests unknown network defaults to mainnet chainId.
   *
   * @covers ::getChainId
   */
  public function testUnknownNetworkDefaultsToMainnet(): void {
    $this->config('wallet_auth.settings')
      ->set('network', 'unknown_network')
      ->save();

    $block = $this->createBlock();
    $build = $block->build();

    $settings = $build['#attached']['drupalSettings']['walletAuth'];

    $this->assertEquals('unknown_network', $settings['network']);
    $this->assertEquals(1, $settings['chainId']);
  }

  /**
   * Tests block returns empty array for authenticated users.
   *
   * @covers ::build
   */
  public function testBlockEmptyForAuthenticatedUser(): void {
    // Create and set an authenticated user.
    $user = $this->createUser();
    $this->setCurrentUser($user);

    $block = $this->createBlock();
    $build = $block->build();

    $this->assertEmpty($build);
  }

  /**
   * Create a WalletLoginBlock instance.
   *
   * @return \Drupal\wallet_auth\Plugin\Block\WalletLoginBlock
   *   The block instance.
   */
  protected function createBlock(): WalletLoginBlock {
    $blockManager = $this->container->get('plugin.manager.block');
    return $blockManager->createInstance('wallet_login_block', []);
  }

  /**
   * Create a user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function createUser() {
    $user = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'name' => 'test_user',
        'mail' => 'test@example.com',
        'status' => 1,
      ]);
    $user->save();
    return $user;
  }

  /**
   * Set the current user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to set as current.
   */
  protected function setCurrentUser($user): void {
    $this->container->get('current_user')->setAccount($user);
  }

}
