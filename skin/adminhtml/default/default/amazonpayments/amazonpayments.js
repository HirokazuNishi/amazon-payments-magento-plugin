/**
 * Amazon Payments Adminhtml
 *
 * var AmazonSp is set by Amazon_Payments_Model_SimplePath->getJsonAmazonSpConfig()
 */

var amazonPollInterval = 1500; // poll every ms for keys
var amazonPollTimer = null;

document.observe("dom:loaded", function() {
  if ($("payment_amazon_payments")) {
    var amazonSimplepath = $("amazon_simplepath");
    var amazonInstructions = $("amazon_instructions");
    var amazonFields = $$("#payment_amazon_payments tr");
    var amazonImport = $("row_payment_ap_credentials_simplepath_json");
    var amazonImportButton = $("row_payment_ap_credentials_simplepath_import_button");
    var amazonSpBack = $("amazon_simplepath_back");

    amazonInstructions.hide();
    amazonFields.each(Element.hide);
    if (amazonImport != null) amazonImport.hide();

    if ($("payment_ap_credentials_seller_id") == null || $("payment_ap_credentials_seller_id").value) {
        showAmazonConfig();
    }

    // Generate form to post to Amazon
    var form = new Element('form', { method: 'post', action: AmazonSp.amazonUrl, id: 'simplepath_form', target: 'simplepath'});
    amazonSimplepath.wrap(form);

    // Convert formParams JSON to hidden inputs
    var formInput;
    for (var key in AmazonSp.formParams) {
        if (typeof AmazonSp.formParams[key] == 'object' || typeof AmazonSp.formParams[key] == 'array') {
            for (var i in AmazonSp.formParams[key]) {
                if (typeof AmazonSp.formParams[key][i] != "function") {
                    form.insert(new Element('input', { type: 'hidden', name: key, value: AmazonSp.formParams[key][i]}));
                }
            }
        } else {
            form.insert(new Element('input', { type: 'hidden', name: key, value: AmazonSp.formParams[key]}));
        }
    }

    // Get Started clicked
    $("simplepath_form").observe("submit", function(e) {
        var heights = [660, 720, 810, 900];
        var popupHeight = heights[0];
        for (var i in heights) {
          popupHeight = (window.innerHeight >= heights[i]) ? heights[i] : popupHeight;
        }

        window.launchPopup('', 768, popupHeight);

        amazonFields[1].show();
        amazonImport.show();
        amazonImportButton.hide();

        if (!amazonPollTimer) {
            amazonPollTimer = setTimeout(pollForKeys, amazonPollInterval);
        }

    });

    // User is skipping simplepath
    amazonSimplepath.select("a")[0].observe("click", function(e) {
        e.stop();
        amazonSpBack.show();
        showAmazonConfig();
    });

    // User clicked 'Back'
    $("amazon_simplepath_back").select("a")[0].observe("click", function(e) {
        e.stop();
        amazonSpBack.hide();
        amazonSimplepath.show();
        amazonInstructions.hide();

    });

    // Show clipboard import textbox
    amazonImportButton.select("button")[0].observe("click", function(e) {
        amazonImportButton.hide();
        amazonImport.show();
    });

    // User clicked import 'Save'
    $("row_payment_ap_credentials_simplepath_json").select("button")[0].observe("click", function(e) {
        e.stop();
        this.className = "disabled";

        new Ajax.Request(AmazonSp.importUrl, {
            method:'post',
            parameters: { 'json': $("payment_ap_credentials_simplepath_json").value },
            onSuccess: function(transport) {
              location.reload();
            },
            onFailure: function() {  }
        });
    });

    if (!AmazonSp.isSecure) {
        $("amazon_https_required").show();
    }
    if (!AmazonSp.hasOpenssl) {
        $("amazon_openssl_required").show();
    }


    if (!AmazonSp.isUsa) {
        showAmazonConfig();
    }



  }

  function showAmazonConfig() {
      amazonFields.each(Element.show);
      amazonSimplepath.hide();
      amazonImport.hide();
      amazonSpBack.show();

      if ($("payment_ap_credentials_seller_id") == null || $("payment_ap_credentials_seller_id").value) {
        amazonInstructions.hide();
      } else {
        amazonInstructions.show();
      }


  }


  function pollForKeys() {
      new Ajax.Request(AmazonSp.pollUrl, {
          method:'get',
          onSuccess: function(transport) {
              if (transport.responseText == '1') {
                  $("amazon_reload").show();
                  document.location.replace(document.location + "#payment_amazon_payments-head");
                  location.reload();
              } else {
                  amazonPollTimer = setTimeout(pollForKeys, amazonPollInterval);
              }

          },
          onFailure: function() {  },
          // Disable "Please Wait" modal
          onCreate: function(request) {
              Ajax.Responders.unregister(varienLoaderHandler.handler);
          },
      });
  }



});





// Amazon Pop-up
(function () {
    'use strict';

    function launchPopup(url, requestedWidth, requestedHeight) {
        var leftOffset = getLeftOffset(requestedWidth),
            topOffset = getTopOffset(requestedHeight),
            newWindow = window.open(url, 'simplepath', 'scrollbars=yes, width=' + requestedWidth + ', height=' + requestedHeight + ', top=' + topOffset + ', left=' + leftOffset);

        if (window.focus) {
            newWindow.focus();
        }
    }

    function getLeftOffset(requestedWidth) {
        var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : screen.left;

        return ((windowWidth() / 2) - (requestedWidth / 2)) + dualScreenLeft;
    }

    function getTopOffset(requestedHeight) {
        var dualScreenTop = window.screenTop !== undefined ? window.screenTop : screen.top;

        return ((windowHeight() / 2) - (requestedHeight / 2)) + dualScreenTop;
    }

    function windowWidth() {
        if (window.innerWidth) {
            return window.innerWidth;
        } else if (document.documentElement.clientWidth) {
            return document.documentElement.clientWidth;
        } else {
            return screen.width;
        }
    }

    function windowHeight() {
        if (window.innerHeight) {
            return window.innerHeight;
        } else if (document.documentElement.clientHeight) {
            return document.documentElement.clientHeight;
        } else {
            return screen.height;
        }
    }

    window.launchPopup = launchPopup;
})();

