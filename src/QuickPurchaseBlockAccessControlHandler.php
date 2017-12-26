<?php

namespace Drupal\commerce_quick_purchase;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\block\BlockAccessControlHandler;

/**
 * Overrides the access control handler for the block entity type.
 *
 * @see \Drupal\block\Entity\Block
 */
class QuickPurchaseBlockAccessControlHandler extends BlockAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\block\BlockInterface $entity */
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }
    // Don't grant access to disabled blocks.
    if (!$entity->status()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    else {
      $conditions = [];
      $missing_context = FALSE;
      $visibility = $entity->getVisibilityConditions();
      $available_contexts = count($visibility);
      $and_or = 'and';
      if ($visibility->has('commerce_quick_purchase_and_or')) {
        $and_or = $visibility->get('commerce_quick_purchase_and_or')->getConfiguration()['all'];
        // This condition was need just to fetch and/or config setting.
        $visibility->removeInstanceId('commerce_quick_purchase_and_or');
      }
      foreach ($visibility as $condition_id => $condition) {
        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
            $this->contextHandler->applyContextMapping($condition, $contexts);
            $conditions[$condition_id] = $condition;
          }
          catch (ContextException $e) {
            $available_contexts--;
            // The required or last condition checked has no context.
            if (($and_or == 'and') || !$available_contexts) {
              $missing_context = TRUE;
            }
          }
        }
        else {
          $conditions[$condition_id] = $condition;
        }
      }
      if ($missing_context) {
        // If any context is missing then we might be missing cacheable
        // metadata, and don't know based on what conditions the block is
        // accessible or not. For example, blocks that have a node type
        // condition will have a missing context on any non-node route like the
        // frontpage.
        // @todo Avoid setting max-age 0 for some or all cases, for example by
        //   treating available contexts without value differently in
        //   https://www.drupal.org/node/2521956.
        $access = AccessResult::forbidden()->setCacheMaxAge(0);
      }
      elseif ($this->resolveConditions($conditions, $and_or) !== FALSE) {

        // Delegate to the plugin.
        $block_plugin = $entity->getPlugin();
        try {
          if ($block_plugin instanceof ContextAwarePluginInterface) {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($block_plugin->getContextMapping()));
            $this->contextHandler->applyContextMapping($block_plugin, $contexts);
          }
          $access = $block_plugin->access($account, TRUE);
        }
        catch (ContextException $e) {
          // Setting access to forbidden if any context is missing for the same
          // reasons as with conditions (described in the comment above).
          // @todo Avoid setting max-age 0 for some or all cases, for example by
          //   treating available contexts without value differently in
          //   https://www.drupal.org/node/2521956.
          $access = AccessResult::forbidden()->setCacheMaxAge(0);
        }
      }
      else {
        $access = AccessResult::forbidden();
      }

      $this->mergeCacheabilityFromConditions($access, $conditions);

      // Ensure that access is evaluated again when the block changes.
      return $access->addCacheableDependency($entity);
    }
  }

}
