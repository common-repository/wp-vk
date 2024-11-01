<?php

/**
 * PAYJS 支付通知
 */

require_once dirname(__DIR__).'/load.php';

require_once __DIR__.'/payjs.php';
VK_Payjs::notify();
