<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Block\Form;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\Product\Context;
use Magento\Payment\Model\Config as ModelConfig;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;

class Embedded extends \Magento\Payment\Block\Form\Cc {

    /**
     * @var ModelConfig
     */
    public $modelConfig;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Tools
     */
    public $tools;

    /**
     * @var String
     */
    protected $_template = 'CheckoutCom_Magento2::form/embedded.phtml';

    public function __construct(Context $context, ModelConfig $modelConfig, Config $config, Tools $tools) {
        $this->config = $config;
        $this->tools = $tools;
        parent::__construct($context, $modelConfig);
    }

    public function isAutoCapture() {
        return $this->config->isMotoAutoCapture();
    }

    public function getAutoCaptureTime() {
        return $this->config->getMotoAutoCaptureTime();
    }

    protected function _prepareLayout() {
        return parent::_prepareLayout();
    }
}