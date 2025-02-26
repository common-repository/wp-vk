<?php


/**
 * 
 * JSAPI支付实现类
 *
 */
class VK_Wxpay_Jsapi
{
	/**
	 * 
	 * 网页授权接口微信服务器返回的数据，返回样例如下
	 * {
	 *  "access_token":"ACCESS_TOKEN",
	 *  "expires_in":7200,
	 *  "refresh_token":"REFRESH_TOKEN",
	 *  "openid":"OPENID",
	 *  "scope":"SCOPE",
	 *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	 * }
	 * 其中access_token可用于获取共享收货地址
	 * openid是微信支付jsapi支付接口必须的参数
	 * @var array
	 */
	public $data = null;

    /**
     *
     * @var VK_Wxpay_Config
     */
	public $config = null;


	public function __construct($conf)
    {
        $this->config = $conf;
    }


    /**
	 * 
	 * 通过跳转获取用户的openid，跳转流程如下：
	 * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
	 * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
	 * 
	 * @return string 用户的openid
	 */
	public function GetOpenid()
	{
		//通过code获得openid
		if (!isset($_GET['code'])){
			//触发微信返回code码
            $url = $this->_CreateOauthUrlForCode();
            //error_log($url,3,__DIR__.'/log.txt');
			Header("Location: ".$url);
			exit();
		} else {
			//获取code码，以获取openid
		    $code = $_GET['code'];
            //error_log($code,3,__DIR__.'/log.txt');
            $openid = '';
            try{
                $openid = $this->getOpenidFromMp($code);
            }catch (Exception $ex){
                wp_die(esc_html('获取open_id出错。【'.$ex->getMessage().'】'));
            }

			return $openid;
		}
	}
	
	/**
	 * 
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 * 
	 * @return string json数据，可直接填入js函数作为参数
	 */
	public function GetJsApiParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new WxPayException("参数错误");
		}

		$jsapi = new WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(WxPayApi::getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);

		$jsapi->SetPaySign($jsapi->MakeSign($this->config));
		$parameters = wp_json_encode($jsapi->GetValues());
		return $parameters;
	}
	
	/**
	 * 
	 * 通过code从工作平台获取openid机器access_token
	 * @param string $code 微信跳转回来带上的code
	 * @throws Exception
	 * @return openid
	 */
	public function GetOpenidFromMp($code)
	{
		$url = $this->__CreateOauthUrlForOpenid($code);

		$http = wp_remote_get($url,['sslverify'=>false]);

		if(is_wp_error($http)){
		    throw new Exception(esc_html('network error['.$http->get_error_message().']'),100);
        }
		$code = wp_remote_retrieve_response_code($http);
		if($code != 200){
		    throw new Exception(esc_html('wx api response code error [code:'.$code.']'));
        }
		$body = wp_remote_retrieve_body($http);
		if(!$body){
            throw new Exception('wx api response empty body');
        }
		$data = json_decode($body,true);
		if(!$data){
            throw new Exception(esc_html('wx api response data decode error.[body:'.$body.']'));
        }
		if(isset($data['error'])){
            throw new Exception(esc_html('wx api error.[err:'.$data['error'].']'));
        }
		if(!isset($data['openid'])){
            throw new Exception('wx request open_id fail.');
        }
		return $data['openid'];
		/*//初始化curl
		$ch = curl_init();

		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
		//curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);


		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		$data = json_decode($res,true);
        //error_log($res,3,__DIR__.'/log.txt');;
		$this->data = $data;
		$openid = $data['openid'];
		return $openid;*/
	}
	
	/**
	 * 
	 * 拼接签名字符串
	 * @param array $urlObj
	 * 
	 * @return string 返回已经拼接好的字符串
	 */
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	
	/**
	 * 
	 * 获取地址js参数
	 * 
	 * @return string 获取共享收货地址js函数需要的参数，json格式可以直接做参数使用
	 */
	public function GetEditAddressParameters()
	{	
		$config = $this->config;
		$getData = $this->data;
		$data = array();
		$data["appid"] = $config->GetAppId();
		$data["url"] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$time = time();
		$data["timestamp"] = "$time";
		$data["noncestr"] = WxPayApi::getNonceStr();
		$data["accesstoken"] = $getData["access_token"];
		ksort($data);
		$params = $this->ToUrlParams($data);
		$addrSign = sha1($params);
		
		$afterData = array(
			"addrSign" => $addrSign,
			"signType" => "sha1",
			"scope" => "jsapi_address",
			"appId" => $config->GetAppId(),
			"timeStamp" => $data["timestamp"],
			"nonceStr" => $data["noncestr"]
		);
		$parameters = wp_json_encode($afterData);
		return $parameters;
	}
	
	/**
	 * 
	 * 构造获取code的url连接
	 * @param string $redirectUrl 微信服务器回跳的url，需要url编码
	 * 
	 * @return string 返回构造好的url
	 */
	private function _CreateOauthUrlForCode()
	{
		$urlObj["appid"] = $this->config->GetAppId();
		$urlObj["redirect_uri"] = urlencode($this->config->GetRedirectUri());
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_base";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->ToUrlParams($urlObj);

		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}
	
	/**
	 * 
	 * 构造获取open和access_toke的url地址
	 * @param string $code，微信跳转带回的code
	 * 
	 * @return string 请求的url
	 */
	private function __CreateOauthUrlForOpenid($code)
	{
		$urlObj["appid"] = $this->config->GetAppId();
		$urlObj["secret"] = $this->config->GetAppSecret();
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->ToUrlParams($urlObj);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}
}
