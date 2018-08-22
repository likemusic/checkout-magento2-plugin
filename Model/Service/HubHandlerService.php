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
     * @var Client
     */
    protected $client;

    /**
     * HubHandlerService constructor.
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Config $config,
        Client $client
    ) {
        $this->orderRepository    = $orderRepository;
        $this->config             = $config;
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
            'value' => $amount,
            'trackId' => $trackId
        ]; 

        // Send the request
        $response = $this->client->post($url, $params);

        // Format the response
        $response = isset($response) ? (array) json_decode($response) : null;


        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/response.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($response, 1));

        // Logging
        //$this->watchdog->bark($response);
    }

    public function refundRemoteTransaction($transactionId, $amount) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transactionId . '/refund';
    }
}