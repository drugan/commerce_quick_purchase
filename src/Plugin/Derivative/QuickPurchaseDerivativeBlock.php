<?php

namespace Drupal\commerce_quick_purchase\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for Quickly add any product to cart forms.
 *
 * @see \Drupal\commerce_quick_purchase\Plugin\Block\QuickPurchaseAddToCartBlock
 */
class QuickPurchaseDerivativeBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var string
   */
  protected $baseId;

  /**
   * Constructs new QuickPurchaseAddToCartBlock.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   */
  public function __construct($base_plugin_id) {
    $this->baseId = $base_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $this->derivatives["new_{$this->baseId}"] = $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
