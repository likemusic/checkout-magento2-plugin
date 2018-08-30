<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Framework\File\Csv;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Stdlib\CookieManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;

class MadaHandlerService {

    const MADA_FLAG = 'MADA';

    /**
     * @var Csv
     */
    protected $csvParser;
    
    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * MadaHandlerService constructor.
     */
    public function __construct(
        Csv $csvParser,
        Reader $directoryReader,
        Config $config,
        CookieManagerInterface $cookieManager,
        Tools $tools
    ) {
        $this->directoryReader = $directoryReader;
        $this->csvParser       = $csvParser;
        $this->config          = $config;
        $this->cookieManager   = $cookieManager;
        $this->tools           = $tools;
    }

    /**
     * Checks a MADA BIN
     *
     * @return bool
     */
    public function isMadaBin($bin) {
        // Set the root path
        $csvPath = $this->directoryReader->getModuleDir('', $this->tools->modmeta['name'])  . '/' . $this->config->getMadaBinPath();

        // Get the data
        $csvData = $this->csvParser->getData($csvPath);

        // Remove the first row of csv columns
        unset($csvData[0]);

        // Build the MADA BIN array
        $binArray = [];
        foreach ($csvData as $row) {
            $binArray[] = $row[1];
        }
        
        return in_array($bin, $binArray);
    }

    /**
     * Check cookies for MADA data.
     */
    public function checkBin() {
        if ((int) $this->cookieManager->getCookie('ckoCardBin') > 0) {
            $bin = $this->cookieManager->getCookie('ckoCardBin');
            if ($this->isMadaBin($bin) && $this->config->isMadaEnabled() === true) {
                return self::MADA_FLAG;
            }
        }

        return '';
    }

}
