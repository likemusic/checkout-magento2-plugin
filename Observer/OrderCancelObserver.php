<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Model\Service\HubHandlerService;

class OrderCancelObserver implements ObserverInterface {

    /**
     * @var OrderHandlerService
     */
    protected $orderService;

    /**
     * @var HubHandlerService
     */
    protected $hubService;

    /**
     * OrderCancelObserver constructor.
     */
    public function __construct(
        OrderHandlerService $orderService,
        HubHandlerService $hubService
    ) {
        $this->orderService  = $orderService;    
        $this->hubService    = $hubService;   
    }

    /**
     * Handles the observer for order cancellation.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) {
        // Get the order
        $order = $observer->getEvent()->getOrder();

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $observer->getEvent()->getName() . '.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($observer->getEvent()->getName());

        return $this;
    }
}
