<?php

namespace Drupal\commerce_quick_purchase;

use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_cart\OrderItemMatcher;

/**
 * Overrides the order item matcher.
 */
class QuickPurchaseOrderItemMatcher extends OrderItemMatcher {

  /**
   * {@inheritdoc}
   */
  public function matchAll(OrderItemInterface $order_item, array $order_items) {
    $purchased_entity = $order_item->getPurchasedEntity();
    if (empty($purchased_entity)) {
      // Don't support combining order items without a purchased entity.
      return [];
    }

    $comparison_fields = ['type', 'purchased_entity'];
    $event = new OrderItemComparisonFieldsEvent($comparison_fields, $order_item);
    $this->eventDispatcher->dispatch(CartEvents::ORDER_ITEM_COMPARISON_FIELDS, $event);
    $comparison_fields = $event->getComparisonFields();

    $matched_order_items = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $existing_order_item */
    foreach ($order_items as $existing_order_item) {
      foreach ($comparison_fields as $comparison_field) {
        if (!$existing_order_item->hasField($comparison_field) || !$order_item->hasField($comparison_field)) {
          // The field is missing on one of the order items.
          continue 2;
        }
        $existing_id = $existing_order_item->get($comparison_field)->first()->get('target_id')->getValue();
        $id = $order_item->get($comparison_field)->first()->get('target_id')->getValue();
        if ($existing_id !== $id) {
          continue 2;
        }
      }
      $matched_order_items[] = $existing_order_item;
    }

    return $matched_order_items;
  }

}
