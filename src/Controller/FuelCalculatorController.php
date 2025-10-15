<?php

namespace Drupal\fuel_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\fuel_calculator\Form\FuelCalculatorForm;

class FuelCalculatorController extends ControllerBase {

  public function content(){
    return \Drupal::formBuilder()->getForm('Drupal\fuel_calculator\Form\FuelCalculatorForm');
  }

}
