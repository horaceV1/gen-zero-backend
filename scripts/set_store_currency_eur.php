<?php

/**
 * @file
 * One-shot script: force EUR currency across Commerce.
 *
 * Run with Drush:
 *   drush scr scripts/set_store_currency_eur.php
 *   ddev exec drush scr scripts/set_store_currency_eur.php   (local DDEV)
 *
 * Why:
 *   EuPago only supports EUR. This script:
 *   1. Sets every commerce_store's default_currency to EUR.
 *   2. Relabels existing product-variation prices to EUR (numeric value kept).
 *   3. Relabels existing order item & order total prices to EUR.
 *   4. Ensures the EUR currency entity is enabled.
 *
 * Notes:
 *   - This DOES NOT convert numbers (no FX). It assumes the displayed amounts
 *     were already intended as euros and only the currency label was wrong.
 *   - Safe to run multiple times (idempotent).
 */

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;

$entityTypeManager = \Drupal::entityTypeManager();

// 1. Make sure EUR currency entity exists & is enabled.
$eur = Currency::load('EUR');
if (!$eur) {
  $eur = Currency::create([
    'currencyCode' => 'EUR',
    'name' => 'Euro',
    'numericCode' => '978',
    'symbol' => '€',
    'fractionDigits' => 2,
  ]);
  $eur->save();
  echo "Created EUR currency entity.\n";
}

// 2. Set default_currency = EUR on every commerce_store.
$stores = $entityTypeManager->getStorage('commerce_store')->loadMultiple();
foreach ($stores as $store) {
  if ($store->getDefaultCurrencyCode() !== 'EUR') {
    $store->setDefaultCurrencyCode('EUR');
    $store->save();
    echo "Store {$store->id()} default currency -> EUR\n";
  }
  else {
    echo "Store {$store->id()} already EUR\n";
  }
}

// 3. Relabel existing product-variation prices.
$variationStorage = $entityTypeManager->getStorage('commerce_product_variation');
$ids = $variationStorage->getQuery()->accessCheck(FALSE)->execute();
$updated = 0;
foreach (array_chunk($ids, 100) as $chunk) {
  /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
  $variations = $variationStorage->loadMultiple($chunk);
  foreach ($variations as $variation) {
    $price = $variation->getPrice();
    if ($price instanceof Price && $price->getCurrencyCode() !== 'EUR') {
      $variation->setPrice(new Price($price->getNumber(), 'EUR'));
      $variation->save();
      $updated++;
    }
    if ($variation->hasField('list_price') && !$variation->get('list_price')->isEmpty()) {
      $listPrice = $variation->getListPrice();
      if ($listPrice instanceof Price && $listPrice->getCurrencyCode() !== 'EUR') {
        $variation->setListPrice(new Price($listPrice->getNumber(), 'EUR'));
        $variation->save();
        $updated++;
      }
    }
  }
}
echo "Variation prices relabeled to EUR: {$updated}\n";

// 4. Relabel existing orders + order items.
$orderStorage = $entityTypeManager->getStorage('commerce_order');
$orderItemStorage = $entityTypeManager->getStorage('commerce_order_item');

$orderItemIds = $orderItemStorage->getQuery()->accessCheck(FALSE)->execute();
$itemsUpdated = 0;
foreach (array_chunk($orderItemIds, 100) as $chunk) {
  /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $items */
  $items = $orderItemStorage->loadMultiple($chunk);
  foreach ($items as $item) {
    $changed = FALSE;
    $unit = $item->getUnitPrice();
    if ($unit instanceof Price && $unit->getCurrencyCode() !== 'EUR') {
      $item->setUnitPrice(new Price($unit->getNumber(), 'EUR'));
      $changed = TRUE;
    }
    $total = $item->getTotalPrice();
    if ($total instanceof Price && $total->getCurrencyCode() !== 'EUR') {
      $item->set('total_price', new Price($total->getNumber(), 'EUR'));
      $changed = TRUE;
    }
    if ($changed) {
      $item->save();
      $itemsUpdated++;
    }
  }
}
echo "Order items relabeled: {$itemsUpdated}\n";

$orderIds = $orderStorage->getQuery()->accessCheck(FALSE)->execute();
$ordersUpdated = 0;
foreach (array_chunk($orderIds, 100) as $chunk) {
  /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
  $orders = $orderStorage->loadMultiple($chunk);
  foreach ($orders as $order) {
    // Recalculate totals from the (now-EUR) order items.
    $order->recalculateTotalPrice();
    $total = $order->getTotalPrice();
    if (!$total || $total->getCurrencyCode() !== 'EUR') {
      // Force a save so the cached total_price column refreshes.
      $order->save();
    }
    else {
      $order->save();
    }
    $ordersUpdated++;
  }
}
echo "Orders recalculated: {$ordersUpdated}\n";

echo "Done. All store/product/order prices are now EUR.\n";
