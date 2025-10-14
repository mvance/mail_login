<?php

namespace Drupal\Tests\mail_login\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\mail_login\AuthDecorator;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserAuthenticationInterface;

/**
 * Tests for the AuthDecorator class.
 *
 * @group mail_login
 */
class AuthDecoratorTest extends UnitTestCase {

  /**
   * The mocked user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userAuth;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $connection;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The mocked config object.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * The mocked messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * The mocked user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userStorage;

  /**
   * The AuthDecorator instance under test.
   *
   * @var \Drupal\mail_login\AuthDecorator
   */
  protected $authDecorator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks for all dependencies.
    $this->userAuth = $this->createMock(UserAuthInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->connection = $this->createMock(Connection::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);

    // Configure config factory to return our config mock.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('mail_login.settings')
      ->willReturn($this->config);

    // Configure entity type manager to return user storage mock.
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    // Create the AuthDecorator instance with mocked dependencies.
    $this->authDecorator = new AuthDecorator(
      $this->userAuth,
      $this->entityTypeManager,
      $this->connection,
      $this->configFactory,
      $this->messenger
    );
  }

  /**
   * Test that AuthDecorator can be instantiated with mocked dependencies.
   */
  public function testAuthDecoratorInstantiation() {
    $this->assertInstanceOf(AuthDecorator::class, $this->authDecorator);
    $this->assertInstanceOf(UserAuthInterface::class, $this->authDecorator);
    $this->assertInstanceOf(UserAuthenticationInterface::class, $this->authDecorator);
  }

}
