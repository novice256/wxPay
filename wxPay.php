<?php

class wxPay{

    /**
     * 获取$prepayId，并签名传递给app
     */
    public function wxPay()
    {
        /**************************请求参数**************************/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = "WX888888888888888";

        //付款金额，必填
        $total_fee = '1';

        $body = '订单说明';

        //此处，根据具体情况自己生成订单记录


        /************************************************************/
        include_once("WxPay.pub.config.php");
        include_once("UnifiedOrderPub.php");

        // 获取prepay_id
        $unifiedOrder = new \UnifiedOrderPub();
        $unifiedOrder->setParameter("body", $body);//商品描述，文档里写着不能超过32个字符，否则会报错，经过实际测试，临界点大概在128左右，稳妥点最好按照文档，不要超过32个字符
        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        //此处为大坑之一，支付宝的单位的元，而微信的单位是分，此处金额数值小于1将会得不到$prepayId
        $unifiedOrder->setParameter("total_fee", $total_fee * 100);//总金额,单位为分
        $unifiedOrder->setParameter("notify_url", \WxPayConfPub::NOTIFY_URL);//通知地址
        $unifiedOrder->setParameter("trade_type","APP");//交易类型
        $prepayId = $unifiedOrder->getPrepayId();

        // 计算paySign

        //此处为大坑之二
        //以下为第二次签名，要严格按照以下格式进行加密，参数不能少
        $payPackage = [
            "appid" => \WxPayConfPub::APPID,
            "partnerid" => \WxPayConfPub::MCHID,
            "noncestr" => $unifiedOrder->createNoncestr(),
            "prepayid" => $prepayId,
            "package" => "Sign=WXPay",
            "timestamp" => "".time()
        ];
        $paySign = $unifiedOrder->getSign($payPackage);
        $payPackage['sign'] = $paySign;
        echo json_encode($payPackage);
    }

     /**
     * 微信退款
     * @param $order_id
     * @return bool
     * @throws \Exception
     */
    protected function wxRefund($order_id) {
        $order = M('appOrder')->where("id=%d", $order_id)->field('id,order_no,ali_transaction_no,amount')->find();

        /************************************************************/
        include_once "WxPay.pub.config.php";
        require_once "UnifiedOrderPub.php";

        // 获取prepay_id
        $unifiedOrder = new \UnifiedOrderPub();

        // 计算paySign
        $payPackage = [
            'appid' => \WxPayConfPub::APPID,
            'mch_id' => \WxPayConfPub::MCHID,
            'nonce_str' => $unifiedOrder->createNoncestr(),
            'transaction_id'=> $order['ali_transaction_no'],//微信订单号 1.2二选一,商户侧传给微信的订单号
            'out_refund_no' => $order['order_no'],//商户内部唯一退款单号
            'total_fee'=> intval($order['amount'] * 100),//总金额
            'refund_fee'=> intval($order['amount'] * 100)//退款金额
        ];
        $paySign = $unifiedOrder->getSign($payPackage);
        $payPackage['sign'] = $paySign;
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";//微信退款地址，post请求
        $xml = $unifiedOrder->arrayToXml($payPackage);

        //退款
        $data = $unifiedOrder->refund($xml,$url);

        if($data){
            $result = $unifiedOrder->xmlToArray($data);
            if(($result['return_code']=='SUCCESS') && ($result['result_code']=='SUCCESS')){
                //退款成功
                $rs['status'] = 1;
                $rs['msg'] = '退款成功';
                return $rs;
            }else if(($result['return_code']=='FAIL') || ($result['result_code']=='FAIL')){
                //退款失败
                //原因
                $reason = (empty($result['err_code_des'])?$result['return_msg']:$result['err_code_des']);
                $rs['status'] = 0;
                $rs['msg'] = $reason;
                return $rs;
            }else{
                //失败
                $rs['status'] = 0;
                $rs['msg'] = '退款成功';
                return $rs;
            }
        }else{
            $rs['status'] = 0;
            $rs['msg'] = '退款成功';
            return $rs;
        }
    }
}
