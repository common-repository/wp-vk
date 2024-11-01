<?php




class VK_Payjs
{
    public static $debug = false;

    public static $config = null;

    public static function set_config($conf){
        self::$config = $conf;
    }

    public static function log($data){
        // global $wpdb;

        if(!self::$debug){
            return;
        }
        $d = array(
            'type'=>2,
            'created'=>current_time('mysql'),
            'uri'=>$_SERVER['REQUEST_URI'],
            'body'=>wp_json_encode($data),
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

    public static function trade_success($param){
        // global $wpdb;

        /*

        array(
        'trade_order_id'，商户网站订单ID
         'total_fee',订单支付金额
         'transaction_id',//支付平台订单ID
         'order_date',//支付时间
         'plugins',//自定义插件ID,与支付请求时一致
         'status'=>'OD'//订单状态，OD已支付，WP未支付
        )*/

        $db = self::db();

        $t  = $db->prefix.'vk_trade_log';
        $attach = $param['attach'];
        $pay_type = array('wxpay'=>2,'alipay'=>1);
        $d = array(
            'oid'=>$attach['oid'],
            'order_no'=>$param['out_trade_no'],
            'trade_no'=>$param['transaction_id'],
            'trade_status'=>'SUCCESS',
            'type'=>3,
            'pay_type'=>$attach['pay_type'],
            'buyer_id'=>$attach['uid'],
            'total_amount'=>round($param['total_fee'] /100,2),
            'gmt_payment'=>$param['time_end'],
            'created'=>current_time('mysql'),
        );

        $ret = $db->insert($t,$d);
        if($ret){
            $trade = $db->get_row($db->prepare("SELECT * FROM $t WHERE id = %d",$db->insert_id));
            do_action('vk_pay_success',$trade);
        }
    }

    public static function notify(){

        $data = $_POST;
        foreach ($data as $k=>$v){
            $data[$k] = trim(stripslashes($v));
        }

        self::log($data);

        if(!isset($data['sign']) || !isset($data['return_code']) || !isset($data['attach'])){
            echo 'failed';
            self::txt_log('empty notify param');
            exit;
        }

        $attach = json_decode($data['attach'],true);

        $pay_conf = WP_VK_Order::pay_conf();
        if(!isset($pay_conf[$attach['type']])){
            echo 'failed';
            self::txt_log('empty pay config');
            exit;
        }

        $config = $pay_conf[$attach['type']];



        $appid              = $config['appid'];
        $appsecret          = $config['appsecret'];

        $hash = self::sign($data,$appsecret);
        if($data['sign']!=$hash){
            //签名验证失败
            echo 'failed';
            self::txt_log('verify notify param error');
            exit;
        }


        if($data['return_code'] == 1){
            self::txt_log('pay success');

            $data['attach'] = $attach;
            self::trade_success($data);

        }else{
            //处理未支付的情况
            self::txt_log('pay status:'.$data['return_code']);
        }
        //以下是处理成功后输出，当支付平台接收到此消息后，将不再重复回调当前接口
        echo 'success';
        exit;
    }

    public static function startPay($order_info,$pay_conf){

        //print_r($pay_conf);exit();
        self::$config = $pay_conf;
        if(preg_match('#MicroMessenger#',$_SERVER['HTTP_USER_AGENT'])) {

            self::wxPay($order_info,$pay_conf);
            exit();
        }else if(self::is_mobile() && isset($pay_conf['h5']) && $pay_conf['h5']){
            self::h5Pay($order_info,$pay_conf);
            exit();
        }

        self::qrPay($order_info,$pay_conf);
        exit();
    }

    public static function qrPay($order_info,$pay_conf)
    {
        $data = self::payParam('native',$order_info,$pay_conf);

        $pay = [];
        $api = 'https://payjs.cn/api/native';
        try{

            $body = self::http_post($api,$data);

            $pay = json_decode($body,true);
            if(!$pay || !$pay['return_code']){

                wp_die(esc_html('支付接口请求失败[err : '.$pay['return_msg'].']'));
            }

        }catch (Exception $ex){
            wp_die(esc_html('支付接口请求失败[err : '.$ex->getMessage().']'));
        }


        $_ajax_nonce = wp_create_nonce('wp_ajax_vk_pay');

        include __DIR__.'/pay_weixin.php';

        exit();


    }

    public static function h5Pay($order_info,$pay_conf)
    {
        $data = self::payParam('mweb',$order_info,$pay_conf);

        $api = 'https://payjs.cn/api/mweb';
        try{
            $body = self::http_post($api,$data);
            $pay = json_decode($body,true);
            if(!$pay || !$pay['return_code']){
                wp_die(esc_html('支付接口请求失败[err : '.$pay['return_msg'].']'));
            }
            if(!isset($pay['h5_url']) || !$pay['h5_url']){
                wp_die('支付接口请求失败[err : empty h5 url]');
            }

            $code_url = $pay['h5_url'];

            include __DIR__.'/pay_h5_page.php';

            exit();

        }catch (Exception $ex){
            wp_die(esc_html('支付接口请求失败[err : '.$ex->getMessage().']'));
        }
    }

    public static function wxPay($order_info,$pay_conf)
    {
        $data = self::payParam('cashier',$order_info,$pay_conf);

        $url = 'https://payjs.cn/api/cashier?'.http_build_query($data);

        wp_redirect($url);
        exit();
    }

    public static function payParam($api,$order_info,$pay_conf)
    {
        $attach = array('oid'=>$order_info->id,'pay_type'=>$order_info->pay_type,'uid'=>$order_info->uid,'type'=>$pay_conf['type']);

        $data=array(
            'mchid'     => $pay_conf['appid'], //商户号
            'total_fee' => round($order_info->money * 100),//金额。单位：分
            'out_trade_no'=> $order_info->order_no, //用户端自主生成的订单号，在用户端要保证唯一性
            'body'     => $order_info->name, //订单标题
            'attach'=>wp_json_encode($attach),//用户自定义数据，在notify的时候会原样返回
            'notify_url'=>  $pay_conf['notify_url'], //接收微信支付异步通知的回调地址
            'callback_url'=>$pay_conf['callback_url'],//用户支付成功后，前端跳转地址。留空则支付后关闭webview
            //'auto'      => 1,//auto=1：无需点击支付按钮，自动发起支付。默认手动点击发起支付
            //'hide' => 1,//hide=1：隐藏收银台背景界面。默认显示背景界面（这里hide为1时，自动忽略auto参数）
            //'logo'=>'',//收银台显示的logo图片url
        );

        if($api == 'cashier' && isset($pay_conf['logo']) && $pay_conf['logo']){
            $data['logo'] = $pay_conf['logo'];
        }

        if($api == 'native'){
            unset($data['callback_url']);
            if($pay_conf['type'] == 'alipay'){
                $data['type'] = 'alipay';
            }
        }

        $data['sign']     = self::sign($data,$pay_conf['appsecret']);

        return $data;
    }

    public static function is_mobile()
    {
        if(function_exists('wp_is_mobile')){
            return wp_is_mobile();
        }else{
            return preg_match('#(mobile|android|iphone)#i',$_SERVER['HTTP_USER_AGENT']);
        }
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public static function http_post($url,$data){

        $http = wp_remote_post($url,array('sslverify'=>false,'timeout'=>30,'body'=>$data,'headers'=>array('referer'=>home_url())));
        $code = wp_remote_retrieve_response_code($http);
        if($code != 200){

            if(is_wp_error($http)){

                throw new Exception(esc_html("network error[".$http->get_error_message().']'));
            }else{

                throw new Exception(esc_html("error response [code:".$code.']'));
            }

        }
        return wp_remote_retrieve_body($http);
    }

    public static  function sign(array $datas,$hashkey){
        ksort($datas);
        reset($datas);

        $pre =array();
        foreach ($datas as $key => $data){
            if(is_null($data)||$data===''){continue;}
            if($key=='sign'){
                continue;
            }
            $pre[$key]=stripslashes($data);
        }

        $str  = [];

        foreach ($pre as $key=>$val){
            $str[] = $key.'='.$val;
        }

        $str[] = 'key='.$hashkey;

        return strtoupper(md5(implode('&',$str)));
    }

}
