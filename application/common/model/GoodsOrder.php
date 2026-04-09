<?php
namespace app\common\model;

use think\Db;

class GoodsOrder extends Base
{
    // 对应数据表（不含前缀）
    protected $name = 'goods_order';

    protected $createTime = '';
    protected $updateTime = '';

    protected $auto   = [];
    protected $insert = [];
    protected $update = [];

    /**
     * 会员中心商品订单列表（关联商品名称）
     */
    public function listData($where, $order, $page = 1, $limit = 20, $start = 0)
    {
        $page  = $page > 0 ? (int)$page : 1;
        $limit = $limit ? (int)$limit : 20;
        $start = $start ? (int)$start : 0;
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $limitStr = ($limit * ($page - 1) + $start) . ',' . $limit;

        $total = Db::name('goods_order')->alias('go')->where($where)->count();
        $list  = Db::name('goods_order')->alias('go')
            ->field('go.*,g.goods_name,g.goods_pic')
            ->join('__GOODS__ g', 'go.goods_id = g.goods_id', 'LEFT')
            ->where($where)
            ->order($order)
            ->limit($limitStr)
            ->select();

        return [
            'code'      => 1,
            'msg'       => lang('data_list'),
            'page'      => $page,
            'pagecount' => $limit > 0 ? ceil($total / $limit) : 0,
            'limit'     => $limit,
            'total'     => $total,
            'list'      => $list,
        ];
    }

    public function infoData($where, $field = '*')
    {
        if (empty($where) || !is_array($where)) {
            return ['code' => 1001, 'msg' => lang('param_err')];
        }
        $info = $this->field($field)->where($where)->find();
        if (empty($info)) {
            return ['code' => 1002, 'msg' => lang('obtain_err')];
        }
        $info = $info->toArray();
        return ['code' => 1, 'msg' => lang('obtain_ok'), 'info' => $info];
    }

