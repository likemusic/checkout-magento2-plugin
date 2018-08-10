<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
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

    public function __construct(Context $context, Config $config, Tools $tools, array $data = []) {
        $this->config = $config;
        $this->tools = $tools;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}