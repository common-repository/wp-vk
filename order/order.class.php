<?php


class WP_VK_Order extends WP_VK_Base
{

  private static $debug = false;

  public static $err = null;



  public static function init()
  {

    if (is_admin()) {
      add_action('admin_menu', array(__CLASS__, 'admin_menu_hanlder'));
    }
    add_action('wp_ajax_vk_order', array(__CLASS__, 'order_ajax_handler'));


    add_action('vk_pay_success', function ($trade) {

      // global $wpdb;

      if ($trade->trade_status == 'SUCCESS') {
        self::pay_success($trade);
      }
    });

    add_action('vk_pay_fail', function ($trade) {
      if ($trade->trade_status == 'FAIL') {

        self::pay_fail($trade);
      }
    });
  }


  public static function admin_menu_hanlder()
  {


    if (!get_option('wp_vk_ver', 0)) {
      return;
    }

    if (!current_user_can('list_users')) {
      add_submenu_page(
        'profile.php',
        '我的订单',
        '我的订单',
        'read',
        'vk_my_oder',
        array(__CLASS__, 'vk_my_order')
      );

    }

    //profile.php
  }

  public static  function order_list()
  {
  }



  public static function order_ajax_handler()
  {
    $op = sanitize_text_field(self::param('do', ''));

    switch ($op) {
      case 'get_order_cnf':
        $ret = array();
        $ret['data'] = array(
          'pay_types' => array(1 => '支付宝', 2 => '微信'),
          'pay_status' => array(0 => '未支付', 1 => '已支付'),
          'status' => array(1 => '未支付', 2 => '已支付'),
          'order_types' => array(1 => '登录订单', 2 => '免登录订单')
        );
        $ret['desc'] = 'success';
        $ret['code'] = 0;

        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;

      case 'get_order_list':
        self::get_order_list();
        exit();
        break;

      case 'my_order':
        $ret = array();
        $ret['data'] = self::my_order();
        $ret['desc'] = 'success';
        $ret['code'] = 0;

        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;

      case 'del_my_order':
        self::delete_my_order(intval(self::param('oid', 0)));
        $ret = array();
        $ret['desc'] = 'success';
        $ret['code'] = 0;

        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;
    }
  }

