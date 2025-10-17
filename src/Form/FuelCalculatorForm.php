<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fuel_calculator\Service\CalculationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class FuelCalculatorForm extends FormBase
{
    /**
     * The calculation service.
     *
     * @var \Drupal\fuel_calculator\Service\CalculationService
     */
    protected $calculator;

    /**
     * Constructs a new FuelCalculatorForm object.
     */
    public function __construct(CalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static($container->get('fuel_calculator.calculation_service'));
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'fuel_calculator_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Request $request = null)
    {
        $config = $this->config('fuel_calculator.settings');
        $request = $request ?: \Drupal::request();

        $distance_param = $request->query->get('distance') ?? $config->get('default_distance');
        $efficiency_param = $request->query->get('efficiency') ?? $config->get('default_efficiency');
        $price_param = $request->query->get('price') ?? $config->get('default_price');

        $form['#theme'] = 'fuel_calculator_form';
        $form['#prefix'] = '<div id="fuel-calculator-ajax-wrapper">';
        $form['#suffix'] = '</div>';

        $form['distance'] = [
        '#type' => 'number',
        '#title' => $this->t('Distance (km)'),
        '#default_value' => $form_state->getValue('distance', $distance_param),
        '#step' => 0.1,
        '#min' => 0.1,
        '#required' => true,
        ];

        $form['efficiency'] = [
        '#type' => 'number',
        '#title' => $this->t('Fuel efficiency (L/100km)'),
        '#default_value' => $form_state->getValue('efficiency', $efficiency_param),
        '#step' => 0.1,
        '#min' => 0.1,
        '#required' => true,
        ];

        $form['price'] = [
        '#type' => 'number',
        '#title' => $this->t('Price per liter (EUR)'),
        '#default_value' => $form_state->getValue('price', $price_param),
        '#step' => 0.01,
        '#min' => 0.01,
        '#required' => true,
        ];

        $form['actions'] = [
        '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Calculate'),
        '#button_type' => 'primary',
        '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'fuel-calculator-ajax-wrapper',
            'effect' => 'fade',
        ]
        ];

        $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
        '#limit_validation_errors' => [],
        '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'fuel-calculator-ajax-wrapper',
            'effect' => 'fade',
        ]
        ];

        if ($form_state->get('results')) {
            $results = $form_state->get('results');
            $form['results'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['fuel-result'], 'id' => 'fuel-calculator-result'],
            'spent' => [
            '#markup' => $this->t('Fuel spent: ') . $results['spent'] . ' ' . $this->t('liters'),
            ],
            'cost' => [
            '#markup' => $this->t('Total cost: ') . $results['cost'] . ' EUR',
            ],
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $distance = $form_state->getValue('distance');
        $efficiency = $form_state->getValue('efficiency');
        $price = $form_state->getValue('price');

        if ($distance <= 0) {
            $form_state->setErrorByName('distance', $this->t('Distance must be greater than 0.'));
        }
        if ($efficiency <= 0) {
            $form_state->setErrorByName('efficiency', $this->t('Fuel efficiency must be greater than 0.'));
        }
        if ($price <= 0) {
            $form_state->setErrorByName('price', $this->t('Price per liter must be greater than 0.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $distance = $form_state->getValue('distance');
        $efficiency = $form_state->getValue('efficiency');
        $price = $form_state->getValue('price');

        $results = $this->calculator->calculateFuel($distance, $efficiency, $price);
        $form_state->set('results', $results);
        $form_state->setRebuild();
    }

    public function ajaxCallback(array &$form, FormStateInterface $form_state)
    {
        if (empty($form)) {
            $form_builder = \Drupal::formBuilder();
            $form = $form_builder->getForm($this);
        }
        return $form;
    }


    /**
     * Reset form handler.
     */
    public function resetForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('fuel_calculator.settings');

        $default_distance = $config->get('default_distance');
        $default_efficiency = $config->get('default_efficiency');
        $default_price = $config->get('default_price');

        $form_state->setValues([
        'distance' => $default_distance,
        'efficiency' => $default_efficiency,
        'price' => $default_price,
        ]);
        $input = $form_state->getUserInput();
        $input['distance'] = $default_distance;
        $input['efficiency'] = $default_efficiency;
        $input['price'] = $default_price;
        $form_state->setUserInput($input);

        $form_state->set('results', null);
        $form_state->setRebuild();
    }
}
