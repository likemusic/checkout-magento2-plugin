<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use CheckoutCom\Magento2\Gateway\Config\Config;

class SaveCard extends Action {

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Handles the controller method.
     */
    public function execute() { 
       echo "<pre>";
       var_dump($_REQUEST);
       echo "</pre>";
       exit();
    } 
}