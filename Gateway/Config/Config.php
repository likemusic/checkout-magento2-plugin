<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */
 
namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use CheckoutCom\Magento2\Helper\Tools;

class Config {
    
    const KEY_MODTAG = 'modtag';
    const KEY_ENVIRONMENT = 'environment';
    const KEY_ENVIRONMENT_LIVE = 'live';
    const KEY_ACTIVE = 'active';
    const KEY_INTEGRATION = 'integration';
    const KEY_INTEGRATION_HOSTED = 'hosted';
    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_SECRET_KEY = 'secret_key';
    const KEY_PRIVATE_SHARED_KEY = 'private_shared_key';
    const KEY_AUTO_CAPTURE = 'auto_capture';
    const KEY_AUTO_CAPTURE_TIME = 'auto_capture_time';
    const KEY_VERIFY_3DSECURE = 'verify_3dsecure';
    const KEY_ATTEMPT_N3D = 'attemptN3D';
    const KEY_SANDBOX_API_URL = 'sandbox_api_url';
    const KEY_LIVE_API_URL = 'live_api_url';
    const KEY_SANDBOX_EMBEDDED_URL = 'sandbox_embedded_url';
    const KEY_SANDBOX_HOSTED_URL = 'sandbox_hosted_url';
    const KEY_LIVE_EMBEDDED_URL = 'live_embedded_url';
    const KEY_LIVE_HOSTED_URL = 'live_hosted_url';
    const MIN_AUTO_CAPTURE_TIME = 0;
    const MAX_AUTO_CAPTURE_TIME = 168;
    const KEY_USE_DESCRIPTOR = 'descriptor_enable';
    const KEY_DESCRIPTOR_NAME = 'descriptor_name';
    const KEY_DESCRIPTOR_CITY = 'descriptor_city';
    const CODE_3DSECURE = 'three_d_secure';
    const KEY_THEME_COLOR = 'theme_color';
    const KEY_BUTTON_LABEL = 'button_label';
    const KEY_BOX_TITLE = 'box_title';
    const KEY_BOX_SUBTITLE = 'box_subtitle';
    const KEY_LOGO_URL = 'logo_url';
    const KEY_HOSTED_THEME = 'hosted_theme';
    const KEY_NEW_ORDER_STATUS = 'new_order_status';
    const KEY_ORDER_STATUS_AUTHORIZED = 'order_status_authorized';
    const KEY_ORDER_STATUS_CAPTURED = 'order_status_captured';
    const KEY_ORDER_STATUS_FLAGGED = 'order_status_flagged';
    const KEY_ACCEPTED_CURRENCIES = 'accepted_currencies';
    const KEY_PAYMENT_CURRENCY = 'payment_currency';
    const KEY_CUSTOM_CURRENCY = 'custom_currency';
    const KEY_PAYMENT_MODE = 'payment_mode';
    const KEY_AUTO_GENERATE_INVOICE = 'auto_generate_invoice';
    const KEY_EMBEDDED_THEME = 'embedded_theme';
    const KEY_ORDER_COMMENTS_OVERRIDE = 'order_comments_override';
    const KEY_ORDER_CREATION = 'order_creation';
    const KEY_EMBEDDED_CSS = 'embedded_css';
    const KEY_JS_LOGGING = 'js_logging';
    const KEY_PHP_LOGGING = 'php_logging';
    const KEY_GATEWAY_LOGGING = 'gateway_logging';
    const KEY_MOTO_AUTO_CAPTURE = 'moto_auto_capture';
    const KEY_MOTO_AUTO_CAPTURE_TIME = 'moto_auto_capture_time';
    const KEY_MADA_BIN_PATH = 'mada_bin_path';
    const KEY_MADA_BIN_PATH_TEST = 'mada_bin_path_test';
    const KEY_MADA_ENABLED = 'mada_enabled';
    const KEY_SAVE_CARD_AUTH_CURRENCY = 'save_card_auth_currency';
    const KEY_SAVE_CARD_AUTH_AMOUNT = 'save_card_auth_amount';

