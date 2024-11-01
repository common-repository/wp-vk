<?php

/**
 * Class VK_Hupay
 *
 */

require_once __DIR__.'/xunpay.php';


class VK_Hupay
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
        {
            "trade_order_id":"19112309425538",
            "total_fee":"0.10",
            "transaction_id":"4200000452201911236648686816",
            "open_order_id":"20201085773",
            "order_title":"一个月特价",
            "status":"OD",
            "plugins":"{"oid":"10","pay_type":"2","type":"wxpay","uid":"1"}",
            "nonce_str":"1324478357",
            "time":"1574473382",
            "appid":"2147483647",
            "hash":"6ace7cbe58ed0a6f4aa94501c74f915b"
        }
        array(
        'trade_order_id'，商户网站订单ID
         'total_fee',订单支付金额
         'transaction_id',//支付平台订单ID
         'order_date',//支付时间
         'plugins',//自定义插件ID,与支付请求时一致
         'status'=>'OD'//订单状态，OD已支付，WP未支付
        )*/

        $db = self::db();
        if(!$param['order_date']){
            $param['order_date'] = gmdate('Y-m-d H:i:s',$param['time']);
        }
        $t  = $db->prefix.'vk_trade_log';
        $plugins = $param['plugins'];
        $pay_type = array('wxpay'=>2,'alipay'=>1);
        $d = array(
            'oid'=>$plugins['oid'],
            'order_no'=>$param['trade_order_id'],
            'trade_no'=>$param['transaction_id'],
            'trade_status'=>'SUCCESS',
            'type'=>2,
            'pay_type'=>$plugins['pay_type'],
            'buyer_id'=>$plugins['uid'],
            'total_amount'=>$param['total_fee'],
            'gmt_payment'=>$param['order_date'],
            'created'=>current_time('mysql'),
        );

        $ret = $db->insert($t,$d);
        if($ret){
            $trade = $db->get_row($db->prepare("SELECT * FROM $t WHERE id = %d",$db->insert_id));
            do_action('vk_pay_success',$trade);
        }
    }

    public static function notify(){

        $pay_conf = WP_VK_Order::pay_conf();
        if(isset($pay_conf['ver']) && $pay_conf['ver'] == 2){
            WP_VK_XunhuPay::notify();
            exit();
        }

        $data = $_POST;
        foreach ($data as $k=>$v){
            $data[$k] = trim(stripslashes($v));
        }

        self::log($data);

        if(!isset($data['hash']) || !isset($data['trade_order_id']) || !isset($data['plugins'])){
            echo 'failed';
            self::txt_log('empty notify param');
            exit;
        }

        $plugins = json_decode($data['plugins'],true);


        if(!isset($pay_conf[$plugins['type']])){
            echo 'failed';
            self::txt_log('empty pay config');
            exit;
        }

        $config = $pay_conf[$plugins['type']];



        $appid              = $config['appid'];
        $appsecret          = $config['appsecret'];

        $hash = self::sign($data,$appsecret);
        if($data['hash'] != $hash){
            //签名验证失败
            echo 'failed';
            self::txt_log('verify notify param error');
            exit;
        }


        if($data['status']=='OD'){
            self::txt_log('pay success');

            $data['plugins'] = $plugins;
            self::trade_success($data);

        }else{
            //处理未支付的情况
            self::txt_log('pay status:'.$data['status']);
        }
        //以下是处理成功后输出，当支付平台接收到此消息后，将不再重复回调当前接口
        echo 'success';
        exit;
    }

    public static function is_mobile()
    {
        if(function_exists('wp_is_mobile')){
            return wp_is_mobile();
        }else{
            return preg_match('#(mobile|android|iphone)#i',$_SERVER['HTTP_USER_AGENT']);
        }
    }

    public static function webPay($order_info,$pay_conf){
        if(isset($pay_conf['ver']) && $pay_conf['ver'] == 2){
            if(!empty($pay_conf['api'])){
                WP_VK_XunhuPay::$api = $pay_conf['api'];
            }
            WP_VK_XunhuPay::webPay($order_info, $pay_conf);
            return;
        }

        self::$config = $pay_conf;
        $notify_url = $pay_conf['notify_url'];
        $return_url = $pay_conf['return_url'];

        $callback_url = $pay_conf['callback_url'];

        $plugins = array('oid'=>$order_info->id,'pay_type'=>$order_info->pay_type,'uid'=>$order_info->uid,'type'=>$pay_conf['type']);

        $appsecret          = self::$config['appsecret'];
        $data=array(
            'version'   => '1.1',//固定值，api 版本，目前暂时是1.1
            'appid'     => self::$config['appid'], //必须的，APPID
            'trade_order_id'=> $order_info->order_no, //必须的，网站订单ID，唯一的，匹配[a-zA-Z\d\-_]+
            'total_fee' => $order_info->money,//人民币，单位精确到分(测试账户只支持0.1元内付款)
            'title'     => $order_info->name, //必须的，订单标题，长度32或以内
            'time'      => current_time('timestamp'),//必须的，当前时间戳，根据此字段判断订单请求是否已超时，防止第三方攻击服务器
            'notify_url'=>  $notify_url, //必须的，支付成功异步回调接口
            'return_url'=> $return_url,//必须的，支付成功后的跳转地址
            'callback_url'=>$callback_url,//必须的，支付发起地址（未支付或支付失败，系统会会跳到这个地址让用户修改支付信息）
            'nonce_str' => str_shuffle(time()),//必须的，随机字符串，作用：1.避免服务器缓存，2.防止安全密钥被猜测出来
            'plugins'=>wp_json_encode($plugins),//可选。备注字段，可以传入一些备注数据，回调时原样返回
        );

        if($order_info->pay_type == 2 && self::is_mobile() && !preg_match('#MicroMessenger#',$_SERVER['HTTP_USER_AGENT'])){
            $data['type'] = 'WAP';
            $data['wap_url'] = home_url();
            $data['wap_name'] = get_option('blogname');
            //print_r($data);exit();
        }

        $data['hash']     = self::sign($data,$appsecret);
        /**
         * 个人支付宝/微信官方支付，支付网关：https://api.xunhupay.com
         * 微信支付宝代收款，需提现，支付网关：https://pay.wordpressopen.com
         */
        $url              = 'https://api.xunhupay.com/payment/do.html';
        if(!empty($pay_conf['api'])){
            $url = $pay_conf['api'];
        }
        try {
            $response     = self::http_post($url, wp_json_encode($data));
            /**
             * 支付回调数据
             * @var array(
             *      order_id,//支付系统订单ID
             *      url//支付跳转地址
             *  )
             */
            $result       = $response?json_decode($response,true):null;
            if(!$result){
                throw new Exception('Internal server error',500);
            }

            $hash         = self::sign($result,$appsecret);
            if(!isset( $result['hash'])|| $hash!=$result['hash']){
                throw new Exception('Invalid hash',40029);
            }

            if($result['errcode']!=0){
                throw new Exception($result['errmsg'],$result['errcode']);
            }

            if(self::isWebApp()){
                $pay_url =$result['url'];
                header("Location: $pay_url");
            }else{
                $code_url = $result["url_qrcode"];
                $back_url = $pay_conf['return_url'];
                $_ajax_nonce = wp_create_nonce('wp_ajax_vk_pay');
                //$back_url = wb_url('member') . '#/my-order';
                include __DIR__ . '/page_pay.php';
            }
            exit;
        } catch (Exception $e) {


            //echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";
            //TODO:处理支付调用异常的情况

            wp_die(esc_html('支付异常,[code:'.$e->getCode().',err:'.$e->getMessage().']，请稍候再试。'));

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

                throw new Exception(esc_html("error response [code:".$code.']'.wp_remote_retrieve_body($http)));
            }

        }
        return wp_remote_retrieve_body($http);
    }

    public static function isWebApp(){
        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            return false;
        }

        $u=strtolower($_SERVER['HTTP_USER_AGENT']);
        if($u==null||strlen($u)==0){
            return false;
        }

        preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/',$u,$res);

        if($res&&count($res)>0){
            return true;
        }

        if(strlen($u)<4){
            return false;
        }

        preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/',substr($u,0,4),$res);
        if($res&&count($res)>0){
            return true;
        }

        $ipadchar = "/(ipad|ipad2)/i";
        preg_match($ipadchar,$u,$res);
        return $res&&count($res)>0;
    }

    public static  function sign(array $datas,$hashkey){
        ksort($datas);
        reset($datas);

        $pre =array();
        foreach ($datas as $key => $data){
            if(is_null($data)||$data===''){continue;}
            if($key=='hash'){
                continue;
            }
            $pre[$key]=stripslashes($data);
        }

        $arg  = '';
        $qty = count($pre);
        $index=0;

        foreach ($pre as $key=>$val){
            $arg.="$key=$val";
            if($index++<($qty-1)){
                $arg.="&";
            }
        }

        return md5($arg.$hashkey);
    }

    public static  function is_wechat_app(){
        return strripos($_SERVER['HTTP_USER_AGENT'],'micromessenger');
    }

}