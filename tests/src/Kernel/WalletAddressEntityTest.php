<?php

declare(strict_types=1);

namespace Drupal\Tests\wallet_auth\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\wallet_auth\Entity\WalletAddress;

/**
 * Tests wallet address entity CRUD operations and constraints.
 *
 * @coversDefaultClass \Drupal\wallet_auth\Entity\WalletAddress
 * @group wallet_auth
 */
class WalletAddressEntityTest extends KernelTestBase {

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
   * A test user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wallet_address');
    $this->installSchema('externalauth', ['authmap']);

    // Create a test user for ownership tests.
    $this->testUser = User::create([
      'name' => 'test_user',
      'mail' => 'test@example.com',
      'status' => 1,
    ]);
    $this->testUser->save();

    // Ensure clean database for each test.
    $this->cleanupTestData();
  }

  /**
   * Cleanup test data.
   */
  protected function cleanupTestData(): void {
    // Delete all wallet address entities.
    $wallets = WalletAddress::loadMultiple();
    foreach ($wallets as $wallet) {
      $wallet->delete();
    }
  }

  /**
   * Tests creating and saving a wallet address entity.
   *
   * @covers ::create
   * @covers ::save
   * @covers ::getWalletAddress
   * @covers ::setWalletAddress
   * @covers ::getOwnerId
   * @covers ::isActive
   */
  public function testCreateWalletAddressEntity(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();

    // Verify entity was saved with an ID.
    $this->assertNotNull($entity->id());
    $this->assertIsNumeric($entity->id());

    // Verify field values.
    $this->assertEquals($walletAddress, $entity->getWalletAddress());
    $this->assertEquals($this->testUser->id(), $entity->getOwnerId());
    $this->assertTrue($entity->isActive());

    // Verify UUID was generated.
    $this->assertNotNull($entity->uuid());
    $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $entity->uuid());
  }

  /**
   * Tests loading a wallet address entity by ID and by properties.
   *
   * @covers ::load
   * @covers ::loadByProperties
   */
  public function testLoadWalletAddressEntity(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();
    $entityId = $entity->id();

    // Clear static cache to ensure fresh load.
    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    // Test load by ID.
    $loadedEntity = WalletAddress::load($entityId);
    $this->assertNotNull($loadedEntity);
    $this->assertEquals($walletAddress, $loadedEntity->getWalletAddress());
    $this->assertEquals($this->testUser->id(), $loadedEntity->getOwnerId());

    // Test load by properties - wallet address.
    $entities = $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->loadByProperties(['wallet_address' => $walletAddress]);
    $this->assertCount(1, $entities);
    $loadedByAddress = reset($entities);
    $this->assertEquals($entityId, $loadedByAddress->id());

    // Test load by properties - owner.
    $entities = $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->loadByProperties(['uid' => $this->testUser->id()]);
    $this->assertCount(1, $entities);

    // Test load by properties - non-existent wallet address.
    $entities = $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->loadByProperties(['wallet_address' => '0x0000000000000000000000000000000000000000']);
    $this->assertCount(0, $entities);
  }

  /**
   * Tests updating wallet address entity fields.
   *
   * @covers ::save
   * @covers ::setActive
   * @covers ::setLastUsedTime
   */
  public function testUpdateWalletAddressEntity(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();
    $entityId = $entity->id();

    // Update fields.
    $newLastUsed = time() + 1000;
    $entity->setActive(FALSE);
    $entity->setLastUsedTime($newLastUsed);
    $entity->save();

    // Clear static cache and reload.
    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $loadedEntity = WalletAddress::load($entityId);

    // Verify updates persisted.
    $this->assertFalse($loadedEntity->isActive());
    $this->assertEquals($newLastUsed, $loadedEntity->getLastUsedTime());

    // Verify original fields unchanged.
    $this->assertEquals($walletAddress, $loadedEntity->getWalletAddress());
    $this->assertEquals($this->testUser->id(), $loadedEntity->getOwnerId());
  }

  /**
   * Tests deleting a wallet address entity.
   *
   * @covers ::delete
   */
  public function testDeleteWalletAddressEntity(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();
    $entityId = $entity->id();

    // Verify entity exists.
    $this->assertNotNull(WalletAddress::load($entityId));

    // Delete entity.
    $entity->delete();

    // Clear static cache.
    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    // Verify entity is gone.
    $this->assertNull(WalletAddress::load($entityId));

    // Verify load by properties also returns empty.
    $entities = $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->loadByProperties(['wallet_address' => $walletAddress]);
    $this->assertCount(0, $entities);
  }

  /**
   * Tests that UniqueField constraint rejects duplicate wallet addresses.
   *
   * @covers ::baseFieldDefinitions
   */
  public function testUniqueWalletAddressConstraint(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // Create first entity.
    $entity1 = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity1->save();

    // Create a second user for the duplicate test.
    $user2 = User::create([
      'name' => 'test_user_2',
      'mail' => 'test2@example.com',
      'status' => 1,
    ]);
    $user2->save();

    // Attempt to create second entity with same wallet address.
    $entity2 = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $user2->id(),
      'status' => TRUE,
    ]);

    // Validate the entity - should have constraint violations.
    $violations = $entity2->validate();
    $this->assertGreaterThan(0, $violations->count());

    // Check that the violation is on the wallet_address field.
    $walletAddressViolations = $violations->getByField('wallet_address');
    $this->assertGreaterThan(0, count($walletAddressViolations));

    // Verify only one entity exists with this wallet address.
    $entities = $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->loadByProperties(['wallet_address' => $walletAddress]);
    $this->assertCount(1, $entities);
  }

  /**
   * Tests different wallet addresses are allowed.
   *
   * @covers ::baseFieldDefinitions
   */
  public function testDifferentWalletAddressesAllowed(): void {
    $walletAddress1 = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';
    $walletAddress2 = '0x1234567890123456789012345678901234567890';

    // Create first entity.
    $entity1 = WalletAddress::create([
      'wallet_address' => $walletAddress1,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity1->save();

    // Create second entity with different wallet address.
    $entity2 = WalletAddress::create([
      'wallet_address' => $walletAddress2,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);

    // Should have no validation errors.
    $violations = $entity2->validate();
    $this->assertEquals(0, $violations->count());

    // Should save successfully.
    $entity2->save();
    $this->assertNotNull($entity2->id());
  }

  /**
   * Tests owner relationship with User entity.
   *
   * @covers ::getOwner
   * @covers ::getOwnerId
   * @covers ::setOwner
   * @covers ::setOwnerId
   */
  public function testEntityOwnership(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();

    // Test getOwnerId.
    $this->assertEquals($this->testUser->id(), $entity->getOwnerId());

    // Test getOwner returns User entity.
    $owner = $entity->getOwner();
    $this->assertInstanceOf(User::class, $owner);
    $this->assertEquals($this->testUser->id(), $owner->id());
    $this->assertEquals('test_user', $owner->getAccountName());

    // Create another user.
    $user2 = User::create([
      'name' => 'test_user_2',
      'mail' => 'test2@example.com',
      'status' => 1,
    ]);
    $user2->save();

    // Test setOwnerId.
    $entity->setOwnerId((int) $user2->id());
    $entity->save();

    // Clear cache and reload.
    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $loadedEntity = WalletAddress::load($entity->id());
    $this->assertEquals($user2->id(), $loadedEntity->getOwnerId());

    // Test setOwner.
    $loadedEntity->setOwner($this->testUser);
    $loadedEntity->save();

    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $reloadedEntity = WalletAddress::load($entity->id());
    $this->assertEquals($this->testUser->id(), $reloadedEntity->getOwnerId());
  }

  /**
   * Tests that wallet address entity without owner can be saved.
   *
   * @covers ::getOwnerId
   */
  public function testEntityWithoutOwner(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // Create entity without specifying uid (defaults to anonymous/0).
    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'status' => TRUE,
    ]);
    $entity->save();

    // Owner ID should be 0 (anonymous user).
    $this->assertEquals(0, $entity->getOwnerId());
  }

  /**
   * Tests active status field toggling.
   *
   * @covers ::isActive
   * @covers ::setActive
   */
  public function testActiveStatusField(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // Test default status is TRUE (as defined in baseFieldDefinitions).
    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
    ]);
    $this->assertTrue($entity->isActive());

    // Test explicit FALSE status.
    $entity->setActive(FALSE);
    $this->assertFalse($entity->isActive());
    $entity->save();

    // Clear cache and reload to verify persistence.
    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $loadedEntity = WalletAddress::load($entity->id());
    $this->assertFalse($loadedEntity->isActive());

    // Toggle back to TRUE.
    $loadedEntity->setActive(TRUE);
    $loadedEntity->save();

    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $reloadedEntity = WalletAddress::load($entity->id());
    $this->assertTrue($reloadedEntity->isActive());
  }

  /**
   * Tests status field with explicit FALSE in create.
   *
   * @covers ::isActive
   */
  public function testActiveStatusFieldExplicitFalse(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    // Create with explicit FALSE status.
    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => FALSE,
    ]);
    $entity->save();

    $this->assertFalse($entity->isActive());

    // Reload and verify.
    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $loadedEntity = WalletAddress::load($entity->id());
    $this->assertFalse($loadedEntity->isActive());
  }

  /**
   * Tests created and last_used timestamp fields.
   *
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getLastUsedTime
   * @covers ::setLastUsedTime
   */
  public function testTimestampFields(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';
    $currentTime = time();

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();

    // Created timestamp should be set automatically on save.
    // Allow 5 second tolerance for test execution time.
    $createdTime = $entity->getCreatedTime();
    $this->assertGreaterThan(0, $createdTime);
    $this->assertEqualsWithDelta($currentTime, $createdTime, 5);

    // last_used should also be set on initial save.
    $lastUsedTime = $entity->getLastUsedTime();
    $this->assertGreaterThan(0, $lastUsedTime);
    $this->assertEqualsWithDelta($currentTime, $lastUsedTime, 5);

    // Test manual setting of created time.
    $customCreatedTime = 1600000000;
    $entity->setCreatedTime($customCreatedTime);
    $entity->save();

    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $loadedEntity = WalletAddress::load($entity->id());
    $this->assertEquals($customCreatedTime, $loadedEntity->getCreatedTime());

    // Test manual setting of last_used time.
    $customLastUsed = 1700000000;
    $loadedEntity->setLastUsedTime($customLastUsed);
    $loadedEntity->save();

    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $reloadedEntity = WalletAddress::load($entity->id());
    $this->assertEquals($customLastUsed, $reloadedEntity->getLastUsedTime());
  }

  /**
   * Tests that created timestamp is preserved on update.
   *
   * @covers ::getCreatedTime
   */
  public function testCreatedTimestampPreservedOnUpdate(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();

    $originalCreated = $entity->getCreatedTime();

    // Wait a moment and update the entity.
    sleep(1);
    $entity->setActive(FALSE);
    $entity->save();

    // Created time should remain the same.
    $this->assertEquals($originalCreated, $entity->getCreatedTime());
  }

  /**
   * Tests last_used timestamp can be updated independently.
   *
   * @covers ::setLastUsedTime
   * @covers ::getLastUsedTime
   */
  public function testLastUsedTimestampUpdate(): void {
    $walletAddress = '0x71C7656EC7ab88b098defB751B7401B5f6d8976F';

    $entity = WalletAddress::create([
      'wallet_address' => $walletAddress,
      'uid' => $this->testUser->id(),
      'status' => TRUE,
    ]);
    $entity->save();

    $initialLastUsed = $entity->getLastUsedTime();

    // Simulate authentication use - update last_used.
    $newLastUsed = $initialLastUsed + 3600;
    $entity->setLastUsedTime($newLastUsed);
    $entity->save();

    $this->container->get('entity_type.manager')
      ->getStorage('wallet_address')
      ->resetCache();

    $loadedEntity = WalletAddress::load($entity->id());
    $this->assertEquals($newLastUsed, $loadedEntity->getLastUsedTime());
    $this->assertGreaterThan($initialLastUsed, $loadedEntity->getLastUsedTime());
  }

  /**
   * Tests fluent interface for setters.
   *
   * @covers ::setWalletAddress
   * @covers ::setCreatedTime
   * @covers ::setLastUsedTime
   * @covers ::setActive
   */
  public function testFluentInterface(): void {
    $entity = WalletAddress::create([
      'uid' => $this->testUser->id(),
    ]);

    // All setters should return the entity instance for chaining.
    $result = $entity
      ->setWalletAddress('0x71C7656EC7ab88b098defB751B7401B5f6d8976F')
      ->setCreatedTime(1600000000)
      ->setLastUsedTime(1700000000)
      ->setActive(TRUE);

    $this->assertInstanceOf(WalletAddress::class, $result);
    $this->assertEquals('0x71C7656EC7ab88b098defB751B7401B5f6d8976F', $entity->getWalletAddress());
    $this->assertEquals(1600000000, $entity->getCreatedTime());
    $this->assertEquals(1700000000, $entity->getLastUsedTime());
    $this->assertTrue($entity->isActive());
  }

}
