<?php

namespace Drupal\fuel_calculator\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\fuel_calculator\Service\CalculationService;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 *
 * @RestResource(
 *   id = "fuel_calculator_resource",
 *   label = @Translation("Fuel Calculator"),
 *   uri_paths = {
 *     "create" = "/api/fuel-calculator/calculate"
 *   }
 * )
 */
class FuelCalculatorResource extends ResourceBase {

  /**
   *
   * @var \Drupal\fuel_calculator\Service\CalculationService
   */
  protected $calculationService;

  /**
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\fuel_calculator\Service\CalculationService $calculation_service
   *   The calculation service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    CalculationService $calculation_service,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->calculationService = $calculation_service;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('fuel_calculator'),
      $container->get('fuel_calculator.calculation_service'),
      $container->get('current_user')
    );
  }

  /**
   *
   * @param array $data
   *   The request data containing distance, efficiency, and price.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(array $data) {

    if (!$this->currentUser->hasPermission('access fuel calculator api')) {
      throw new AccessDeniedHttpException();
    }

    $required_fields = ['distance', 'efficiency', 'price'];
    foreach ($required_fields as $field) {
      if (!isset($data[$field]) || !is_numeric($data[$field])) {
        throw new BadRequestHttpException(sprintf('Missing or invalid field: %s', $field));
      }
    }

    $distance = (float) $data['distance'];
    $efficiency = (float) $data['efficiency'];
    $price = (float) $data['price'];

    if ($distance <= 0) {
      throw new BadRequestHttpException('Distance must be greater than 0.');
    }
    if ($efficiency <= 0) {
      throw new BadRequestHttpException('Fuel efficiency must be greater than 0.');
    }
    if ($price <= 0) {
      throw new BadRequestHttpException('Price per liter must be greater than 0.');
    }

    $results = $this->calculationService->calculateFuel($distance, $efficiency, $price);

    $response_data = [
      'status' => 'success',
      'input' => [
        'distance' => $distance,
        'efficiency' => $efficiency,
        'price' => $price,
      ],
      'results' => [
        'fuel_spent' => $results['spent'],
        'fuel_cost' => $results['cost'],
      ],
      'timestamp' => date('Y-m-d H:i:s'),
    ];

    return new ModifiedResourceResponse($response_data, 200);
  }

}
