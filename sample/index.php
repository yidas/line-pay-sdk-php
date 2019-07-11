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
        form.action = (form.otk.value) ? "onetimekeys-pay.php" : "request.php";
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
    <?php if(isset($order['refundList'])):?>
      <?php foreach ($order['refundList'] as $key => $refund): ?>
      <hr>
      <p>RefundAmount: <?=$refund['refundAmount']?></p>
      <p>RefundTransactionDate: <?=$refund['refundTransactionDate']?></p>
      <?php endforeach ?>
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
    <div class="form-group">
      <label for="inputChannelId">ChannelId</label>
      <input type="text" class="form-control" id="inputChannelId" name="channelId" placeholder="Enter X-LINE-ChannelId" value="<?=isset($config['channelId']) ? $config['channelId'] : ''?>" required>
    </div>
    <div class="form-group">
      <label for="inputChannelSecret">ChannelSecret</label>
      <input type="text" class="form-control" id="inputChannelSecret" name="channelSecret" placeholder="Enter X-LINE-ChannelSecret" value="<?=isset($config['channelSecret']) ? $config['channelSecret'] : ''?>" required>
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
      <div class="col col-6">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputSandbox" name="isSandbox" <?=isset($config['isSandbox']) && !$config['isSandbox'] ? '' : 'checked'?>>
          <label class="form-check-label" for="inputSandbox">Sandbox</label>
        </div>
      </div>
      <div class="col col-6 text-right">
        <a href="javascript:void(0);" data-toggle="modal" data-target="#logModal">View Log</a>
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
</body>
</html>