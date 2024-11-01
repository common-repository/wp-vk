


CREATE TABLE IF NOT EXISTS `wp_vk_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `pay_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `trade_sn` varchar(64) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `expired` datetime DEFAULT NULL,
  `pay_at` datetime DEFAULT NULL,
  `price` float(10,2) NOT NULL DEFAULT '0.00',
  `money` decimal(8,2) NOT NULL DEFAULT '0.00',
  `trade_money` float(8,2) NOT NULL DEFAULT '0.00',
  `u_del` tinyint(4) NOT NULL DEFAULT '0',
  `pay_type` tinyint(4) NOT NULL DEFAULT '1',
  `name` varchar(200) DEFAULT NULL,
  `use_coupon` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `memo` text DEFAULT NULL,
  `verify_code` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_no` (`order_no`),
  KEY `uid` (`uid`),
  KEY `pid` (`pid`),
  KEY `verify_code` (`verify_code`)
) ENGINE=InnoDB;


-- row split --


CREATE TABLE IF NOT EXISTS `wp_vk_trade_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `oid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `order_no` varchar(64) NOT NULL,
  `trade_no` varchar(64) NOT NULL,
  `trade_status` varchar(64) NOT NULL,
  `type` tinyint(3) UNSIGNED NOT NULL,
  `pay_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `buyer_id` varchar(128) DEFAULT NULL,
  `total_amount` float(10,2) NOT NULL DEFAULT '0.00',
  `gmt_payment` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `memo` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_no` (`order_no`),
  KEY `trade_status` (`trade_status`),
  KEY `oid` (`oid`)
) ENGINE=InnoDB;



