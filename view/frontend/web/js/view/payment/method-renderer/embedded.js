/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/method-renderer/cc-form',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Ui/js/modal/modal',
        'mage/translate',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/redirect-on-success',
    ],
    function($, Component, VaultEnabler, modal, __, CheckoutCom, quote, url, setPaymentInformationAction, fullScreenLoader, additionalValidators, checkoutData, redirectOnSuccessAction, customer) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/embedded',
                code: 'checkout_com',
                cardTokenId: null,
                redirectAfterPlaceOrder: true
            },

            /**
             * @returns {exports}
             */
            initialize: function() {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                this.selectedAlternativePayment = null;
                this.alternativePayments = null;

                return this;
            },

            /**
             * @returns {bool}
             */
            isVaultEnabled: function() {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @returns {string}
             */
            getVaultCode: function() {
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            },

            /**
             * @returns {string}
             */
            getCode: function() {
                return CheckoutCom.getCode();
            },

            /**
             * @param {string} cardTokenId
             */
            setCardTokenId: function(token) {
                this.cardTokenId = token;
            },

            /**
             * @returns {bool}
             */
            isActive: function() {
                return CheckoutCom.getPaymentConfig()['isActive'];
            },

            /**
             * @returns {string}
             */
            getPublicKey: function() {
                return CheckoutCom.getPaymentConfig()['public_key'];
            },

            /**
             * @returns {string}
             */
            getApiUrl: function() {
                return CheckoutCom.getPaymentConfig()['api_url'];
            },

            /**
             * @returns {string}
             */
            getCdnUrl: function() {
                return CheckoutCom.getPaymentConfig()['cdn_url'];
            },

            /**
             * @returns {string}
             */
            getPaymentToken: function() {                
                return CheckoutCom.getPaymentConfig()['payment_token'];
            },

            /**
             * @returns {string}
             */
            getQuoteValue: function() {
                return quote.getTotals();
            },

            /**
             * @returns {string}
             */
            getQuoteCurrency: function() {
                return CheckoutCom.getPaymentConfig()['quote_currency'];
            },

            /**
             * @returns {bool}
             */
            isCardAutosave: function() {
                return CheckoutCom.getPaymentConfig()['card_autosave'];
            },
            
            /**
             * @returns {string}
             */
            getRedirectUrl: function() {
                return url.build('checkout_com/payment/placeOrder');
            },

            /**
             * @returns {string}
             */
            getFramesIntegration: function() {
                return CheckoutCom.getPaymentConfig()['frames_integration'];
            },

            /**
             * @returns {void}
             */
            saveSessionData: function() {
                // Get self
                var self = this;

                // Prepare the session data
                var sessionData = {
                    saveShopperCard: $('#checkout_com_enable_vault').is(":checked"),
                    customerEmail: self.getEmailAddress()
                };

                // Send the session data to be saved
                $.ajax({
                    url: url.build('checkout_com/shopper/sessionData'),
                    type: "POST",
                    data: sessionData,
                    success: function(data, textStatus, xhr) {},
                    error: function(xhr, textStatus, error) {
                        console.log(error);
                    }
                });
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                // Get self
                var self = this;

                // Get the form
                var paymentForm = document.getElementById('embeddedForm');

                // Validate before submission
                if (additionalValidators.validate()) {
                    // Set the save card option in session
                    self.saveSessionData();

                    // Check the AP submission
                    if (this.selectedAlternativePayment) {
                        // Find the selected Alternative Payment
                        var selectedAp = this.findObjectInArray(
                            'id', 
                            this.selectedAlternativePayment, 
                            this.alternativePayments
                        );

                        // Place the AP order
                        this.openModal(selectedAp);
                    }
                    
                    // Check the Frames submission
                    else if (Frames.isCardValid()) {
                        // Submit frames form
                        Frames.submitCard();
                    }
                }
            },

            /**
             * @returns {void}
             */
            openModal: function(item) {
                // Assign this to self
                var self = this;

                // Prepare the variables
                var modalContent = '';
                var itemHasExtraOptions = (item.customFields != 'undefined' && item.customFields.length > 0);
                if (itemHasExtraOptions) {
                    modalContent += '<select id="extraPaymentIssuer">';
                    $.each(item.customFields[0].lookupValues, function(key, val) {
                        modalContent += '<option value="' + val + '">' + __(key) + '</option>';
                    });
                    modalContent += '</select>';

                    // Build the modal content
                    var itemModal = $('<div/>').html(modalContent).modal({
                        type: 'popup',
                        responsive: true,
                        modalClass: 'cko-modal',
                        title: __('Pay with') + ' ' + item.name,
                        buttons: [{
                            text: __('Continue'),
                            click: function () {
                                var theSelect = $('#extraPaymentIssuer');
                                self.lpCharge(item, theSelect.val());
                            }
                        }]
                    });

                    // Open the modal window
                    itemModal.modal('openModal');
                }
                else {
                    this.lpCharge(item, null);
                }
            },

            /**
             * @returns {void}
             */
            lpCharge: function(item, issuerId) {
                // Prepare the variables
                var lpChargeUrl = this.getApiUrl() + 'charges/localpayment';

                // Create the item payload
                var itemData = {
                    email : this.getEmailAddress(),
                    localPayment : {
                        lppId : item.id,
                        userData : (issuerId) ? {issuerId: issuerId}  : {}
                    },
                    paymentToken : this.getPaymentToken()
                };

                // Perform the local payment charge
                $.ajax({
                    url: lpChargeUrl,
                    type: "POST",
                    data: JSON.stringify(itemData),
                    beforeSend: function(xhr){
                        xhr.setRequestHeader('Authorization', 'sk_test_ae8b4fe8-f140-4fe4-8e4c-946db8b179da');
                        xhr.setRequestHeader('Content-Type', 'application/json');
                    },
                    success: function(data, textStatus, xhr) {
                        if ((data) && !!data.localPayment.paymentUrl.trim()) {
                            window.location.replace(data.localPayment.paymentUrl);
                        }
                        else if (parseInt(data.responseCode) > 0) {
                            var msg  = __('An error has occured. Code') + ': ' + data.responseCode;
                                msg += (data.message) ? '-' + data.message : '';
                            alert(msg);
                        }
                        else {
                            console.log(data);
                        }
                    },
                    error: function(xhr, textStatus, error) {
                        console.log(error)
                    }
                });
            },

            /**
             * @override
             */
            placeOrder: function() {
                // Assign this to self
                var self = this;

                // Disable migrate output
                $.migrateMute = true;

                // Freeze the place order button
                this.isPlaceOrderActionAllowed(false);

                // Place the orders
                this.getPlaceOrderDeferredObject()
                .fail(
                    function() {
                        self.isPlaceOrderActionAllowed(true);
                        self.reloadEmbeddedForm();
                    }
                ).done(
                    function() {
                        self.afterPlaceOrder();

                        if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                        }
                    }
                );
            },

            /**
             * @returns {void}
             */
            getEmbeddedForm: function() {
                // Get self
                var self = this;

                // Prepare parameters
                var ckoTheme = CheckoutCom.getPaymentConfig()['embedded_theme'];
                var css_file = CheckoutCom.getPaymentConfig()['css_file'];
                var custom_css = CheckoutCom.getPaymentConfig()['custom_css'];
                var ckoThemeOverride = ((custom_css) && custom_css !== '' && css_file == 'custom') ? custom_css : undefined;
                var redirectUrl = self.getRedirectUrl();
                var threeds_enabled = CheckoutCom.getPaymentConfig()['three_d_secure']['enabled'];
                var paymentForm = document.getElementById('embeddedForm');
                var framesIntegration = this.getFramesIntegration();

                // Freeze the place order button on initialisation
                self.isPlaceOrderActionAllowed(false);

                // Initialise the embedded form
                if (framesIntegration == 'form' || framesIntegration == 'both') {
                    Frames.init({
                        publicKey: self.getPublicKey(),
                        containerSelector: '#cko-form-holder',
                        theme: ckoTheme,
                        themeOverride: ckoThemeOverride,
                        frameActivated: function () {
                            self.isPlaceOrderActionAllowed(false);
                        },
                        cardValidationChanged: function() {
                            self.isPlaceOrderActionAllowed(Frames.isCardValid());
                        },
                        cardTokenised: function(event) {
                            // Set the card token
                            self.setCardTokenId(event.data.cardToken);

                            // Add the card token to the form
                            Frames.addCardToken(paymentForm, event.data.cardToken);

                            // Place order
                            if (threeds_enabled) {
                                window.location.replace(redirectUrl + '?cko-card-token=' + event.data.cardToken + '&cko-context-id=' + self.getEmailAddress());
                            } else {
                                self.placeOrder();
                            }
                        },
                    });   
                }   
                            
                // Handle alternative payments
                if (framesIntegration == 'ap' || framesIntegration == 'both') {
                    // Prepare the variables
                    var paymentToken = this.getPaymentToken();
                    var apiUrl = this.getApiUrl() + 'providers/localpayments/?paymentToken=' + paymentToken;

                    // Send the Alternative Payments request
                    $.ajax({
                        url: apiUrl,
                        type: "GET",
                        beforeSend: function(xhr){
                            xhr.setRequestHeader('Authorization', self.getPublicKey());
                        },
                        success: function(res, textStatus, xhr) {
                            if (parseInt(res.count) > 0) {
                                // Add the Alternative payments to global scope for later use (place order)
                                self.alternativePayments = res.data;

                                // Process each Alternative Payment result
                                $.each(res.data, function(i, item) {
                                    // Add the element
                                    var imageUrl = self.getCdnUrl() + 'img/lp_logos/' + item.name.toLowerCase() + '.png';
                                    $.get(imageUrl).done(function() { 
                                        // Create the image tag
                                        var html = $('<div></div>').append($('<img>', {
                                            id: item.id,
                                            src: imageUrl
                                        }));

                                        // Add the html to the container
                                        $('#cko-ap-holder').append(html);

                                        // Create the icon effects
                                        self.addIconEffects(item);
                                    });
                                });
                            }
                        },
                        error: function(xhr, textStatus, error) {
                            console.log(error);
                        } 
                    });
                }

                if (framesIntegration == 'both') {
                    // Disable AP selection on frames slide click
                    $('#tab-1').click(function () {
                        // Disable icons
                        self.disableIcons();

                        // Disable place order
                        self.isPlaceOrderActionAllowed(false);
                    });
                }
                else {
                    // Disable accordion
                    $('.cko-tab .tab-content').addClass('slide-disabled');
                }
            },

            findObjectInArray: function(key, val, arr) {
                for (var i = 0; i < arr.length; i++) {
                    if (arr[i][key] == val) {
                        return arr[i];
                    }
                }

                return false;
            },

            /**
             * @returns {void}
             */
            addIconEffects: function (element) {
                // Get self
                var self = this;
                
                // Hover effect
                self.addIconHover(element.id);

                // Create the click event
                $('#' + element.id).click(function() {
                    // Disable all icons
                    self.disableIcons();

                    // Make the current active
                    self.enableActiveIcon($(this));
                    
                    // Enable place order
                    self.isPlaceOrderActionAllowed(true);
                });
            },

            /**
             * @returns {void}
             */
            addIconHover: function (elementId) {
                $('#' + elementId).hover(
                    function() {
                        $(this).animate({
                            opacity: 1
                        }, 20);
                    },  
                    function() {
                        $(this).animate({
                            opacity: 0.5
                        }, 20);
                    }
                );
            },

            /**
             * @returns {void}
             */
            enableActiveIcon: function (element) {
                element.parent().animate({
                    borderTopWidth: '3px',
                    borderBottomWidth: '3px',
                    borderLeftWidth: '3px',
                    borderRightWidth: '3px',
                    opacity: 1
                }, 100);
                element.animate({
                    opacity: 1
                }, 80);      

                // Remove the over effect
                element.off('hover');

                // Save the selection
                this.selectedAlternativePayment = element.prop('id');
            },

            /**
             * @returns {void}
             */
            disableIcons: function () {
                // Get self
                var self = this;

                // Disable all active icons
                $('#cko-ap-holder > div').animate({
                    borderTopWidth: '1px',
                    borderBottomWidth: '1px',
                    borderLeftWidth: '1px',
                    borderRightWidth: '1px'
                }, 100);

                // Set opacity for all icons
                $('#cko-ap-holder > div img').animate({
                    opacity: 0.5
                }, 100);

                // Activate hover for all icons
                $('#cko-ap-holder > div img').each(function (index, value) { 
                    self.addIconHover($(this).prop('id')); 
                });

                // Empty the global AP container
                this.selectedAlternativePayment = null;
            },

            /**
             * @returns {void}
             */
            reloadEmbeddedForm: function() {
                // Get self
                var self = this;

                // Reload the iframe
                $('#cko-form-holder form iframe').remove();
                self.getEmbeddedForm();
            },
        });
    }
);