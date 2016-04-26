<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Localizecomment extends Mage_Core_Model_Config_Data
{
    /**
     * Localize links found in Amazon admin
     */
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        switch (Mage::helper('amazon_payments')->getAdminRegion()) {
            case 'eu':
                $domain = 'sellercentral-europe.amazon.com';
                break;
            default:
                $domain = null;
                break;
        }

        // Use JS as the settings/header comment doesn't support getCommentText
        if ($domain) {
            $script = '
                $$(".amzn-link").each(function(el, i) {
                    if (el.href) {
                        //el.href = el.href.replace("sellercentral.amazon.com", "' . $domain . '");
                        el.href = "https://' . $domain . '";
                    }

                    var onclick = el.readAttribute("onclick");

                    if (onclick) {
                        el.writeAttribute("href", "javascript:window.open(\'https://' . $domain . '\')");
                        //el.writeAttribute("onclick", onclick.toString().replace("sellercentral.amazon.com", "' . $domain . '"));
                    }

                });

                $$(".amzn-us-signup").invoke("hide");
                ';

            // Update doc domain
            if (Mage::helper('amazon_payments')->isAdminGermany()) {
                $script .= '
                $$(".amzn-doc-link").each(function(el, i) {
                    el.href = el.href.replace(".co.uk", ".de");
                });
                ';
            }

            return '<script>document.observe("dom:loaded", function() { '. $script . '});</script>';
        }
    }
}