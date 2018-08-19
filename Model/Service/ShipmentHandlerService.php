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

use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Gateway\Config\Config;

class ShipmentHandlerService {

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        Tools $tools,
        Config $config
    ) {
        $this->tools                 = $tools;
        $this->config                = $config;
    }

    public function processShipment($order, $invoice) {
        // Assign the required values
        $this->order = $order;
        $this->invoice = $invoice;
       
        // Trigger the shipment creation
        if ($this->shouldShip()) $this->createShipment();
    }

    public function shouldShip() {
        //return $this->config->getAutoGenerateShipment();
        return true;
    }

    public function createShipment() {
        // Create the shipment object
        $shipment = $this->shipmentFactory->create($this->order, []);

        // If the shipment is valid, proceed
        if ((int) $shipment->getTotalQty() > 0) {
            // Register the shipment
            $shipment->register();

            // Update the order
            $this->order->setIsInProcess(true);
            $this->order->save();

            // Create the shipment transaction
            $this->transactionFactory->create()
            ->addObject($shipment)
            ->addObject($this->order)
            ->save();
        }
    }    
}
