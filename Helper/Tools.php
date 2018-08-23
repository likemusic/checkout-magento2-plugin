<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\Module\Dir\Reader;
use CheckoutCom\Magento2\Gateway\Http\Client;
use CheckoutCom\Magento2\Helper\Watchdog;

class Tools {

    const KEY_MODNAME = 'modname';
    const KEY_MODTAG = 'modtag';
    const KEY_MODTAG_APPLE_PAY = 'modtagapplepay';
    const KEY_MODLABEL = 'modlabel';
    const KEY_MODURL = 'modurl';
    const KEY_MODLOGO = 'modlogo';
    const KEY_PARAM_PATH = 'conf/param';
    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';
    const KEY_PRIVATE_SHARED_KEY = 'private_shared_key';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Client
     */
    protected $client;

     /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * Tools constructor.
     */ 
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        UrlInterface $urlInterface,
        Reader $directoryReader,
        Client $client,
        Watchdog $watchdog
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->customerSession   = $customerSession;
        $this->urlInterface      = $urlInterface;
        $this->modmeta           = $this->getModuleMetadata();
        $this->directoryReader   = $directoryReader;
        $this->client            = $client;
        $this->watchdog          = $watchdog;
    }

    /**
     * Get the module version from composer.json file.
     */    
    public function getModuleVersion() {
        // Get the module path
        $module_path = $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2');
        
        // Get the content of composer.json
        $json = file_get_contents($module_path . '/composer.json');
        
        // Decode the data and return
        $data = json_decode($json);
        return $data->version;
    }

    /**
     * Get some module metadata from the xml configuration.
     */ 
    private function getModuleMetadata() {
        return [
            'tag'          => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODTAG),
            'tagapplepay'  => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODTAG_APPLE_PAY),
            'name'         => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODNAME),
            'label'        => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODLABEL),
            'url'          => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODURL),
            'logo'         => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODLOGO),
        ];
    }

    /**
     * Format a given amount.
     */ 
    public function formatAmount($amount) {
        return number_format($amount/100, 2);
    }

    /**
     * Check private public key validity.
     */ 
    public function publicKeyIsValid(string $key) {
        return $this->scopeConfig->getValue('payment/' . $this->modmeta['tag'] . '/' . self::KEY_PUBLIC_KEY) == $key;
    }

    /**
     * Check private key validity.
     */ 
    public function privateKeyIsValid(string $key) {
        return $this->scopeConfig->getValue('payment/' . $this->modmeta['tag'] . '/' . self::KEY_PRIVATE_KEY) == $key;
    }

    /**
     * Check private shared key validity.
     */ 
    public function privateSharedKeyIsValid(string $key) {
        return $this->scopeConfig->getValue('payment/' . $this->modmeta['tag'] . '/' . self::KEY_PRIVATE_SHARED_KEY) == $key;
    }

    /**
     * Check if charge is successful.
     */ 
    public function chargeIsSuccess($response) {
        if (isset($response->responseCode)) {
            $responseCode = (int) $response->responseCode;
            if ($responseCode == 10000 || $responseCode == 10100)
            {
                return true;
            }            
        }
        
        return false;
    }

    /**
     * Check if a quote is valid.
     */      
    public function quoteIsValid($quote) {
        if (!$quote || !$quote->getItemsCount()) {
            return false;
        }

        return true;
    }

    /**
     * Force authentication if the user is not logged in.
     */    
    public function checkLoggedIn() {
        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession->setAfterAuthUrl($this->urlInterface->getCurrentUrl());
            $this->customerSession->authenticate();
        }    
    }

    /**
     * Returns a prepared post response.
     */    
    public function getPostResponse($url, $params) {
        // Send the request
        $response = $this->client->post($url, $params);

        // Format the response
        $response = isset($response) ? (array) json_decode($response) : null;

        // Logging
        //$this->watchdog->bark($response);
        
        return $response;
    }

    /**
     * Returns a prepared get response.
     */    
    public function getGetResponse($url) {
        // Send the request
        $response = $this->client->get($url);

         // Format the response
        $response = isset($response) ? (array) json_decode($response) : null;

         // Logging
        $this->watchdog->bark($response);

        return $response;
    }
}