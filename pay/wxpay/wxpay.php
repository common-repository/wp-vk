<?php


require_once __DIR__.'/lib/WxPay.Api.php';


/**
 * Class VK_Wxpay
 *
 */

class VK_Wxpay_Config extends WxPayConfigInterface
{

    private $config = array();


    public function __construct($conf)
    {
        $this->config = $conf;

    }

    public function GetAppId()
    {
        return isset($this->config['appid'])?$this->config['appid']:'';
    }

    public function GetMerchantId()
    {
        return isset($this->config['merchantid'])?$this->config['merchantid']:'';
    }

    public function GetNotifyUrl()
    {
        return isset($this->config['notify_url'])?$this->config['notify_url']:'';
    }

    public function GetSignType()
    {
        return "HMAC-SHA256";
    }

    public function GetProxy(&$proxyHost, &$proxyPort)
    {
        $proxyHost = "0.0.0.0";
        $proxyPort = 0;

    }

    public function GetReportLevenl()
    {
        return 1;
    }

    public function GetKey()
    {
        return isset($this->config['key'])?$this->config['key']:'';
    }

    public function GetAppSecret()
    {
        return isset($this->config['appsecret'])?$this->config['appsecret']:'';
    }

    public function GetRedirectUri(){
        return isset($this->config['redirect_uri'])?$this->config['redirect_uri']:'';
    }

    public function GetSSLCertPath(&$sslCertPath, &$sslKeyPath)
    {
        $sslCertPath = WP_VK_PATH.'/pay/wxpay/#key/apiclient_cert.pem';
        $sslKeyPath = WP_VK_PATH.'/pay/wxpay/#key/apiclient_key.pem';
    }

}


class  VK_Wxpay
{

    public static $debug = false;

    public static $config = null;


    public static function pay_return(){
        self::txt_log('wx notify');
        self::txt_log('_GET',$_GET);
        self::log($_GET);
        wp_redirect(home_url());
        exit();
    }

    public static function notify(){


        self::txt_log('wx notify');


        if(!isset($GLOBALS['HTTP_RAW_POST_DATA'])){
            $raw_input = file_get_contents('php://input');
            if($raw_input){
                $GLOBALS['HTTP_RAW_POST_DATA'] = $raw_input;
            }
        }


        if(isset($GLOBALS['HTTP_RAW_POST_DATA'])){
            self::txt_log('HTTP_RAW_POST_DATA',$GLOBALS['HTTP_RAW_POST_DATA']);
            self::log($GLOBALS['HTTP_RAW_POST_DATA']);
        }else{
            self::txt_log('_POST',$_POST);
            self::log($_POST);
            exit();
        }


        require_once __DIR__.'/pay_notify.php';

        $conf = WP_VK_Order::pay_conf();

        $config = new VK_Wxpay_Config($conf['wxpay']);
        self::txt_log("begin notify");

        $notify = new VK_PayNotifyCallBack();
        $notify->Handle($config, false);


        exit();
    }

    public static function startPay($order_info,$config){


        $is_wx = false;
        if(preg_match('#MicroMessenger#',$_SERVER['HTTP_USER_AGENT'])){
            require_once __DIR__.'/pay_jsapi.php';

            $open_id = self::wx_open_id($config);
            if($open_id){
                $is_wx = true;
                $config['open_id'] = $open_id;
            }

        }

        if($is_wx){

            $jsApiParameters = self::JSApi($order_info,$error,$config);
            if(!$jsApiParameters){
                $is_wx = false;
                wp_die(esc_html('微信支付出错。[code:'.$error->getMessage().',err:'.$error->getCode().']'));
            }

        }
        $is_h5 = 0;
        if(!$is_wx){


            if(self::is_mobile() && isset($config['h5']) && $config['h5']){

                $is_h5 = 1;
                $result = self::H5Pay($order_info,$error,$config);
            }else{
                $result = self::QRCode($order_info,$error,$config);
            }

            if(!$result){
                wp_die(esc_html('微信支付出错。[code:'.$error->getMessage().',err:'.$error->getCode().']'));
            }
            if($result['return_code'] == 'FAIL'){
                wp_die(esc_html('微信支付出错。[code:'.$result['err_code'].',err:'.$result['err_code_des'].',msg:'.$result['return_msg'].']'));
            }
            if($result['result_code'] == 'FAIL'){
                wp_die(esc_html('微信支付出错。[code:'.$result['err_code'].',err:'.$result['err_code_des'].']'));
            }

            if($is_h5){
                $code_url = $result['mweb_url'];
            }else{
                $code_url = $result["code_url"];
            }


        }
        $_ajax_nonce = wp_create_nonce('wp_ajax_vk_pay');

        include __DIR__.'/pay_weixin.php';

        exit();
    }

