<?php

class notify
{

    /**
     * 异步回调，及配置文件填写的回调地址
     */
    public function notify(){
        //获取异步通知返回值
        $xml = file_get_contents('php://input');
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        include_once("WxPay.pub.config.php");
        include_once("UnifiedOrderPub.php");
        $unifiedOrder = new \UnifiedOrderPub();
        $sign_get = $arr['sign'];
        unset($arr['sign']);
        //大坑之三
        //验证签名，确认数值可靠性，验证方式为：将除了签名字段的返回值再次进行签名操作，并验证与签名字段的值是否一致
        $sign = $unifiedOrder->getSign($arr);
        //验签名。默认支持MD5
        if ( $sign === $sign_get) {//校验返回的订单金额是否与商户侧的订单金额一致。修改订单表中的支付状态。
            //订单状态
            $result_code = $arr['result_code'];

            if ($result_code == 'SUCCESS') {
                //订单状态修改，添加记录，自行操作！

                //微信会重复发送八次回调信息，确认成功后回复success后,微信将不再发送
                $this->returnMsg('SUCCESS', '订单支付验证成功！');
                die();
            } else {

            }
        }else{
            $this->returnMsg('Fail', '订单支付验证失败！');
            die();
        }
    }

    function returnMsg($return_code,$return_msg){
        $return = ['return_code'=>$return_code,'return_msg'=>$return_msg];
        $xml = '<xml>';
        foreach($return as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        return $xml;
    }
}