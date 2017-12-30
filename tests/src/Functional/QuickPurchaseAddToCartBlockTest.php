<?php

namespace Drupal\Tests\commerce_quick_purchase\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the quickly add any product to cart form.
 *
 * @covers \Drupal\commerce_quick_purchase\Form\QuickPurchaseAddToCartForm
 *
 * @group commerce
 */
class QuickPurchaseAddToCartBlockTest extends CartBrowserTestBase {

  /**
   * The variation1 to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation1;

  /**
   * The variation2 to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation2;

  /**
   * The instance of the block to test against.
   *
   * @var \Drupal\commerce_quick_purchase\Plugin\Block\QuickPurchaseAddToCartBlock
   */
  protected $block;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_quick_purchase',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer blocks',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Rid off of the parent's variation because it has random label and SKU
    // values and therefore is useless for the test except that to disable it
    // and check for not having this variation in the autocomplete suggestions.
    $this->variation->setActive(FALSE);
    $this->variation->save();
    $this->variation->getProduct()->setUnpublished();
    $this->variation->getProduct()->save();

    $sku = $this->variation->getSku();
    $label = $this->variation->label();
    $price = $this->variation->getPrice()->__toString();
    $this->variation = "$label $price (SKU: $sku)";

    $this->variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'sku-1',
      'price' => [
        'number' => 8.88,
        'currency_code' => 'USD',
      ],
    ]);

    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Test_1',
      'stores' => [$this->store],
      'variations' => [$this->variation1],
    ]);

    $this->variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'sku-2',
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => '2_Test',
      'stores' => [$this->store],
      'variations' => [$this->variation2],
    ]);

    $plugin_id = 'commerce_quick_purchase_add_to_cart_block:new_commerce_quick_purchase_add_to_cart_block';
    $settings = [
      'id' => 'quicklyaddanyproducttocart',
      'block_id' => 'quicklyaddanyproducttocart',
      'label' => 'Quickly add any product to cart',
    ];
    $this->placeBlock($plugin_id, $settings);
    $this->block = \Drupal::service('entity_type.manager')->getStorage('block')->load($settings['id'])->getPlugin();
  }

  /**
   * Tests adding to cart by inserting variation label or SKU in the textfield.
   */
  public function testAddToCartTextfieldValues() {
    $this->drupalGet('<front>');

    $this->assertSession()->pageTextContains('Quickly add any product to cart');
    $textfield = $this->getSession()->getPage()->findField('purchased_entity');
    $this->assertNotNull($textfield, 'Textfield exists');
    $add_to_cart_button = $this->getSession()->getPage()->findButton('Add to cart');
    $this->assertNotNull($add_to_cart_button, 'Add to cart button exists');
    $this->assertEmpty($textfield->getValue(), 'Textfield field is empty');

    // Empty textfield.
    $add_to_cart_button->click();
    $warning = 'Product which could be identified by the ??? is not available.';
    $this->assertSession()->pageTextContains($warning);

    // Wrong input.
    $textfield->setValue('WRONG');
    $value = $textfield->getValue();
    $this->assertEquals('WRONG', $value, "$value === WRONG");
    $add_to_cart_button->click();
    $warning = 'Product which could be identified by the WRONG is not available.';
    $this->assertSession()->pageTextContains($warning);

    // Inactive variation in the autocomplete suggestion format.
    $textfield->setValue($this->variation);
    $value = $textfield->getValue();
    $this->assertEquals($this->variation, $value, "$value === {$this->variation}");
    $add_to_cart_button->click();
    $warning = "Product which could be identified by the {$this->variation} is not available.";
    $this->assertSession()->pageTextContains($warning);

    // Autocomplete suggestion format.
    $textfield->setValue('Test_1 8.88 USD (SKU: sku-1)');
    $value = $textfield->getValue();
    $this->assertEquals('Test_1 8.88 USD (SKU: sku-1)', $value, "$value === Test_1 8.88 USD (SKU: sku-1)");
    $add_to_cart_button->click();
    $this->assertSession()->pageTextContains('Test_1 added to your cart.');

    // Variation label (title).
    $textfield->setValue('2_test');
    $value = $textfield->getValue();
    $this->assertEquals('2_test', $value, "$value === 2_test");
    $add_to_cart_button->click();
    $this->assertSession()->pageTextContains('2_Test added to your cart.');

    // Variation SKU.
    $textfield->setValue('sku-1');
    $value = $textfield->getValue();
    $this->assertEquals('sku-1', $value, "$value === sku-1");
    $add_to_cart_button->click();
    $this->assertSession()->pageTextContains('Test_1 added to your cart.');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertCount(2, $order_items);
    // The first order item has 2 combined items.
    $this->assertOrderItemInOrder($this->variation1, $order_items[0], 2);
    $this->assertOrderItemInOrder($this->variation2, $order_items[1]);
  }

}
