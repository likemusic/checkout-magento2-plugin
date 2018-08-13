<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Gateway\Http;

use Magento\Framework\HTTP\Client\Curl;
use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use CheckoutCom\Magento2\Helper\Tools;
class Client {

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ProductMetadataInterface
     */
    protected $metadata;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Client constructor.
     */     
    public function __construct(
        Curl $curl,
        Config $config,
        ProductMetadataInterface $metadata,
        Tools $tools,
        ModuleListInterface $moduleList,
        StoreManagerInterface $storeManager
    ) {
        $this->curl            = $curl;
        $this->config          = $config;
        $this->metadata        = $metadata;
        $this->tools           = $tools;
        $this->moduleList      = $moduleList;
        $this->storeManager    = $storeManager;

        // Launch functions
        $this->addHeaders();
    }

    private function addHeaders() {
        $this->curl->addHeader('Authorization', $this->config->getSecretKey());
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->buildMetaData();
    }

    private function buildMetaData() {
        return [
            'metadata' => [
                'magento_name'      => $this->metadata->getName(),
                'magento_edition'   => $this->metadata->getEdition(),
                'magento_version'   => $this->metadata->getVersion(),
                'setup_version'     => $this->moduleList->getOne('CheckoutCom_Magento2')['setup_version'],
                'module_version'    => $this->tools->getModuleVersion(),
                'store_id'          => $this->storeManager->getStore()->getId(),
            ],
        ];
    }

    public function post($url, $params) {
        // Add the request metadata
        $params['metadata'] = $this->buildMetaData();

        // Send the CURL POST request
        $this->curl->post($url, json_encode($params));

        // Return the response
        return $this->curl->getBody();
    }
   
    public function get($url) {
        // Send the CURL GET request
        $this->curl->get($url);

        // Return the response
        return $this->curl->getBody();     
    }
}
