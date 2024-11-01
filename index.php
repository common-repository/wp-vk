<?php
/*
Plugin Name: WP VK-付费内容插件
Plugin URI: http://wordpress.org/plugins/wp-vk/
Version: 1.4.3
Description: WP VK（付费内容插件）支持站长自主配置支付接口（包括微信支付/支付宝官方支付API和第三方支付接口-虎皮椒）；对部分需要付费的文字、下载等内容执行加密，需用户付费解锁后才能查看。
Author: 闪电博
Author URI: http://www.wbolt.com/
*/
define('WP_VK_PATH',__DIR__);
define('WP_VK_BASE_FILE',__FILE__);
define('WP_VK_VERSION','1.4.3');
define('WP_VK_ASSETS_VER','1.4.3');

define('WP_VK_URL', plugin_dir_url(WP_VK_BASE_FILE));

require_once __DIR__ . '/classes/vk.class.php';
require_once __DIR__ . '/classes/block.class.php';
require_once __DIR__ . '/classes/utils.class.php';
require_once __DIR__ . '/order/order.class.php';
require_once __DIR__ . '/pay/pay.class.php';
require_once __DIR__ . '/widget/widget.php';
require_once __DIR__ . '/widget/WB_WP_VK_Widget.php';
require_once __DIR__ . '/classes/vk_justnews.class.php';

WP_VK_BLOCK::init();
WP_VK::init();
WP_VK_Pay::init();
WP_VK_Widget::init();
WP_VK_Justnews::init();