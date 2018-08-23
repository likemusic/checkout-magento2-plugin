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
use Magento\Sales\Api\OrderManagementInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Gateway\Http\Client;

class HubHandlerService {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

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
        OrderManagementInterface $orderManagement,
        Config $config,
        Tools $tools,
        Client $client
    ) {
        $this->orderRepository    = $orderRepository;
        $this->orderManagement    = $orderManagement;
        $this->config             = $config;
        $this->tools              = $tools;
        $this->client             = $client;
    }

    public function voidRemoteTransaction($transaction, $amount) {
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
            // Cancel the order
            $this->orderManagement->cancel($transaction->getOrderId());
            $order->setStatus($this->config->getOrderStatusVoided());
            $this->orderRepository->save($order);

            return true;
        }
       
        return false;
    }

    public function refundRemoteTransaction($transaction, $amount) {
        // Prepare the request URL
        $url = $this->config->getApiUrl() . 'charges/' . $transaction->getParentTxnId() . '/refund';

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
            $this->orderManagement->cancel($transaction->getOrderId());
            $order->setStatus($this->config->getOrderStatusRefunded());
            $this->orderRepository->save($order);

            return true;
        }
       
        return false;
    }
}