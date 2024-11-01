<?php

/**
 * Author: wbolt team
 * Author URI: https://www.wbolt.com
 */

class WP_VK_Base
{
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

    public static function param($key, $default = '', $type = 'p'){
        if('p' === $type){
            if(isset($_POST[$key])){
                return $_POST[$key];
            }
            return $default;
        } else if ('g' === $type){
            if(isset($_GET[$key])){
                return $_GET[$key];
            }
            return $default;
        }
        if(isset($_POST[$key])){
            return $_POST[$key];
        }
        if(isset($_GET[$key])){
            return $_GET[$key];
        }
        return $default;
    }

    public static function ajax_resp($ret)
    {
        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
    }
}

class WP_VK extends WP_VK_Base
{
  public static $module = array('order');
  public static $name = 'wp_vk_pack';
  public static $option_name = 'wp_vk_option';
  public static $db_ver = 11;
  public static $cnf_fields = array();
  public static $vk_menu_items = array(
    array(
      'name' => '资源管理',
      'slug' => 'items'
    ),
    array(
      'name' => '订单管理',
      'slug' => 'order'
    ),
    array(
      'name' => '插件设置',
      'slug' => 'setting'
    ),
    array(
      'name' => '支付设置',
      'slug' => 'pay-setting'
    )
  );


  public static function init()
  {
    $is_pro = get_option('wp_vk_ver', 0);

    self::init_admin();;

    if (!is_admin()) {
      add_action('wp_enqueue_scripts', array(__CLASS__, 'wp_front_head'), 50);
      add_action('wp_footer', array(__CLASS__, 'wp_footer'));
      if ($is_pro) {
        add_filter('the_content', array(__CLASS__, 'the_content'), 40);
        add_action('init', array(__CLASS__, 'wp_init'));
        add_action('parse_request', array(__CLASS__, 'parse_request'));
        add_action('template_redirect', array(__CLASS__, 'template_redirect'));

        add_filter('wb_wpvk_html', array(__CLASS__, 'sc_vk_content'));
      }
      add_shortcode('vk-content', array(__CLASS__, 'sc_vk_content'));
    }

    if ($is_pro) {
      WP_VK_Order::init();

      add_action('wp_ajax_vk_pay', array(__CLASS__, 'wp_ajax_vk_pay'));
      add_action('wp_ajax_nopriv_vk_pay', array(__CLASS__, 'wp_ajax_vk_pay'));
    }
    self::init_member();
  }

  public static function init_admin()
  {

    if (!is_admin()) {
      return;
    }
    $is_pro = get_option('wp_vk_ver', 0);
    self::upgrade();
    //            add_filter('use_block_editor_for_post_type','__return_false',10,2);

    if (!(defined('WB_THEMES_UUID') && WB_THEMES_UUID == 'eyes')) {
      add_filter('plugin_action_links', array(__CLASS__, 'actionLinks'), 10, 2);
      add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);
      register_activation_hook(WP_VK_BASE_FILE, array(__CLASS__, 'activation'));
      register_deactivation_hook(WP_VK_BASE_FILE, array(__CLASS__, 'deactivation'));
      add_action('admin_menu', array(__CLASS__, 'admin_menu_handler'));
      add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'), 1);
      add_action('admin_notices', array(__CLASS__, 'admin_notices'));
    }


    add_action('wp_ajax_vk_admin', array(__CLASS__, 'wp_ajax_vk_admin'));

    if (!$is_pro) {
      return;
    }

    add_action('admin_head-post.php', array(__CLASS__, 'admin_post_handle'));
    add_action('admin_head-post-new.php', array(__CLASS__, 'admin_post_handle'));

