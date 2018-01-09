/**
 * @file
 * Block settings summary behaviour.
 */

(function ($, window, Drupal) {

  /**
   * Provide the summary information for the block settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.QuickPurchaseblockSettingsSummary = {
    attach() {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      /**
       * Create a summary for checkboxes in the provided context.
       *
       * @param {HTMLDocument|HTMLElement} context
       *   A context where one would find checkboxes to summarize.
       *
       * @return {string}
       *   A string with the summary.
       */
      function checkboxesSummary(context) {
        const vals = [];
        const $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
        const il = $checkboxes.length;
        for (let i = 0; i < il; i++) {
          vals.push($($checkboxes[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('details[data-drupal-selector|="edit-visibility-commerce-quick-purchase"], details[data-drupal-selector|="edit-settings-commerce-quick-purchase"]').drupalSetSummary(checkboxesSummary);

      $('details[data-drupal-selector="edit-visibility-commerce-quick-purchase-and-or"]').drupalSetSummary((context) => {
        $or = context.querySelector('[data-drupal-selector="edit-visibility-commerce-quick-purchase-and-or-all-or"]').checked;
        return $or ? 'OR' : 'AND';
      });

      $('[data-drupal-selector="edit-settings-commerce-quick-purchase-price"]').drupalSetSummary((context) => {
        $val = $(context).find('[data-drupal-selector="edit-settings-commerce-quick-purchase-price-price"]').val();
        if ($val > 0) {
          $negate = context.querySelector('[data-drupal-selector="edit-settings-commerce-quick-purchase-price-negate"]').checked;
          $operator = $negate ? '>= ' : '<= ';

          return $operator + $val;
        }

        return Drupal.t('Not restricted');
      });

    },
  };
}(jQuery, window, Drupal));
