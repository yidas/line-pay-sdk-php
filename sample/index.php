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
    <p>ProductName: <?= $order['params']['packages'][0]['products'][0]['name']?></p>
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

  <form method="POST" action="request.php">
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
    <div class="form-check">
      <input type="checkbox" class="form-check-input" id="inputSandbox" name="isSandbox" <?=isset($config['isSandbox']) && !$config['isSandbox'] ? '' : 'checked'?>>
      <label class="form-check-label" for="inputSandbox">Sandbox</label>
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
        <?php if(isset($order['isSuccessful'])):?><a href="./index.php?route=order" class="btn btn-info btn-block">Check Last Order</a><?php endif ?>
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

</div>
</body>
</html>