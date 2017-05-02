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
}