    /**
     * @var array
     */
    protected static $cardTypeMap = [
        'amex'          => 'AE',
        'visa'          => 'VI',
        'mastercard'    => 'MC',
        'discover'      => 'DI',
        'jcb'           => 'JCB',
        'diners'        => 'DN',
        'dinersclub'    => 'DN',
    ];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * Config constructor.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Tools $tools,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface
    ) {
        $this->scopeConfig      = $scopeConfig;
        $this->tools            = $tools;
        $this->checkoutSession  = $checkoutSession;
        $this->storeManager     = $storeManager;
        $this->urlInterface     = $urlInterface;
    }

    /**
     * Retrieve mapper between Magento and Checkout.com card types.
     *
     * @return array
     */
    public function getCardTypeMapper() {
        return self::$cardTypeMap;
    }

    /**
     * Get a payment token.
     *
     * @return string
     */

    private function getValue($path, $prependPath = true) {
        // Build the config path
        $path = ($prependPath) ? 'payment/' . $this->tools->modmeta['tag'] . '/' . $path : $path;

        // Return the value
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is MADA BIN check enabled
     *
     * @return bool
     */
    public function isMadaEnabled() {
        return (bool) $this->getValue(self::KEY_MADA_ENABLED);
    }

    /**
     * Get the currency for the save card authorization
     *
     * @return bool
     */
    public function getSaveCardAuthCurrency() {
        return (bool) $this->getValue(self::KEY_SAVE_CARD_AUTH_CURRENCY);
    }

    /**
     * Get the amount for the save card authorization
     *
     * @return bool
     */
    public function getSaveCardAuthAmount() {
        return (bool) $this->getValue(self::KEY_SAVE_CARD_AUTH_AMOUNT);
    }

    /**
     * Return the MADA BIN file path.
     *
     * @return string
     */
    public function getMadaBinPath() {
        return (string) (($this->isLive()) ?
        $this->getValue(self::KEY_MADA_BIN_PATH) : 
        $this->getValue(self::KEY_MADA_BIN_PATH_TEST));
    }

    /**
     * Returns the environment type.
     *
     * @return string
     */
    public function getEnvironment() {
        return (string) $this->getValue(self::KEY_ENVIRONMENT);
    }

    /**
     * Returns the place order redirect URL.
     *
     * @return string
     */
    public function getPlaceOrderRedirectUrl() {
        $redirectUrl = $this->tools->modmeta['tag'] . '/payment/placeorder';
        return (string) $this->urlInterface->getUrl($redirectUrl);
    }

    /**
     * Returns the vault option title.
     *
     * @return string
     */
    public function getVaultTitle() {
        return (string) $this->getValue(
            'payment/' . $this->tools->modmeta['tag'] . '_cc_vault/title',
            false
        );
    }

    /**
     * Provides the vault option code.
     *
     * @return string
     */
    public function getVaultCode() {
        return (string) $this->tools->modmeta['tag'] . '_cc_vault';
    }

    /**
     * Returns the payment mode.
     *
     * @return string
     */
    public function getPaymentMode() {
        return (string) $this->getValue(self::KEY_PAYMENT_MODE);
    }

    /**
     * Returns the automatic invoice generation state.
     *
     * @return bool
     */
    public function getAutoGenerateInvoice() {
        return (bool) $this->getValue(self::KEY_AUTO_GENERATE_INVOICE);
    }

    /**
     * Returns the new order status.
     *
     * @return string
     */
    public function getNewOrderStatus() {
        return (string) $this->getValue(self::KEY_NEW_ORDER_STATUS);
    }

    /**
     * Returns the authorized order status.
     *
     * @return string
     */
    public function getOrderStatusAuthorized() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_AUTHORIZED);
    }

    /**
     * Returns the captured order status.
     *
     * @return string
     */
    public function getOrderStatusCaptured() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_CAPTURED);
    }

    /**
     * Returns the flagged order status.
     *
     * @return string
     */
    public function getOrderStatusFlagged() {
        return (string) $this->getValue(self::KEY_ORDER_STATUS_FLAGGED);
    }

    /**
     * Returns the Hosted integration theme color
     *
     * @return string
     */
    public function getHostedThemeColor() {
        return $this->getValue(self::KEY_THEME_COLOR);
    }

    /**
     * Returns the Hosted integration button label
     *
     * @return string
     */
    public function getHostedButtonLabel() {
        return $this->getValue(self::KEY_BUTTON_LABEL);
    }

    /**
     * Returns the Hosted integration box title
     *
     * @return string
     */
    public function getHostedBoxTitle() {
        return $this->getValue(self::KEY_BOX_TITLE);
    }

    /**
     * Returns the Hosted integration box sub title
     *
     * @return string
     */
    public function getHostedBoxSubtitle() {
        return $this->getValue(self::KEY_BOX_SUBTITLE);
    }

    /**
     * Returns the Hosted integration logo URL
     *
     * @return string
     */
    public function getHostedLogoUrl() {
        return $this->getLogoUrl();
    }

    /**
     * Returns the hosted logo URL.
     *
     * @return string
     */
    public function getLogoUrl() {
        $logoUrl = $this->getValue(self::KEY_LOGO_URL);
        return (string) (isset($logoUrl) && !empty($logoUrl)) ? $logoUrl : 'none';
    }

    /**
     * Determines if the environment is set as live (production) mode.
     *
     * @return bool
     */
    public function isLive() {
        return $this->getEnvironment() == self::KEY_ENVIRONMENT_LIVE;
    }

    /**
     * Determines if PHP logging is enabled.
     *
     * @return bool
     */
    public function isPhpLogging() {
        return (bool) $this->getValue(self::KEY_PHP_LOGGING);
    }

    /**
     * Determines if Javascript logging is enabled.
     *
     * @return bool
     */
    public function isJsLogging() {
        return (bool) $this->getValue(self::KEY_JS_LOGGING);
    }

    /**
     * Determines if Gateway logging is enabled.
     *
     * @return bool
     */
    public function isGatewayLogging() {
        return (bool) $this->getValue(self::KEY_GATEWAY_LOGGING);
    }

    /**
     * Returns the type of integration.
     *
     * @return string
     */
    public function getIntegration() {
        return (string) $this->getValue(self::KEY_INTEGRATION);
    }

    /**
     * Says if the integration is Hosted.
     *
     * @return string
     */
    public function isHostedIntegration() {
        return (string) $this->getIntegration() == 'hosted';
    }

    /**
     * Says if the integration is Embedded.
     *
     * @return string
     */
    public function isEmbeddedIntegration() {
        return (string) $this->getIntegration() == 'embedded';
    }

    /**
     * Determines if the gateway is active.
     *
     * @return bool
     */
    public function isActive() {
        $quote = $this->checkoutSession->getQuote();

        // Return true if module is active and currency is accepted
        return (bool) ($this->getValue(self::KEY_ACTIVE))
        && in_array(
            $quote->getQuoteCurrencyCode(),
            $this->getAcceptedCurrencies()
        );
    }

    /**
     * Determines if the core order comments need override.
     *
     * @return bool
     */
    public function overrideOrderComments() {
        return (bool) $this->getValue(self::KEY_ORDER_COMMENTS_OVERRIDE);
    }

    /**
     * Get the quote value.
     *
     * @return bool
     */
    public function getQuoteValue() {
        return $this->checkoutSession->getQuote()->getGrandTotal()*100;
    }

    /**
     * Get a quote currency code.
     *
     * @return string
     */
    public function getQuoteCurrency() {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Returns the public key for client-side functionality.
     *
     * @return string
     */
    public function getPublicKey() {
        return (string) $this->getValue(self::KEY_PUBLIC_KEY);
    }

    /**
     * Returns the secret key for server-side functionality.
     *
     * @return string
     */
    public function getSecretKey() {
        return (string) $this->getValue(self::KEY_SECRET_KEY);
    }

    /**
     * Returns the private shared key used for callback function.
     *
     * @return string
     */
    public function getPrivateSharedKey() {
        return (string) $this->getValue(self::KEY_PRIVATE_SHARED_KEY);
    }

    /**
     * Determines if 3D Secure option is enabled.
     *
     * @return bool
     */
    public function isVerify3DSecure() {
        return (bool) $this->getValue(self::KEY_VERIFY_3DSECURE);
    }

    /**
     * Determines if attempt Non 3D Secure option is enabled.
     *
     * @return bool
     */
    public function isAttemptN3D() {
        return (bool) $this->getValue(self::KEY_ATTEMPT_N3D);
    }

    /**
     * Returns the currencies allowed for payment.
     *
     * @return array
     */
    public function getAcceptedCurrencies() {
        return (array) explode(',', $this->getValue(self::KEY_ACCEPTED_CURRENCIES));
    }

    /**
     * Returns the payment currency.
     *
     * @return string
     */
    public function getPaymentCurrency() {
        return (string) $this->getValue(self::KEY_PAYMENT_CURRENCY);
    }

    /**
     * Returns the custom payment currency.
     *
     * @return string
     */
    public function getCustomCurrency() {
        return (string) $this->getValue(self::KEY_CUSTOM_CURRENCY);
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getSandboxApiUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_API_URL);
    }

    /**
     * Returns the API URL for sandbox environment.
     *
     * @return string
     */
    public function getLiveApiUrl() {
        return (string) $this->getValue(self::KEY_LIVE_API_URL);
    }

    /**
     * Returns the API URL based on environment settings.
     *
     * @return string
     */
    public function getApiUrl() {
        return $this->isLive() ? $this->getLiveApiUrl() : $this->getSandboxApiUrl();
    }

    /**
     * Returns the URL for hosted integration for sandbox environment.
     *
     * @return string
     */
    public function getSandboxHostedUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_HOSTED_URL);
    }

    /**
     * Returns the URL for hosted integration for live environment.
     *
     * @return string
     */
    public function getLiveHostedUrl() {
        return (string) $this->getValue(self::KEY_LIVE_HOSTED_URL);
    }

    /**
     * Returns the URL for hosted integration based on environment settings.
     *
     * @return string
     */
    public function getHostedUrl() {
        return $this->isLive() ? $this->getLiveHostedUrl() : $this->getSandboxHostedUrl();
    }


    /**
     * Returns the URL for embedded integration for sandbox environment.
     *
     * @return string
     */
    public function getSandboxEmbeddedUrl() {
        return (string) $this->getValue(self::KEY_SANDBOX_EMBEDDED_URL);
    }

    /**
     * Returns the URL for embedded integration for live environment.
     *
     * @return string
     */
    public function getLiveEmbeddedUrl() {
        return (string) $this->getValue(self::KEY_LIVE_EMBEDDED_URL);
    }

    /**
     * Returns the URL for embedded integration based on environment settings.
     *
     * @return string
     */
    public function getEmbeddedUrl() {
        return $this->isLive() ? $this->getLiveEmbeddedUrl() : $this->getSandboxEmbeddedUrl();
    }

    /**
     * Returns the CSS URL for embedded integration.
     *
     * @return string
     */
    public function getEmbeddedCss() {
        return (string) $this->getValue(self::KEY_EMBEDDED_CSS);
    }

    /**
     * Returns the new order creation setting.
     *
     * @return string
     */
    public function getOrderCreation() {
        return (string) $this->getValue(self::KEY_ORDER_CREATION);
    }

    /**
     * Determines if auto capture option is enabled.
     *
     * @return bool
     */
    public function isAutoCapture() {
        return (bool) $this->getValue(self::KEY_AUTO_CAPTURE);
    }

    /**
     * Returns the number of hours, after which the capture method should be invoked.
     *
     * @return int
     */
    public function getAutoCaptureTime() {
        return $this->getValue(self::KEY_AUTO_CAPTURE_TIME);
    }

    /**
     * Determines if auto capture option is enabled for MOTO payment.
     *
     * @return bool
     */
    public function isMotoAutoCapture() {
        return (bool) $this->getValue(
            'payment/' . $this->tools->modmeta['tag'] . '_admin/' . self::KEY_MOTO_AUTO_CAPTURE,
            false
        );
    }

    /**
     * Returns the number of hours, after which the capture method should be invoked.
     *
     * @return int
     */
    public function getMotoAutoCaptureTime() {
        return $this->getValue(self::KEY_MOTO_AUTO_CAPTURE_TIME);
    }
    
    /**
     * Check if the descriptor is enabled.
     *
     * @return bool
     */
    public function isDescriptorEnabled() {
        return (bool) $this->getValue(self::KEY_USE_DESCRIPTOR);
    }
    
    /**
     * Returns the descriptor name.
     *
     * @return string
     */
    public function getDescriptorName() {
        return (string) $this->getValue(self::KEY_DESCRIPTOR_NAME);
    }

    /**
     * Returns the descriptor city.
     *
     * @return string
     */
    public function getDescriptorCity() {
        return (string) $this->getValue(self::KEY_DESCRIPTOR_CITY);
    }

    /**
     * Returns the embedded theme.
     *
     * @return string
     */
    public function getEmbeddedTheme() {
        return (string) $this->getValue(self::KEY_EMBEDDED_THEME);
    }
}
