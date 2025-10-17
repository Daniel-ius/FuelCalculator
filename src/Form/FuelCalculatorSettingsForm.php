<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FuelCalculatorSettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['fuel_calculator.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'fuel_calculator_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('fuel_calculator.settings');

        $form['default_distance'] = [
        '#type' => 'number',
        '#title' => $this->t('Default distance (km)'),
        '#default_value' => $config->get('default_distance') ?: 0,
        '#step' => 0.1,
        '#required' => true,
        ];

        $form['default_efficiency'] = [
        '#type' => 'number',
        '#title' => $this->t('Default fuel efficiency (L/100km)'),
        '#default_value' => $config->get('default_efficiency') ?: 0,
        '#step' => 0.1,
        '#required' => true,
        ];

        $form['default_price'] = [
        '#type' => 'number',
        '#title' => $this->t('Default fuel price per liter'),
        '#default_value' => $config->get('default_price') ?: 0,
        '#step' => 0.01,
        '#required' => true,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('fuel_calculator.settings')
            ->set('default_distance', $form_state->getValue('default_distance'))
            ->set('default_efficiency', $form_state->getValue('default_efficiency'))
            ->set('default_price', $form_state->getValue('default_price'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
