<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Message\ManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;

class Watchdog {

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Array
     */
    protected $data;

    public function __construct(
        ManagerInterface $messageManager,
        Config $config,
        Tools $tools
    ) {
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->tools = $tools;
    }

    public function bark($data) {
        // Assign the data to self
        $this->data = (array) $data;

        // Log to file
        if ($this->config->isPhpLogging()) {
            $this->logToFile();
        }

        // Log to screen
        if ($this->config->isGatewayLogging()) {
            $this->logToScreen();
        }
    }

    private function logToFile() {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $this->tools->modmeta['tag'] . '.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($this->data, 1));              
    }

    private function logToScreen() {
        // Add the response code
        if (isset($data['responseCode'])) {
            $this->messageManager->addNoticeMessage(__('Response code') . ' : ' .  $data['responseCode']);
        }

        // Add the response message
        if (isset($data['responseMessage'])) {
            $this->messageManager->addNoticeMessage(__('Response message') . ' : ' .  $data['responseMessage']);    
        }   

        // Add the error code
        if (isset($data['errorCode'])) {
            $this->messageManager->addNoticeMessage(__('Error code') . ' : ' .  $data['errorCode']);    
        }  

        // Add the Status
        if (isset($data['status'])) {
            $this->messageManager->addNoticeMessage(__('Status') . ' : ' .  $data['status']);    
        }   

        // Add the message
        if (isset($data['message'])) {
            $this->messageManager->addNoticeMessage(__('Message') . ' : ' .  $data['message']);    
        }                     
    }
}
