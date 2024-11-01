<?php


/**
 * 支付宝 支付页面返回
 */

require_once dirname(__DIR__).'/load.php';


require_once __DIR__.'/alipay.php';
VK_Alipay::pay_return();