  public static function vk_my_order()
  {
    //global $wpdb;

    $db = self::db();
    $pay_types = array(1 => '支付宝', 2 => '微信');
    $status = array(0 => '待付款', 10 => '已开通');
    $pay_status = array(0 => '未支付', 1 => '已支付');
    $cur_page_url = admin_url('profile.php?page=' . self::param('page', '', 'g'));
    $t = $db->prefix . 'vk_orders';

    $param = array('pagesize' => 20);
    //$get = $_GET;

    $limit = WP_VK_Utils::limit($param['pagesize']);
    $user_id = get_current_user_id();

    if (!$user_id) {
      return;
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS a.* FROM $t a WHERE pay_status=1 ";
    $sql .=  $db->prepare(" AND a.uid=%d", $user_id);
    $sql .= " ORDER BY  a.id DESC " . $limit;
    //echo $sql;

    $list = $db->get_results($sql);

    $param['total'] = $db->get_var("SELECT FOUND_ROWS()");
    $pages = WP_VK_Utils::pages($param);

    include __DIR__ . '/my_order.tpl.php';
  }

  /**
   * order list
   */
  public static function get_order_list()
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html(__('You do not have sufficient permissions to access this page.')));
    }

    // global $wpdb;
      $db = self::db();
    $t = $db->prefix . 'vk_orders';

    $pagesize = 10;
    //$get = $_POST;

    $num = absint(self::param('num', 30));
    if (!$num) {
      $num = 30;
    }
    $page = absint(self::param('page',1));
    if (!$page) {
      $page = 1;
    }
    $offset = ($page - 1) * $num;
    /*if (isset($get['num']) && $get['num']) {
            $pagesize = intval($get['num']);
	    }
        //$param = ['pagesize'=>max($pagesize,10)];

	    if(!isset($_GET['paged']) && isset($_POST['page'])){
	        $_GET['paged'] = $_POST['page'];
        }*/

    $limit = "LIMIT $offset,$num"; //
    //WP_VK_Utils::limit($param['pagesize']);

    /*$page = 1;
	    $num = intval($get['num']);
	    if(isset($get['page'])){
		    $page = absint($get['page']);
	    }

	    $offset = max(0,($page-1) * $num);
	    $limit = $offset.','.$num;*/

    $sql = "SELECT SQL_CALC_FOUND_ROWS b.user_login,b.user_email,b.display_name,a.*
		FROM $t a LEFT JOIN $db->users b ON a.uid=b.id WHERE 1=1  ";


    $status = absint(self::param('status', 2));
    $pid = absint(self::param('pid', 0));
    $uid = absint(self::param('uid', 0));
    $pay_type = absint(self::param('pay_type', 0));
    $order_uid = absint(self::param('order_uid', 0));
    $fromdate = sanitize_text_field(self::param('fromdate', ''));
    $todate = sanitize_text_field(self::param('todate', ''));
    $search = sanitize_text_field(self::param('q', ''));
    $title = sanitize_text_field(self::param('title', ''));
    $orderby = sanitize_text_field(self::param('orderby', ''));
    $order = sanitize_text_field(self::param('order', ''));

    if ($pid) {
      $sql .= $db->prepare(" AND a.pid=%d",$pid);
    }
    if ($status === 1) { //待支付
        $sql .= ' AND pay_status=0';
    } else if ($status === 2) { //已支付
        $sql .= ' AND pay_status=1';
    }
    if ($uid) {
      $sql .=  $db->prepare(' AND a.uid=%d', $uid);
    }

    if ($pay_type) {
        $sql .=  $db->prepare(' AND a.pay_type=%d', $pay_type);
    }
      if ($order_uid === 1) {
          $sql .= ' AND a.uid>0';
      } else if ($order_uid === 2) {
          $sql .= ' AND a.uid=0';
      }


    if ($fromdate) {
      $sql .= $db->prepare(" AND DATE_FORMAT(a.created,'%%Y%%m%%d')>=%d", str_replace('-', '', $fromdate));
    }
    if ($todate) {
      $sql .= $db->prepare(" AND DATE_FORMAT(a.created,'%%Y%%m%%d')<=%d", str_replace('-', '', $todate));
    }
    if ($search) {
        $search = esc_sql($search);
      $sql .= $db->prepare(" AND concat_ws('',a.order_no,b.user_login,user_email,a.verify_code) like %s", '%' . $search . '%');
    }
    if ($title) {
      $title = esc_sql($title);
      $sql .= $db->prepare(" AND concat_ws('',a.name) like %s", '%' . $title . '%');
    }

    $sort = 'a.id';
    if ($orderby && in_array($orderby, array('created', 'money'))) {
      $sort = 'a.' . $orderby;
    }
    if ($order == 'asc') {
      $sort .= ' ASC';
    } else {
      $sort .= ' DESC';
    }

    $sql .= " ORDER BY  $sort " . $limit;

    $list = $db->get_results($sql);
    $total = $db->get_var("SELECT FOUND_ROWS()");

    foreach ($list as $item) {
      $item->post_url =  get_permalink($item->pid);
    }


    $ret = array('code' => 0, 'desc' => 'success');
    $ret['sql'] = $sql;
    $ret['total'] = intval($total);
    $ret['num'] = $pagesize;
    $ret['data'] = $list;

    header('content-type:text/json;charset=utf-8');
    echo wp_json_encode($ret);
  }


  public static function guest_pay($oid, $post_id, $code)
  {
    // global $wpdb;

      $db = self::db();
    $t = $db->prefix . 'vk_orders';

    $sql = $db->prepare("SELECT * FROM $t WHERE id=%d AND pay_status=1 AND pid=%d ", $oid, $post_id);
    if ($code) {
      $sql .= $db->prepare(" AND verify_code=%s", $code);
    }

    $row = $db->get_row($sql);

    return $row;
  }

  public static function verify_order($post_id, $code)
  {
    // global $wpdb;

    if (!$code) {
      return 0;
    }

    $db = self::db();

    $t = $db->prefix . 'vk_orders';
    $row = $db->get_row($db->prepare("SELECT * FROM $t WHERE pay_status=1 AND pid=%d AND verify_code=%s ", $post_id, $code));

    return $row;
  }

  public static function is_buy($post_id, $uid = 0)
  {
    $opt = WP_VK::cnf();
    $is_buy = 0;
    do {
      if (isset($opt['guest_pay']) && $opt['guest_pay']) {
        $guest = WP_VK::get_guest();
        if ($guest && in_array($post_id, $guest)) {
          $is_buy = 1;
        }
      }
      if ($is_buy) {
        break;
      }
      $is_buy = self::user_is_buy($post_id, $uid);
    } while (0);

    return $is_buy;
  }
  public static function user_is_buy($post_id, $uid = 0)
  {

    // global $wpdb;



    if (!$uid) $uid = get_current_user_id();

    if (!$uid) {
      return 0;
    }
    $db = self::db();

    $t = $db->prefix . 'vk_orders';

    $row = $db->get_row($db->prepare("SELECT * FROM $t WHERE uid=%d AND pid=%d and pay_status=1", $uid, $post_id));

    return $row;
  }

  public static function my_order()
  {

    // global $wpdb;
      $db = self::db();

    $t = $db->prefix . 'vk_orders';

    $uid = get_current_user_id();
    if (!$uid) {
      return array('data' => array(), 'total' => 0);
    }

    $pagesize = absint(self::param('num', 20));
    if (!$pagesize) {
      $pagesize = 20;
    }

    $limit = WP_VK_Utils::limit($pagesize);

    $sql = $db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM $t WHERE uid=%d AND u_del=0 ", $uid);

    $type = absint(self::param('type', 0));

      if ($type === 2) {
        $sql .= " AND pay_status=0";
      } else if ($type === 3) {
        $sql .= " AND pay_status=1";
      } else {
        $sql .= " AND pay_status=1";
        }

    $list = $db->get_results($sql . " ORDER BY id DESC " . $limit);
    $total = $db->get_var("SELECT FOUND_ROWS()");

    foreach ($list as $r) {
      $r->detail_url = get_permalink($r->pid);
    }

    return array('data' => $list, 'total' => $total);
  }

  public static function delete_my_order($oid)
  {
    //global $wpdb;
    $db = self::db();
    $t = $db->prefix . 'vk_orders';

    $uid = get_current_user_id();
    if (!$uid) {
      return false;
    }

    $ret = $db->query($db->prepare("UPDATE $t SET u_del=1 WHERE uid=%d AND id=%d", $uid, $oid));


    return $ret;
  }



  public static function qrcode_pay()
  {
    $conf = self::pay_conf();

    if (isset($conf['wxpay']) && $conf['type'] == 10) {
      return true;
    }

    if ((isset($conf['wxpay']) || isset($conf['alipay'])) && $conf['type'] == 3) {
      return true;
    }
    return false;
  }


  public static function pay_conf()
  {

    static $conf = array();

    if (!empty($conf)) {
      return $conf;
    }
    $conf = WP_VK_Pay::order_pay_cnf();
    return $conf;
  }


  public static function post($post_id)
  {
    // global $wpdb;

    $db = self::db();

    $sql = "SELECT a.*,IFNULL(b.meta_id,0) meta_id,b.meta_value as price 
                    FROM $db->posts a LEFT JOIN $db->postmeta b ON a.ID=b.post_id AND b.meta_key='vk_price' 
                    WHERE a.ID=%d  AND post_status='publish'";
    $row = $db->get_row($db->prepare($sql, $post_id));
    if (!$row->meta_id) {
      $default_price = WP_VK::cnf('default_price');
      if ($default_price) {
        $row->price = $default_price;
        update_post_meta($post_id, 'vk_price', $default_price);
      } else {
        $row->price = 0;
      }
    }

    return $row;
  }


  public static function verify()
  {
    //获取商品信息
      $id = absint(self::param('id','0', 'g'));
    if (!$id) {
      wp_redirect(home_url());
      exit();
    }

    $post = self::post($id);

    if (!$post) {
      wp_redirect(home_url());
      exit();
    }

    return $post;
  }


  public static function info($value, $field = 'id')
  {
    // global $wpdb;
    $db = self::db();
    $t = $db->prefix . 'vk_orders';
    if ($field == 'id') {
      $format = '%d';
    } else if ($field == 'order_no') {
      $format = '%s';
    } else {
      return false;
    }

    $info = $db->get_row($db->prepare("SELECT *,IF(expired>NOW(),1,0) valid FROM $t WHERE $field=$format", $value));
    return $info;
  }

  public static function pay_info($oid)
  {
    // global $wpdb;
    $db = self::db();
    $t = $db->prefix . 'vk_orders';

    $info = $db->get_row($db->prepare("SELECT *,IF(expired>NOW(),1,0) valid FROM $t WHERE id=%d", $oid));
    return $info;
  }

  public static function set_pay_type($oid, $type)
  {
    // global $wpdb;
    $db = self::db();
    $t = $db->prefix . 'vk_orders';

      $db->query($db->prepare("UPDATE $t SET pay_type=%d WHERE id=%d", $type, $oid));
  }

  public static function post_price($post_id)
  {
    $price = get_post_meta($post_id, 'vk_price', true);
    if (!$price) {
      $price = 0;
    }
    $price = floatval($price);
    $price = abs($price);
    return $price;
  }

  public static function order($post, $uid = 0, $verify_code = null)
  {

    // global $wpdb;


    $price = abs(floatval($post->price));


    $pay_type = 2;
    $pay_type_def = array('alipay' => 1, 'weixin' => 2);
    $pay_type_q = self::param('pay_type', '', 'g');
    if ($pay_type_q && isset($pay_type_def[$pay_type_q])) {
      $pay_type = $pay_type_def[$pay_type_q];
    }
    $order_no = current_time('ymdHi') . wp_rand(1000, 9999);
    //生成订单
    $d = array(
      'order_no' => $order_no,
      'uid' => $uid,
      'pid' => $post->ID,
      'status' => 0,
      'pay_status' => 0,
      'created' => current_time('mysql'),
      'expired' => gmdate('Y-m-d H:i:s', strtotime('+7 day', strtotime(current_time('mysql')))),
      //'pay_at'=>'0000-00-00 00:00:00',
      'price' => $price,
      'money' => $price,
      'pay_type' => $pay_type,
      'name' => $post->post_title
    );
    if ($verify_code) {
      $d['verify_code'] = trim($verify_code);
    }
    $db = self::db();

    if ($db->insert($db->prefix . 'vk_orders', $d)) {
      return $db->insert_id;
    }
    return 0;
  }



  public static function txt_log($msg)
  {

    if (!self::$debug) {
      return;
    }

    if (func_num_args() > 1) {
      $msg = wp_json_encode(func_get_args());
    } else if (is_array($msg)) {
      $msg = wp_json_encode($msg);
    }

    error_log('[' . current_time('mysql') . ']' . $msg, 3, __DIR__ . '/log.txt');
  }

  /**
   *
   * @param $trade table wp_vk_trade_log{`id`, `oid`, `order_no`, `trade_no`, `trade_status`, `type`, `pay_type`, `buyer_id`, `total_amount`, `gmt_payment`, `created`, `memo`}
   * @return bool;
   */
  public static function pay_fail($trade)
  {
    // global $wpdb;
    $db = self::db();
    $t = $db->prefix . 'vk_orders';
    self::txt_log('pay_fail,' . $trade->order_no);
    $t = $db->prefix . 'vk_orders';

    $order = $db->get_row($db->prepare("select * from $t where order_no = %s", $trade->order_no));

    if (!$order || !$order->id) {
      self::txt_log('can not find order ' . $trade->order_no);
      return false;
    }

    if ($order->pay_status) {
      self::txt_log('order[' . $order->order_no . '] already pay');
      return true;
    }

    $up = array('status' => 10, 'memo' => $trade->memo);
      $db->update($t, $up, array('id' => $order->id));

    foreach ($up as $k => $v) {
      $order->$k = $v;
    }

    do_action('vk_order_fail', $order);

    return true;
  }


  /**
   *
   * @param $trade table wp_vk_trade_log{`id`, `oid`, `order_no`, `trade_no`, `trade_status`, `type`, `pay_type`, `buyer_id`, `total_amount`, `gmt_payment`, `created`, `memo`}
   * @return bool;
   */
  public static function pay_success($trade)
  {

    // global $wpdb;

    $db = self::db();
    self::txt_log('pay_success,' . $trade->order_no);
    $t = $db->prefix . 'vk_orders';

    $order = $db->get_row($db->prepare("select * from $t where order_no = %s", $trade->order_no));

    if (!$order || !$order->id) {
      self::txt_log('can not find order ' . $trade->order_no);
      return false;
    }

    if ($order->pay_status) {
      self::txt_log('order[' . $order->order_no . '] already pay');
      return true;
    }

    if (!($order->money > $trade->total_amount) && !$order->pay_status) {


      self::txt_log('pay order  ' . $trade->order_no . ',' . $trade->total_amount . ',' . $trade->trade_no);
      $up = array(
        'status' => 10,
        'pay_status' => 1,
        'trade_sn' => $trade->trade_no,
        'pay_at' => current_time('mysql'),
        'trade_money' => $trade->total_amount
      );

      $ret = $db->update($t, $up, array('id' => $order->id));

      if (!$ret) {
        $up['id'] = $order->id;
        self::txt_log('update order error.[' . wp_json_encode($up) . ']');
        return false;
      }

      foreach ($up as $k => $v) {
        $order->$k = $v;
      }

      clean_post_cache($order->pid);

      do_action('vk_order_success', $order);
      return true;
    }
    self::txt_log('can not pay order ' . $trade->total_amount . ',' . $order->money);
    return false;
  }

  public static function pay_free($oid)
  {

    // global $wpdb;

    $db = self::db();
    $t = $db->prefix . 'vk_orders';

    $up = array(
      'status' => 10,
      'pay_status' => 1,
      'trade_sn' => '',
      'pay_at' => current_time('mysql'),
      'trade_money' => 0,
    );

      $db->update($t, $up, array('id' => $oid));
    $order = $db->get_row($db->prepare("select * from $t where id = %d", $oid));
    do_action('vk_order_success', $order);
  }


  /**
   * table wp_vk_trade_log{`id`, `oid`, `order_no`, `trade_no`, `trade_status`, `type`, `pay_type`, `buyer_id`, `total_amount`, `gmt_payment`, `created`, `memo`}
   * @param $trade
   */
  public static function pay_finish($trade)
  {
  }

  public static function isWbDown()
  {
    return class_exists('DLIP_DownLoadAdmin') || class_exists('WB_MagicPost_Download');
  }

  public static function itemUpdatePrice()
  {
    if (!current_user_can('manage_options')) {
      return array('code' => 0, 'desc' => '403');
    }

    // global $wpdb;

    do {
        $id = sanitize_text_field(self::param('id', ''));
      if (!$id) {
        break;
      }
      $dlp = 0;
      if (self::isWbDown()) {
        $dlp = 1;
      }
      $price = round(floatval(self::param('price', 0)),2);

      //$price = isset($_POST['price']) ? floatval(round(floatval($_POST['price']), 2)) : 0;
      $id_list = wp_parse_id_list($id);
      foreach ($id_list as $post_id) {
        update_post_meta($post_id, 'vk_price', $price);
        if ($dlp) {
          update_post_meta($post_id, 'wb_down_price', $price);
        }
      }
    } while (0);

    return ['code' => 0, 'desc' => 'success'];
  }

  public static function itemCancelPay()
  {
    if (!current_user_can('manage_options')) {
      return array('code' => 0, 'desc' => '403');
    }
    $dlp = 0;
    if (self::isWbDown()) {
      $dlp = 1;
    }

    // global $wpdb;

    do {
        $id = sanitize_text_field(self::param('id', ''));
      //$id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : 0;
      if (!$id) {
        break;
      }
      $id_list = wp_parse_id_list($id);
      foreach ($id_list as $post_id) {
        update_post_meta($post_id, 'vk_price', 0);
        $post = get_post($post_id);
        if ($dlp) {
          $mode = get_post_meta($post_id, 'wb_dl_mode', 1);
          if ($mode == '2') {
            update_post_meta($post_id, 'wb_dl_mode', '0');
            update_post_meta($id, 'wb_down_price', 0);
          }
        }
        $up = [];
        if (preg_match('#<!--以下为付费内容-->#i', $post->post_content)) {
          $up['post_ID'] = $post->ID;
          $up['post_content'] = str_replace('<!--以下为付费内容-->', '', $post->post_content);
        }
        if (preg_match('#\[/?vk-content\]#i', $post->post_content)) {
          $up['post_ID'] = $post->ID;
          $up['post_content'] = str_replace(['[vk-content]', '[/vk-content]'], '', $post->post_content);
        }
        if ($up) {
          $up['post_author'] = $post->post_author;
          edit_post($up);
        }
      }
    } while (0);

    return ['code' => 0, 'desc' => 'success'];
  }


  public static function get_sale_items()
  {

    if (!current_user_can('manage_options')) {
      return array('code' => 0, 'data' => [], 'desc' => '403');
    }

    // global $wpdb;

    $dlp = 0;
    if (self::isWbDown()) {
      $dlp = 1;
    }
    //

    //$get = $_POST;

      $pagesize = absint(self::param('num', 10));
    if(!$pagesize){
        $pagesize = 10;
    }

    $param = ['pagesize' => max($pagesize, 10)];

    if (!isset($_GET['paged']) && isset($_POST['page'])) {
      $_GET['paged'] = $_POST['page'];
    }
    $db = self::db();
    $t = $db->prefix . 'vk_orders';
    $sale_join = " LEFT JOIN (SELECT COUNT(1) sale_num ,pid AS post_id FROM $t WHERE pay_status=1 GROUP BY pid) AS s ON a.ID=s.post_id";

    $limit = WP_VK_Utils::limit($param['pagesize']);

    $dlp_where = $dlp_table = '';
    if ($dlp) {
      $dlp_field = ",IF(c.meta_id > 0,2,1) AS vk_type";
      $dlp_table = "LEFT JOIN $db->postmeta c ON b.post_id=c.post_id AND c.meta_key='wb_dl_mode' AND c.meta_value='2'";
      $dlp_where = ' OR c.meta_id IS NOT NULL';
    } else {
      $dlp_field = ",1 AS vk_type";
    }
    $sql = "SELECT SQL_CALC_FOUND_ROWS a.*,IFNULL(b.meta_id,0) meta_id,b.meta_value as price,IFNULL(s.sale_num,0) AS sale_num $dlp_field
                    FROM $db->posts a $sale_join ,$db->postmeta b $dlp_table 
                    WHERE a.ID=b.post_id AND b.meta_key='vk_price' AND b.meta_value<>''  AND post_status='publish'";


    //wb_dl_mode

      $status = absint(self::param('status', 0 ));
      if ($dlp) {
          if ($status === 1) { //内容付费
              $sql .= ' AND c.meta_id IS NULL';
          } else if ($status === 2) { //下载付费
              $sql .= ' AND c.meta_id > 0';
          }
      } else {
          if ($status === 1) { //内容付费
              //$sql .= ' AND pay_status=0';
          } else if ($status === 2) { //下载付费
              $sql .= ' AND 1=0';
          }
      }


      $fromdate = sanitize_text_field(self::param('fromdate'));
      $todate = sanitize_text_field(self::param('todate'));
      $search = sanitize_text_field(self::param('q'));
      $orderby = sanitize_text_field(self::param('orderby'));
      $order = sanitize_text_field(self::param('order'));


    if ($fromdate) {
      $sql .= $db->prepare(" AND a.post_date>=%s", $fromdate . ' 00:00:00');
    }
    if ($todate) {
      $sql .= $db->prepare(" AND a.post_date<=%s", $todate . ' 23:59:59');
    }


    if ($search) {
        $search = esc_sql($search);
      $sql .= $db->prepare(" AND concat_ws('',a.post_title) like %s", '%' . $search . '%');
    }

    $sql .= " AND (a.post_content REGEXP '\\\\[/?vk-content\\\\]' OR a.post_content REGEXP '<!--以下为付费内容-->' $dlp_where)";


    $sort = 'a.post_date';
    if ($orderby) {
      //'price','sale_num','post_date'
      if ($orderby == 'price') {
        $sort = '0 + b.meta_value';
      } else if ($orderby == 'sale_num') {
        $sort = '0 + s.sale_num';
      } else if ($orderby == 'post_date') {
        $sort = 'a.post_date';
      }
      //$sort = 'a.'.$orderby;
    }
    if ($order == 'asc') {
      $sort .= ' ASC';
    } else {
      $sort .= ' DESC';
    }

    $sql .= " ORDER BY  $sort " . $limit;

    $list = $db->get_results($sql);
    $total = $db->get_var("SELECT FOUND_ROWS()");

    foreach ($list as $item) {
      $item->post_url =  get_permalink($item);
      $item->edit_url =  get_edit_post_link($item, 'edit');
    }


    $ret = array('code' => 0, 'desc' => 'success');

    $ret['total'] = intval($total);
    $ret['num'] = $pagesize;
    $ret['data'] = $list;
    $ret['dlp'] = $dlp;
    // $ret['sql'] = $sql;

    return $ret;
  }
}
