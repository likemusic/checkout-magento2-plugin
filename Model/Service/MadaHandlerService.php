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
use CheckoutCom\Magento2\Gateway\Config\Config;

class MadaHandlerService {

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
     * MadaHandlerService constructor.
     */
    public function __construct(
        Csv $csvParser,
        Reader $directoryReader,
        Config $config
    ) {
        $this->directoryReader = $directoryReader;
        $this->csvParser       = $csvParser;
        $this->config          = $config;
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
}
