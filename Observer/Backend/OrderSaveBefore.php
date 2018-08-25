<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\ObserverInterface; 
use Magento\Framework\Event\Observer;

class OrderSaveBefore implements ObserverInterface { 
 
    protected $connector;

    public function __construct() { 
    }
 
    public function execute(Observer $observer) { 
        $order = $observer->getEvent()->getOrder();
        $customerId = $order->getCustomerId();
 
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderSave.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($customerId);
    
        throw new \Magento\Framework\Exception\LocalizedException(__('Hey stop clam.'));

    }
}