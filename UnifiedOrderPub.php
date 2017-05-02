<?php

/**
 * 统一支付接口类
 */
class UnifiedOrderPub
{
	function __construct()
	{
		//设置接口链接
		$this->url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		//设置curl超时时间
		$this->curl_timeout = WxPayConfPub::CURL_TIMEOUT;
	}

	/**
	 * 	作用：设置请求参数
	 */
	function setParameter($parameter, $parameterValue)
	{
		$this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}

	function trimString($value)
	{
		$ret = null;
		if (null != $value)
		{
			$ret = $value;
			if (strlen($ret) == 0)
			{
				$ret = null;
			}
		}
		return $ret;
	}

	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		//检测必填参数
		if($this->parameters["out_trade_no"] == null)
		{
			$this->error("缺少统一支付接口必填参数out_trade_no！"."<br>");
		}elseif($this->parameters["body"] == null){
			$this->error("缺少统一支付接口必填参数body！"."<br>");
		}elseif ($this->parameters["total_fee"] == null ) {
			$this->error("缺少统一支付接口必填参数total_fee！"."<br>");
		}elseif ($this->parameters["notify_url"] == null) {
			$this->error("缺少统一支付接口必填参数notify_url！"."<br>");
		}elseif ($this->parameters["trade_type"] == null) {
			$this->error("缺少统一支付接口必填参数trade_type！"."<br>");
		}
		$this->parameters["appid"] = WxPayConfPub::APPID;//公众账号ID
		$this->parameters["mch_id"] = WxPayConfPub::MCHID;//商户号
		$this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];//终端ip
		if (!isset($this->parameters["nonce_str"])) {
			$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
		}
		//此处为第一次签名，参与签名的参数参照微信文档
		$this->parameters["sign"] = $this->getSign($this->parameters);//签名
		return  $this->arrayToXml($this->parameters);
	}

	/**
	 * 	作用：生成签名
	 */
	public function getSign($Obj)
	{
		foreach ($Obj as $k => $v)
		{
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String."&key=".WxPayConfPub::KEY;
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}

	/**
	 * 	作用：产生随机字符串，不长于32位
	 */
	public function createNoncestr( $length = 32 )
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}

	/**
	 * 	作用：array转xml
	 */
	function arrayToXml($arr)
	{
		$xml = "<xml>";
		foreach ($arr as $key=>$val)
		{
			if (is_numeric($val))
			{
				$xml.="<".$key.">".$val."</".$key.">";

			}
			else
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
		}
		$xml.="</xml>";
		return $xml;
	}

	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
			if($urlencode)
			{
				$v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0)
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}

	/**
	 * 获取prepay_id
	 */
	function getPrepayId()
	{
		$this->postXml();
		$this->result = $this->xmlToArray($this->response);
		$prepay_id = $this->result["prepay_id"];
		return $prepay_id;
	}

	/**
	 * 	作用：将xml转为array
	 */
	public function xmlToArray($xml)
	{
		//将XML转为array
		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $array_data;
	}

	/**
	 * 	作用：post请求xml
	 */
	function postXml()
	{
		$xml = $this->createXml();
		$this->response = $this->postXmlCurl($xml,$this->url,$this->curl_timeout);
		return $this->response;
	}

	/**
	 * 	作用：以post方式提交xml到对应的接口url
	 */
	public function postXmlCurl($xml,$url,$second=30)
	{
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data)
		{
			curl_close($ch);
			return $data;
		}
		else
		{
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error"."<br>";
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}
}
