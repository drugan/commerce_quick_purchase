/**
 * @file
 * Block settings summary behaviour.
 */

(function ($, window, Drupal) {
  Drupal.behaviors.QuickPurchaseblockSettingsSummary = {
    attach: function attach() {
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }
      function checkboxesSummary(context) {
        var vals = [];
        var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
        var il = $checkboxes.length;
        for (var i = 0; i < il; i++) {
          vals.push($($checkboxes[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('details[data-drupal-selector|="edit-visibility-commerce-quick-purchase"], details[data-drupal-selector|="edit-settings-commerce-quick-purchase"]').drupalSetSummary(checkboxesSummary);

      $('details[data-drupal-selector="edit-visibility-commerce-quick-purchase-and-or"]')
        .drupalSetSummary(function (context) {
          $or = context.querySelector('[data-drupal-selector="edit-visibility-commerce-quick-purchase-and-or-all-or"]').checked;
          return $or ? 'OR' : 'AND';
        });

      $('[data-drupal-selector="edit-settings-commerce-quick-purchase-price"]')
        .drupalSetSummary(function (context) {
          $val = $(context).find('[data-drupal-selector="edit-settings-commerce-quick-purchase-price-price"]').val();
          if ($val > 0) {
            $negate = context.querySelector('[data-drupal-selector="edit-settings-commerce-quick-purchase-price-negate"]').checked;
            $operator = $negate ? '>= ' : '<= ';

            return $operator + $val;
          }

          return Drupal.t('Not restricted');
        });
    }
  };
})(jQuery, window, Drupal);
