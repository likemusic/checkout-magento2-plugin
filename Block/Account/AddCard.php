<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Block\Account;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\Product\Context;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;

class AddCard extends Template {

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Tools
     */
    public $tools;

    /**
     * AddCard constructor.
     */
    public function __construct(Context $context, Config $config, Tools $tools, array $data = []) {
        $this->config = $config;
        $this->tools = $tools;
        parent::__construct($context, $data);
    }
}