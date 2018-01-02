<?php

namespace Drupal\commerce_quick_purchase\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_store\StoreStorageInterface;
use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_product\ProductVariationStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_quick_purchase\Form\QuickPurchaseAddToCartForm;

/**
 * Provides a 'Quickly add any product to cart' block.
 *
 * @Block(
 *   id = "commerce_quick_purchase_add_to_cart_block",
 *   admin_label = @Translation("Quickly add any product to cart"),
 *   category = @Translation("Commerce"),
 *   deriver = "Drupal\commerce_quick_purchase\Plugin\Derivative\QuickPurchaseDerivativeBlock"
 * )
 */
class QuickPurchaseAddToCartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The variation IDs.
   *
   * @var array|bool
   */
  protected $variationIds;

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storeStorage;

  /**
   * The product storage.
   *
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $productStorage;

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $productVariationStorage;

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * The product variation type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productVariationTypeStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new QuickPurchaseAddToCartBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_store\StoreStorageInterface $store_storage
   *   The store storage.
   * @param \Drupal\commerce\CommerceContentEntityStorage $product_storage
   *   The product storage.
   * @param \Drupal\commerce_product\ProductVariationStorageInterface $product_variation_storage
   *   The product variation storage.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $product_type_storage
   *   The product type storage.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $product_variation_type_storage
   *   The product variation type storage.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StoreStorageInterface $store_storage, CommerceContentEntityStorage $product_storage, ProductVariationStorageInterface $product_variation_storage, ConfigEntityStorageInterface $product_type_storage, ConfigEntityStorageInterface $product_variation_type_storage, AccountProxyInterface $current_user, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->storeStorage = $store_storage;
    $this->productStorage = $product_storage;
    $this->productVariationStorage = $product_variation_storage;
    $this->productTypeStorage = $product_type_storage;
    $this->productVariationTypeStorage = $product_variation_type_storage;
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager->getStorage('commerce_store'),
      $entity_type_manager->getStorage('commerce_product'),
      $entity_type_manager->getStorage('commerce_product_variation'),
      $entity_type_manager->getStorage('commerce_product_type'),
      $entity_type_manager->getStorage('commerce_product_variation_type'),
      $container->get('current_user'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $template = <<<'HTML'
<h2>Product title: {{ variation.getProduct.getTitle }}</h2>
<h2>Variation title: {{ variation.getTitle }}</h2>
<p>SKU: {{ variation.getSku }}</p>
<p>Formatted price: {{ variation.getPrice|commerce_price_format }} </p>
<p>Price number: {{ variation.getPrice.number }}</p>
<p>Price currencyCode: {{ variation.getPrice.currencyCode }}</p>
HTML;

    return [
      'block_id' => '',
      'redirection' => 'no',
      'do_not_add_to_cart' => 0,
      'button_text' => $this->t('Add to cart'),
      'use_image_button' => 0,
      'image_button' => '',
      'placeholder' => '',
      'description' => '',
      'autocomplete' => 1,
      'autocomplete_threshold' => 10,
      'default_value' => '',
      'show_price' => 1,
      'stores' => [],
      'stores_negate' => 0,
      'product_types' => [],
      'product_types_negate' => 0,
      'variation_types' => [],
      'variation_types_negate' => 0,
      'published_active' => [
        'published' => 'published',
        'active' => 'active',
      ],
      'published_active_negate' => 0,
      'price' => 0,
      'price_negate' => 0,
      'use_template' => 0,
      'template' => $template,
      'external_template' => 0,
      'library' => 'commerce_quick_purchase/form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form['help'] = [
      '#type' => 'item',
      '#title' => $this->t('Need help?'),
      '#markup' => $this->t('A verbose tutorial with a lot of screenshots can be found here: <a href=":href" target="_blank">admin/help/commerce_quick_purchase</a>', [':href' => '/admin/help/commerce_quick_purchase']),
    ];

    $form['redirection'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirection'),
      '#field_prefix' => $this->t('Choose if you prefer stay on the page or redirect the user after submitting the form.'),
      '#default_value' => $config['redirection'],
      '#options' => [
        'no' => $this->t('No redirection'),
        'cart_page' => $this->t('Redirection on cart page'),
        'checkout_page' => $this->t('Redirection on checkout page'),
        'variation_page' => $this->t('Redirection on the selected product variation page'),
      ],
    ];

    $form['do_not_add_to_cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not add variation to a shopping cart, just redirect to its page.'),
      '#default_value' => $config['do_not_add_to_cart'],
      '#states' => [
        'visible' => [
          ':input[name="settings[redirection]"]' => ['value' => 'variation_page'],
        ],
      ],
    ];

    $form['autocomplete'] = [
      '#type' => 'radios',
      '#title' => $this->t('The "Quickly add to cart" field type'),
      '#field_prefix' => $this->t('Whether to autocomplete product variation suggestions in the dropdown list while user types in the field.'),
      '#default_value' => $config['autocomplete'],
      '#options' => [
        $this->t('Do not autocomplete'),
        $this->t('Autocomplete'),
      ],
    ];

    $form['autocomplete_threshold'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 2,
      '#max' => 100,
      '#default_value' => $config['autocomplete_threshold'],
      '#title' => $this->t('Autocomplete threshold'),
      '#description' => $this->t('The maximum number of suggestions to display while user types in the autocomplete field. This value can also be narrowed down by any of the "Variations availability" settings below.'),
      '#placeholder' => $this->t('min = 1, max = 100'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="settings[autocomplete]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['show_price'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show price'),
      '#field_prefix' => $this->t('Whether to show product variation price in the autocomplete field suggestions.'),
      '#default_value' => $config['show_price'],
      '#options' => [
        'No',
        'Yes',
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[autocomplete]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $config['button_text'],
      '#description' => $this->t('Defines the text for the submit button.'),
      '#required' => TRUE,
    ];

    $form['use_image_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use image button instead of a button text.'),
      '#default_value' => $config['use_image_button'],
    ];

    if (empty($config['image_button'])) {
      $path = \Drupal::service('module_handler')->getModule('commerce')->getPath();
      $config['image_button'] = "$path/icons/000000/drupal-cart.svg";
    }

    $form['image_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image button'),
      '#default_value' => $config['image_button'],
      '#description' => $this->t('Defines the src attribute of the image button. Can be absolute or relative URL of the image. Note that text defined for a button text above will be used for title and alt attributes of the image button.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[use_image_button]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $config['placeholder'],
      '#description' => $this->t('(optional) The text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $config['description'],
      '#description' => $this->t('(optional) The text that will be shown below the field to give a more verbose instructions
for a user on the field usage.'),
    ];

    // The settings below can be used only after the block creation.
    if (!empty($config['block_id'])) {

      $form['default_value'] = [
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'commerce_quick_purchase.sku_autocomplete',
        '#autocomplete_route_parameters' => ['id' => $config['block_id']],
        '#title' => $this->t('Default value'),
        '#default_value' => $config['default_value'],
        '#description' => $this->t('(optional) The text to display in the "Quickly add to cart" field by default. Can be a product variation title and/or SKU.'),
      ];

      $form['use_template'] = [
        '#access' => $this->currentUser->hasPermission('use text format full_html'),
        '#type' => 'checkbox',
        '#title' => $this->t('Create inline template instead of displaying default value in the text field.'),
        '#default_value' => $config['use_template'],
        '#states' => [
          'invisible' => [
            ':input[name="settings[default_value]"]' => ['value' => ''],
          ],
        ],
      ];

      $form['template'] = [
        '#access' => $this->currentUser->hasPermission('use text format full_html'),
        '#type' => 'textarea',
        '#title' => $this->t('Inline template'),
        '#default_value' => $config['template'],
        '#description' => $this->t('Only users having "Full HTML" text format permission can access this feature (you). Any valid HTML and/or Twig syntax can be used for template creating. The default variation object is passed to the template and might be used as it shown in the template example. Note that created template is valid only for the current block instance. Still, you may create a reusable template in your custom module and insert the template name instead of HTML/Twig code in the field. Read more: <a href=":href" target="_blank">admin/help/commerce_quick_purchase#external-template</a>', [':href' => '/admin/help/commerce_quick_purchase#external-template']),
        '#states' => [
          'visible' => [
            ':input[name="settings[use_template]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['library'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Library'),
      '#default_value' => $config['library'],
      '#description' => $this->t('The library to use with the current block instance. Can be any valid <a href=":href" target="_blank">Drupal library</a>.', [':href' => 'https://www.drupal.org/node/2274843']),
      '#suffix' => '<br><br>',
    ];

    $form['availability_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Variations availability'),
      '#parents' => ['availability_tabs'],
      '#attached' => [
        'library' => [
          'block/drupal.block',
          'commerce_quick_purchase/block.settings',
        ],
      ],
      '#field_prefix' => $this->t('Leave all availability conditions empty if you want all existing variations (unpublished products and inactive variations including) to be available from the current block. Read more: <a href=":href" target="_blank">admin/help/commerce_quick_purchase#variations-availability</a>', [':href' => '/admin/help/commerce_quick_purchase#variations-availability']),
      '#suffix' => '<br><br>',
    ];

    $stores = $product_types = $variation_types = [];
    foreach ($this->storeStorage->loadMultiple() as $store) {
      $stores[$store->id()] = $store->label();
    }
    foreach ($this->productTypeStorage->loadMultiple() as $type) {
      $product_types[$type->id()] = $type->label();
    }
    foreach ($this->productVariationTypeStorage->loadMultiple() as $type) {
      $variation_types[$type->id()] = $type->label();
    }

    if ($stores || $product_types || $variation_types) {
      $form['commerce_quick_purchase_published_active'] = [
        '#access' => $this->currentUser->hasPermission('administer commerce_order'),
        '#type' => 'details',
        '#title' => $this->t('Published AND/OR active'),
        '#group' => 'availability_tabs',
        'published_active' => [
          '#type' => 'checkboxes',
          '#options' => [
            'published' => $this->t('Only variations whose product is published are available'),
            'active' => $this->t('Only active variations are available (their product might be unpublished)'),
          ],
          '#default_value' => $config['published_active'],
        ],
        'negate' => [
          '#type' => 'checkbox',
          '#field_prefix' => '<br>',
          '#title' => $this->t('Negate the condition.'),
          '#default_value' => $config['published_active_negate'],
          '#states' => [
            'visible' => [
              ':input[name^="settings[commerce_quick_purchase_published_active]["]' => ['checked' => TRUE],
            ],
          ],
        ],
      ];
    }

    if ($stores) {
      $form['commerce_quick_purchase_stores'] = [
        '#type' => 'details',
        '#title' => $this->t('Stores'),
        '#group' => 'availability_tabs',
        'stores' => [
          '#type' => 'checkboxes',
          '#options' => $stores,
          '#default_value' => $config['stores'],
        ],
        'negate' => [
          '#type' => 'checkbox',
          '#field_prefix' => '<br>',
          '#title' => $this->t('Negate the condition.'),
          '#default_value' => $config['stores_negate'],
          '#states' => [
            'visible' => [
              ':input[name^="settings[commerce_quick_purchase_stores][stores]["]' => ['checked' => TRUE],
            ],
          ],
        ],
      ];
    }

    if ($product_types) {
      $form['commerce_quick_purchase_product_types'] = [
        '#type' => 'details',
        '#title' => $this->t('Product types'),
        '#group' => 'availability_tabs',
        'product_types' => [
          '#type' => 'checkboxes',
          '#options' => $product_types,
          '#default_value' => $config['product_types'],
        ],
        'negate' => [
          '#type' => 'checkbox',
          '#field_prefix' => '<br>',
          '#title' => $this->t('Negate the condition.'),
          '#default_value' => $config['product_types_negate'],
          '#states' => [
            'visible' => [
              ':input[name^="settings[commerce_quick_purchase_product_types][product_types]["]' => ['checked' => TRUE],
            ],
          ],
        ],
      ];
    }

    if ($variation_types) {
      $form['commerce_quick_purchase_variation_types'] = [
        '#type' => 'details',
        '#title' => $this->t('Variation types'),
        '#group' => 'availability_tabs',
        'variation_types' => [
          '#type' => 'checkboxes',
          '#options' => $variation_types,
          '#default_value' => $config['variation_types'],
        ],
        'negate' => [
          '#type' => 'checkbox',
          '#field_prefix' => '<br>',
          '#title' => $this->t('Negate the condition.'),
          '#default_value' => $config['variation_types_negate'],
          '#states' => [
            'visible' => [
              ':input[name^="settings[commerce_quick_purchase_variation_types][variation_types]["]' => ['checked' => TRUE],
            ],
          ],
        ],
      ];

      $form['commerce_quick_purchase_price'] = [
        '#type' => 'details',
        '#title' => $this->t('Variation price'),
        '#group' => 'availability_tabs',
        'price' => [
          '#type' => 'number',
          '#title' => $this->t('Price number is less than or equal to'),
          '#description' => $this->t('Set number to 0 for no price condition.'),
          '#step' => 0.000001,
          '#min' => 0,
          '#default_value' => $config['price'],
        ],
        'negate' => [
          '#type' => 'checkbox',
          '#field_prefix' => '<br>',
          '#title' => $this->t('Negate the condition.'),
          '#default_value' => $config['price_negate'],
          '#states' => [
            'invisible' => [
              ':input[name="settings[commerce_quick_purchase_price][price]"]' => ['value' => '0'],
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fields = [
      'published_active',
      'stores',
      'product_types',
      'variation_types',
      'price',
    ];

    foreach ($fields as $field) {
      if (!isset($values["commerce_quick_purchase_{$field}"][$field])) {
        continue;
      }
      $value = $values["commerce_quick_purchase_{$field}"][$field];
      if ($field == 'stores' || $field == 'product_types' || $field == 'variation_types') {
        $value = array_filter($value);
      }
      elseif ($field == 'price') {
        $value = (float) $value;
      }
      $this->setConfigurationValue($field, $value);
      $this->setConfigurationValue("{$field}_negate", $value ? (int) $values["commerce_quick_purchase_{$field}"]['negate'] : 0);
    }

    if (empty($this->getVariationIds())) {
      $form_state->setErrorByName('availability_tabs', $this->t('No variations available with the current "Variations availability" settings.'));
    }

    $str = isset($values['default_value']) ? $values['default_value'] : '';
    if (!($str ? $this->getVariationBySkuOrTitle($str) : TRUE)) {
      $form_state->setErrorByName('default_value', $this->t('Default variation which could be identified by the %str is not available.', ['%str' => $str ?: '???']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $default = $this->defaultConfiguration();
    $values = $form_state->getValues();
    $do_not_add_cart = $values['redirection'] == 'variation_page' ? $values['do_not_add_to_cart'] : 0;

    $this->configuration['block_id'] = $form_state->getFormObject()->getEntity()->id();
    $this->configuration['redirection'] = $values['redirection'];
    $this->configuration['do_not_add_to_cart'] = (int) $do_not_add_cart;
    $this->configuration['autocomplete'] = (int) $values['autocomplete'];
    $this->configuration['autocomplete_threshold'] = (int) $values['autocomplete_threshold'];
    $this->configuration['show_price'] = (int) $values['show_price'];
    $this->configuration['button_text'] = $values['button_text'];
    $this->configuration['use_image_button'] = (int) $values['use_image_button'];
    $this->configuration['image_button'] = $values['image_button'] ?: $default['image_button'];
    $this->configuration['placeholder'] = $values['placeholder'];
    $this->configuration['description'] = $values['description'];
    $this->configuration['default_value'] = isset($values['default_value']) ? $values['default_value'] : '';
    $this->configuration['library'] = empty($values['library']) ? $default['library'] : $values['library'];
    $this->configuration['use_template'] = empty($values['default_value']) ? 0 : (int) $values['use_template'];
    $this->configuration['external_template'] = 0;
    if (empty($values['template'])) {
      $this->configuration['use_template'] = 0;
      $this->configuration['template'] = $default['template'];
    }
    else {
      $this->configuration['template'] = $values['template'];
      $new_value = preg_replace('/[^a-z0-9_]+/', '_', strtolower($values['template']));
      $new_value = preg_replace('/_+/', '_', $new_value);
      // Seems the template value is a valid Drupal template (#theme) name.
      if ($new_value === $values['template']) {
        $this->configuration['external_template'] = 1;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;
    $args = [
      'has_variations' => !empty($this->getVariationIds()),
      'id' => $config['block_id'],
      'autocomplete' => $config['autocomplete'],
      'placeholder' => $config['placeholder'],
      'description' => $config['description'],
      'button_text' => $config['button_text'],
      'use_image_button' => $config['use_image_button'],
      'image_button' => $config['use_image_button'] ? $config['image_button'] : '',
      'default_value' => '',
      'use_template' => 0,
      'template' => '',
      'external_template' => 0,
      'library' => $config['library'],
    ];

    if ($args['has_variations'] && $config['default_value']) {
      if ($variation = $this->getVariationBySkuOrTitle($config['default_value'])) {
        $args['default_value'] = $config['default_value'];
        if ($config['use_template']) {
          $args['use_template'] = $config['use_template'];
          $args['template'] = $config['template'];
          $args['external_template'] = $config['external_template'];
          $args['variation'] = $variation;
        }
      }
    }

    return $this->formBuilder->getForm(QuickPurchaseAddToCartForm::class, $args);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationBySkuOrTitle($str = '') {
    if (!$str || !($ids = $this->getVariationIds())) {
      return;
    }
    $properties = [];
    preg_match('/\ \(SKU:\ (.*)\)$/', $str, $matches);

    if ($sku = isset($matches[1]) ? $matches[1] : NULL) {
      $variation = $this->productVariationStorage->loadBySku($sku);
      if ($variation && is_array($ids)) {
        $variation = in_array($variation->id(), $ids) ? $variation : NULL;
      }
    }
    else {
      $properties['title'] = $str;
    }

    if (is_array($ids)) {
      $properties['variation_id'] = $ids;
    }
    if (!$sku && !$variation = $this->productVariationStorage->loadByProperties($properties)) {
      // As the last resort assume that raw SKU string was entered.
      unset($properties['title']);
      $properties['sku'] = $str;
      $variation = $this->productVariationStorage->loadByProperties($properties);
    }

    return is_array($variation) ? reset($variation) : $variation;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationsLabelsBySkuOrTitle($str = '') {
    if (!$str || !($ids = $this->getVariationsIdsBySkuOrTitle($str))) {
      return [];
    }
    $config = $this->configuration;
    $labels = [];

    foreach ($this->productVariationStorage->loadMultiple($ids) as $variation) {
      $sku = $variation->getSku();
      $label = $variation->label();
      $price = $config['show_price'] ? $variation->getPrice()->__toString() : '';
      $value = "$label $price (SKU: $sku)";
      $label = "<span title=\"SKU: $sku\">$value</span>";
      $labels[] = ['value' => $value, 'label' => $label];
    }

    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationsIdsBySkuOrTitle($str = '') {
    if (!$str || !($ids = $this->getVariationIds())) {
      return;
    }
    $config = $this->configuration;
    $max = (int) ($config['autocomplete_threshold'] / 2);

    $variation_title = $this->productVariationStorage->getQuery();
    $variation_title->range(0, $max);
    is_array($ids) && $variation_title->condition('variation_id', $ids, 'IN');
    $variation_title->condition('title', $str, 'CONTAINS');

    $variation_sku = $this->productVariationStorage->getQuery();
    $variation_sku->range(0, $max);
    is_array($ids) && $variation_sku->condition('variation_id', $ids, 'IN');
    $variation_sku->condition('sku', $str, 'CONTAINS');

    $titles = (array) $variation_title->execute();
    $skus = (array) $variation_sku->execute();

    return array_values($titles + $skus);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationIds() {
    if ($this->variationIds) {
      return $this->variationIds;
    }
    $config = $this->configuration;

    $product_query = $this->productStorage->getQuery();
    if ($is_published = $config['published_active']['published']) {
      $status = $config['published_active_negate'] ? '0' : '1';
      $product_query->condition('status', $status, '=');
    }
    if ($config['stores']) {
      $operator = $config['stores_negate'] ? 'NOT IN' : 'IN';
      $product_query->condition('stores', $config['stores'], $operator);
    }
    if ($config['product_types']) {
      $operator = $config['product_types_negate'] ? 'NOT IN' : 'IN';
      $product_query->condition('type', $config['product_types'], $operator);
    }
    $product_ids = $is_published || $config['stores'] || $config['product_types'] ? $product_query->execute() : NULL;

    // The query was executed under some conditions and there is no valid IDs.
    if (!$product_ids && $product_ids !== NULL) {
      return [];
    }

    $variation_query = $this->productVariationStorage->getQuery();
    if ($product_ids) {
      $variation_query->condition('product_id', $product_ids, 'IN');
    }
    if ($is_active = $config['published_active']['active']) {
      $status = $config['published_active_negate'] ? '0' : '1';
      $variation_query->condition('status', $status, '=');
    }
    if ($config['variation_types']) {
      $operator = $config['variation_types_negate'] ? 'NOT IN' : 'IN';
      $variation_query->condition('type', $config['variation_types'], $operator);
    }
    if ($config['price']) {
      $operator = $config['price_negate'] ? '>=' : '<=';
      $variation_query->condition('price.number', $config['price'], $operator);
    }
    // If there is no conditions set for stores or product type or variation
    // type or variation price; it means that further queries should not have
    // 'variation_id' condition.
    $this->variationIds = $product_ids || $is_active || $config['variation_types'] || $config['price'] ? array_values($variation_query->execute()) : TRUE;

    return $this->variationIds;
  }

}
