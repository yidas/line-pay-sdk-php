LINE Pay API Tools
==================

<img src="https://raw.githubusercontent.com/yidas/line-pay-sdk-php/master/img/sample-index-desktop.png" height="500" /><img src="https://raw.githubusercontent.com/yidas/line-pay-sdk-php/master/img/sample-index-mobile.png" height="500" />

FEATURES
--------

*1. **No database** required.*

*2. **Saving config with authentication** by session for payment processes and next order.*

*3. **View Logs, Merchants Setting** feature.*

*4. **Offline OTK pay, Pre-Approved, Transaction Search** functions*

---

INSTALLATION
------------

Download repository and run Composer install in your Web directory: 

```
git clone https://github.com/yidas/line-pay-sdk-php.git;
cd line-pay-sdk-php;
composer install;
```

Then you can access the sample site from `https://{yourweb-dir}/line-pay-sdk-php/tool`.


---

FLOW
----

Payment flow: [Request](https://github.com/yidas/line-pay-sdk-php/tree/v3#request-api) -> [Confirm](https://github.com/yidas/line-pay-sdk-php/tree/v3#confirm-api) / [Details](https://github.com/yidas/line-pay-sdk-php/tree/v3#payment-details-api) -> [Refund](https://github.com/yidas/line-pay-sdk-php/tree/v3#refund-api)

---

MERCHANTS SETTING
-----------------

You can save your favorite or test LINE Pay merchant account for display and selection on the sample page.

To enable the setting, create `tool/_merchants.json` file (Under `tool` folder) using the following JSON format:

```json
[
    {
        "title": "First Merchant",
        "channelId": "{your channelId}",
        "channelSecret": "{your channelSecret}"
    },
    {
        "title": "Second Merchant",
        "channelId": "{your channelId}",
        "channelSecret": "{your channelSecret}"
    }
]
```