    public function saveData($data)
    {
        if (!empty($data['go_id'])) {
            $where = [];
            $where['go_id'] = ['eq', $data['go_id']];
            $res = $this->allowField(true)->where($where)->update($data);
        } else {
            $data['go_time'] = isset($data['go_time']) && $data['go_time'] > 0 ? $data['go_time'] : time();
            $res = $this->allowField(true)->insert($data);
        }
        if ($res === false) {
            return ['code' => 1002, 'msg' => lang('save_err') . '：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => lang('save_ok')];
    }

    /**
     * 创建商品订单
     * @param array  $user
     * @param array  $goods
     * @param int    $num
     * @param string $payType
     * @return array
     */
    public function createOrder($user, $goods, $num = 1, $payType = '', $extra = [])
    {
        $num = intval($num);
        if ($num < 1) {
            $num = 1;
        }

        if (empty($user['user_id']) || empty($goods['goods_id'])) {
            return ['code' => 1001, 'msg' => lang('param_err')];
        }

        if (isset($goods['goods_status']) && $goods['goods_status'] != 1) {
            return ['code' => 2001, 'msg' => lang('index/order_not')];
        }
        if (isset($goods['goods_stock']) && $goods['goods_stock'] > 0 && $goods['goods_stock'] < $num) {
            return ['code' => 2002, 'msg' => lang('index/order_not')];
        }

        $gk = isset($goods['goods_kind']) ? intval($goods['goods_kind']) : 1;
        if ($gk !== 2) {
            $gk = 1;
        }

        $priceSingle = floatval($goods['goods_price']);
        $pointsSingle = intval($goods['goods_points']);
        $totalPrice = sprintf('%.2f', $priceSingle * $num);
        $totalPoints = $pointsSingle * $num;

        $code = 'GDS' . mac_get_uniqid_code();

        $data = [];
        $data['user_id'] = $user['user_id'];
        $data['goods_id'] = $goods['goods_id'];
        $data['go_status'] = 0;
        $data['go_code'] = $code;
        $data['go_price'] = $totalPrice;
        $data['go_points'] = $totalPoints;
        $data['go_num'] = $num;
        $data['go_pay_type'] = $payType;
        $data['go_time'] = time();
        $data['go_pay_time'] = 0;
        $data['go_remark'] = '';
        $data['go_goods_kind'] = $gk;
        $data['go_ship_status'] = 0;
        $data['go_ship_time'] = 0;
        $data['go_express_name'] = '';
        $data['go_express_no'] = '';
        $data['go_address_id'] = 0;
        $data['go_receiver_name'] = '';
        $data['go_receiver_phone'] = '';
        $data['go_receiver_region'] = '';
        $data['go_receiver_address'] = '';
        $data['go_download_snapshot'] = '';

        if ($gk == 2 && !empty($extra['address']) && is_array($extra['address'])) {
            $a = $extra['address'];
            $data['go_address_id'] = isset($a['ua_id']) ? intval($a['ua_id']) : 0;
            $data['go_receiver_name'] = isset($a['ua_name']) ? (string)$a['ua_name'] : '';
            $data['go_receiver_phone'] = isset($a['ua_phone']) ? (string)$a['ua_phone'] : '';
            $data['go_receiver_region'] = isset($a['ua_region']) ? (string)$a['ua_region'] : '';
            $data['go_receiver_address'] = isset($a['ua_address']) ? (string)$a['ua_address'] : '';
        }

        Db::startTrans();
        try {
            $goId = $this->insertGetId($data);
            if (!$goId) {
                Db::rollback();
                return ['code' => 2003, 'msg' => lang('save_err')];
            }

            // 初始支付流水记录（待支付）
            if (class_exists('\\app\\common\\model\\GoodsPaylog')) {
                $log = [];
                $log['go_id'] = $goId;
                $log['user_id'] = $user['user_id'];
                $log['gp_amount'] = $totalPrice;
                $log['gp_points'] = $totalPoints;
                $log['gp_channel'] = $payType;
                $log['gp_trade_no'] = '';
                $log['gp_status'] = 0;
                $log['gp_time'] = time();
                $log['gp_ext'] = '';
                model('GoodsPaylog')->saveData($log);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 2004, 'msg' => lang('save_err')];
        }

        $data['go_id'] = $goId;
        return ['code' => 1, 'msg' => lang('save_ok'), 'info' => $data];
    }

    /**
     * 支付回调（商品场景）
     * @param string $goCode   订单编号
     * @param string $payType  支付渠道标识
     * @param string $tradeNo  第三方交易号
     * @param float  $amount   实付金额
     * @param array  $ext      其它原始回调参数
     * @return array
     */
    public function notify($goCode, $payType, $tradeNo = '', $amount = 0, $ext = [])
    {
        if (empty($goCode) || empty($payType)) {
            return ['code' => 1001, 'msg' => lang('param_err')];
        }

        $where = [];
        $where['go_code'] = $goCode;
        $order = $this->infoData($where);
        if ($order['code'] > 1) {
            return $order;
        }
        $info = $order['info'];

        if ($info['go_status'] == 1) {
            // 已支付视为成功，避免重复处理
            return ['code' => 1, 'msg' => lang('model/order/pay_over')];
        }

        $goods = model('Goods')->infoData(['goods_id' => ['eq', $info['goods_id']]]);
        if ($goods['code'] > 1) {
            return $goods;
        }

        $payAmount = $amount > 0 ? $amount : $info['go_price'];

        Db::startTrans();
        try {
            // 更新订单状态；虚拟商品：快照下载内容并标记已发货；实体：待后台发货
            $gk = isset($info['go_goods_kind']) ? intval($info['go_goods_kind']) : 1;
            if ($gk !== 2) {
                $gk = 1;
            }
            $update = [];
            $update['go_status'] = 1;
            $update['go_pay_time'] = time();
            $update['go_pay_type'] = $payType;
            if ($gk == 1) {
                $update['go_download_snapshot'] = isset($goods['info']['goods_download']) ? (string)$goods['info']['goods_download'] : '';
                $update['go_ship_status'] = 1;
                $update['go_ship_time'] = time();
            }
            $res = $this->where($where)->update($update);
            if ($res === false) {
                Db::rollback();
                return ['code' => 2002, 'msg' => lang('model/order/update_status_err')];
            }

            // 更新库存与销量
            $goodsWhere = [];
            $goodsWhere['goods_id'] = $info['goods_id'];
            if (isset($goods['info']['goods_stock']) && $goods['info']['goods_stock'] > 0) {
                $resStock = model('Goods')->where($goodsWhere)->setDec('goods_stock', $info['go_num']);
                if ($resStock === false) {
                    Db::rollback();
                    return ['code' => 2003, 'msg' => lang('model/order/update_status_err')];
                }
            }
            $resSales = model('Goods')->where($goodsWhere)->setInc('goods_sales', $info['go_num']);
            if ($resSales === false) {
                Db::rollback();
                return ['code' => 2004, 'msg' => lang('model/order/update_status_err')];
            }

            // 更新或记录支付流水
            if (class_exists('\\app\\common\\model\\GoodsPaylog')) {
                $logData = [];
                $logData['go_id'] = $info['go_id'];
                $logData['user_id'] = $info['user_id'];
                $logData['gp_amount'] = $payAmount;
                $logData['gp_points'] = $info['go_points'];
                $logData['gp_channel'] = $payType;
                $logData['gp_trade_no'] = (string)$tradeNo;
                $logData['gp_status'] = 1;
                $logData['gp_time'] = time();
                $logData['gp_ext'] = !empty($ext) ? json_encode($ext) : '';

                // 按 go_id 更新最新一条记录
                $logWhere = [];
                $logWhere['go_id'] = $info['go_id'];
                $exists = model('GoodsPaylog')->where($logWhere)->find();
                if ($exists) {
                    model('GoodsPaylog')->where($logWhere)->update($logData);
                } else {
                    model('GoodsPaylog')->saveData($logData);
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 2005, 'msg' => lang('model/order/update_status_err')];
        }

        return ['code' => 1, 'msg' => lang('model/order/pay_ok')];
    }
}

