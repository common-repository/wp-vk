<?php
if(!defined('WP_VK_PATH'))exit(0);

$post_url = get_permalink($order_info->pid);
if(strpos($post_url,'?')){
    $post_url = $post_url . '&_t='.time();
}else{
    $post_url = $post_url .'?_t='.time();
}

$pay['name'] = '微信';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($pay['name']);?>支付收银台</title>
    <style>
        body{padding:0;margin:0;background: #f3f4f5;}
        .wxpay-hd{ height: 75px; line-height: 75px; text-align: center; font-size: 30px; font-weight: 300; border-bottom: 2px solid #eee; background: #fff; }
        .wxpay-hd svg{display:inline-block; vertical-align: middle;}
        .wxpay-hd span{display:inline-block; vertical-align: middle; margin-left:10px;}
        .content-pay-wx{padding-top:60px}.order-info-bar{line-height:45px;padding-bottom:10px;padding-top:30px;max-width:960px;margin-left:auto;margin-right:auto;font-size:16px;display:-webkit-box;display:-ms-flexbox;display:flex}.order-info-bar .order-number{-webkit-box-flex:1;-ms-flex:1;flex:1}.order-info-bar .order-price strong{font-weight:700;font-size:20px;color:#d8674e}.scan-qr-box{position:relative;background:#fff;border:1px solid #f0f0f0;-webkit-box-shadow:0 7px 8px 0 rgba(0,0,0,.11);box-shadow:0 7px 8px 0 rgba(0,0,0,.11);max-width:960px;margin-left:auto;margin-right:auto;text-align:center;padding-bottom:60px;padding-top:60px}.scan-qr-box .main{display:inline-block;vertical-align:top;width:375px;text-align:center}.scan-qr-box .tips-pic{display:inline-block;vertical-align:top;max-width:325px;margin-left:45px}.scan-qr-box .tips-pic img{display:block;width:100%;height:auto}.scan-qr-box .back-pre{line-height:20px;padding-left:30px;padding-bottom:15px;position:absolute;left:0;bottom:0;font-size:14px}.scan-qr-box .pay-type{line-height:30px;font-size:0}.scan-qr-box .pay-type span,.scan-qr-box .pay-type svg{display:inline-block;vertical-align:middle}.scan-qr-box .pay-type span{font-size:16px;padding-left:10px}.scan-qr-box .tip-info{line-height:20px;padding-top:10px}.scan-qr-box .tip-info b{font-weight:700;color:#d0021b}.scan-qr-box .qrcode-box{display:block;background:#fff;border:1px solid #adadad;-webkit-box-sizing:border-box;box-sizing:border-box;width:260px;height:260px;margin:10px auto}.scan-qr-box .qrcode-box img{display:block;width:100%;height:auto}.scan-qr-box .qr-tips{background:#09bb07;width:260px;height:65px;line-height:65px;margin-left:auto;margin-right:auto;text-align:center;font-size:0}.scan-qr-box .qr-tips svg{display:inline-block;vertical-align:middle}.scan-qr-box .qr-tips span{display:inline-block;vertical-align:middle;line-height:22px;color:#fff;font-size:14px;text-align:left;padding-left:10px}
        .qr-tips-wx{font-size:12px;color:#fff;background-color:#333;padding:10px;}
        @media (max-width: 1000px) {
            .wxpay-hd{height:60px;line-height:60px;font-size:20px;}
            .content-pay-wx{padding:0 20px;} .scan-qr-box .tips-pic{display:none;}
            .order-info-bar{display:block; padding-top:20px; line-height:22px; font-size:14px;}
            .scan-qr-box{padding-top:20px;}
            .scan-qr-box .main{width:auto;}
            .scan-qr-box .tip-info{display:none;}
            .scan-qr-box .pay-type svg{display:none;}
        }
        .weixin-mode .scan-qr-box{display:none;}
        .weixin-mode .wb-wxpay-tips,
        .weixin-mode .order-info-bar{display:block;}
        .wb-wxpay-btns{display:none;}
        .weixin-mode .wb-wxpay-btns{display:block; text-align:center;padding-top:30px;}
        .wb-wxpay-btns .wb-btn{
            position: relative;
            display: block;
            margin-left: auto;
            margin-right: auto;
            padding-left: 14px;
            padding-right: 14px;
            box-sizing: border-box;
            font-size: 18px;
            text-align: center;
            text-decoration: none;
            color: #FFFFFF;
            line-height: 2.55555556;
            border-radius: 5px;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .wb-wxpay-btns .wb-btn-primary{background-color: #07c160;}
        .wb-wxpay-btns .wb-btn-primary:active{color: rgba(255, 255, 255, 0.6); background-color: #06ad56;}
        .wb-wxpay-btns .wb-btn-outlined{color: #07c160; border: 1px solid #07c160;}
        .wb-wxpay-btns .wb-btn-outlined:active{color:#06ad56; border-color: #06ad56;}
        .wb-wxpay-tips{display:none; font-size:12px; color:#999; padding-bottom:20px; text-align:center;}
    </style>
</head>
<body class="weixin-mode">

<div class="wxpay-hd">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="28"><path fill="#09BB07" fill-rule="evenodd" d="M11.64 17.68c-.14.07-.3.1-.48.1-.4 0-.74-.21-.92-.54l-.08-.15-2.93-6.32c-.03-.08-.03-.15-.03-.22 0-.3.22-.52.52-.52a.6.6 0 01.33.11l3.45 2.43c.26.15.55.26.89.26.18 0 .37-.04.55-.11l16.17-7.13C26.22 2.2 21.44 0 16.02 0 7.2 0 0 5.92 0 13.23c0 3.96 2.15 7.57 5.52 10 .26.18.45.5.45.84a1 1 0 01-.08.33c-.25.99-.7 2.6-.7 2.68-.04.11-.08.26-.08.4 0 .3.22.52.52.52.11 0 .22-.04.3-.11l3.48-2.02c.26-.15.56-.26.86-.26.14 0 .33.04.48.08 1.63.47 3.41.73 5.23.73C24.8 26.42 32 20.51 32 13.2c0-2.21-.67-4.3-1.82-6.14L11.76 17.6l-.12.08z"/></svg>
    <span><?php echo esc_html($pay['name']);?>支付收银台</span>
</div>
<div class="content content-pay-wx">

    <div class="order-info-bar">
        <div class="order-number"> 购买：<?php echo esc_html($order_info->name);?></div>
        <div class="order-price">应付金额：<strong>&yen;<?php echo esc_html($order_info->money);?></strong>元</div>
    </div>


    <div class="wb-wxpay-btns">
        <a class="wb-btn wb-btn-primary" id="go_wx_h5_pay" href="<?php echo esc_url($code_url);?>">微信支付</a>
        <a class="wb-btn wb-btn-outlined" onclick="return cancel_pay();">取消支付</a>
    </div>
    <div class="wb-wxpay-tips">支付完成后，如需售后服务请联系客服</div>

</div>

<script type="text/javascript">

    function cancel_pay() {


        location.href='<?php echo esc_url($post_url);?>';

        return false;
    }
    function callpay()
    {
        document.getElementById('go_wx_h5_pay').click();
    }
    //callpay();

</script>
</body>
</html>