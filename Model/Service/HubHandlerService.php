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

class HubHandlerService {

    /**
     * @var Config
     */
    protected $config;

    /**
     * HubHandlerService constructor.
     */
    public function __construct(
        Config $config
    ) {
        $this->config                = $config;
    }

    public function cancelTransactionToRemote(Order $order) {

        
    }
}