    public static function pay(){

        $oid = absint($_GET['oid']);

        if(!$oid){
            wp_die('订单不存在。');
            exit();
        }

        $uid = get_current_user_id();

        if(!$uid){
            $pay_url = WP_VK::pay_url('pay',['oid'=>$oid]);
            wp_redirect(wp_login_url($pay_url));
            exit();
        }

        $pay_conf = WP_VK_Order::pay_conf();

        if($pay_conf['type'] != 10 || !isset($pay_conf['wxpay']) || empty($pay_conf['wxpay'])){
            $pay_url = WP_VK::pay_url('pay',['oid'=>$oid]);
            wp_redirect($pay_url);
            exit();
        }

        $config = $pay_conf['wxpay'];

        $is_wx = false;
        if(preg_match('#MicroMessenger#',$_SERVER['HTTP_USER_AGENT'])){
            require_once __DIR__.'/pay_jsapi.php';
            $redirect_uri = WP_VK::pay_url('pay',['oid'=>$oid]);
            $config['redirect_uri'] = $redirect_uri;
            $open_id = self::wx_open_id($config);
            if($open_id){
                $is_wx = true;
                $config['open_id'] = $open_id;
            }

        }


        $order_info = WP_VK_Order::pay_info($oid);
        if(!$order_info || !$order_info->id){
            wp_die('订单不存在。');
            exit();
        }
        //print_r($order_info);exit();
        if($order_info->uid != $uid){
            wp_die('非法操作，订单不存在');
            exit();
        }
        if($order_info->pay_status){//已支付订单
            wp_die('订单已支付，无需再支付。');
            exit();
        }
        if(!$order_info->valid){
            wp_die('订单已失效。请重新下单。');
            exit();
        }
        if((float)$order_info->money < 0.01){//免费订单
            WP_VK_Order::pay_free($oid);
            wp_redirect(get_permalink($order_info->pid));
            exit();
        }
        if($order_info->pay_type!=2){
            $pay_url = WP_VK::pay_url('pay',['oid'=>$order_info->id]);
            wp_redirect($pay_url);
            exit();
        }






        if($is_wx){

            $jsApiParameters = self::JSApi($order_info,$error,$config);
            if(!$jsApiParameters){
                $is_wx = false;
                wp_die(esc_html('微信支付出错。[code:'.$error->getMessage().',err:'.$error->getCode().']'));
            }

        }


        $is_h5 = 0;
        if(!$is_wx){


            if(self::is_mobile() && isset($config['h5']) && $config['h5']){

                $is_h5 = 1;
                $result = self::H5Pay($order_info,$error,$config);
            }else{
                $result = self::QRCode($order_info,$error,$config);
            }

            if(!$result){
                wp_die(esc_html('微信支付出错。[code:'.$error->getMessage().',err:'.$error->getCode().']'));
            }
            if($result['return_code'] == 'FAIL'){
                wp_die(esc_html('微信支付出错。[code:'.$result['err_code'].',err:'.$result['err_code_des'].',msg:'.$result['return_msg'].']'));
            }
            if($result['result_code'] == 'FAIL'){
                wp_die(esc_html('微信支付出错。[code:'.$result['err_code'].',err:'.$result['err_code_des'].']'));
            }

            if($is_h5){
                $code_url = $result['mweb_url'];
            }else{
                $code_url = $result["code_url"];
            }


        }
        $_ajax_nonce = wp_create_nonce('wp_ajax_vk_pay');

        include __DIR__.'/pay_weixin.php';

        exit();
    }

