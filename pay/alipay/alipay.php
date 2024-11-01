<?php


/**
 * Class VK_Alipay
 *
 */



class  VK_Alipay
{

    public static $debug = false;


    public static $config = array(
        'alipay_key'=>'',
        'public_key'=>'',
        'private_key'=>'',
    );

    public static function set_config($conf){
        self::$config = $conf;
    }

    public static function log($data){

        // global $wpdb;

        if(!self::$debug){
            return;
        }

        $d = array(
            'type'=>3,
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

        $db = self::db();
        $t = $db->prefix.'vk_trade_log';

        if(isset($param['passback_params']) && $param['passback_params']){
            $param['passback_params'] = json_decode($param['passback_params'],true);
        }
        $d = array(
            'oid'=>$param['passback_params']['oid'],
            'order_no'=>$param['out_trade_no'],
            'trade_no'=>$param['trade_no'],
            'trade_status'=>'SUCCESS',
            'type'=>10,
            'pay_type'=>2,
            'buyer_id'=>$param['buyer_id'],
            'total_amount'=>$param['total_amount'],
            'gmt_payment'=>$param['gmt_payment'],
            'created'=>current_time('mysql'),
        );

        $ret = $db->insert($t,$d);

        if($ret){
            $trade = $db->get_row($db->prepare("SELECT * FROM $t WHERE id = %d",$db->insert_id));
            do_action('vk_pay_success',$trade);
        }
    }

    public static function pay_return(){


        $param = $_GET;
        if(isset($param['fund_bill_list'])){
            $param['fund_bill_list'] = stripslashes($param['fund_bill_list']);
        }
        if(isset($param['subject'])){
            $param['subject'] = stripslashes($param['subject']);
        }
        if(isset($param['body'])){
            $param['body'] = stripslashes($param['body']);
        }
        if(isset($param['passback_params'])){
            $param['passback_params'] = stripslashes($param['passback_params']);
        }

        $url = home_url();
        self::txt_log($param);
        if(isset($param['passback_params'])){
            $param['passback_params'] = json_decode($param['passback_params'],true);
            if($param['pid']){
                $url = get_permalink($param['pid']);
            }
        }else if(isset($param['out_trade_no'])){
            $info = WP_VK_Order::info($param['out_trade_no'],'order_no');
            if($info && $info->pid){
                $url = get_permalink($info->pid);
            }
        }
        if(strpos($url,'?')){
            $url = $url . '&_t='.time();
        }else{
            $url = $url .'?_t='.time();
        }
        wp_redirect($url);
        exit();
    }

    public static function notify(){
        $param = $_POST;
        if(isset($param['fund_bill_list'])){
            $param['fund_bill_list'] = stripslashes($param['fund_bill_list']);
        }
        if(isset($param['subject'])){
            $param['subject'] = stripslashes($param['subject']);
        }
        if(isset($param['body'])){
            $param['body'] = stripslashes($param['body']);
        }
        if(isset($param['passback_params'])){
            $param['passback_params'] = stripslashes($param['passback_params']);
        }
        self::log($param);
        self::txt_log($param);

        if(!isset($param['sign'])){
            exit();
        }

        $pay = WP_VK_Order::pay_conf();
        self::txt_log($pay);

        if(!isset($pay['alipay']) || $pay['type'] != 10){

            self::txt_log('system pay config error!');
            echo "fail";
            exit();
        }
        self::set_config($pay['alipay']);
        $result = self::verify($param);

        if($result){
            //$post = $_POST;

            if($param['trade_status'] == 'TRADE_SUCCESS') {//支付成功
                self::trade_success($param);
            }

            echo "success";	//请不要修改或删除
        }else{
            echo "fail";
        }
        exit();
    }

    public static function startPay($order_info,$config)
    {
        self::webPay($order_info,$config);
        exit();
    }

    public static function is_mobile()
    {
        if(function_exists('wp_is_mobile')){
            return wp_is_mobile();
        }else{
            return preg_match('#(mobile|android|iphone)#i',$_SERVER['HTTP_USER_AGENT']);
        }
    }

    public static function webPay($order_info,$config){

        self::$config = $config;

        $biz = array(
            'out_trade_no'=>$order_info->order_no,//商户订单号,64个字符以内、可包含字母、数字、下划线；需保证在商户端不重复
            'product_code'=>'FAST_INSTANT_TRADE_PAY',//销售产品码，与支付宝签约的产品码名称。注：目前仅支持FAST_INSTANT_TRADE_PAY
            'total_amount'=>$order_info->money,//订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
            'subject'=>$order_info->name,//订单标题
            'body'=>'',//订单描述
            //'time_expire'=>date('Y-m-d H:i:s',strtotime('+7 day',strtotime(current_time('mysql')))),//绝对超时时间，格式为yyyy-MM-dd HH:mm:ss
            'timeout_express'=>'15m',//该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m
            'passback_params'=>wp_json_encode(array('oid'=>$order_info->id,'uid'=>$order_info->uid,'pid'=>$order_info->pid)),
        );
        $param = array(
            'app_id'=>$config['appid'],//支付宝分配给开发者的应用ID
            'method'=>'alipay.trade.page.pay',//接口名称
            'format'=>'JSON',//仅支持JSON
            'charset'=>'utf-8',//请求使用的编码格式
            'sign_type'=>'RSA2',//商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA
            'version'=>'1.0',//调用的接口版，本固定为：1.0

            'notify_url'=>$config['notify_url'],//支付宝服务器主动通知商户服务器里指定的页面http/https路径
            'return_url'=>$config['return_url'],//HTTP/HTTPS开头字符串
            'timestamp'=>current_time('mysql'),//发送请求的时间，格式"yyyy-MM-dd HH:mm:ss"

            'biz_content'=>wp_json_encode($biz),//请求参数的集合，最大长度不限，除公共参数外所有请求参数都必须放在这个参数中传递
        );

        if(self::is_mobile() && isset($config['h5']) && $config['h5']){
            $param['method'] = 'alipay.trade.wap.pay';
        }


        //'sign'=>'',//商户请求参数的签名串，详见签名
        //print_r($param);
        $param['sign'] = self::sign($param);



        //print_r($param);
        echo '<form id="alipaysubmit" name="alipaysubmit" action="https://openapi.alipay.com/gateway.do?charset=utf-8" method="post">';

        foreach ($param as $k=>$v){
            if($v === '' || $v === null)continue;
            echo  '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'" />';
        }
        echo '<input type="submit" value="ok" style="display:none;" />';

        echo "<script>document.forms['alipaysubmit'].submit();</script>";

        //return $sHtml;

    }

    public static function verify($data,$sign=null){
        if(is_array($data)){
            self::txt_log(print_r($data,true));

            ksort($data);
            if(isset($data['sign']) && !$sign){
                $sign = $data['sign'];
                unset($data['sign']);
            }
            if(isset($data['sign_type'])){
                unset($data['sign_type']);
            }

            $param = array();
            foreach($data as $k=>$v){
                if($v==='' || $v===null)continue;
                $param[] = $k.'='.$v;
            }
            $data = implode('&',$param);
        }
        //print_r($data);
        //print_r(self::$config);

        self::txt_log($data);


        $key = openssl_get_publickey(self::$config['alipay_key']);
        if(!$key){
            return false;
        }

        //print_r($sign);

        $result = 1 === openssl_verify($data, base64_decode($sign), $key, OPENSSL_ALGO_SHA256);
        //print_r([$result]);
        self::txt_log(print_r([$result],true));
        openssl_free_key($key);

        return $result;
    }

    /**
     * 私钥加密数据
     * @param $data
     * @return bool|string
     */
    public static function sign($data){
        ksort($data);

        $param = array();
        foreach($data as $k=>$v){
            if($v==='' || $v===null)continue;
            $param[] = $k.'='.$v;
        }
        $data = implode('&',$param);


        $key = openssl_get_privatekey(self::$config['private_key']);
        if(!$key){
            return false;
        }
        openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        return base64_encode($sign);
    }

}


