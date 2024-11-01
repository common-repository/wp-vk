<?php
if(!defined('WP_VK_PATH'))exit(0);
$pay_conf['pay_name'] = $pay_conf['type'] == 'alipay' ? '支付宝' : '微信';
?>
<!DOCTYPE html>
    <html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($pay_conf['pay_name']);?>支付收银台</title>
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
    <script type='text/javascript' src='<?php echo esc_url(home_url());?>/wp-includes/js/jquery/jquery.js?ver=1.12.4-wp'></script>
</head>
<div class="wxpay-hd">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="28"><path fill="#09BB07" fill-rule="evenodd" d="M11.64 17.68c-.14.07-.3.1-.48.1-.4 0-.74-.21-.92-.54l-.08-.15-2.93-6.32c-.03-.08-.03-.15-.03-.22 0-.3.22-.52.52-.52a.6.6 0 01.33.11l3.45 2.43c.26.15.55.26.89.26.18 0 .37-.04.55-.11l16.17-7.13C26.22 2.2 21.44 0 16.02 0 7.2 0 0 5.92 0 13.23c0 3.96 2.15 7.57 5.52 10 .26.18.45.5.45.84a1 1 0 01-.08.33c-.25.99-.7 2.6-.7 2.68-.04.11-.08.26-.08.4 0 .3.22.52.52.52.11 0 .22-.04.3-.11l3.48-2.02c.26-.15.56-.26.86-.26.14 0 .33.04.48.08 1.63.47 3.41.73 5.23.73C24.8 26.42 32 20.51 32 13.2c0-2.21-.67-4.3-1.82-6.14L11.76 17.6l-.12.08z"/></svg>
    <span><?php echo esc_html($pay_conf['pay_name']);?>支付收银台</span>
</div>
<div class="content content-pay-wx">
    <div class="order-info-bar">
        <div class="order-number">购买：<?php echo esc_html($order_info->name);?></div>
        <div class="order-price">应付金额：<strong>&yen;<?php echo esc_html($order_info->money);?></strong>元</div>
    </div>
    <div class="scan-qr-box">
        <div class="main">
            <div class="pay-type">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="28"><path fill="#09BB07" fill-rule="evenodd" d="M11.64 17.68c-.14.07-.3.1-.48.1-.4 0-.74-.21-.92-.54l-.08-.15-2.93-6.32c-.03-.08-.03-.15-.03-.22 0-.3.22-.52.52-.52a.6.6 0 01.33.11l3.45 2.43c.26.15.55.26.89.26.18 0 .37-.04.55-.11l16.17-7.13C26.22 2.2 21.44 0 16.02 0 7.2 0 0 5.92 0 13.23c0 3.96 2.15 7.57 5.52 10 .26.18.45.5.45.84a1 1 0 01-.08.33c-.25.99-.7 2.6-.7 2.68-.04.11-.08.26-.08.4 0 .3.22.52.52.52.11 0 .22-.04.3-.11l3.48-2.02c.26-.15.56-.26.86-.26.14 0 .33.04.48.08 1.63.47 3.41.73 5.23.73C24.8 26.42 32 20.51 32 13.2c0-2.21-.67-4.3-1.82-6.14L11.76 17.6l-.12.08z"/></svg>
                <span><?php echo esc_html($pay_conf['pay_name']);?>支付</span>
            </div>
            <div class="tip-info">
                二维码有效期 <b>15</b> 分钟
            </div>
            <div class="qrcode-box"><img src="<?php echo $code_url; ?>" width="520" height="520" alt="二维码"></div>
            <div class="qr-tips">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="33" height="36"><defs><path id="a" d="M0 0h32.33v36H0z"/></defs><g fill="none" fill-rule="evenodd"><mask id="b" fill="#fff"><use xlink:href="#a"/></mask><path fill="#FFF" d="M23.15 33.08h6.55v-7.3h2.62V36h-9.17v-2.92zm6.55-22.86v-7.3h-6.55V0h9.18v10.22H29.7zM2.62 25.78v7.3h6.55V36H0V25.78h2.62zM9.17 2.92H2.62v7.3H0V0h9.17v2.92zM0 19.46h32.33v-2.92H0v2.92z" mask="url(#b)"/></g></svg>
                <span>请使用<?php echo esc_html($pay_conf['pay_name']);?>扫一扫<br>扫描二维码支付</span>
            </div>
        </div>
        <div class="tips-pic"><img src=<?php echo esc_url(WP_VK_URL.($pay_conf['type'] == 'alipay'?'assets/img/alipay-pic.png':'assets/img/wxp_pic.png')); ?>" alt="扫码提示"></div>
    </div>

</div>
    <script>
        (function(){
            var wb_wxpay_check = function(){
                jQuery.post('<?php echo esc_url(admin_url('/admin-ajax.php'));?>',
                    {'action':'vk_pay','_ajax_nonce':'<?php echo esc_attr($_ajax_nonce);?>','op':'check','oid':'<?php echo absint($order_info->id);?>'},
                    function(ret){
                        if(ret.code>1){
                            alert('获取支付状态失败。请确认是否支付成功。');
                        }else if(ret.code){
                            setTimeout(function(){wb_wxpay_check()},10000);
                        }else if(!ret.code){
                            setTimeout(function(){location.replace('<?php echo esc_url($back_url);?>');},1000);
                        }},'json');};
            setTimeout(function(){wb_wxpay_check()},10000);
        })()


    </script>
</body>
</html>