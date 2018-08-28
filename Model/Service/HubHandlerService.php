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

use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
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

    public function voidRemoteTransaction($transaction, $amount, $payment = false) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transaction->getTxnId() . '/void';

        // Get the order
        $order = $this->orderRepository->get($transaction->getOrderId());

        // Get the track id
        $trackId = $order->getIncrementId();

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

    public function refundRemoteTransaction($transaction, $amount, $payment = false) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transaction->getTxnId() . '/refund';

        // Get the order
        $order = $this->orderRepository->get($transaction->getOrderId());

        // Get the track id
        $trackId = $order->getIncrementId();

        // Prepare the request parameters
        $params = [
            'value' => $this->tools->formatAmount($amount),
            'trackId' => $trackId
        ]; 

        // Send the request
        $response = $this->client->getPostResponse($url, $params);

        // Process the response
        if ($this->tools->isChargeSuccess($response)) {
            // Create the transaction
            if ($payment) {
               $payment->setTransactionId($response['id']);
               $payment->setParentTransactionId($transaction->getTxnId());
               $payment->setIsTransactionClosed(1);
               $payment->save();
            }

            return true;
        }
       
        return false;
    }
}