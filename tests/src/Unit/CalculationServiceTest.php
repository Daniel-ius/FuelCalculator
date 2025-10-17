<?php

namespace Drupal\Tests\fuel_calculator\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Drupal\fuel_calculator\Service\CalculationService;

class CalculationServiceTest extends UnitTestCase
{
    protected $entityTypeManager;
    protected $entityStorage;
    protected $loggerFactory;
    protected $logger;
    protected $currentUser;
    protected $requestStack;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->entityStorage = $this->createMock(EntityStorageInterface::class);

        $this->entityTypeManager
            ->method('getStorage')
            ->with('fuel_calculation')
            ->willReturn($this->entityStorage);

        $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
        $this->logger = $this->createMock(LoggerChannelInterface::class);

        $this->loggerFactory
            ->method('get')
            ->with('fuel_calculator')
            ->willReturn($this->logger);

        $this->currentUser = $this->createMock(AccountProxyInterface::class);
        $this->currentUser
            ->method('getDisplayName')
            ->willReturn('Test User');
        $this->currentUser
            ->method('id')
            ->willReturn(1);

        $this->requestStack = $this->createMock(RequestStack::class);
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('127.0.0.1');
        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->service = new CalculationService(
            $this->entityTypeManager,
            $this->loggerFactory,
            $this->currentUser,
            $this->requestStack
        );
    }

    public function testCalculateFuelReturnsCorrectValues()
    {
        $distance = 200.0;
        $efficiency = 8.5;
        $price = 1.75;

        $mockEntity = $this->createMock(EntityInterface::class);
        $mockEntity->expects($this->once())->method('save');

        $this->entityStorage
            ->expects($this->once())
            ->method('create')
            ->willReturn($mockEntity);

        $result = $this->service->calculateFuel($distance, $efficiency, $price);

        $expectedFuel = ($distance * $efficiency) / 100;
        $expectedCost = $expectedFuel * $price;

        $this->assertEquals(number_format($expectedFuel, 2), $result['spent']);
        $this->assertEquals(number_format($expectedCost, 2), $result['cost']);
    }

    public function testCalculateFuelHandlesStorageExceptions()
    {
        $this->entityStorage
            ->method('create')
            ->willThrowException(new \Exception('storage error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to save fuel calculation: @error',
                ['@error' => 'storage error']
            );

        $result = $this->service->calculateFuel(100, 10, 2.0);

        $this->assertArrayHasKey('spent', $result);
        $this->assertArrayHasKey('cost', $result);
        $this->assertEquals('10.00', $result['spent']);
        $this->assertEquals('20.00', $result['cost']);
    }

    public function testCalculateFuelWithZeroValues()
    {
        $mockEntity = $this->createMock(EntityInterface::class);
        $mockEntity->expects($this->once())->method('save');

        $this->entityStorage
            ->method('create')
            ->willReturn($mockEntity);

        $result = $this->service->calculateFuel(0, 0, 0);

        $this->assertEquals('0.00', $result['spent']);
        $this->assertEquals('0.00', $result['cost']);
    }

    public function testCalculateFuelLogsCalculation()
    {
        $mockEntity = $this->createMock(EntityInterface::class);
        $this->entityStorage->method('create')->willReturn($mockEntity);

        $this->logger
            ->expects($this->once())
            ->method('notice')
            ->with(
                $this->stringContains('Fuel Calculator'),
                $this->arrayHasKey('@ip')
            );

        $this->service->calculateFuel(100, 7.5, 1.50);
    }
}
