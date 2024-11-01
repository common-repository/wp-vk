<?php

/**
 * Author: wbolt team
 * Author URI: https://www.wbolt.com
 */

class WP_VK_Justnews
{

    public static function init()
    {


        if (!get_option('wp_vk_ver', 0)) {
            return;
        }

        add_filter('wpcom_account_tabs', array(__CLASS__, 'wpcom_account_tabs'), 100);
        add_action('wpcom_account_tabs_order', array(__CLASS__, 'wpcom_account_tabs_order'));

        add_filter('login_url', array(__CLASS__, 'login_url'), 100, 3);

        add_filter('register_url', array(__CLASS__, 'register_url'), 100);

        add_shortcode('vk_my_order', array(__CLASS__, 'wpcom_account_tabs_order'));
    }

    public static function db()
    {
        static $db = null;
        if($db){
            return $db;
        }
        $db = $GLOBALS['wpdb'];
        if($db instanceof wpdb){
            return $db;
        }
        return $db;
    }

    public static function register_url($register_url)
    {

        //print_r([$register_url]);

        if (isset($_GET['redirect_to']) && !preg_match('#redirect_to=#', $register_url)) {
            if (strpos($register_url, '?') === false) {
                $register_url .= '?redirect_to=' . urlencode(esc_url($_GET['redirect_to']));
            } else {
                $register_url .= '&redirect_to=' . urlencode(esc_url($_GET['redirect_to']));
            }
        }

        return $register_url;
    }

    public static function login_url($login_url, $redirect, $force_reauth)
    {
        //print_r([$login_url, $redirect, $force_reauth]);
        if (!$redirect && isset($_GET['redirect_to']) && !preg_match('#redirect_to=#', $login_url)) {
            if (strpos($login_url, '?') === false) {
                $login_url .= '?redirect_to=' . urlencode(esc_url($_GET['redirect_to']));
            } else {
                $login_url .= '&redirect_to=' . urlencode(esc_url($_GET['redirect_to']));
            }
        }

        return $login_url;
    }


    public static function wpcom_account_tabs_order()
    {
        // global $wpdb;
        $db = self::db();
        $t = $db->prefix . 'vk_orders';

        $uid = get_current_user_id();
        $pagesize = 100;
        if (!empty($_POST['num'])) {
            $pagesize = absint($_POST['num']);
            if(!$pagesize) $pagesize = 100;
        }

        $limit = WP_VK_Utils::limit($pagesize);
        $sql = $db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM $t WHERE uid=%d AND u_del=0 ", $uid);

        $sql .= " AND pay_status=1";


        $list = $db->get_results($sql . " ORDER BY id DESC " . $limit);

        $total = $db->get_var("SELECT FOUND_ROWS()");




        include WP_VK_PATH . '/tpl/my_order.tpl.php';
    }
    public static function wpcom_account_tabs($tabs)
    {

        //version - 6 order-circle
        //version - 4 glass
        $tabs[11] = array('slug' => 'order', 'title' => '我的订单', 'icon' => 'glass');

        return $tabs;
    }
}
