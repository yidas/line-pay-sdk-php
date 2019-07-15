<?php

require __DIR__ . '/_config.php';

// Route process
$route = isset($_GET['route']) ? $_GET['route'] : null;
switch ($route) {
  case 'clear':
    session_destroy();
    // Redirect back
    header('Location: ./index.php');
    break;

  case 'order':
  case 'index':
  default:
    # code...
    break;
}

// Get the order from session
$order = isset($_SESSION['linePayOrder']) ? $_SESSION['linePayOrder'] : [];
// Get last form data if exists
$config = isset($_SESSION['config']) ? $_SESSION['config'] : [];
// Get merchant list if exist
$merchants = Merchant::getList();
// Get log
$logs = isset($_SESSION['logs']) ? $_SESSION['logs'] : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/x-icon" class="js-site-favicon" href="https://github.githubassets.com/favicon.ico">
    <title>Sample - yidas/line-pay-sdk-php</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
      pre.log {
        word-break: break-all; 
        white-space: pre-wrap; 
        font-size: 9pt;
        background-color: #f5f5f5;
        padding: 5px;
      }
    </style>
    <script>
      /**
       * Form Submit for Online or Offline API ways
       * 
       * @param element form
       */
      function formSubmit(form) {
        form.action = "request.php";
        if (form.otk.value) {
          form.action = "onetimekeys-pay.php";
        }
        else if (form.useRegKey.checked) {
          form.action = "preapproved.php";
        }
        else if (form.transactionId.value) {
          form.action = "details.php";
        }
        form.form.submit();
        return;
      }
    </script>
