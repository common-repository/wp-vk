<?php

/**
 * 微信 支付通知
 */

require_once dirname(__DIR__).'/load.php';

require_once __DIR__.'/wxpay.php';
VK_Wxpay::notify();