<?php

/**
 * 虎皮椒 支付通知
 */

require_once dirname(__DIR__).'/load.php';


require_once __DIR__.'/hupay.php';
VK_Hupay::notify();