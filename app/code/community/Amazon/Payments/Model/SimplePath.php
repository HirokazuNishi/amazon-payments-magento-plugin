<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_SimplePath
{
    const API_ENDPOINT_DOWNLOAD_KEYS = 'https://payments.amazon.com/register';
    const API_ENDPOINT_GET_PUBLICKEY = 'https://payments.amazon.com/register/getpublickey';

    const PARAM_SP_ID = 'A2K7HE1S3M5XJ';

    const CONFIG_XML_PATH_PRIVATE_KEY = 'payment/amazon_payments/simplepath/privatekey';
    const CONFIG_XML_PATH_PUBLIC_KEY  = 'payment/amazon_payments/simplepath/publickey';

    /**
     * Generate and save RSA keys
     */
    public function generateKeys()
    {
        $rsa = new Zend_Crypt_Rsa;
        $keys = $rsa->generateKeys(array('private_key_bits' => 2048, 'privateKeyBits' => 2048, 'hashAlgorithm' => 'sha1'));

        Mage::getConfig()
            ->saveConfig(self::CONFIG_XML_PATH_PUBLIC_KEY, $keys['publicKey'], 'default', 0)
            ->saveConfig(self::CONFIG_XML_PATH_PRIVATE_KEY, Mage::helper('core')->encrypt($keys['privateKey']), 'default', 0);

        Mage::app()->cleanCache();

        return $keys;
    }

    /**
     * Delete key-pair from config
     */
    public function destroyKeys()
    {
        Mage::getConfig()
            ->deleteConfig(self::CONFIG_XML_PATH_PUBLIC_KEY, 'default', 0)
            ->deleteConfig(self::CONFIG_XML_PATH_PRIVATE_KEY, 'default', 0);

        Mage::app()->cleanCache();
    }

    /**
     * Return RSA public key
     *
     * @param bool $pemformat  Return key in PEM format
     */
    public function getPublicKey($pemformat = false, $reset = false)
    {
        $publickey = Mage::getStoreConfig(self::CONFIG_XML_PATH_PUBLIC_KEY, 0);

        // Generate key pair
        if (!$publickey || $reset || strlen($publickey) < 300) {
            $keys = $this->generateKeys();
            $publickey = $keys['publicKey'];
        }

        if (!$pemformat) {
            $publickey = str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"), array('','',''), $publickey);
        }
        return $publickey;
    }

    /**
     * Return RSA private key
     */
    public function getPrivateKey()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::CONFIG_XML_PATH_PRIVATE_KEY, 0));
    }

    /**
     * Convert key to PEM format for openssl functions
     */
    public function key2pem($key)
    {
        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split($key, 64, "\n") .
               "-----END PUBLIC KEY-----\n";
    }

    /**
     * Verify and decrypt JSON payload
     *
     * @param string $payloadJson
     */
    public function decryptPayload($payloadJson, $autoEnable = true)
    {
        try {
          $payload = Zend_Json::decode($payloadJson, Zend_Json::TYPE_OBJECT);
          $payloadVerify = clone $payload;

          // Unencrypted?
          if (isset($payload->merchant_id, $payload->access_key, $payload->secret_key)) {
              return $this->saveToConfig($payloadJson, $autoEnable);
          }

          // Validate JSON
          if (!isset($payload->encryptedKey, $payload->encryptedPayload, $payload->iv, $payload->sigKeyID, $payload->signature)) {
              Mage::throwException("Unable to import Amazon keys. Please verify your JSON format and values.");
          }

          // URL decode values
          foreach ($payload as $key => $value) {
              $payload->$key = urldecode($value);
          }

          // Retrieve Amazon public key to verify signature
          try {
              $client = new Zend_Http_Client(self::API_ENDPOINT_GET_PUBLICKEY, array(
                  'maxredirects' => 2,
                  'timeout'      => 30));

              $client->setParameterGet(array('sigkey_id' => $payload->sigKeyID));

              $response = $client->request();
              $amazonPublickey = urldecode($response->getBody());

          } catch (Exception $e) {
              Mage::throwException($e->getMessage());
          }

          // Use raw JSON (without signature or URL decode) as the data to verify signature
          unset($payloadVerify->signature);
          $payloadVerifyJson = Zend_Json::encode($payloadVerify);

          // Verify signature using Amazon publickey and JSON paylaod
          if ($amazonPublickey && openssl_verify($payloadVerifyJson, base64_decode($payload->signature), $this->key2pem($amazonPublickey), 'SHA256')) {

              // Decrypt Amazon key using own private key
              $decryptedKey = null;
              openssl_private_decrypt(base64_decode($payload->encryptedKey), $decryptedKey, $this->getPrivateKey(), OPENSSL_PKCS1_OAEP_PADDING);

              // Decrypt final payload (AES 128-bit)
              $finalPayload = mcrypt_cbc(MCRYPT_RIJNDAEL_128, $decryptedKey, base64_decode($payload->encryptedPayload), MCRYPT_DECRYPT, base64_decode($payload->iv));

              // Remove binary characters
              $finalPayload = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $finalPayload);

              if (Zend_Json::decode($finalPayload)) {
                  $this->saveToConfig($finalPayload, $autoEnable);
                  $this->destroyKeys();
                  return $finalPayload;
              }

          } else {
              Mage::throwException("Unable to verify signature for JSON payload.");
          }

        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amazon_payments')->__($e->getMessage()));

            $link = 'https://payments.amazon.com/help/202024240';
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amazon_payments')->__("If you're experiencing consistent errors with transferring keys, click <a href=\"%s\" target=\"_blank\">Manual Transfer Instructions</a> to learn more.", $link));
        }

        return false;
    }

    /**
     * Save values to Mage config
     *
     * @param string $json
     */
    public function saveToConfig($json, $autoEnable = true)
    {
        if ($values = Zend_Json::decode($json, Zend_Json::TYPE_OBJECT)) {
            foreach ($values as $key => $value) {
              $values->{strtolower($key)} = $value;
            }
            $config = Mage::getModel('core/config');
            $amazonConfig = Mage::getSingleton('amazon_payments/config');

            $config->saveConfig($amazonConfig::CONFIG_XML_PATH_SELLER_ID, $values->merchant_id, 'default', 0);
            $config->saveConfig($amazonConfig::CONFIG_XML_PATH_CLIENT_ID, $values->client_id, 'default', 0);
            $config->saveConfig($amazonConfig::CONFIG_XML_PATH_CLIENT_SECRET, Mage::helper('core')->encrypt($values->client_secret), 'default', 0);
            $config->saveConfig($amazonConfig::CONFIG_XML_PATH_ACCESS_KEY, $values->access_key, 'default', 0);
            $config->saveConfig($amazonConfig::CONFIG_XML_PATH_ACCESS_SECRET, Mage::helper('core')->encrypt($values->secret_key), 'default', 0);


            if ($autoEnable) {
                $this->autoEnable();
            }

            Mage::app()->cleanCache();

            return true;
        }
    }

    /**
     * Auto-enable payment method
     */
    public function autoEnable()
    {
        $enableMessage = Mage::helper('amazon_payments')->__("Login and Pay with Amazon is now enabled.");

        $config = Mage::getModel('core/config');
        $amazonConfig = Mage::getSingleton('amazon_payments/config');

        if (!Mage::getStoreConfig($amazonConfig::CONFIG_XML_PATH_ENABLED)) {
            $config->saveConfig($amazonConfig::CONFIG_XML_PATH_ENABLED, true, 'default', 0);
            Mage::getSingleton('adminhtml/session')
                ->addSuccess($enableMessage)
                ->setEnableMessage($enableMessage);
        }
    }

    /**
     * Return listner URL
     */
    public function getListenerUrl()
    {
        $url = Mage::getUrl('amazon_payments/simplepath', array('_store' => Mage::helper('amazon_payments')->getAdminStoreId(), '_forced_secure' => true));
        // Add index.php
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
        return str_replace($baseUrl, $baseUrl . 'index.php/', $url);
    }

    /**
     * Return simplepath URL
     */
    public function getSimplepathUrl()
    {
        return self::API_ENDPOINT_DOWNLOAD_KEYS . '?returnUrl=' . $this->getListenerUrl() .
						'&pub_key=' . urlencode($this->getPublicKey()) .
						'#event/fromSP';
    }

    /**
     * Return array of form POST params for SimplePath sign up
     */
    public function getFormParams()
    {
        // Retrieve store URLs from config
        $urls = array();
        $db = Mage::getSingleton('core/resource')->getConnection('core_read');

        $select = $db->select()
            ->from(Mage::getSingleton('core/resource')->getTableName('core_config_data'))
            ->where('path IN (?)', array('web/unsecure/base_url', 'web/secure/base_url'));

        foreach ($db->fetchAll($select) as $row) {
            $url = parse_url($row['value']);

            if (isset($url['host'])){
                $urls[] = 'https://' . $url['host'];
            }
        }

        // Check config files
        foreach (Mage::app()->getStores() as $store) {
            $storeCode = $store->getCode();
            if ($url = (string) Mage::getConfig()->getNode('stores/' . $storeCode . '/web/secure/base_url')) {
                $urls[] = rtrim($url, '/');
            }
        }

        $urls = array_unique($urls);

        return array(
            'locale' => Mage::getStoreConfig('general/country/default'),
            'spId' => self::PARAM_SP_ID,
            'allowedLoginDomains[]' => $urls,
            'spSoftwareVersion' => Mage::getVersion(),
            'spAmazonPluginVersion' => Mage::getConfig()->getModuleConfig("Amazon_Payments")->version,
        );
    }

    /**
     * Return array of config for JSON AmazonSp variable.
     *
     * @see Amazon_Payments_Model_System_Config_Backend_Enabled->getCommentText()
     */
    public function getJsonAmazonSpConfig()
    {
        return array(
            'amazonUrl'     => $this->getSimplepathUrl(),
            'pollUrl'       => Mage::helper('adminhtml')->getUrl('adminhtml/amazon_simplepath/poll'),
            //'spUrl'         => Mage::helper("adminhtml")->getUrl('adminhtml/amazon_simplepath/spurl'),
            'importUrl'     => Mage::helper('adminhtml')->getUrl('adminhtml/amazon_simplepath/import'),
            'isSecure'      => (int) (Mage::app()->getFrontController()->getRequest()->isSecure()),
            'isUsa'         => (int) (Mage::helper('amazon_payments')->getAdminConfig('general/country/default') == 'US' && Mage::helper('amazon_payments')->getAdminRegion() != 'eu'),
            'hasOpenssl'    => (int) (extension_loaded('openssl')),
            'formParams'    => $this->getFormParams(),
        );
    }
}
