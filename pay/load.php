<?php

/**
 * wp entry
 */

$wp_root = preg_replace('#'.preg_quote(DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR).'.+#i','',__DIR__);

require_once $wp_root.'/wp-config.php';

