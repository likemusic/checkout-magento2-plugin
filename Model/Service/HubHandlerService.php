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

use Magento\Sales\Api\OrderRepositoryInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Gateway\Http\Client;

class HubHandlerService {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Client
     */
    protected $client;

    /**
     * HubHandlerService constructor.
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Config $config,
        Tools $tools,
        Client $client
    ) {
        $this->orderRepository    = $orderRepository;
        $this->config             = $config;
        $this->tools              = $tools;
        $this->client             = $client;
    }

    public function voidRemoteTransaction($transaction, $amount) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transaction->getTxnId() . '/void';

        // Get the track id
        $trackId = $this->orderRepository
        ->get($transaction->getOrderId())
        ->getIncrementId();

        // Prepare the request parameters
        $params = [
            'value' => $this->tools->formatAmount($amount),
            'trackId' => $trackId
        ]; 

        // Send the request
        $response = $this->client->getPostResponse($url, $params);

        // Process the response
        if ($this->tools->isChargeSuccess($response)) {
            return true;
        }
       
        return false;
    }

    public function refundRemoteTransaction($transactionId, $amount) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transactionId . '/refund';
    }
}