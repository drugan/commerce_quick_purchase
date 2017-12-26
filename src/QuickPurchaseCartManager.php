<?php

namespace Drupal\commerce_quick_purchase;

use Drupal\commerce_cart\CartManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\OrderItemMatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\commerce\AvailabilityManagerInterface;

/**
 * Overrrides the cart manager service.
 */
class QuickPurchaseCartManager extends CartManager {

  /**
   * The availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManagerInterface
   */
  public $availabilityManager;

  /**
   * Constructs a new CartManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_cart\OrderItemMatcherInterface $order_item_matcher
   *   The order item matcher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce\AvailabilityManagerInterface $availability_manager
   *   The availability manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OrderItemMatcherInterface $order_item_matcher, EventDispatcherInterface $event_dispatcher, AvailabilityManagerInterface $availability_manager) {
    parent::__construct($entity_type_manager, $order_item_matcher, $event_dispatcher);
    $this->availabilityManager = $availability_manager;
  }

}
