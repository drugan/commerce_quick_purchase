<?php

namespace Drupal\commerce_quick_purchase\Form;

use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderItem;

/**
 * Overrides the commerce order item add to cart form.
 */
class QuickPurchaseAddToCartForm extends AddToCartForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_quick_purchase.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'commerce_quick_purchase_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (empty($this->formId)) {
      $this->formId = $this->getBaseFormId() . '_block_form';
    }
    return $this->formId;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (!($entity = parent::getEntity())) {
      return new OrderItem([], 'commerce_product');
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$form_state->has('entity_form_initialized')) {
      $form_state->set('entity_form_initialized', TRUE);
    }
    $args = $form_state->getBuildInfo()['args'][0];

    $form['block_id'] = [
      '#type' => 'hidden',
      '#value' => $args['id'],
    ];

    if ($args['use_template']) {
      $form['template'] = [
        '#context' => [
          'variation' => $args['variation'],
        ],
        '#prefix' => "<div id=\"{$args['id']}-template\" class=\"commerce-quick-purchase__template\">",
        '#suffix' => '</div>',
      ];
      if ($args['external_template']) {
        $form['template'] += [
          '#type' => 'item',
          '#theme' => [$args['template']],
        ];
      }
      else {
        $form['template'] += [
          '#type' => 'inline_template',
          '#template' => $args['template'],
        ];
      }
      $form['purchased_entity'] = [
        '#type' => 'hidden',
        '#value' => $args['default_value'],
        '#default_value' => $args['default_value'],
      ];
    }
    else {
      $form['purchased_entity'] = [
        '#type' => 'textfield',
        '#id' => 'edit-purchased-entity-' . $args['id'],
        '#default_value' => $args['default_value'],
        '#size' => 256,
        '#maxlength' => 512,
        '#placeholder' => $args['has_variations'] ? $args['placeholder'] : $this->t('No varitions available'),
        '#description' => $args['description'],
        '#disabled' => !$args['has_variations'],
        '#attributes' => ['class' => ['commerce-quick-purchase__textfield']],
        '#prefix' => "<div id=\"{$args['id']}-field\" class=\"commerce-quick-purchase__field\">",
        '#suffix' => '</div>',
      ];
      if ($args['autocomplete'] && $args['id']) {
        $form['purchased_entity'] += [
          '#autocomplete_route_name' => 'commerce_quick_purchase.sku_autocomplete',
          '#autocomplete_route_parameters' => ['id' => $args['id']],
        ];
      }
    }

    $form += [
      'actions' => $this->actions($form, $form_state),
      '#entity_builders' => [
        'update_form_langcode' => '::updateFormLangcode',
      ],
      '#attached' => [
        'library' => [$args['library']],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'][0];
    $actions = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $args['button_text'],
        '#button_type' => 'primary',
        '#weight' => 5,
        '#submit' => ['::submitForm'],
        '#disabled' => !$args['has_variations'],
        '#attributes' => [
          'title' => $args['button_text'],
          'class' => ['commerce-quick-purchase__button'],
        ],
        '#prefix' => "<div id=\"{$args['id']}-submit\" class=\"commerce-quick-purchase__submit\">",
        '#suffix' => '</div>',
      ],
    ];
    if ($args['use_image_button']) {
      $actions['submit']['#type'] = 'image_button';
      $actions['submit']['#src'] = $args['image_button'];
      $actions['submit']['#attributes']['alt'] = $args['button_text'];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $purchased_entity = NULL;
    $input = $form_state->getUserInput();

    if ($str = trim($input['purchased_entity'])) {
      $block = $this->entityManager->getStorage('block')
        ->load($input['block_id'])
        ->getPlugin();
      $config = $block->getConfiguration();
      $form_state->set('do_not_add_to_cart', $config['do_not_add_to_cart']);
      $form_state->set('redirection', $config['redirection']);
      $purchased_entity = $block->getVariationBySkuOrTitle($str);

      if ($purchased_entity instanceof PurchasableEntityInterface) {
        $values = (array) $form_state->getValues();
        $values['selected_variation'] = $purchased_entity;
        $values['purchased_entity'] = [
          [
            'variation' => $purchased_entity->id(),
            'attributes' => $purchased_entity->getAttributeValueIds(),
          ],
        ];
        $form_state->setValues($values);

        if (!$config['do_not_add_to_cart']) {
          $order_item = $this->cartManager->createOrderItem($purchased_entity);
          $form_display = entity_get_form_display($order_item->getEntityTypeId(), $order_item->bundle(), 'add_to_cart');
          if (!$quantity = $form_display->getComponent('quantity')) {
            $form_display_default = entity_get_form_display($order_item->getEntityTypeId(), $order_item->bundle(), 'default');
            $quantity = $form_display_default->getComponent('quantity');
          }

          $default_quantity = 1;
          if (isset($quantity['settings']['default_value']) && $quantity['settings']['default_value']) {
            $default_quantity = $quantity['settings']['default_value'];
          }
          // Now recreate order item using the default quantity.
          if ($default_quantity != 1) {
            $order_item = $this->cartManager->createOrderItem($purchased_entity, $default_quantity);
          }

          if ($available = $purchased_entity) {
            $availability = $this->cartManager->availabilityManager;
            $context = new Context($this->currentUser, $this->selectStore($purchased_entity), time(), [
              'xquantity' => 'add_to_cart',
            ]);
            $available = $availability->check($purchased_entity, $default_quantity, $context);
            if (!$available && method_exists($order_item, 'rotateStock') && $order_item->rotateStock($purchased_entity, $default_quantity, $context)) {
              $available = $availability->check($purchased_entity, $default_quantity, $context);
            }
          }
          if (!$available) {
            $form['purchased_entity']['#value'] = $form['purchased_entity']['#default_value'] ?: '';
            $msg = $this->t('Unfortunately, the quantity %quantity of the %label is not available right at the moment.', [
              '%quantity' => $default_quantity,
              '%label' => $purchased_entity ? $purchased_entity->label() : $this->t('???'),
            ]);
            \Drupal::moduleHandler()->alter("xquantity_add_to_cart_not_available_msg", $msg, $default_quantity, $purchased_entity);
            $form_state->setErrorByName('purchased_entity', $msg);
          }
          $this->setEntity($order_item);
          $form_state->getFormObject()->setEntity($order_item);

          $product = $purchased_entity->getProduct();
          $form_state->set('product', $product);

          $display = entity_get_display($product->getEntityTypeId(), $product->bundle(), 'default');
          $combine = $display->getComponent('variations')['settings']['combine'];

          $form_state->set('settings', ['combine' => $combine]);
          $this->setFormDisplay($form_display, $form_state);

          parent::validateForm($form, $form_state);
        }
      }
    }

    if (!$purchased_entity) {
      $form['purchased_entity']['#value'] = $form['purchased_entity']['#default_value'] ?: '';
      $form_state->setErrorByName('purchased_entity', $this->t('Product which could be identified by the %str is not available.', [
        '%str' => $str ?: '???',
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $redirection = $form_state->get('redirection');
    if ($redirection != 'no') {
      $url = $form_state->getValue('selected_variation')->toUrl();
    }

    if (!$form_state->get('do_not_add_to_cart')) {
      parent::submitForm($form, $form_state);
      $id = $form_state->get('cart_id');

      if ($redirection == 'cart_page') {
        $fragment = "views-form-commerce-cart-form-default-$id";
        $url = $url->fromRoute('commerce_cart.page', [], ['fragment' => $fragment]);
      }
      elseif ($redirection == 'checkout_page') {
        $url = $url->fromRoute('commerce_checkout.form', ['commerce_order' => $id]);
      }
    }
    isset($url) && $form_state->setRedirectUrl($url);
  }

}