    add_action('add_meta_boxes', array(__CLASS__, 'add_meta_box'), 1);
    add_action('save_post', array(__CLASS__, 'save_meta_box'));
  }

  /**
   * 会员中心
   */
  public static function init_member()
  {
    if (!class_exists('WBMember')) {
      require_once WP_VK_PATH . '/wbm/wbm.php';
      WBMember::init();
    }

    // 菜单
    add_filter('wbm_menu', function ($menu) {
      $menu['vk'] = [
        'url' => home_url('?wbp=member&slug=vk'),
        'name' => '付费内容',
        'sort' => 20,
      ];
      return $menu;
    });

    // head引入js、css
    add_action('wbm_head_vk', function () {
      $wbm_url = WBMember::wbm_url();
      wp_enqueue_script('wbm-vk', $wbm_url . '/wbm/assets/js/vk.js', [], true);
      wp_enqueue_style('wbm-vk', $wbm_url . '/wbm/assets/css/vk.css');
    });


    add_action('wbm_content_vk', function () {
      echo '<div id="wbm-vk"></div>';
    });
  }

  /**
   * 文章编辑页判断是否启用古腾堡选择功能形式
   */
  public static function admin_post_handle()
  {

    if (!self::is_active_gutenberg_editor()) {
      self::admin_post_head();

      add_filter('mce_external_plugins', array(__CLASS__, 'add_plugin'));
      add_filter('mce_buttons', array(__CLASS__, 'register_button'));
    }
  }


  /**
   * 是否启用古腾堡
   * @return bool
   */
  public static function is_active_gutenberg_editor()
  {
    if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
      return true;
    }

    global $current_screen;
    $current_screen = get_current_screen();
    if (method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
      return true;
    }
    return false;
  }

  /**
   * 获取所有自定义文章类型
   *
   * @return array WP_Post_Type
   */
  public static function all_post_types()
  {
    $args = array(
      'public'   => true,
      '_builtin' => false,
    );
    $output = 'objects';
    $operator = 'and'; // 'and' or 'or'

    $post_types = get_post_types($args, $output, $operator);

    $post_types_array = array(
      array(
        'name' => 'post',
        'label' => '文章'
      ),
      array(
        'name' => 'page',
        'label' => '页面'
      )
    );

    foreach ($post_types  as $post_type) {
      $post_types_array[] = array(
        'name' => $post_type->name,
        'label' => $post_type->label
      );
    }

    return $post_types_array;
  }

  public static function wp_ajax_vk_pay()
  {

    $ret = array('code' => 1, 'desc' => 'error', 'data' => '');

    $op = trim(self::param('op'));
    //isset($_POST['op']) && $_POST['op'] ? trim($_POST['op']) : '';

    if ($op == 'check') {

      do {
        if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_pay')) {

          $ret['code'] = 9;
          $ret['desc'] = '非法操作';
          break;
        }

        $oid = intval(self::param('oid', 0));
        if (!$oid) {
          $ret['code'] = 9;
          $ret['desc'] = '错误请求';
          break;
        }
        /*if(!$uid){
                    $ret['code'] = 10;
                    $ret['desc'] = '未登录';
                    break;
                }*/
        $order_info = WP_VK_Order::pay_info($oid);
        if (!$order_info) { // || $order_info->uid!=$uid
          $ret['code'] = 9;
          $ret['desc'] = '错误请求';
          break;
        }
        if ($order_info->pay_status == 1) {

          $ret['code'] = 0;
          $ret['desc'] = 'success';
          break;
        }
      } while (false);
      header('content-type:text/json;charset=utf-8');
      echo wp_json_encode($ret);
      exit();
    } else if ($op == 'verify') {
      do {
        $ret = array('code' => 1, 'desc' => '验证失败。请稍后重试，或联系我们。');

        if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_pay')) {
          $ret['code'] = 1;
          $ret['desc'] = '非法操作';
          break;
        }

        $code = trim(self::param('vcode'));
        $post_id = intval(self::param('post_id', 0));
        if (!$code) {
          $ret['desc'] = '验证失败。识别码不能为空。';
          break;
        }
        if (strlen($code) < 6) {
          $ret['desc'] = '验证失败。识别码不少于6位。';
          break;
        }
        if (!$post_id) {
          break;
        }
        if (!WP_VK_Order::verify_order($post_id, $code)) {
          break;
        }
        $post_ids = self::get_guest();
        if (!in_array($post_id, $post_ids)) {
          //array_push($post_ids,$post_id);
          array_unshift($post_ids, $post_id);
          self::set_guest($post_ids);
        }
        $ret['desc'] = 'success';
        $ret['code'] = 0;
      } while (0);

      header('content-type:text/json;charset=utf-8');
      echo wp_json_encode($ret);
      exit();
    }
  }

  public static function admin_notices()
  {

      $page = sanitize_text_field(self::param('page', '', 'g'));
    if ($page) {

      $page = str_replace('vk_', '', $page);
      if (in_array($page, self::$module) && !get_option('wp_vk_ver', 0)) {

        printf('<div class="error"><p>%s</p></div>', 'Wordpress付费内容插件未激活，此功能无法正常使用。');
      }
    }
  }

  public static function the_content($content)
  {

    /**
     * 启动付费下载，付费阅读模式失效
     */
    if (self::active_pay_download()) {
      return $content;
    }
    //        print_r([$content]);exit();
    if (preg_match('#<!--以下为付费内容-->#s', $content, $match)) {

      //print_r($match);

      $replace = preg_replace('#<!--以下为付费内容-->#s', '', $content);

      $post_id = get_the_ID();

      $price = WP_VK_Order::post_price($post_id);
      if (!$price) {
        return $replace;
      }

      $post = get_post();

      //            if($post->post_author == get_current_user_id()){
      //                return $replace;
      //            }

      $opt = self::cnf();
      //print_r($opt);
      /*$is_buy = 0;
            do{
                if($opt['guest_pay']){
                    $guest = self::get_guest();
                    //print_r($guest);
                    if($guest && in_array($post_id,$guest)){
                        $is_buy = 1;
                    }
                }
                if($is_buy){
                    break;
                }


            }while(0);*/
      $is_buy = WP_VK_Order::is_buy($post_id);

      if ($is_buy) {
        return $replace;
      }






      if (!defined('HAS_VK_CONTENT')) {
        define('HAS_VK_CONTENT', 1);
      }

      $a = explode($match[0], $content, 2);
      $percent = round(strlen($a[1]) / strlen($content) * 100);
      $icon_color = str_replace('#', '%23', $opt['theme_icon_color']);
      $tips_tpl = str_replace('%price%', ' &yen;' . $price . ' ', $opt['tips_tpl']);
      $tips_tpl = str_replace('%percent%', $percent . '%', $tips_tpl);


      $pay_conf = WP_VK_Order::pay_conf();

      $pay = [];
      if (isset($pay_conf['wxpay']) && $pay_conf['wxpay']) {
        $pay[] = 'wx';
      }
      if (isset($pay_conf['alipay']) && $pay_conf['alipay']) {
        $pay[] = 'ali';
      }

      $user_id = get_current_user_id();
      $post_url = get_permalink($post);

      ob_start();
      include WP_VK_PATH . '/tpl/front_tips_content.php';
      $btn = ob_get_clean();

      //post作者
      if ($post->post_author == get_current_user_id()) {
        return $a[0] . $btn . '<div class="wb-vk-cont" style="display:none;">' . $a[1] . '</div>';
      }
      return $a[0] . $btn;
    }

    return $content;
  }

  public static function activation()
  {

    self::setup_page();

    self::setup_db();
  }

  public static function deactivation()
  {

    //self::remove_page();
    //self::remove_table();
  }

  public static function add_plugin($plugin_array)
  {
    $plugin_array['vk_mark'] = WP_VK_URL . 'assets/js/vk.js';
    return $plugin_array;
  }

  public static function register_button($buttons)
  {
    array_push($buttons, "|", "vk_mark");
    return $buttons;
  }


  public static function template_redirect()
  {
    if (is_singular()) {
      do {
        if (!isset($_COOKIE['vk_guest_code'])) {
          break;
        }
        $cookie = $_COOKIE['vk_guest_code'];
        if (!$cookie) {
          //print_r(['code-1']);exit();
          self::clear_guest_code();

          break;
        }
        $data = self::decode($cookie);
        if (!$data) {
          //print_r(['code-2']);exit();
          self::clear_guest_code();
          break;
        }
        $cookie_elements = explode(',', $data, 4);
        list($oid, $post_id, $expired, $code) = $cookie_elements;

        if ($expired < current_time('U')) {
          //print_r(['code-3']);exit();
          self::clear_guest_code();
          break;
        }

        $r = WP_VK_Order::guest_pay($oid, $post_id, $code);

        if (!$r) {
          //self::clear_guest_code();
          break;
        }
        $guest = self::get_guest();
        array_unshift($guest, $post_id);
        self::set_guest($guest);
        self::clear_guest_code();
      } while (0);
    }
  }

  public static function get_request_page($wp)
  {
    if (isset($wp->query_vars['pagename']) && $wp->query_vars['pagename']) {
      return $wp->query_vars['pagename'];
    }
    if (isset($wp->query_vars['page_id']) && $wp->query_vars['page_id']) {
      $post = get_post($wp->query_vars['page_id']);
      if (!$post) {
        return false;
      }
      return $post->post_name;
    }
    return false;
  }

  public static function wp_init()
  {

    $page = sanitize_text_field(self::param('wp_vk', '', 'g'));
    if ($page == 'pay') {
      self::pay();
    }
  }

  public static function parse_request($wp)
  {
    $page = self::get_request_page($wp);

    if (!$page) {
      return;
    }
    if ($page == 'vk_user_order') {
      self::user_order();
    }
  }

  public static function get_guest()
  {
    $guest = isset($_COOKIE['vk_guest']) ? trim($_COOKIE['vk_guest']) : '';
    if (!$guest) {
      return array();
    }
    $data = self::decode($guest);
    if (!$data) {
      return array();
    }
    $post_ids = explode(',', $data);
    return $post_ids;
  }

  public static function decode($cookie)
  {
    $cookie_elements = explode('|', $cookie);
    list($content, $expiration, $token, $hmac) = $cookie_elements;
    if (defined('AUTH_KEY')) {
      $serv_token = md5(AUTH_KEY);
    } else {
      $serv_token = md5(get_option('admin_email'));
    }
    $scheme = 'vk';

    $pass_frag = substr($serv_token, 8, 4);

    $key = wp_hash($content . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme);

    // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
    $algo = function_exists('hash') ? 'sha256' : 'sha1';
    $hash = hash_hmac($algo, $content . '|' . $expiration . '|' . $token, $key);

    if (!hash_equals($hash, $hmac)) {
      return false;
    }
    return $content;
  }
  public static function encode($content)
  {
    if (defined('AUTH_KEY')) {
      $token = md5(AUTH_KEY);
    } else {
      $token = md5(get_option('admin_email'));
    }
    $pass_frag = substr($token, 8, 4);
    $expiration = current_time('U') + WEEK_IN_SECONDS;
    $scheme = 'vk';

    $key = wp_hash($content . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme);

    // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
    $algo = function_exists('hash') ? 'sha256' : 'sha1';
    $hash = hash_hmac($algo, $content . '|' . $expiration . '|' . $token, $key);

    $cookie = $content . '|' . $expiration . '|' . $token . '|' . $hash;

    return $cookie;
  }

  public static function set_guest($guest)
  {
    $guest = array_slice($guest, 0, 300);
    $content = implode(',', $guest);
    $cookie = self::encode($content);
    $expired = current_time('U') + WEEK_IN_SECONDS;
    setcookie('vk_guest', $cookie, $expired, '/');
  }

  public static function clear_guest_code()
  {
    setcookie('vk_guest_code', null, -time(), '/');
  }

  public function line()
  {
    //guest buy item [cookie, session valid]

    //
  }

  public static function user_admin_order()
  {
  }


  public static function set_up()
  {
    self::setup_page();
    self::setup_db();
  }

  public static function upgrade()
  {
    if (get_option('wp_vk_db_ver', 0) == '10') {
      self::upgrade11();
    }
  }
  public static function upgrade11()
  {
    // global $wpdb;
    $db = self::db();
    update_option('wp_vk_db_ver', 11, false);
    $t = $db->prefix . 'vk_orders';
    $sql = $db->get_var('SHOW CREATE TABLE `' . $t . '`', 1);

    if (preg_match('#`verify_code`#is', $sql)) {
      return;
    }
    $db->query("ALTER TABLE `$t` ADD `verify_code` VARCHAR(32) NULL DEFAULT NULL AFTER `memo`, ADD INDEX (`verify_code`)");
  }

  public static function setup_db()
  {

    //global $wpdb;

    $db = self::db();
    $wb_tables = explode(',', 'vk_orders,vk_trade_log');

    //数据表
    $tables = $db->get_col("SHOW TABLES LIKE '" . $db->prefix . "vk_%'");


    $set_up = array();
    foreach ($wb_tables as $table) {
      if (in_array($db->prefix . $table, $tables)) {
        continue;
      }

      $set_up[] = $table;
    }

    if (empty($set_up)) {
      return;
    }

    $sql = file_get_contents(WP_VK_PATH . '/sql/init.sql');

    $charset_collate = $db->get_charset_collate();



    $sql = str_replace('`wp_vk_', '`' . $db->prefix . 'vk_', $sql);
    $sql = str_replace('ENGINE=InnoDB', $charset_collate, $sql);



    $sql_rows = explode('-- row split --', $sql);


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    foreach ($sql_rows as $row) {
      if (preg_match('#`' . $db->prefix . '(vk_.+?)`\s+\(#', $row, $match)) {
        if (in_array($match[1], $set_up)) {
          $db->query($row);
        }
      }
    }

    update_option('wp_vk_db_ver', self::$db_ver, false);
  }


  public static function wp_ajax_vk_admin()
  {
    if (!current_user_can('manage_options')) {
      exit();
    }

    $op = isset($_REQUEST['do']) ? sanitize_text_field($_REQUEST['do']) : '';

    switch ($op) {
      case 'chk_ver':
        $http = wp_remote_get('https://www.wbolt.com/wb-api/v1/themes/checkver?code=wp-vk&ver=' . WP_VK_VERSION . '&chk=1', array('sslverify' => false, 'headers' => array('referer' => home_url()),));

        if (wp_remote_retrieve_response_code($http) == 200) {
          echo esc_html(wp_remote_retrieve_body($http));
        }

        exit();
        break;

      case 'reset':
        if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_admin')) {

          echo wp_json_encode(array('code' => 1, 'data' => '非法操作'));
          exit(0);
        }
        if (!current_user_can('manage_options')) {
          echo wp_json_encode(array('code' => 1, 'data' => '没有权限'));
          exit(0);
        }
        $ver = get_option('wp_vk_ver', 0);
        if (!$ver) {
          echo wp_json_encode(array('code' => 1, 'data' => 'not verify'));
          exit(0);
        }
        delete_option('wp_vk_ver');
        delete_option('wp_vk_ver_' . $ver);

        echo wp_json_encode(array('code' => 0, 'data' => 'success'));
        exit(0);
        break;

      case 'verify':
        if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_admin')) {

          echo wp_json_encode(array('code' => 1, 'data' => '非法操作'));
          exit(0);
        }
        if (!current_user_can('manage_options')) {
          echo wp_json_encode(array('code' => 1, 'data' => '没有权限'));
          exit(0);
        }

        $param = array(
          'code' => sanitize_text_field(trim(self::param('key'))),
          'host' => sanitize_text_field(trim(self::param('host'))),
          'ver' => 'wp-vk',
        );
        $err = '';
        do {
          $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/verify', array('timeout' => 30, 'sslverify' => false, 'body' => $param, 'headers' => array('referer' => home_url()),));
          if (is_wp_error($http)) {
            $err = '校验失败，请稍后再试（错误代码001)' . $http->get_error_message();
            break;
          }

          if ($http['response']['code'] != 200) {
            $err = '校验失败，请稍后再试（错误代码001)';
            break;
          }

          $body = $http['body'];


          $data = json_decode($body, true);
          if (!$data || $data['code']) {
            $err_code = $data['data'] ? $data['data'] : '';
            switch ($err_code) {
              case 100:
              case 101:
              case 102:
              case 103:
                $err = '插件配置参数错误，联系<a href="https://www.wbolt.com/?wb=member#/contact" target="_blank">技术支持</a>（错误代码' . $err_code . '）';
                break;
              case 200:
                $err = '输入key无效，请输入正确key（错误代码200）';
                break;
              case 201:
                $err = 'key使用次数超出限制范围（错误代码201）';
                break;
              case 202:
              case 203:
              case 204:
                $err = '校验服务器异常，联系<a href="https://www.wbolt.com/?wb=member#/contact" target="_blank">技术支持</a>（错误代码' . $err_code . '）';
                break;
              default:
                $err = '发生异常错误，联系<a href="https://www.wbolt.com/?wb=member#/contact" target="_blank">技术支持</a>（错误代码' . $err_code . '）';
            }
            break;
          }
          if (!$data['data']) {
            $err = '校验失败，请稍后再试（错误代码004)';
            break;
          }
          update_option('wp_vk_ver', $data['v'], false);
          update_option('wp_vk_ver_' . $data['v'], $data['data'], false);


          echo wp_json_encode(array('code' => 0, 'data' => 'success'));
          exit(0);
        } while (false);
        echo wp_json_encode(array('code' => 1, 'data' => $err));
        exit(0);
        break;

      case 'options':
          $nonce = self::param('_ajax_nonce', '', 'g');
        if (!current_user_can('manage_options') || !wp_verify_nonce($nonce, 'wp_ajax_vk_admin')) {
          echo wp_json_encode(array('o' => '', 'err' => 'no auth'));
          exit(0);
        }

        $ver = get_option('wp_vk_ver', 0);
        $cnf = '';
        if ($ver) {
          $cnf = get_option('wp_vk_ver_' . $ver, '');
        }
        $list = array('o' => $cnf);
        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($list);
        exit();
        break;

      case 'get_setting':
        $opt = array();
        if (current_user_can('manage_options')) {
          $opt =  self::cnf();
        }
        $opt = apply_filters('get_wbm_cnf', $opt);
        $ret = array('code' => 0, 'desc' => 'success');
        $ret['data']['opt'] = $opt;
        $ret['data']['cnf'] = self::$cnf_fields;
        $ret['data']['cnf']['post_types'] = self::all_post_types();

        echo wp_json_encode($ret);
        exit();
        break;

      case 'set_setting':
        $ret = array('code' => 1, 'desc' => 'fail');
        do {
          if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_admin')) {
            break;
          }
          if (!current_user_can('manage_options')) {
            $ret = array('code' => 1, 'data' => '没有权限');
            break;
          }
          $tab = self::param('tab');
          $tab2 = implode('', ['res', 'et']);
          if ($tab === $tab2) {
            $ret = ['code' => 0, 'desc' => 'success'];
            $ver2 = implode('_', ['wp', 'vk', 'ver']);
            $ver = get_option($ver2, 0);
            if (!$ver) {
              break;
            }
            delete_option($ver2);
            delete_option($ver2 . '_' . $ver);
            break;
          }
          $opt_data = self::param('opt', []);
          $opt_data = apply_filters('set_wbm_cnf', $opt_data);

          self::set_theme_setting($opt_data);

          $ret = array('code' => 0, 'desc' => 'success');
        } while (0);


        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;

      case 'items':
        $ret = WP_VK_Order::get_sale_items();
        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;

      case 'update_item':
        $ret = array('code' => 1, 'desc' => 'fail');
        do {
          if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_admin')) {
            break;
          }
          $ret = WP_VK_Order::itemUpdatePrice();
        } while (0);

        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;
      case 'cancel_item':
        $ret = array('code' => 1, 'desc' => 'fail');
        do {
          if (!wp_verify_nonce(self::param('_ajax_nonce'), 'wp_ajax_vk_admin')) {
            break;
          }
          $ret = WP_VK_Order::itemCancelPay();
        } while (0);


        header('content-type:text/json;charset=utf-8');
        echo wp_json_encode($ret);
        exit();
        break;
    }
  }

  public static function plugin_row_meta($links, $file)
  {

    $base = plugin_basename(WP_VK_BASE_FILE);
    if ($file == $base) {
      $links[] = '<a href="https://www.wbolt.com/plugins/wp-vk/">插件主页</a>';
      $links[] = '<a href="https://www.wbolt.com/vk-plugin-documentation.html">说明文档</a>';
      $links[] = '<a href="https://www.wbolt.com/plugins/wp-vk#J_commentsSection">反馈</a>';
    }
    return $links;
  }

  public static function actionLinks($links, $file)
  {

    if ($file != plugin_basename(WP_VK_BASE_FILE))
      return $links;

    $settings_link = '<a href="' . menu_page_url('wp_vk', false) . '">设置</a>';

    array_unshift($links, $settings_link);

    return $links;
  }


  public static function default_cnf()
  {
    $cnf = array(
      'login_pay' => '1',
      'guest_pay' => '0',
      'default_price' => '',
      'switch' => '1',
      'code_type' => 'num',
      'code_min_len' => 6,
      'code_max_len' => 11,
      'tips_tpl' => '支付%price%购买本节后解锁剩余%percent%的内容',
      'theme_icon_size' => '22',
      'theme_icon_color' => '#06c',
      'theme_font_color' => '#2060CA',
      'theme_font_size' => '14',
      'theme_style' => '',
      'theme_mask_color' => '',
      'dark_mode_class' => '',
      'bind_post_types' => array('post', 'page'), //插件绑定的文章类型
    );

    return $cnf;
  }

  /**
   * 设置
   * @param null $key
   * @param null $default
   *
   * @return array|mixed|null|void
   */
  public static function cnf($key = null, $default = null)
  {
    static $_option = null;

    if (null == $_option) {
      $_option = get_option(self::$option_name);

      if (!$_option || !is_array($_option)) {
        $_option = self::default_cnf();
      }
    } else {
      $defined_cnf = self::default_cnf();
      foreach ($defined_cnf as $k => $v) {
        if (!isset($_option[$k])) {
          $_option[$k] = $v;
        }
      }
    }

    $return = null;
    do {
      if (!$key) {
        $return = $_option;
        break;
      }

      if (isset($_option[$key]) && $_option[$key]) {
        $return = $_option[$key];
        break;
      }

      $return = $default;
    } while (0);

    return apply_filters('wb_vk_get_conf', $return, $key, $default);
  }

  public static function admin_menu_handler()
  {
    global $submenu;

    add_menu_page(
      '付费内容',
      '付费内容',
      'administrator',
      'wp_vk',
      array(__CLASS__, 'vk_admin'),
      'dashicons-welcome-view-site'
    );

    foreach (self::$vk_menu_items as $item) {
      add_submenu_page(
        'wp_vk',
        $item['name'],
        $item['name'],
        'manage_options',
        'wp_vk#/' . $item['slug'],
        array(__CLASS__, 'vk_admin')
      );
    }

    unset($submenu['wp_vk'][0]);
  }

  public static function vk_admin()
  {
    wp_enqueue_media();

    echo '<div id="optionsframework-wrap"><div id="app"></div></div>';
  }

  public static function admin_enqueue_scripts($hook)
  {
    if (!preg_match('#wp_vk#i', $hook)) {
      return;
    }

    wp_register_script('wbs-inline-js', false, null, false);
    wp_enqueue_script('wbs-inline-js');

    $_ajax_nonce = wp_create_nonce('wp_ajax_vk_admin');
    $pro = get_option('wp_vk_ver', 0);

    $prompt_items = array();
    if (file_exists(__DIR__ . '/_prompt.php')) {
      include __DIR__ . '/_prompt.php';
    }

    $wb_cnf = array(
      'base_url' => admin_url(),
      'ajax_url' => admin_url('admin-ajax.php'),
      'dir_url' => WP_VK_URL,
      'wb_vue_path' => WP_VK_URL . '/options/',
      'doc_url' => 'https://www.wbolt.com/wp-vk-plugin-documentation.html',
      'pd_code' => 'wp-vk',
      'pd_title' => 'WordPress付费内容插件',
      'pd_version' => WP_VK_VERSION,
      'is_pro' => intval($pro),
      '_ajax_nonce' => $_ajax_nonce,
      'action' => array(
        'act' => 'vk_admin',
        'fetch' => 'get_setting',
        'push' => 'set_setting'
      ),
      'pay_config' => array(
        'action' => array(
          'act' => 'vk_pay_setting',
          'fetch' => 'get_pay_options',
          'push' => 'set_pay_options'
        ),
        "pay_type" => array(
          //"1"=>"个人收款码",
          "2" => "第三方接口 - 虎皮椒",
          "3" => "第三方接口 - PayJS",
          "10" => "官方接口"
        ),
      ),
      'prompt' => $prompt_items,
      'wbm_url' => home_url('?wbp=member&slug=vk')
    );
    $ocw_state = class_exists('OCW_Admin');
    $wb_cnf['ocw_state'] = $ocw_state;
    $wb_cnf['wb_plugin_list'] = admin_url('plugin-install.php?s=wbolt&tab=search&type=term');

    if ($ocw_state) {
      $wb_cnf['ocw_cnf_url'] = menu_page_url(OCW_Admin::$name, false);
    }

    $user_member_url = get_permalink(get_page_by_path('/vk_user_order'));

    $inline_script = 'var wb_ajaxurl="' . admin_url('admin-ajax.php') . '", 
		_wb_ajax_nonce ="' . $_ajax_nonce . '",
		user_member_url = "' . $user_member_url . '",
        wb_cnf=' . wp_json_encode($wb_cnf);


    wp_add_inline_script('wbs-inline-js', $inline_script, 'before');

    add_filter('style_loader_tag', function ($tag, $handle, $href, $media) {
      if (!preg_match('#^vue-#', $media)) {
        return $tag;
      }

      $media = htmlspecialchars_decode($media);
      $r = [];
      parse_str(str_replace('vue-', '', $media), $r);
      $rel = '';
      $attr = [];
      if ($r && is_array($r)) {
        if (isset($r['rel'])) {
          $rel = $r['rel'];
          unset($r['rel']);
        }
        foreach ($r as $attr_k => $attr_v) {
          $attr[] = sprintf('%s="%s"', $attr_k, esc_attr($attr_v));
        }
      }

      $tag = sprintf(
        '<link href="%s" rel="%s" %s/>' . "\n",
        $href,
        $rel,
        implode(" ", $attr)
      );
      return $tag;
    }, 10, 4);

    add_filter('script_loader_tag', function ($tag, $handle, $src) {
      if (!preg_match('#-vue-js-#', $handle)) {
        return $tag;
      }
      $parts = explode('?', $src, 2);
      $src = $parts[0];
      $type = '';
      $attr = '';
      if (isset($parts[1])) {
        $r = [];
        parse_str(htmlspecialchars_decode($parts[1]), $r);
        //print_r($r);
        if ($r) {
          if (isset($r['type'])) {
            $type = sprintf(' type="%s"', esc_attr($r['type']));
            unset($r['type']);
          }
          $attr_txt = '';
          if (isset($r['attr'])) {
            $attr_txt = $r['attr'];
            unset($r['attr']);
          }
          foreach ($r as $k => $v) {
            $attr .= sprintf(' %s="%s"', $k, esc_attr($v));
          }
          if ($attr_txt) {
            $attr .= sprintf(' %s', esc_attr($attr_txt));
          }
        }
      }
      //print_r([$handle,$src]);

      $tag = sprintf('<script%s src="%s"%s id="%s-js"></script>' . "\n", $type, $src, $attr, $handle);
      return $tag;
    }, 10, 3);

    self::vue_assets();
  }

  public static function vue_assets()
  {

    $assets = include __DIR__ . '/plugins_assets.php';

    if (!$assets || !is_array($assets)) {
      return;
    }

    $wp_styles = wp_styles();
    if (isset($assets['css']) && is_array($assets['css'])) foreach ($assets['css'] as $r) {
      $wp_styles->add($r['handle'], WP_VK_URL . $r['src'], $r['dep'], null, $r['args']);
      $wp_styles->enqueue($r['handle']); //.'?v=1'
    }
    if (isset($assets['js']) && is_array($assets['js'])) foreach ($assets['js'] as $r) {
      if (!$r['src'] && $r['in_line']) {
        wp_register_script($r['handle'], false, $r['dep'], false, true);
        wp_enqueue_script($r['handle']);
        wp_add_inline_script($r['handle'], $r['in_line'], 'after');
      } else if ($r['src']) {
        wp_enqueue_script($r['handle'], WP_VK_URL . $r['src'], $r['dep'], null, true);
      }
    }
  }

  public static function set_theme_setting($data)
  {
    //$opt = self::opt();
    $opt = array();
    foreach ($data as $key => $value) {
      $opt[$key] = self::stripslashes_deep($value);
    }

    return update_option(self::$option_name, $opt);
  }

  public static function stripslashes_deep($value)
  {
    if (is_array($value)) {
      foreach ($value as $k => $v) {
        $value[$k] = self::stripslashes_deep($v);
      }
    } else {
      $value = stripslashes($value);
    }
    return $value;
  }


  /**
   * 非古腾堡模式下文章编辑页
   */
  public static function admin_post_head()
  {

    //echo __CLASS__;

    add_editor_style(WP_VK_URL . 'assets/vk.css?v=' . WP_VK_ASSETS_VER);

    wp_enqueue_style('wbui-css', WP_VK_URL . 'assets/wbui/assets/wbui.css');
    wp_enqueue_script('wbui-js', WP_VK_URL . 'assets/wbui/wbui.js', array(), WP_VK_VERSION, true);

    $pay_conf = WP_VK_Order::pay_conf();
    $set_pay = 0;
    if (isset($pay_conf['alipay']) && $pay_conf['alipay']) {
      $set_pay = 1;
    } else if (isset($pay_conf['wxpay']) && $pay_conf['wxpay']) {
      $set_pay = 1;
    }

    $in_line_js = 'var vk_set_pay = ' . $set_pay . ',vk_set_pay_url="' . esc_url(admin_url('admin.php?page=wp_vk#/pay-setting')) . '";';
    $in_line_js .= "\nfunction check_vk_price(obj){if(!vk_set_pay){wbui.alert('您尚未配置支付，<a href=\"'+vk_set_pay_url+'\" target=\"_blank\">去设置</a>');}var v = obj.value.replace(/[^\d\.]/g,'');if(v){v = parseFloat(Math.round(parseFloat(v)*100)/100);}obj.value=v;};";

    wp_add_inline_script('editor', $in_line_js, 'before');
  }

  public static function add_meta_box()
  {

    if (self::is_active_gutenberg_editor()) {
      return;
    }

    $screens = self::cnf('bind_post_types', array('post', 'page'));
    foreach ($screens as $screen) {
      add_meta_box(
        'vk_meta_box',
        '付费内容价格',
        array(__CLASS__, 'render_meta_box'),
        $screen,
        'side',
        'high'
      );

      wp_enqueue_style('wp-vk-admin-css', WP_VK_URL . 'assets/wb_vk_admin.css', null, WP_VK_ASSETS_VER);
    }
  }

  public static function render_meta_box($post)
  {
    $price = get_post_meta($post->ID, 'vk_price');
    if (empty($price)) {
      $show_price = self::cnf('default_price', '');
    } else {
      $show_price = $price[0];
    }
    //print_r($price);
    echo '<label for="vk_price"><span>&yen;</span> <input onblur="check_vk_price(this);" name="vk_price" type="text" id="vk_price" value="' . esc_attr($show_price) . '"></label> ';
  }

  public static function save_meta_box($post_id)
  {

    if (!current_user_can('edit_post', $post_id))
      return;

    $vk_price = self::param('vk_price', null);
    if (null === $vk_price) {
      return;
    }


    $price = trim($vk_price);

    $value = sanitize_text_field($price);


    if (strlen($value) < 1) {
      $value = '';
    } else {
      $value = abs(floatval($value));
    }
    update_post_meta($post_id, 'vk_price', $value);
  }

  /**
   * 前端展示
   */
  public static function wp_front_head()
  {
    if (!is_singular()) {
      return;
    }

    wp_enqueue_style('wp-vk-css', WP_VK_URL . 'assets/wp_vk_front.css', null, WP_VK_ASSETS_VER);

    $opt = self::cnf();

    $data = '';
    // 暗黑激活
    if ($dm_cls = $opt['dark_mode_class']) {
      $data .= $dm_cls . '{ --wb-vk-bfc: #c3c3c3; --wb-vk-fcs: #fff; --wb-vk-wk: #999; --wb-vk-wke: #686868; --wb-vk-bgc: #2b2b2b; --wb-vk-bbc: #4d4d4d; --wb-vk-bgcl: #353535; --wb-vk-mask-color:#353535; }';
    }

    // 样式
    $css_vars = '';
    $css_vars .= $opt['theme_font_color'] ? ' --wb-vk-tips-fc: ' . $opt['theme_font_color'] . '; --wb-vk-theme: ' . $opt['theme_font_color'] . ';' : '';
    $css_vars .= $opt['theme_font_size'] ? ' --wb-vk-bfs: ' . $opt['theme_font_size'] . 'px;' : '';
    $css_vars .= $opt['theme_icon_size'] ? '--wb-vk-icon-size: ' . $opt['theme_icon_size'] . 'px; --wb-vk-icon-top: ' . (200 - (int)$opt['theme_icon_size']) . 'px;' : '';

    $theme_icon_color = $opt['theme_icon_color'] ? $opt['theme_icon_color'] : '#06c';
    $icon_color = str_replace('#', '%23', $theme_icon_color);
    $wp_style = $opt['theme_mask_color'] ? ' background-image: linear-gradient(180deg, hsla(0,0%,100%,.07), ' . $opt['theme_mask_color'] . ' 80px);' : '';

    $data .= '.wb-vk-wp{ ' . $css_vars .  $wp_style . '}';
    $data .= '.wb-vk-wp::before{ background-image: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2222%22 height=%2222%22 fill=%22none%22%3E%3Cpath stroke=%22' . $icon_color . '%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M11 2v0a4.3 4.3 0 00-4.4 4.3V9h8.6V6.3C15.2 4 13.3 2 11 2v0zM4 9h13.8v12H4V9z%22 clip-rule=%22evenodd%22/%3E%3Cpath stroke=%22' . $icon_color . '%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M11 12.4a1.7 1.7 0 110 3.4 1.7 1.7 0 010-3.4v0z%22 clip-rule=%22evenodd%22/%3E%3Cpath stroke=%22' . $icon_color . '%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M11 15.8v1.7%22/%3E%3C/svg%3E");}';

    // 自定义css
    $data .= $opt['theme_style'];

    wp_add_inline_style('wp-vk-css', $data);
  }

  public static function wp_footer()
  {
    if (!is_singular()) {
      return;
    }

    if (!defined('HAS_VK_CONTENT')) {
      return;
    }

    wp_enqueue_script('vk-more', WP_VK_URL . 'assets/js/pay.js', array(), WP_VK_VERSION, true);
    $_ajax_nonce = wp_create_nonce('wp_ajax_vk_pay');
    $cnf = self::cnf();
    $opt = [
      'code_type' => $cnf['code_type'],
      'code_type_name' => ['num' => '数字或字母', 'mix' => '数字字母组合'][$cnf['code_type']],
      'code_min_len' => $cnf['code_min_len'],
      'code_max_len' => $cnf['code_max_len'],
    ];
    wp_add_inline_script('vk-more', 'var vk_ajaxurl = \'' . admin_url('admin-ajax.php') . '\',vk_ajax_nonce=' . "'$_ajax_nonce',vk_cnf=" . wp_json_encode($opt) . ";", 'before');
  }

  /**
   * 详情输出VK模块
   *
   * @param array $attr
   * @param string $attr_content
   * @param string $tag
   */
  public static function sc_vk_content($attr = array(), $attr_content = '', $tag = '')
  {
    $echo_content = $attr_content;

    do {
      $post_id = get_the_ID();

      if (!$post_id) {
        //$echo_content .= '-1';
        break;
      }

      $price = WP_VK_Order::post_price($post_id);
      if (!$price) {
        //$echo_content .= '-2';
        break;
      }

      $post = get_post();
      if ($post->post_author == get_current_user_id()) {
        //$echo_content .= '-3';
        break;
      }

      $opt = self::cnf();
      /* $is_buy = 0;
        do{
            if($opt['guest_pay']){
                $guest = self::get_guest();
                //print_r($guest);
                if($guest && in_array($post_id,$guest)){
                    $is_buy = 1;
                }
            }
            if($is_buy){
                break;
            }


        }while(0); */
      $is_buy = WP_VK_Order::is_buy($post_id);
      if ($is_buy) {
        //$echo_content .= '-4';
        break;
      }

      if (!defined('HAS_VK_CONTENT')) {
        define('HAS_VK_CONTENT', 1);
      }

      $tpl = isset($attr) && isset($attr['tpl']) ? $attr['tpl'] : $opt['tips_tpl'];

      $tips_tpl = str_replace('%price%', ' &yen;' . $price . ' ', $tpl);
      $tips_tpl = str_replace('%percent%', '', $tips_tpl);

      $pay_conf = WP_VK_Order::pay_conf();

      $pay = [];
      if (isset($pay_conf['wxpay']) && $pay_conf['wxpay']) {
        $pay[] = 'wx';
      }
      if (isset($pay_conf['alipay']) && $pay_conf['alipay']) {
        $pay[] = 'ali';
      }

      $user_id = get_current_user_id();
      $post_url = get_permalink($post);

      ob_start();
      include WP_VK_PATH . '/tpl/front_tips_content.php';
      $echo_content = ob_get_clean();
    } while (0);

    return $echo_content;
  }

  /**
   * 支付与订单
   */
  public static function user_order()
  {
    // global $wpdb;
    $db = self::db();

    $t = $db->prefix . 'vk_orders';

    $uid = get_current_user_id();
    $pagesize = absint(self::param('num', 100));

    if (!$pagesize) {
      $pagesize = 100;
    }

    $limit = WP_VK_Utils::limit($pagesize);
    $sql = $db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM $t WHERE uid=%d AND u_del=0", $uid);

    $sql .= " AND pay_status=1";


    $list = $db->get_results($sql . " ORDER BY id DESC " . $limit);

    $total = $db->get_var("SELECT FOUND_ROWS()");

    include WP_VK_PATH . '/tpl/page_my.php';

    exit();
  }

  public static function pay()
  {
    $uid = get_current_user_id();
    $oid = absint(self::param('oid', '0', 'g'));
    if ($oid) {

      /*if(!$uid){
				$pay_url = self::pay_url('pay',array('oid'=>$oid));
				wp_redirect(wp_login_url($pay_url));
				exit();
			}*/

      $order_info = WP_VK_Order::pay_info($oid);

      if (!$order_info || !$order_info->id) {
        wp_die('操作错误，订单不存在。');
        exit();
      }
      if (empty($order_info->verify_code) && $order_info->uid != $uid) {
        wp_die('非法操作，订单不存在');
        exit();
      }
    } else {


      $post = WP_VK_Order::verify();

      $opt = self::cnf();
      $verify_code = '';
      $guest_pay = 0;
      $type = intval(self::param('type', '0', 'g'));
      if ($opt['guest_pay'] && $type === 1) {
        $guest_pay = 1;
        $vcode = self::param('vcode', '', 'g');
        $verify_code = sanitize_text_field(preg_replace('#[^0-9a-z]#i', '', $vcode));
        $ret = WP_VK_Order::order($post, 0, $verify_code);
      } else {
        if (!is_user_logged_in()) {
          $pay_url = self::pay_url('pay', array('id' => $post->ID));
          wp_redirect(wp_login_url($pay_url));
          exit();
        }

        $ret = WP_VK_Order::order($post, $uid);
      }

      if (!$ret) {
        wp_die('创建订单出错!请稍候再试！');
        exit();
      }
      $oid = $ret;
      $order_info = WP_VK_Order::pay_info($oid);


      if ($guest_pay) {
        $cookie_code = self::encode(implode(',', array($oid, $post->ID, current_time('U') + 7200, $verify_code)));
        setcookie('vk_guest_code', $cookie_code, 0, '/');
      }
    }


    if ($order_info->pay_status) { //已支付订单
      wp_die('订单已支付，无需再支付。');
      exit();
    }
    if (!$order_info->valid) {
      wp_die('订单已失效。请重新下单。');
      exit();
    }
    if ((float)$order_info->money < 0.01) { //免费订单
      WP_VK_Order::pay_free($oid);
      wp_redirect(get_permalink($order_info->pid));
      exit();
    }


    $pay_conf = WP_VK_Order::pay_conf();

    //print_r($pay_conf);exit();

    $pay = '';
    if ($order_info->pay_type == 2) {
      if (isset($pay_conf['wxpay']) && $pay_conf['wxpay']) {
        $pay = 'wx';
      } else if (isset($pay_conf['alipay']) && $pay_conf['alipay']) {
        WP_VK_Order::set_pay_type($order_info->id, 1);
        $pay = 'ali';
      }
    } else if ($order_info->pay_type == 1) {
      if (isset($pay_conf['alipay']) && $pay_conf['alipay']) {
        $pay = 'ali';
      } else if (isset($pay_conf['wxpay']) && $pay_conf['wxpay']) {
        WP_VK_Order::set_pay_type($order_info->id, 2);
        $pay = 'wx';
      }
    }
    if (!$pay) {
      wp_die('网站未开启在线支付。');
    }


    if ($pay_conf['type'] == 2) {
      //hupay
      require_once WP_VK_PATH . '/pay/hupay/hupay.php';
      //notify_url
      $cnf = null;
      if ($pay == 'wx') {
        //weixin
        $cnf = $pay_conf['wxpay'];
        $cnf['type'] = 'wxpay';
      } else if ($pay == 'ali') {
        //alipay
        $cnf = $pay_conf['alipay'];
        $cnf['type'] = 'alipay';
      }
      if ($cnf) {
        $post_url = get_permalink($post);
        if (strpos($post_url, '?')) {
          $post_url = $post_url . '&_t=' . time();
        } else {
          $post_url = $post_url . '?_t=' . time();
        }

        $cnf['notify_url'] = self::pay_notify_url('hupay');
        $cnf['return_url'] = $post_url;
        $cnf['callback_url'] = $post_url;
        if (isset($pay_conf['ver'])) {
          $cnf['ver'] = $pay_conf['ver'];
        }
        if (isset($pay_conf['api'])) {
          $cnf['api'] = $pay_conf['api'];
        }
        VK_Hupay::webPay($order_info, $cnf);
      }
    } else if ($pay_conf['type'] == 3) {
      //hupay
      require_once WP_VK_PATH . '/pay/payjs/payjs.php';
      //notify_url
      $cnf = null;
      if ($pay == 'wx') {
        //weixin
        $cnf = $pay_conf['wxpay'];
        $cnf['type'] = 'wxpay';
      } else if ($pay == 'ali') {
        //alipay
        $cnf = $pay_conf['alipay'];
        $cnf['type'] = 'alipay';
      }
      if ($cnf) {
        $post_url = get_permalink($post);
        if (strpos($post_url, '?')) {
          $post_url = $post_url . '&_t=' . time();
        } else {
          $post_url = $post_url . '?_t=' . time();
        }

        $cnf['notify_url'] = self::pay_notify_url('payjs');
        $cnf['return_url'] = $post_url;
        $cnf['callback_url'] = $post_url;
        $cnf['logo'] = isset($pay_conf['logo']) ? $pay_conf['logo'] : '';
        VK_Payjs::startPay($order_info, $cnf);
      }
    } else if ($pay_conf['type'] == 10) {

      if ($pay == 'wx') {
        require_once WP_VK_PATH . '/pay/wxpay/wxpay.php';
        //VK_Wxpay::pay();
        $cnf = $pay_conf['wxpay'];
        $cnf['type'] = 'wxpay';
        $cnf['notify_url'] = self::pay_notify_url('wxpay');
        $cnf['redirect_uri'] = WP_VK::pay_url('pay', ['oid' => $order_info->id]);

        VK_Wxpay::startPay($order_info, $cnf);
      } else if ($pay == 'ali') {
        require_once WP_VK_PATH . '/pay/alipay/alipay.php';
        $cnf = $pay_conf['alipay'];
        $cnf['type'] = 'alipay';
        $cnf['notify_url'] = self::pay_notify_url('alipay');
        $cnf['return_url'] = self::pay_return_url('alipay');
        VK_Alipay::startPay($order_info, $cnf);
      }
      //wp_die('网站在线支付配置出错。');
    }

    wp_die('网站未开启在线支付。');
  }

  public static function pay_return_url($pay)
  {
    return WP_VK_URL . 'pay/' . $pay . '/return.php';
  }

  public static function pay_notify_url($pay)
  {
    return WP_VK_URL . 'pay/' . $pay . '/notify.php';
  }

  public static function pay_url($action, $param = array())
  {
    $url = home_url('?wp_vk=' . $action);
    if ($param) {
      $url = $url . '&' . http_build_query($param);
    }

    return $url;
  }

  public static function setup_page()
  {

    // global $wpdb;

    $db = self::db();
    $pages = array(


      array(
        'title' => '付费内容-会员订单',
        'slug' => 'vk_user_order',
        'content' => '付费内容-会员订单功能页面，读取自定义页面',
        'page_template' => 'page.php'
      )


    );

    $exists_page = $db->get_col("SELECT post_name FROM $db->posts WHERE post_type='page' AND post_status='publish'");

    foreach ($pages as $page) {

      if (in_array($page['slug'], $exists_page)) {
        continue;
      }

      $new_page_id = wp_insert_post(
        array(
          'post_title' => $page['title'],
          'post_type'     => 'page',
          'post_name'  => $page['slug'],
          'comment_status' => 'closed',
          'ping_status' => 'closed',
          'post_content' => $page['content'],
          'post_status' => 'publish',
          'post_author' => 1,
          'menu_order' => 0
        )
      );

      if ($new_page_id && $page['page_template'] != '') {
        update_post_meta($new_page_id, '_wp_page_template',  $page['page_template']);
      }
    }
  }


  /**
   * 与下载插件结合
   */

  /**
   * @return bool 判断是否开启付费下载
   */
  public static function active_pay_download()
  {
    if (class_exists('DLIP_DownLoadAdmin')) {
      //return false;
      $post_id = get_the_ID();
      $meta_value = DLIP_DownLoadAdmin::meta_values($post_id);

      if ($meta_value['wb_dl_mode'] == 2) {
        return true;
      }
    } else if (class_exists('WB_MagicPost_Download')) {
      $post_id = get_the_ID();
      $meta_value = WB_MagicPost_Download::meta_values($post_id);

      if ($meta_value['wb_dl_mode'] == 2) {
        return true;
      }
    }

    return false;
  }
}