    public static function query_order($order_no,$uid){

        // global $wpdb;

        $db = self::db();

        $t = $db->prefix.'vk_orders';
        $row = $db->get_row($db->prepare("SELECT * FROM $t WHERE order_no=%s AND $uid=%d",$order_no,$uid));

        return $row;

    }

    public static function wx_query_order($order_no){


        $input = new WxPayOrderQuery();

        //$input->SetTransaction_id($transaction_id);
        $input->SetOut_trade_no($order_no);

        $conf = WP_VK_Order::pay_conf();

        $config = new VK_Wxpay_Config($conf['wxpay']);
        try{
            $result = WxPayApi::orderQuery($config, $input);



            self::txt_log("order query:" . wp_json_encode($result));

            if(array_key_exists("return_code", $result)
                && array_key_exists("result_code", $result)
                && $result["return_code"] == "SUCCESS"
                && $result["result_code"] == "SUCCESS" && $result['trade_state'] == 'SUCCESS')
            {
                self::trade_success($result);

                return true;
            }

        }catch (Exception $ex){

            self::txt_log('order query error.[code:'.$ex->getCode().',err:'.$ex->getMessage().']');
        }

        return false;
    }

    public static function wx_open_id($conf){

        try{

            $config = new VK_Wxpay_Config($conf);
            $api = new VK_Wxpay_Jsapi($config);

            return $api->GetOpenid();

        }catch (Exception $e){

            self::txt_log('jsapi get open id error. [code:'.$e->getCode().',msg:'.$e->getMessage().']');
        }


        return false;


    }

    public static function JSApi($order_info,&$error,$conf){

        try{
            //统一下单
            $input = new WxPayUnifiedOrder();
            $input->SetBody($order_info->name);
            $input->SetAttach(wp_json_encode(array('oid'=>$order_info->id,'uid'=>$order_info->uid)));
            $input->SetOut_trade_no($order_info->order_no);
            $input->SetTotal_fee(round($order_info->money * 100) );
            $input->SetTime_start(current_time("YmdHis"));
            $input->SetTime_expire(gmdate("YmdHis", strtotime(current_time('mysql')) + 900));//15分钟有效
            //$input->SetGoods_tag("test");
            //$input->SetNotify_url($conf['notify_url']);
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($conf['open_id']);

            $config = new VK_Wxpay_Config($conf);
            $order = WxPayApi::unifiedOrder($config, $input);


            $tools = new VK_Wxpay_Jsapi($config);

            $jsApiParameters = $tools->GetJsApiParameters($order);

            return $jsApiParameters;

        } catch(Exception $e) {

            $error = $e;
            self::txt_log('create unified order error. [code:'.$e->getCode().',msg:'.$e->getMessage().']');

        }
        return false;
    }

    public static function QRCode($order_info,&$error,$conf){

        try{
            //统一下单

            $input = new WxPayUnifiedOrder();
            $input->SetBody($order_info->name);
            $input->SetAttach(wp_json_encode(array('oid'=>$order_info->id,'uid'=>$order_info->uid)));
            $input->SetOut_trade_no($order_info->order_no);
            $input->SetTotal_fee(round($order_info->money * 100) );
            //$input->SetTime_start(current_time("YmdHis"));
            //$input->SetTime_expire(date("YmdHis", strtotime(current_time('mysql')) + 1800));//30分钟有效
            //$input->SetGoods_tag("test");
            //$input->SetNotify_url($conf['notify_url']);
            $input->SetTrade_type("NATIVE");
            $input->SetProduct_id($order_info->id);

            //print_r($input);exit();

            $config = new VK_Wxpay_Config($conf);
            $result = WxPayApi::unifiedOrder($config, $input);

            return $result;

        } catch(Exception $e) {

            $error = $e;
            self::txt_log('create unified order error. [code:'.$e->getCode().',msg:'.$e->getMessage().']');
        }
        return false;

    }

