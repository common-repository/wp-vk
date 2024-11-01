<?php


/**
 * 支付宝 支付通知
 */

require_once dirname(__DIR__).'/load.php';

require_once __DIR__.'/alipay.php';
VK_Alipay::notify();