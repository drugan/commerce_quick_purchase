<?php

namespace Drupal\Tests\commerce_quick_purchase\FunctionalJavascript;

use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the autocompletion on the form textfield.
 *
 * @covers \Drupal\commerce_quick_purchase\Controller\QuickPurchaseAutocompleteController
 * @covers \Drupal\commerce_quick_purchase\Plugin\Block\QuickPurchaseAddToCartBlock::getVariationsLabelsBySkuOrTitle()
 *
 * @group commerce
 */
class QuickPurchaseAddToCartBlockJavascriptTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

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
  }

  /**
   * Tests autocomplete suggestions based on the title substrings.
   */
  public function testTextfieldAutocompletionTitle() {
    $this->drupalGet('<front>');

    $this->assertSession()->pageTextContains('Quickly add any product to cart');
    $autocomplete_field = $this->getSession()->getPage()->findField('purchased_entity');
    $this->assertNotNull($autocomplete_field, 'Autocomplete field exists');
    $add_to_cart_button = $this->getSession()->getPage()->findButton('Add to cart');
    $this->assertNotNull($add_to_cart_button, 'Add to cart button exists');
    $this->assertEmpty($autocomplete_field->getValue(), 'Autocomplete field is empty');

    $autocomplete_field->setValue('t');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');

    $this->assertCount(2, $results);
    $this->assertSession()->pageTextContains('Test_1 8.88 USD (SKU: sku-1)');
    $this->assertSession()->pageTextContains('2_Test 9.99 USD (SKU: sku-2)');
    $this->assertSession()->pageTextNotContains($this->variation);
    $results[1]->click();
    $value = $autocomplete_field->getValue();
    $this->assertEquals('2_Test 9.99 USD (SKU: sku-2)', $value, "$value === 2_Test 9.99 USD (SKU: sku-2)");
  }

  /**
   * Tests autocomplete suggestions based on the SKU substrings.
   */
  public function testTextfieldAutocompletionSku() {
    $this->drupalGet('<front>');

    $this->assertSession()->pageTextContains('Quickly add any product to cart');
    $autocomplete_field = $this->getSession()->getPage()->findField('purchased_entity');
    $this->assertNotNull($autocomplete_field, 'Autocomplete field exists');
    $add_to_cart_button = $this->getSession()->getPage()->findButton('Add to cart');
    $this->assertNotNull($add_to_cart_button, 'Add to cart button exists');
    $this->assertEmpty($autocomplete_field->getValue(), 'Autocomplete field is empty');

    $autocomplete_field->setValue('sku-');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');

    $this->assertCount(2, $results);
    $this->assertSession()->pageTextContains('Test_1 8.88 USD (SKU: sku-1)');
    $this->assertSession()->pageTextContains('2_Test 9.99 USD (SKU: sku-2)');
    $this->assertSession()->pageTextNotContains($this->variation);
    $results[0]->click();
    $value = $autocomplete_field->getValue();
    $this->assertEquals('Test_1 8.88 USD (SKU: sku-1)', $value, "$value === Test_1 8.88 USD (SKU: sku-1)");
  }

}
