<?php

namespace Drupal\commerce_quick_purchase;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\commerce_product\ProductVariationFieldRenderer;

/**
 * Overrides the ProductVariationFieldRenderer class.
 */
class QuickPurchaseProductVariationFieldRenderer extends ProductVariationFieldRenderer {

  /**
   * {@inheritdoc}
   */
  public function replaceRenderedFields(AjaxResponse $response, ProductVariationInterface $variation, $view_mode = 'default') {
    $rendered_fields = $this->renderFields($variation, $view_mode);
    foreach ($rendered_fields as $rendered_field) {
      // Running ReplaceCommand on an empty field (the product variation field
      // widget) breaks 'purchased_entity' autocompletion field when
      // ::ajaxRefresh() is called.
      if (empty($rendered_field['0'])) {
        continue;
      }
      $response->addCommand(new ReplaceCommand('.' . $rendered_field['#ajax_replace_class'], $rendered_field));
    }
  }

}
