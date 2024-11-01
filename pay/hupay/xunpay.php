<?php



class WP_VK_XunhuPay
{

    public static $api = 'https://admin.xunhuweb.com';

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

        $db = self::db();
        $t  = $db->prefix.'vk_trade_log';
        $plugins = $param['plugins'];
        $pay_type = array('wxpay'=>2,'alipay'=>1);
        $d = array(
            'oid'=>$plugins['oid'],
            'order_no'=>$param['out_trade_no'],
            'trade_no'=>$param['transaction_id'],
            'trade_status'=>'SUCCESS',
            'type'=>2,
            'pay_type'=>$plugins['pay_type'],
            'buyer_id'=>$plugins['uid'],
            'total_amount'=>round($param['total_fee'] / 100,2),
            'gmt_payment'=>$param['order_date'],
            'created'=>current_time('mysql'),
        );

        $ret = $db->insert($t,$d);
        if($ret){
            $trade = $db->get_row($db->prepare("SELECT * FROM $t WHERE id = %d",$db->insert_id));
            do_action('vk_pay_success',$trade);
        }
    }

    public static function notify()
    {
        /**
         * 回调数据
         * @var array(
         *      'transaction_id'，微信支付宝等第三方平台交易号
                'mchid',平台分配商户号
                'order_id',平台返回订单号
                'total_fee',订单支付金额
                'out_trade_no',//用户端自主生成的订单号
                'time_end',//支付时间
                'attach',//自定义插件ID,与支付请求时一致
                'status'=>'complete'//complete：支付成功（目前仅支付成功后会回调通知）
         *   )
         */

        $data = [];

        $body = file_get_contents('php://input');
        if($body){
            $data = json_decode($body, true);
        }
        if(empty($data) ||  !is_array($data)){
            echo 'failed';
            self::txt_log('empty notify param');
            exit;
        }

        self::log($body);
        if(!isset($data['sign']) || !isset($data['return_code']) || $data['return_code'] != 'SUCCESS'){
            echo 'failed';
            self::txt_log('empty notify param');
            exit;
        }

        $attach = [];
        if(isset($data['attach']) && $data['attach']) {
            $attach = json_decode($data['attach'],true);
        }

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
        if($data['sign'] != $hash){
            //签名验证失败
            echo 'failed';
            self::txt_log('verify notify param error');
            exit;
        }




        if($data['status'] == 'complete'){
            self::txt_log('pay success');

            $data['plugins'] = $attach;
            $data['order_date'] = $data['time_end'];
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

    public static function tradeData($order_info, $pay_conf)
    {
        $attach = array('oid'=>$order_info->id,'pay_type'=>$order_info->pay_type,'uid'=>$order_info->uid,'type'=>$pay_conf['type']);

        //print_r($pay_conf);
        $nonce = str_shuffle(time());
        $param = [
            'mchid' => $pay_conf['appid'],//商户号
            'out_trade_no' => $order_info->order_no,//商户订单号
            'total_fee' => round($order_info->money * 100),//订单金额 单位：分
            'body' => $order_info->name,//商品名称
            'notify_url' => $pay_conf['notify_url'],//回调地址
            'type' => 'wechat',//（alipay：支付宝，wechat：微信）不填默认为wechat
            //'trade_type' => '',//签约支付宝2.0时必填，固定值：“WEB”
            'attach' => wp_json_encode($attach),//附加参数
            'nonce_str' => $nonce,//随机字符串
            //'sign' => '',//签名
        ];
        if($pay_conf['type'] == 'alipay'){
            $param['type'] = 'alipay';
        }

        return $param;
    }

    public static function qrPay($order_info, $pay_conf)
    {

        $param = self::tradeData($order_info, $pay_conf);

        if($pay_conf['type'] == 'alipay'){
            if($pay_conf['wap'] == 2){
                $param['trade_type'] = 'WEB';
            }
        }

        $sign = self::sign($param,$pay_conf['appsecret']);
        $param['sign'] = $sign;

        $url              = self::$api.'/pay/payment';

        try {

            $response     = self::http_post($url, wp_json_encode($param));
            //print_r([$response]);

            $result       = $response?json_decode($response,true):null;
            //print_r($result);
            if(!$result){
                throw new Exception('Internal server error',500);
            }
            if(isset($result['code']) && isset($result['msg'])){
                throw new Exception($result['msg'],$result['code']);
            }
            if(!isset($result['return_code']) || $result['return_code'] != 'SUCCESS'){
                throw new Exception($result['err_msg'],500);
            }

            $hash         = self::sign($result, $pay_conf['appsecret']);
            if(!isset( $result['sign'])|| $hash != $result['sign']){
                throw new Exception('支付安全校验失败',500);
            }

            $code_url = $result["code_url"];
            $back_url = $pay_conf['return_url'];
            $_ajax_nonce = wp_create_nonce('wp_ajax_vk_pay');

            include __DIR__ . '/page_pay.php';

            //print_r($result);
            exit;
        } catch (Exception $e) {


            //echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";

            wp_die(esc_html('支付异常,[code:'.$e->getCode().',err:'.$e->getMessage().']，请稍候再试。'));

        }

    }

    public static function cashierPay($order_info, $pay_conf)
    {
        $param = self::tradeData($order_info, $pay_conf);
        $param['redirect_url']  = $pay_conf['return_url'];
        $url = self::$api.'/pay/cashier';
        if($pay_conf['type'] == 'alipay'){
            $url = self::$api.'/alipaycashier';
        }

        $sign = self::sign($param,$pay_conf['appsecret']);
        $param['sign'] = $sign;


        header("Location: $url".http_build_query($param));
        exit();
    }

    public static function h5Pay($order_info, $pay_conf)
    {

        $param = self::tradeData($order_info, $pay_conf);
        $param['trade_type'] = 'WAP';

        if($pay_conf['type'] == 'alipay'){
            $param['type'] = 'alipay';
        }else{
            $param['type'] = 'wechat';
            $param['wap_url'] = home_url();
            $param['wap_name'] = get_bloginfo('name');
        }


        $sign = self::sign($param,$pay_conf['appsecret']);
        $param['sign'] = $sign;

        $url              = self::$api.'/pay/payment';

        try {

            $response     = self::http_post($url, wp_json_encode($param));
            //print_r([$response]);

            $result       = $response?json_decode($response,true):null;
            //print_r($result);
            if(!$result){
                throw new Exception('Internal server error',500);
            }
            if(isset($result['code']) && isset($result['msg'])){
                throw new Exception($result['msg'],$result['code']);
            }
            if(!isset($result['return_code']) || $result['return_code'] != 'SUCCESS'){
                throw new Exception($result['err_msg'],500);
            }

            $hash         = self::sign($result, $pay_conf['appsecret']);
            if(!isset( $result['sign'])|| $hash != $result['sign']){
                throw new Exception('支付安全校验失败',500);
            }

            header("Location:".$result['mweb_url']);
            exit();
        } catch (Exception $e) {


            //echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";

            wp_die(esc_html('支付异常,[code:'.$e->getCode().',err:'.$e->getMessage().']，请稍候再试。'));

        }

    }

    public static function webPay($order_info,$pay_conf){

        self::$config = $pay_conf;

        if($pay_conf['type'] == 'alipay'){
            if(self::isWebApp()){//手机
                if($pay_conf['wap'] == 2){
                    self::h5Pay($order_info, $pay_conf);
                }else{
                    self::cashierPay($order_info, $pay_conf);
                }
            }

            self::qrPay($order_info, $pay_conf);
        }else{//weixin
            if(self::is_wechat_app()){//weixin
                self::cashierPay($order_info, $pay_conf);
            }else if(self::isWebApp()){
                self::h5Pay($order_info, $pay_conf);
            }
            self::qrPay($order_info, $pay_conf);
        }
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public static function http_post($url,$data){

        $param = array(
            'sslverify'=>false,
            'timeout'=>30,
            'body' => $data,
            'headers'=>array(
                'content-type'=>'application/json; charset=utf-8',
                'referer'=>home_url(),
            ));
        $http = wp_remote_post($url,$param);

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
        if(isset($datas['sign']))unset($datas['sign']);
        $arg  = '';
        foreach ($datas as $key=>$val){
            if(is_null($val) || $val === ''){ continue; }
            $arg.="$key=$val&";
        }
        return strtoupper(md5($arg.'key='.$hashkey));
    }

    public static  function is_wechat_app(){
        return strripos($_SERVER['HTTP_USER_AGENT'],'micromessenger');
    }

}