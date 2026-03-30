<?php

namespace Drupal\gen_zero_donations\Controller;

use Drupal\gen_zero_donations\DonationManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for donation configuration endpoint.
 */
class DonationConfigController extends ControllerBase {

  /**
   * The donation manager.
   */
  protected DonationManager $donationManager;

  /**
   * Constructs a DonationConfigController object.
   */
  public function __construct(DonationManager $donation_manager) {
    $this->donationManager = $donation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gen_zero_donations.donation_manager'),
    );
  }

  /**
   * Returns the donation widget configuration.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with donation config.
   */
  public function getConfig(): JsonResponse {
    return new JsonResponse([
      'data' => $this->donationManager->getConfig(),
    ]);
  }

}
