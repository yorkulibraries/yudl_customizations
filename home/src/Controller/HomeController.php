<?php

namespace Drupal\home\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Home routes.
 */
class HomeController extends ControllerBase
{

  /**
   * Builds the response.
   */
  public function build()
  {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t(''),
    ];

    return $build;
  }
}