    public static function H5Pay($order_info,&$error,$conf)
    {

        try{
            //统一下单

            $input = new WxPayUnifiedOrder();
            $input->SetBody($order_info->name);
            $input->SetAttach(wp_json_encode(array('oid'=>$order_info->id,'uid'=>$order_info->uid)));
            $input->SetOut_trade_no($order_info->order_no);
            $input->SetTotal_fee(round($order_info->money * 100) );
            //$input->SetTime_start(current_time("YmdHis"));
            //$input->SetTime_expire(date("YmdHis", strtotime(current_time('mysql')) + 1800));//30分钟有效
            //$input->SetGoods_tag("test");
            //$input->SetNotify_url($conf['notify_url']);
            $input->SetTrade_type("MWEB");
            $input->SetProduct_id($order_info->id);
            $input->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']);
            $input->SetScene_info(wp_json_encode(['h5_info'=>['type'=>'Wap','wap_url'=>home_url(),'wap_name'=>get_option( 'blogname' )]],JSON_UNESCAPED_UNICODE));

            //print_r($input);exit();

            $config = new VK_Wxpay_Config($conf);
            $result = WxPayApi::unifiedOrder($config, $input);

            return $result;

        } catch(Exception $e) {

            $error = $e;
            self::txt_log('create unified order error. [code:'.$e->getCode().',msg:'.$e->getMessage().']');
        }
        return false;

    }

    public static function is_mobile()
    {
        if(function_exists('wp_is_mobile')){
            return wp_is_mobile();
        }else{
            return preg_match('#(mobile|android|iphone)#i',$_SERVER['HTTP_USER_AGENT']);
        }
    }

    public static function trade_success($param){
        // global $wpdb;

        $db = self::db();
        $t  = $db->prefix.'vk_trade_log';

        self::txt_log($param);
        $time_end = '';
        $sep = '';
        for($i=0;$i<14;$i+=2){
            $time_end .= $sep.substr($param['time_end'],$i,2);
            if($i>0)$sep = '-';
            if($i>4)$sep = ' ';
            if($i>6) $sep = ':';
        }

        if(isset($param['attach']) && $param['attach']){
            $param['attach'] = json_decode($param['attach'],true);
        }

        $d = array(
            'oid'=>$param['attach']['oid'],
            'order_no'=>$param['out_trade_no'],
            'trade_no'=>$param['transaction_id'],
            'trade_status'=>'SUCCESS',//$param['result_code'],
            'type'=>10,
            'pay_type'=>2,
            'buyer_id'=>$param['openid'],
            'total_amount'=>round($param['total_fee']/100,2),//total_fee 为分
            'gmt_payment'=>$time_end,//20141030133525
            'created'=>current_time('mysql'),
        );

        self::txt_log($d);
        $ret = $db->insert($t,$d);
        if($ret){
            $trade = $db->get_row($db->prepare("SELECT * FROM $t WHERE id = %d",$db->insert_id));
            do_action('vk_pay_success',$trade);
        }

    }

    public static function log($data){

        // global $wpdb;

        if(!self::$debug){
            return;
        }
        $d = array(
            'type'=>1,
            'created'=>current_time('mysql'),
            'uri'=>$_SERVER['REQUEST_URI'],
            'body'=>is_string($data)?$data:wp_json_encode($data),
            'ip'=>$_SERVER['REMOTE_ADDR'],
            'agent'=>$_SERVER['HTTP_USER_AGENT'],
        );


        //$wpdb->insert($wpdb->prefix.'vk_pay_log',$d);

    }

    public static function txt_log($msg){
        if(!self::$debug){
            return;
        }


        if(func_num_args()>1){
            $msg = wp_json_encode(func_get_args());
        }else if(is_array($msg)){
            $msg = wp_json_encode($msg);
        }

        error_log('['.current_time('mysql').']'.$msg."\n",3,__DIR__.'/#log/logs.txt');

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

    public static function refound($order_no,$money){

        // global $wpdb;
        // $t = $wpdb->prefix.'orders';
        //$order = $wpdb->get_row($wpdb->prepare("select * from $t where order_no=%s",$order_no));

        //print_r($order);exit();


    }

}


