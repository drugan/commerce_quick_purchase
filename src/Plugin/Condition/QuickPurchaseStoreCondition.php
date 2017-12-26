<?php

namespace Drupal\commerce_quick_purchase\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Product store' condition.
 *
 * @Condition(
 *   id = "commerce_quick_purchase_product_store",
 *   label = @Translation("Store"),
 *   context = {
 *     "commerce_store" = @ContextDefinition("entity:commerce_store", label = @Translation("Store"))
 *   }
 * )
 */
class QuickPurchaseStoreCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Creates a new QuickPurchaseStoreCondition instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('commerce_store'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $stores = $this->entityStorage->loadMultiple();
    foreach ($stores as $store) {
      $options[$store->id()] = $store->label();
    }
    $form['product_stores'] = [
      '#title' => $this->t('Stores'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['product_stores'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['product_stores'] = array_filter($form_state->getValue('product_stores'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['product_stores']) > 1) {
      $stores = $this->configuration['product_stores'];
      $last = array_pop($stores);
      $stores = implode(', ', $stores);
      return $this->t('The product store is @stores or @last', ['@stores' => $stores, '@last' => $last]);
    }
    $store = reset($this->configuration['product_stores']);
    return $this->t('The product store is @store', ['@store' => $store]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['product_stores']) && !$this->isNegated()) {
      return TRUE;
    }
    $stores = (array) $this->getContextValue('commerce_store');
    foreach ($stores as $store) {
      if (!empty($this->configuration['product_stores'][$store->id()])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['product_stores' => []] + parent::defaultConfiguration();
  }

}
