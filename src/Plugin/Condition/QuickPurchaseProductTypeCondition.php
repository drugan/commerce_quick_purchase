<?php

namespace Drupal\commerce_quick_purchase\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Product Type' condition.
 *
 * @Condition(
 *   id = "commerce_quick_purchase_product_type",
 *   label = @Translation("Product bundle"),
 *   context = {
 *     "commerce_product" = @ContextDefinition("entity:commerce_product", label = @Translation("Commerce product"))
 *   }
 * )
 */
class QuickPurchaseProductTypeCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Creates a new QuickPurchaseProductTypeCondition instance.
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
      $container->get('entity_type.manager')->getStorage('commerce_product_type'),
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
    $product_types = $this->entityStorage->loadMultiple();
    foreach ($product_types as $type) {
      $options[$type->id()] = $type->label();
    }
    $form['product_bundles'] = [
      '#title' => $this->t('Product types'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['product_bundles'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['product_bundles'] = array_filter($form_state->getValue('product_bundles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['product_bundles']) > 1) {
      $bundles = $this->configuration['product_bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return $this->t('The product bundle is @bundles or @last', ['@bundles' => $bundles, '@last' => $last]);
    }
    $bundle = reset($this->configuration['product_bundles']);
    return $this->t('The product bundle is @bundle', ['@bundle' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['product_bundles']) && !$this->isNegated()) {
      return TRUE;
    }
    $product = $this->getContextValue('commerce_product');
    return !empty($this->configuration['product_bundles'][$product->bundle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['product_bundles' => []] + parent::defaultConfiguration();
  }

}
