<?php
namespace app\common\extend\pay;

class Epay {

    public $name = '易支付';
    public $ver = '1.0';

    public function submit($user,$order,$param)
    {
        $pay_type = 0;
        $pay_type_map = [1 => 'alipay', 2 => 'qqpay', 3 => 'wxpay',];
        if(!empty($param['paytype']) && isset($pay_type_map[$param['paytype']])){
            $pay_type = $pay_type_map[$param['paytype']];
        }
        //组装参数
        $epay_config = $GLOBALS['config']['pay']['epay'];
        $data = array(
            "pid"          => trim($epay_config['appid']),//你的商户ID
            "type"         => $pay_type,//1支付宝支付 3微信支付 2QQ钱包
            "notify_url"   => $GLOBALS['http_type'] . $_SERVER['HTTP_HOST'] . '/index.php/payment/notify/pay_type/epay',//通知地址
            "return_url"   => $GLOBALS['http_type'] . $_SERVER['HTTP_HOST'] . '/index.php/payment/notify/pay_type/epay',//跳转地址
            "out_trade_no" => $order['order_code'], //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
            "name"         => '积分充值（UID：'.$user['user_id'].'）',
            "money"        => (float)$order['order_price'],//金额100元
        );
        // 跳转至收银台，选择其他方式
        if (empty($data['type'])) {
            unset($data['type']);
        }
        ksort($data); //重新排序$data数组
        reset($data); //内部指针指向数组中的第一个元素

        $sign = ''; //初始化需要签名的字符为空
        $urls = ''; //初始化URL参数为空

        foreach ($data as $key => $val) { //遍历需要传递的参数
            if ($val == ''||$key == 'sign') continue; //跳过这些不参数签名
            if ($sign != '') { //后面追加&拼接URL
                $sign .= "&";
                $urls .= "&";
            }
            $sign .= "$key=$val"; //拼接为url参数形式
            $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值
        }

        $query = $urls . '&sign='.md5($sign.trim( $epay_config['appkey'] )); //创建订单所需的参数
        $url = $epay_config['api_url'] . "submit.php?{$query}"; //支付页面
        mac_redirect($url);
    }

    public function notify()
    {
        $param = $_REQUEST;
        // $param['trade_no'] 这是付款人的唯一身份标识或订单ID
        // $param['out_trade_no'] 这是流水号 没有则表示没有付款成功 流水号不同则为不同订单
        // $param['money'] 这是付款金额

        unset($param['/payment/notify/pay_type/epay']);
        unset($param['paytype']);
        unset($param['s']);

        ksort($param); //排序post参数
        reset($param); //内部指针指向数组中的第一个元素
        $sign = '';
        foreach ($param as $key => $val) {
            if ($val == '') continue; //跳过空值
            if ($key != 'sign' && $key != 'sign_type') { //跳过sign
                $sign .= "$key=$val&"; //拼接为url参数形式
            }
        }

        // 异步通知：POST，必须返回纯文本 success/fail 给易支付服务器
        // 同步跳转：GET，用户浏览器打开，应提示并跳转订单页，避免只显示 success 空白页
        $isServerNotify = isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';

        $epay_config = $GLOBALS['config']['pay']['epay'];
        if (!$param['out_trade_no'] || md5(substr($sign, 0, -1) . trim($epay_config['appkey'])) != $param['sign']) {
            if ($isServerNotify) {
                echo 'fail';
            } else {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . lang('index/payment_status') . '</title></head><body><script>alert("支付验证失败，如已扣款请联系客服");location.href="' . MAC_PATH . '";</script></body></html>';
            }
            exit;
        }

        $orderCode = $param['out_trade_no'];
        $tradeNo = isset($param['trade_no']) ? $param['trade_no'] : '';
        $amount = isset($param['money']) ? floatval($param['money']) : 0;
        // 根据前缀区分充值订单与商品订单：PAY* 为充值，GDS* 为商品
        if (strpos($orderCode, 'GDS') === 0) {
            $res = model('GoodsOrder')->notify($orderCode, 'epay', $tradeNo, $amount, $param);
        } else {
            $res = model('Order')->notify($orderCode, 'epay');
        }
        if ($res['code'] > 1) {
            if ($isServerNotify) {
                echo 'fail2';
            } else {
                $msg = isset($res['msg']) ? $res['msg'] : 'fail';
                $msg = str_replace(["\r", "\n", '"'], ['', '', "'"], strip_tags($msg));
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>提示</title></head><body><script>alert("' . $msg . '");location.href="' . MAC_PATH . '";</script></body></html>';
            }
            exit;
        }

        if ($isServerNotify) {
            echo 'success';
            exit;
        }

        // 浏览器同步返回：弹窗后跳转到对应订单页
        $jumpUrl = MAC_PATH;
        if (strpos($orderCode, 'GDS') === 0) {
            $ord = model('GoodsOrder')->infoData(['go_code' => $orderCode]);
            if ($ord['code'] == 1 && !empty($ord['info']['go_id'])) {
                // 与积分支付成功跳转一致：进入该笔商城订单详情；也可改为 mac_url('user/goods_orders') 进购买记录列表
                $jumpUrl = mac_url('goods/order', ['id' => $ord['info']['go_id']]);
            }
        } else {
            $jumpUrl = mac_url('user/plog');
        }
        mac_alert_url(lang('index/payment_ok'), $jumpUrl);
    }
}
