<?php



class VK_PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function Queryorder($config,$transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);


        $result = WxPayApi::orderQuery($config, $input);

        $this->txt_log("query:" . wp_json_encode($result));

        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    /**
     *
     * 回包前的回调方法
     * 业务可以继承该方法，打印日志方便定位
     * @param string $xmlData 返回的xml参数
     *
     **/
    public function LogAfterProcess($xmlData)
    {
        $this->txt_log("call back， return xml:" . $xmlData);
        return;
    }

    public function txt_log($msg){

        VK_Wxpay::txt_log($msg);
    }

    //重写回调处理函数
    /**
     * @param WxPayNotifyResults $data 回调解释出的参数
     * @param WxPayConfigInterface $config
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return bool true 回调出来完成不需要继续回调，false 回调处理未完成需要继续回调
     */
    public function NotifyProcess($objData, $config, &$msg)
    {
        $data = $objData->GetValues();
        $this->txt_log("notify process， data:" . wp_json_encode($data));
        //进行参数校验
        if(!array_key_exists("return_code", $data)
            ||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            return false;
        }
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }

        //进行签名验证
        try {
            $checkResult = $objData->CheckSign($config);
            if($checkResult == false){
                //签名错误
                $this->txt_log("签名错误...");
                return false;
            }
        } catch(Exception $e) {
            $this->txt_log('签名异常',$e->getCode(),$e->getMessage());
            return false;
        }


        //处理业务逻辑
        $this->txt_log("call back:" . wp_json_encode($data));

        $notfiyOutput = array();

        $this->txt_log('trade success');

        VK_Wxpay::trade_success($data);


        //查询订单，判断订单真实性
        /*if(!$this->Queryorder($config,$data["transaction_id"])){
            $msg = "订单查询失败";
            $this->txt_log("订单查询失败," . wp_json_encode($data));
            return false;
        }*/


        return true;
    }
}


