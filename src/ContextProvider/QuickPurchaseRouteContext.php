<?php

namespace Drupal\commerce_quick_purchase\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current commerce_product or commerce_store as the route context.
 */
class QuickPurchaseRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new QuickPurchaseRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:commerce_product', NULL, FALSE);
    $value = NULL;
    $route = $this->routeMatch;
    $route_object = $route->getRouteObject();
    $route_contexts = $route_object->getOption('parameters');

    if (isset($route_contexts['commerce_product'])) {
      if ($commerce_product = $route->getParameter('commerce_product')) {
        $value = $commerce_product;
      }
    }
    elseif ($route->getRouteName() == 'entity.commerce_product.add_form') {
      $commerce_product_type = $route->getParameter('commerce_product_type');
      $value = Product::create(['type' => $commerce_product_type->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['commerce_product'] = $context;

    $context_definition = new ContextDefinition('entity:commerce_store', NULL, FALSE);
    $value = NULL;
    if (isset($commerce_product) && $commerce_product) {
      $value = $commerce_product->getStores();
    }
    elseif (isset($route_contexts['commerce_store'])) {
      if ($commerce_store = $route->getParameter('commerce_store')) {
        $value = [$commerce_store];
      }
    }

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['commerce_store'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $product_context = new Context(new ContextDefinition('entity:commerce_product', $this->t('Product from URL')));
    $store_context = new Context(new ContextDefinition('entity:commerce_store', $this->t('Store from URL or product in the URL')));
    return ['commerce_product' => $product_context, 'commerce_store' => $store_context];
  }

}
