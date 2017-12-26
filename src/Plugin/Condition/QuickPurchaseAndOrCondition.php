<?php

namespace Drupal\commerce_quick_purchase\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'And Or' condition.
 *
 * @Condition(
 *   id = "commerce_quick_purchase_and_or",
 *   label = @Translation("Conditions logic"),
 * )
 */
class QuickPurchaseAndOrCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['all'] = [
      '#type' => 'radios',
      '#title' => $this->t('Set conjunction operator for the visibility conditions below'),
      '#default_value' => $this->configuration['all'],
      '#options' => [
        'and' => $this->t('<strong>@and</strong>: all of the conditions should pass.', ['@and' => 'AND']),
        'or' => $this->t('<strong>@or</strong>: at least one of the conditions should pass.', ['@or' => 'OR']),
      ],
      '#description' => $this->t('Note that this setting has no effect if visiblity is not restricted on any of the conditions.'),
    ];

    $form = parent::buildConfigurationForm($form, $form_state);

    if (isset($form['negate'])) {
      $form['negate']['#type'] = 'value';
      $negate = $form['negate']['#default_value'];
      $form['negate']['#value'] = $negate;
      unset($form['negate']['#title']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['all'] = $form_state->getValue('all');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {

    if (isset($this->configuration['all']) && $this->configuration['all'] == 'or') {
      return $this->t('OR: at least one of the conditions should pass.');
    }
    return $this->t('AND: all of the conditions should pass.');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // This should always pass because it serves just as a runtime config
    // whether to evaluate TRUE all other conditions or just one of them.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['all' => 'and'] + parent::defaultConfiguration();
  }

}
