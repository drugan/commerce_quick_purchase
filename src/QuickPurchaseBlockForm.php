<?php

namespace Drupal\commerce_quick_purchase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\block\BlockForm;

/**
 * Overrides the BlockForm class.
 */
class QuickPurchaseBlockForm extends BlockForm {

  /**
   * {@inheritdoc}
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state) {
    $form['visibility_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Block visibility'),
      '#parents' => ['visibility_tabs'],
      '#attached' => [
        'library' => [
          'block/drupal.block',
          'commerce_quick_purchase/block.settings',
        ],
      ],
      '#field_prefix' => $this->t('Leave all visibibility conditions empty if you want current block to be visible everywhere. Read more: <a href=":href" target="_blank">admin/help/commerce_quick_purchase#block-visibility</a>', [':href' => '/admin/help/commerce_quick_purchase#block-visibility']),
    ];
    $gathered_contexts = $this->manager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts'));
    // Put AND/OR condition at the very top.
    @uksort($gathered_contexts, function ($a, $b) {
      return $a != 'commerce_quick_purchase_and_or';
    });
    // @todo Allow list of conditions to be configured in
    //   https://www.drupal.org/node/2284687.
    $visibility = $this->entity->getVisibility();
    foreach ($gathered_contexts as $condition_id => $definition) {
      // Don't display the current theme condition.
      if ($condition_id == 'current_theme') {
        continue;
      }
      // Don't display the language condition until we have multiple languages.
      if ($condition_id == 'language' && !$this->language->isMultilingual()) {
        continue;
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, isset($visibility[$condition_id]) ? $visibility[$condition_id] : []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form[$condition_id] = $condition_form;
    }

    if (isset($form['language'])) {
      $form['language']['negate']['#type'] = 'value';
      $form['language']['negate']['#value'] = $form['language']['negate']['#default_value'];
    }
    // Remove product context provided by DC as it has no definition and may
    // confuse displaying under product type checkboxes a select element with
    // just one option "Product from URL".
    // @see https://www.drupal.org/node/2915167
    // @todo remove when they'll create @CommerceCondition plugin for the
    // context.
    if (isset($form['commerce_quick_purchase_product_type'])) {
      $form['commerce_quick_purchase_product_type']['context_mapping'] = [
        '#tree' => TRUE,
        'commerce_product' => [
          '#type' => 'value',
          '#value' => '@commerce_quick_purchase.commerce_product_route_context:commerce_product',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateVisibility(array $form, FormStateInterface $form_state) {
    if ($path = $form_state->getValue(['visibility', 'request_path'])) {
      // Forcibly set FALSE for negate if there is no pages defined,
      // otherwise the block will not be displayed even there is no any other
      // restrictions set.
      // @see \Drupal\Component\Plugin\Exception\ContextException\ConditionAccessResolverTrait
      if ((empty($path['pages']) || $path['pages'] === '*') && !empty($path['negate'])) {
        $form_state->setValue(['visibility', 'request_path', 'negate'], FALSE);
      }
    }
    parent::validateVisibility($form, $form_state);
  }

}
