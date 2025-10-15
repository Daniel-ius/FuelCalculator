<?php

namespace Drupal\fuel_calculator\Service;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class CalculationService {

  /**
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function calculateFuel(float $distance, float $efficiency, float $price): array
  {
    $spent=$distance*$efficiency/100;
    $cost= $spent*$price;
    Drupal::logger('fuel_calculator')->notice(
      'Fuel Calculator: IP: @ip, User: @user, Distance: @distance km, Consumption: @consumption L/100km, Price: @price, Fuel: @fuel L, Cost: @cost',
      [
        '@ip' => Drupal::request()->getClientIp(),
        '@user' => Drupal::currentUser()->getDisplayName(),
        '@distance' => number_format($distance, 1),
        '@consumption' => number_format($efficiency, 2),
        '@price' => number_format($price, 2),
        '@fuel' => number_format($spent, 2),
        '@cost' => number_format($cost, 2),
      ]
    );

    if (true) {
      try {
        $calculation = $this->entityTypeManager->getStorage('fuel_calculation')->create([
          'distance' => $distance,
          'efficiency' => $efficiency,
          'price' => $price,
          'fuel_spent' => $spent,
          'fuel_cost' => $cost,
          'user_id' => Drupal::currentUser()->id(),
          'ip_address' => Drupal::request()->getClientIp(),
        ]);
        $calculation->save();
      } catch (\Exception $e) {
        Drupal::logger('fuel_calculator')->notice('Failed to save fuel calculation: @error', ['@error' => $e->getMessage()]);
      }
    }

    return [
      'spent'=>number_format($spent,2),
      'cost'=>number_format($cost,2)
    ];
  }

}
