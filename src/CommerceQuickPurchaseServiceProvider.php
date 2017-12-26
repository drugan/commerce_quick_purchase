<?php

namespace Drupal\commerce_quick_purchase;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service profiler for the commerce_quick_purchase module.
 */
class CommerceQuickPurchaseServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    if (isset($modules['commerce_product'])) {
      $container->getDefinition('commerce_product.variation_field_renderer')
        ->setClass('Drupal\commerce_quick_purchase\QuickPurchaseProductVariationFieldRenderer');
    }
  }

}
