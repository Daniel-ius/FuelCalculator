<?php

namespace Drupal\fuel_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\fuel_calculator\Service\CalculationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FuelCalculatorController extends ControllerBase
{
    protected CalculationService $calculationService;

    public function __construct(CalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('fuel_calculator.calculation_service')
        );
    }

    public function calculate(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
            'status' => 'error',
            'message' => 'Invalid JSON',
            ], 400);
        }

        if (!isset($data['distance']) || !isset($data['consumption']) || !isset($data['price'])) {
            return new JsonResponse([
            'status' => 'error',
            'message' => 'Missing required fields: distance, consumption, price',
            ], 400);
        }

        if (!is_numeric($data['distance']) || !is_numeric($data['consumption']) || !is_numeric($data['price'])) {
            return new JsonResponse([
            'status' => 'error',
            'message' => 'All fields must be numeric values',
            ], 400);
        }

        try {
            $results = $this->calculationService->calculateFuel(
                $data['distance'],
                $data['consumption'],
                $data['price']
            );
            return new JsonResponse([
            'status' => 'success',
            'data' => [
              'results' => $results,
            ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
            'status' => 'error',
            'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function content()
    {
        return \Drupal::formBuilder()
        ->getForm('Drupal\fuel_calculator\Form\FuelCalculatorForm');
    }
}
