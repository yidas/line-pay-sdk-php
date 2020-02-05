Sample codes of LINE Pay
========================

<img src="https://raw.githubusercontent.com/yidas/line-pay-sdk-php/master/img/sample-index-desktop.png" height="500" /><img src="https://raw.githubusercontent.com/yidas/line-pay-sdk-php/master/img/sample-index-mobile.png" height="500" />

FEATURES
--------

*1. **No database** required.*

*2. **Saving config with authentication** by session for payment processes and next order.*

*3. Independent program files such as **Reserve, Confirm, Refund**.*

---

INSTALLATION
------------

Download repository and run Composer install in your Web directory: 

```
git clone https://github.com/yidas/line-pay-sdk-php.git;
cd line-pay-sdk-php;
composer install;
```

Then you can access the sample site from `https://{yourweb-dir}/line-pay-sdk-php/sample`.


---

FLOW
----

Payment flow: [Request](https://github.com/yidas/line-pay-sdk-php/tree/v3#request-api) -> [Confirm](https://github.com/yidas/line-pay-sdk-php/tree/v3#confirm-api) / [Details](https://github.com/yidas/line-pay-sdk-php/tree/v3#payment-details-api) -> [Refund](https://github.com/yidas/line-pay-sdk-php/tree/v3#refund-api)
