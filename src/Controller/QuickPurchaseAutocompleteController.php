<?php

namespace Drupal\commerce_quick_purchase\Controller;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for the entered title or SKU values.
 */
class QuickPurchaseAutocompleteController implements ContainerInjectionInterface {

  /**
   * Tthe block manager.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $block;

  /**
   * Constructs a new QuickPurchaseAutocompleteController.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $block
   *   The block manager.
   */
  public function __construct(ConfigEntityStorageInterface $block) {
    $this->block = $block;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('block')
    );
  }

  /**
   * Retrieves suggestions for product variation autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $str = $request->query->get('q');
    $id = $request->query->get('id');
    $labels = $this->block->load($id)->getPlugin()->getVariationsLabelsBySkuOrTitle($str);

    return new JsonResponse($labels);
  }

}
