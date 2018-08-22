<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Gateway\Http\Client;

class HubHandlerService {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * HubHandlerService constructor.
     */
    public function __construct(
        Config $config,
        Client $client
    ) {
        $this->config     = $config;
        $this->client     = $client;
    }

    public function voidRemoteTransaction(Transaction $transaction, $amount) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transaction->getTxnId() . '/void';

        // Prepare the request parameters
        $params = [
            'value' => $this->tools->toGatewayFormat($value),
            'trackId' => $transaction->getOrderId(),
        ];     

        // Handle the request
        $response = $this->tools->getPostResponse($url, $params);
    }

    public function refundRemoteTransaction(Transaction $transaction, $amount) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transaction->getTxnId() . '/refund';

        // Prepare the request parameters
        $params = [
            'value' => $this->tools->toGatewayFormat($value),
            'trackId' => $transaction->getOrderId(),
        ];     

        // Handle the request
        $response = $this->tools->getPostResponse($url, $params);
    }
}