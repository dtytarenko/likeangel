<?php

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\Contracts\HttpRequest;

if ( ! defined('ABSPATH')) {
  exit;
}

class PrintService
{
  /**
   * @var HttpRequest
   */
  private $httpRequest;

  public function __construct()
  {
    $this->httpRequest = WCUkrShipping::instance()->singleton(HttpRequest::class);
  }

  /**
   * @param array $ttnIds
   * @param string $type
   *
   * @return mixed
   */
  public function printTTNByType($ttnIds, $type = 'A4', $copies = 1)
  {
    switch ($type) {
      case 'marking_85':
        return $this->printMarking85TTN($ttnIds, (int)$copies);
      case 'zebra':
        return $this->printZebraTTN($ttnIds);
      case 'A4_2':
        return $this->printA4DuplicateTTN($ttnIds);
      case 'A4':
      default:
        return $this->printA4TTN($ttnIds);
    }
  }

  /**
   * @param array $ttnIds
   *
   * @return mixed
   */
  public function printA4TTN($ttnIds)
  {
    return $this->httpRequest->get(sprintf(
      'https://my.novaposhta.ua/orders/printDocument/orders/%s/type/pdf/copies/1/apiKey/%s',
      implode(',', $ttnIds),
      get_option('wc_ukr_shipping_np_api_key', '')
    ));
  }

  /**
   * @param array $ttnIds
   *
   * @return mixed
   */
  public function printA4DuplicateTTN($ttnIds)
  {
    return $this->httpRequest->get(sprintf(
      'https://my.novaposhta.ua/orders/printDocument/orders/%s/type/pdf/apiKey/%s',
      implode(',', $ttnIds),
      get_option('wc_ukr_shipping_np_api_key', '')
    ));
  }

  /**
   * @param array $ttnIds
   * @param int $copies
   *
   * @return mixed
   */
  public function printMarking85TTN($ttnIds, $copies)
  {
    return $this->httpRequest->get(sprintf(
      'https://my.novaposhta.ua/orders/printMarking85x85/orders/%s/type/pdf8/copies/%s/apiKey/%s',
      implode(',', $ttnIds),
      $copies,
      get_option('wc_ukr_shipping_np_api_key', '')
    ));
  }

  /**
   * @param array $ttnIds
   *
   * @return mixed
   */
  public function printZebraTTN($ttnIds)
  {
    return $this->httpRequest->get(sprintf(
      'https://my.novaposhta.ua/orders/printMarking100x100/orders/%s/type/pdf/apiKey/%s/zebra',
      implode(',', $ttnIds),
      get_option('wc_ukr_shipping_np_api_key', '')
    ));
  }
}