</head>
<body>
<div style="padding:30px 10px; max-width: 600px; margin: auto;">
  <h3>LINE Pay API Sample</h3>

  <?php if($route=='order'): ?>
  <?php $status = (!isset($order['isSuccessful'])) ? 'none' : (($order['isSuccessful']) ? 'successful' : 'failed') ?>

  <div class="alert alert-<?php if($status=='none'):?>warning<?php elseif($status=='successful'):?>success<?php else:?>danger<?php endif?>" role="alert">
  <h4 class="alert-heading"><?php if($status=='none'):?>Transaction not found<?php elseif($status=='successful'):?>Transaction complete<?php else:?>Transaction failed<?php endif?>!</h4>
    <?php if($status!='none'):?>
    <?php if($status=='failed'):?>
    <hr>
    <p>ErrorCode: <?=$order['confirmCode']?></p>
    <p>ErrorMessage: <?=$order['confirmMessage']?></p>
    <?php endif ?>
    <hr>
    <p>TransactionId: <?=$order['transactionId']?></p>
    <p>OrderId: <?=$order['params']['orderId']?></p>
    <p>ProductName: <?= isset($order['params']['productName']) ? $order['params']['productName'] : $order['params']['packages'][0]['products'][0]['name']?></p>
    <p>Amount: <?=$order['params']['amount']?></p>
    <p>Currency: <?=$order['params']['currency']?></p>
    <hr>
    <p>Environment: <?php if($order['isSandbox']):?>Sandbox<?php else:?>Real<?php endif ?></p>
    <?php endif ?>
    <?php if(isset($order['info']['refundList'])):?>
      <hr>
      <p><strong>Refund Info</strong></p>
      <?php foreach ($order['info']['refundList'] as $key => $refund): ?>
      <p>RefundAmount: <?=$refund['refundAmount']?></p>
      <p>RefundTransactionDate: <?=$refund['refundTransactionDate']?></p>
      <?php endforeach ?>
    <?php endif ?>
    <?php if(isset($config['captureFalse'])):?>
      <hr>
      <p><strong>Capture Info</strong></p>
      <p>Pay Status: <?=isset($order['info']['payStatus']) ? $order['info']['payStatus'] : ''?></p>
      <p>
        <a href="./capture.php?transactionId=<?=$order['transactionId']?>" class="btn btn-primary">Capture</a>
        <a href="./void.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Void</a>
      </p>
    <?php endif ?>
    <?php if(isset($config['preapproved'])):?>
      <hr>
      <p><strong>Preapproved Info</strong></p>
      <p>regKey: <?=$config['regKey']?></p>
    <?php endif ?>
    <hr>
    <div class="clearfix">
      <div class=" float-left">
        <a href="./index.php" class="btn btn-light">Go Back</a>
      </div>
      <div class="float-right">
        <?php if($status=='successful'):?>

        <div class="input-group">
          <input type="text" id="refund-amount" class="form-control" placeholder="Amount" size="7">
          <div class="input-group-append">
            <button class="btn btn-danger" type="button" onclick="location.href='./refund.php?transactionId=<?=$order['transactionId']?>&amount=' + document.getElementById('refund-amount').value">Refund</button>
          </div>
        </div>
        <!-- <input type="text" class="form-control" size="5" style="display: inline; width: 50px;" />
        <a href="./refund.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Refund</a> -->
        <?php endif ?>
      </div>
    </div>
  </div>

  <?php else: ?>

  <form method="POST" onsubmit="formSubmit(this);return;">
    <?php if($merchants): ?>
    <div class="merchant-block form-group" data-block-id="config" style="display: none;">
      <label for="inputChannelId">Merchant (<a class="btn-merchant-switch" href="javascript:void(0);" data-block-id="config">Switch to Custom</a>)</label>
      <select class="form-control" name="merchant" disabled>
      <?php foreach($merchants as $key => $merchant): ?>
        <option value="<?=$key?>" <?php if(isset($config['merchant']) && $config['merchant']==$key):?>selected<?php endif ?>><?=isset($merchant['title']) ? $merchant['title'] : "(Merchant - {$key})"?></option>
      <?php endforeach ?>
      </select>
     </div>
    <?php endif ?>
    <div class="merchant-block" data-block-id="custom">
      <div class="form-group">
        <label for="inputChannelId">ChannelId <?php if($merchants): ?>(<a class="btn-merchant-switch" href="javascript:void(0);" data-block-id="custom">Switch to Config</a>)<?php endif ?></label>
        <input type="text" class="form-control" id="inputChannelId" name="channelId" placeholder="Enter X-LINE-ChannelId" value="<?=(!isset($config['merchant']) && isset($config['channelId'])) ? $config['channelId'] : ''?>" required>
      </div>
      <div class="form-group">
        <label for="inputChannelSecret">ChannelSecret</label>
        <input type="text" class="form-control" id="inputChannelSecret" name="channelSecret" placeholder="Enter X-LINE-ChannelSecret" value="<?=(!isset($config['merchant']) && isset($config['channelSecret'])) ? $config['channelSecret'] : ''?>" required>
      </div>
    </div>
    <div class="form-group">
      <label for="inputProductName">ProductName</label>
      <input type="text" class="form-control" id="inputProductName" name="productName" placeholder="Your product name"  value="<?=isset($config['productName']) ? $config['productName'] : 'QA Service Pack'?>">
    </div>
    <div class="form-group">
      <label for="inputAmount">Amount</label>
      <input type="text" class="form-control" id="inputAmount" name="amount" placeholder="Your product amount" value="<?=isset($config['amount']) ? $config['amount'] : '250'?>" required value="250">
    </div>
    <div class="form-group">
      <label for="inputCurrency">Currency</label>
      <input type="text" class="form-control" id="inputCurrency" name="currency" placeholder="Currency" value="<?=isset($config['currency']) ? $config['currency'] : 'TWD'?>" required>
    </div>
    <div class="form-group">
      <label for="inputCurrency">Offline API option - OneTimeKey (<a href="https://sandbox-web-pay.line.me/web/sandbox/payment/otk" target="_blank">Global</a> / <a href="https://sandbox-web-pay.line.me/web/sandbox/payment/oneTimeKey?countryCode=TW&paymentMethod=card&preset=1" target="_blank">TW</a> Simulation)</label>
      <input type="text" class="form-control" id="inputOtk" name="otk" placeholder="LINE Pay My Code (Fill in to switch to Offline API)" value="">
    </div>
    <div class="row">
      <div class="col col-4">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputSandbox" name="isSandbox" <?=isset($config['isSandbox']) && !$config['isSandbox'] ? '' : 'checked'?>>
          <label class="form-check-label" for="inputSandbox">Sandbox</label>
        </div>
      </div>
      <div class="col col-8 text-right">
        <a href="javascript:void(0);" data-toggle="collapse" data-target="#collapseMoreSettings">More Settings</a>
        |
        <a href="javascript:void(0);" data-toggle="modal" data-target="#logModal">View Logs</a>
      </div>
    </div>
    <div class="collapse" id="collapseMoreSettings">
      <div class="card card-body">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputPreapproved" name="preapproved" <?=isset($config['preapproved']) ? 'checked' : ''?>>
          <label class="form-check-label" for="inputPreapproved">PayType: <code>PREAPPROVED</code> <font color="#cccccc"><i>(Online Only)</i></font></label>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputUseRegKey" name="useRegKey">
          <label class="form-check-label" for="inputUseRegKey">Pay Preapproved by <code>regKey</code> <font color="#cccccc"><i>(Online Only)</i></font></label>
          <input type="text" class="form-control form-control-sm" id="inputRegKey" name="regKey" placeholder="Preapproved regKey" value="<?=isset($config['regKey']) ? $config['regKey'] : ''?>">
        </div>
        <hr>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputCaptureFalse" name="captureFalse" <?=isset($config['captureFalse']) ? 'checked' : ''?>>
          <label class="form-check-label" for="inputCaptureFalse">Capture: <code>false</code></label>
        </div>
        <hr>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text" style="min-width: 115px;">DeviceProfileId</span>
          </div>
          <input type="text" name="merchantDeviceProfileId" class="form-control" pattern="[a-zA-Z0-9\s]+" placeholder="X-LINE-MerchantDeviceProfileId (Alphanumeric only)">
        </div>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text" style="min-width: 115px;">BranchName</span>
          </div>
          <input type="text" name="branchName" class="form-control" placeholder="options.extra.branchName">
        </div>
        <hr>
        <div class="form-group">
          <label>Search Transaction <font color="#cccccc"><i>(Refer by Custom merchant & Sandbox)</i></font></label>
          <div class="input-group">
            <input type="text" class="form-control" name="transactionId" placeholder="Input transactionId to search">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="submit">Submit</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <hr>
    <div class="row">
      <div class="col col-12 col-md-4" style="padding-bottom:5px;">
        <button type="submit" class="btn btn-primary btn-block">Create New Order</button>
      </div>
      <!-- <div class="col col-12 col-md-8 text-right" style="padding-bottom:5px;">
        <?php if(isset($order['isSuccessful'])):?><a href="./index.php?route=order" class="btn btn-info">Check Last Order</a><?php endif ?>
        <button type="reset" class="btn btn-success">Reset</button>
        <button type="button" class="btn btn-danger" onclick="if(confirm('Confirm to clear saved form data?')){location.href='?route=clear'}">Clear</button>
      </div> -->
      <div class="col col-12 col-md-4" style="padding-bottom:5px;">
      <?php if(isset($order['isSuccessful'])):?>
        <a href="./index.php?route=order" class="btn btn-info btn-block">Review Last Order</a>
      <?php elseif(isset($order['transactionId'])): ?>
        <a href="./check.php?transactionId=<?=$order['transactionId']?>" class="btn btn-info btn-block">Check Order Status</a>
      <?php endif ?>
      </div>
      <div class="col col-12 col-md-2" style="padding-bottom:5px;">
        <button type="reset" class="btn btn-success btn-block">Reset</button>
      </div>
      <div class="col col-12 col-md-2" style="padding-bottom:5px;">
        <button type="button" class="btn btn-danger btn-block" onclick="if(confirm('Confirm to clear saved form data?')){location.href='?route=clear'}">Clear</button>
      </div>
    </div>
  </form>

  <?php endif ?>

  <!-- Modal for log -->
  <div class="modal fade" id="logModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Log (Reset by New Order)</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
        <?php foreach ((array) $logs as $key => $log): ?>
          <?php if($key!==0):?>
          <hr>
          <?php endif ?>
          <div>
            <p><strong><?=$log['name']?></strong> (<?=$log['datetime']?>)</p>
            <p>Request body:</p>
            <pre class="log"><?=$log['request']['content']?></pre>
            <p>Response body:</p>
            <pre class="log"><?=$log['response']['content']?></pre>
          </div>
        <?php endforeach ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>

  // Merchant config block
  var elBlockConfig = document.querySelector(".merchant-block[data-block-id='config']");

  // jQuery asset loading precaution (ensure that general functionality is available without jQuery)
  if (typeof $ === 'undefined' && elBlockConfig) {
    elBlockConfig.parentNode.removeChild(elBlockConfig);
  }

  // Merchant switch (jQuery required)
  if (elBlockConfig) {

    $(".btn-merchant-switch").click(function () {
      var self = $(this).data("block-id");
      var target = (self==="custom") ? 'config' : 'custom';
      var $selfBlock = $(".merchant-block[data-block-id='" + self + "']");
      var $targetBlock = $(".merchant-block[data-block-id='" + target + "']");
      // Switch
      $selfBlock.find("input, select").prop('disabled', true);
      $selfBlock.hide(300, function () {
        $targetBlock.find("input, select").prop('disabled', false);
        $targetBlock.show(200);
      });
    });
  }

  <?php if($merchant && (!$config || isset($config['merchant']))): ?>
  // Action for merchant config condition
  $(".merchant-block[data-block-id='custom']").find(".btn-merchant-switch").click();
  <?php endif ?>
</script>
</body>
</html>