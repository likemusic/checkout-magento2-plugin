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

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\ObserverInterface; 
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Request\Http;

class OrderSaveBefore implements ObserverInterface { 
 
    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * @var Http
     */
    protected $request;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(Session $backendAuthSession, Http $request) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer) { 
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the order
            $order = $observer->getEvent()->getOrder();
            $customerId = $order->getCustomerId();

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderSave.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($this->request->getPost(), 1));
    
            // todo - move this to save after for and check for MOTO payment
            // Get the customer id
            /*
            $customerId = $order->getCustomerId();
 
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderSave.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($customerId);

            throw new \Magento\Framework\Exception\LocalizedException(__('Hey stop clam.'));
            */
        }
    }
}