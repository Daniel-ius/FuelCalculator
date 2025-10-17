<?php

/**
 * Service for calculating fuel.
 *
 * @file
 * Contains \Drupal\fuel_calculator\Service\CalculationService.
 */

namespace Drupal\fuel_calculator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Calculation service
 *
 */
class CalculationService
{
    /**
     * The logger factory.
     *
     * @var LoggerChannelFactoryInterface
     */
    protected $logger;

    /**
     * The current user.
     *
     * @var AccountProxyInterface
     */
    protected $currentUser;

    /**
     * The request stack.
     *
     * @var RequestStack
     */
    protected $requestStack;


    /**
     * Entity type manager.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * EntityTypeManagerInterface $entity_type_manager
     *
     * @param EntityTypeManagerInterface    $entity_type_manager
     *
     * LoggerChannelFactoryInterface $logger_factory
     * @param LoggerChannelFactoryInterface $logger_factory
     *
     * AccountProxyInterface $current_user
     * @param AccountProxyInterface         $current_user
     *
     * RequestStack $request_stack
     * @param RequestStack                  $request_stack
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        LoggerChannelFactoryInterface $logger_factory,
        AccountProxyInterface $current_user,
        RequestStack $request_stack
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger_factory->get('fuel_calculator');
        $this->currentUser = $current_user;
        $this->requestStack = $request_stack;
    }

    /**
     * Calculate fuel.
     *
     * Distance in km.
     *
     * @param  float $distance
     *
     * Efficiency L/100km.
     * @param  float $efficiency
     *
     * Price per liter.
     * @param  float $price
     *
     * Returns array with spent and cost.
     * @return array
     */
    public function calculateFuel(
        float $distance,
        float $efficiency,
        float $price
    ) {
        $spent = $distance * $efficiency / 100;
        $cost = $spent * $price;

        $request = $this->requestStack->getCurrentRequest();
        $client_ip = $request ? $request->getClientIp() : 'CLI';


        $this->logger->notice(
            'Fuel Calculator: IP: @ip, User: @user, Distance: @distance km, Consumption: @consumption L/100km, Price: @price, Fuel: @fuel L, Cost: @cost',
            [
                '@ip' => $client_ip,
                '@user' => $this->currentUser->getDisplayName(),
                '@distance' => number_format($distance, 1),
                '@consumption' => number_format($efficiency, 2),
                '@price' => number_format($price, 2),
                '@fuel' => number_format($spent, 2),
                '@cost' => number_format($cost, 2),
            ]
        );

        try {
            $calculation = $this->entityTypeManager->getStorage(
                'fuel_calculation'
            )->create(
                [
                    'distance' => $distance,
                    'efficiency' => $efficiency,
                    'price' => $price,
                    'fuel_spent' => $spent,
                    'fuel_cost' => $cost,
                    'user_id' => $this->currentUser->id(),
                    'ip_address' => $client_ip,
                    ]
            );
            $calculation->save();
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to save fuel calculation: @error',
                ['@error' => $e->getMessage()]
            );
        }
        return [
            'spent' => number_format($spent, 2),
            'cost' => number_format($cost, 2)
        ];
    }
}
