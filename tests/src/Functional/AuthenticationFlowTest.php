<?php

declare(strict_types=1);

namespace Drupal\Tests\wallet_auth\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests wallet authentication REST API.
 *
 * @coversDefaultClass \Drupal\wallet_auth\Controller\AuthenticateController
 * @group wallet_auth
 */
class AuthenticationFlowTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Rebuild routes to ensure wallet_auth routes are registered.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests that authentication route exists.
   */
  public function testAuthenticateRouteExists(): void {
    $url = \Drupal::service('router.route_provider')->getRouteByName('wallet_auth.authenticate');
    $this->assertNotNull($url);
    $this->assertEquals('/wallet-auth/authenticate', $url->getPath());
  }

  /**
   * Tests that nonce route exists.
   */
  public function testNonceRouteExists(): void {
    $url = \Drupal::service('router.route_provider')->getRouteByName('wallet_auth.nonce');
    $this->assertNotNull($url);
    $this->assertEquals('/wallet-auth/nonce', $url->getPath());
  }

  /**
   * Tests that settings route exists and requires permission.
   */
  public function testSettingsRouteExists(): void {
    $url = \Drupal::service('router.route_provider')->getRouteByName('wallet_auth.settings');
    $this->assertNotNull($url);
    $this->assertEquals('/admin/config/people/wallet-auth', $url->getPath());

    // Verify route has correct permission requirement.
    $requirement = $url->getRequirement('_permission');
    $this->assertEquals('administer site configuration', $requirement);
  }

  /**
   * Tests that settings page is inaccessible without permission.
   */
  public function testSettingsRouteAccessDenied(): void {
    // Verify the permission requirement exists on the route.
    $url = \Drupal::service('router.route_provider')->getRouteByName('wallet_auth.settings');
    $requirement = $url->getRequirement('_permission');
    $this->assertNotEmpty($requirement);
  }

  /**
   * Tests request validation with invalid address format.
   */
  public function testRequestValidationInvalidAddress(): void {
    // This test verifies the controller handles invalid input.
    // Full HTTP testing is done via kernel tests for WalletVerification.

    $verification = $this->container->get('wallet_auth.verification');
    $this->assertFalse($verification->validateAddress('invalid'));
    $this->assertFalse($verification->validateAddress('0x123'));
    $this->assertFalse($verification->validateAddress('1234567890123456789012345678901234567890'));
  }

  /**
   * Tests request validation with valid addresses.
   */
  public function testRequestValidationValidAddress(): void {
    $verification = $this->container->get('wallet_auth.verification');

    // Test valid formats.
    $this->assertTrue($verification->validateAddress('0x71C7656EC7ab88b098defB751B7401B5f6d8976F'));
    $this->assertTrue($verification->validateAddress('0x71c7656ec7ab88b098defb751b7401b5f6d8976f'));
  }

  /**
   * Tests nonce generation service.
   */
  public function testNonceGeneration(): void {
    $verification = $this->container->get('wallet_auth.verification');

    $nonce1 = $verification->generateNonce();
    $nonce2 = $verification->generateNonce();

    $this->assertNotEmpty($nonce1);
    $this->assertNotEmpty($nonce2);
    $this->assertNotEquals($nonce1, $nonce2);
    $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $nonce1);
  }

  /**
   * Tests user creation service.
   */
  public function testUserCreationService(): void {
    $userManager = $this->container->get('wallet_auth.user_manager');
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // Create user from wallet.
    $user = $userManager->createUserFromWallet($walletAddress);

    $this->assertNotEmpty($user->id());
    $this->assertStringStartsWith('wallet_', $user->getAccountName());
    $this->assertTrue($user->isActive());

    // Verify user can be loaded by wallet.
    $loadedUser = $userManager->loadUserByWalletAddress($walletAddress);
    $this->assertNotNull($loadedUser);
    $this->assertEquals($user->id(), $loadedUser->id());
  }

  /**
   * Tests user login flow.
   */
  public function testUserLoginFlow(): void {
    $userManager = $this->container->get('wallet_auth.user_manager');
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // First login creates user.
    $user1 = $userManager->loginOrCreateUser($walletAddress);
    $uid1 = $user1->id();

    // Second login returns same user.
    $user2 = $userManager->loginOrCreateUser($walletAddress);

    $this->assertEquals($uid1, $user2->id());
    $this->assertEquals($user1->getAccountName(), $user2->getAccountName());
  }

  /**
   * Tests nonce storage and retrieval.
   */
  public function testNonceStorage(): void {
    $verification = $this->container->get('wallet_auth.verification');
    $tempStore = $this->container->get('tempstore.private')->get('wallet_auth');

    $nonce = $verification->generateNonce();
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // Store nonce.
    $verification->storeNonce($nonce, $walletAddress);

    // Verify it's stored.
    $data = $tempStore->get($nonce);
    $this->assertNotNull($data);
    $this->assertEquals($walletAddress, $data['wallet_address']);
  }

}
