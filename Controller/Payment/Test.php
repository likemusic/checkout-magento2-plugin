<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Test extends Action {

    /**
     * @var CollectionFactory
     */
    protected $quoteCollectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $quoteCollectionFactory
        ) {
        parent::__construct($context);

        $this->quoteCollectionFactory  = $quoteCollectionFactory;
    }

    public function execute() {

        $trackId = '000000034';

        $quoteCollection = $this->quoteCollectionFactory->create()
        ->addFieldToFilter('reserved_order_id', $trackId);
        
        /*
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/myquote.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($quoteCollection->getData(), 1));
        */

        echo "<pre>";
        var_dump(count($quoteCollection));
        echo "</pre>";

        exit('ha');
    }
}