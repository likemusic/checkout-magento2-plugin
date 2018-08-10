<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace CheckoutCom\Magento2\Block\Account;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\Product\Context;

class AddCard extends Template {

    public function __construct(Context $context, array $data = []) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}