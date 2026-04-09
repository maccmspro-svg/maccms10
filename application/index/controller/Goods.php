<?php
namespace app\index\controller;

use think\Db;

class Goods extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 商城首页
     */
    public function index()
    {
        // 后续可根据需要在渲染前做额外的数据准备
        return $this->label_fetch('goods/index');
    }

    /**
     * 分类/列表页
     */
    public function type()
    {
        // 解析URL参数，供模板中展示和筛选使用
        $param = mac_param_url();
        if (isset($param['id']) && is_array($param['id'])) {
            $param['id'] = reset($param['id']);
        }
        $this->assign('param', $param);

        if (!empty($param['id'])) {
            $tid = intval($param['id']);
            $res = model('Type')->infoData(['type_id' => ['eq', $tid]]);
            if ($res['code'] == 1) {
                $this->assign('obj', $res['info']);
                // 与 vod/art 等 label_type 一致，供顶栏/侧栏 {$GLOBALS.type_id} 高亮「商城」等栏目
                $GLOBALS['type_id'] = $res['info']['type_id'];
                $GLOBALS['type_pid'] = isset($res['info']['type_pid']) ? intval($res['info']['type_pid']) : 0;
            }
        }

        $base = $param;
        if (isset($base['page'])) {
            unset($base['page']);
        }
        $merge = function ($extra) use ($base) {
            $u = array_merge($base, $extra);
            $u['page'] = 1;
            return mac_url('goods/type', $u);
        };

        $this->assign('g_sort_time', $merge(['by' => 'time', 'order' => 'desc']));
        $this->assign('g_sort_new', $merge(['by' => 'time_add', 'order' => 'desc']));
        $this->assign('g_sort_price_asc', $merge(['by' => 'price', 'order' => 'asc']));
        $this->assign('g_sort_price_desc', $merge(['by' => 'price', 'order' => 'desc']));
        $this->assign('g_sort_points_asc', $merge(['by' => 'points', 'order' => 'asc']));
        $this->assign('g_sort_hits', $merge(['by' => 'hits', 'order' => 'desc']));
        $this->assign('g_sort_id', $merge(['by' => 'id', 'order' => 'desc']));

        $this->assign('g_level_all', $merge(['level' => '']));
        $this->assign('g_level_9', $merge(['level' => '9']));

        $this->assign('g_letter_all', $merge(['letter' => '']));
        $letterList = [];
        foreach (range('A', 'Z') as $L) {
            $letterList[] = ['letter' => $L, 'url' => $merge(['letter' => $L])];
        }
        $this->assign('g_letter_list', $letterList);

        if (!empty($param['class'])) {
            $this->assign('g_class_clear', $merge(['class' => '']));
        }
        if (!empty($param['tag'])) {
            $this->assign('g_tag_clear', $merge(['tag' => '']));
        }
        if (!empty($param['wd'])) {
            $this->assign('g_wd_clear', $merge(['wd' => '']));
        }

        $by = isset($param['by']) ? (string)$param['by'] : '';
        $ord = isset($param['order']) ? (string)$param['order'] : '';
        $this->assign('g_act_sort_time', ($by === '' || $by === 'time') && ($ord === '' || $ord === 'desc'));
        $this->assign('g_act_sort_new', $by === 'time_add' && $ord === 'desc');
        $this->assign('g_act_price_asc', $by === 'price' && $ord === 'asc');
        $this->assign('g_act_price_desc', $by === 'price' && $ord === 'desc');
        $this->assign('g_act_points_asc', $by === 'points' && $ord === 'asc');
        $this->assign('g_act_hits', $by === 'hits' && $ord === 'desc');
        $this->assign('g_act_id', $by === 'id' && $ord === 'desc');
        $this->assign('g_act_level_all', !isset($param['level']) || $param['level'] === '' || $param['level'] === null);
        $this->assign('g_act_level_9', isset($param['level']) && (string)$param['level'] === '9');
        $this->assign('g_act_letter_all', !isset($param['letter']) || $param['letter'] === '' || $param['letter'] === null);

        return $this->label_fetch('goods/type');
    }

    /**
     * 商品详情页
     */
    public function detail()
    {
        // 获取路由参数（支持 id/en 两种形式）
        $param = mac_param_url();
        $where = [];
        if (!empty($param['id'])) {
            $where['goods_id'] = ['eq', intval($param['id'])];
        } elseif (!empty($param['en'])) {
            $where['goods_en'] = ['eq', $param['en']];
        } else {
            $this->page_error(lang('param_err'));
        }

        $res = model('Goods')->infoData($where);
        if ($res['code'] > 1) {
            $this->page_error($res['msg']);
        }
        $info = $res['info'];

        // 计算当前用户是否有购买权限
        $user = $GLOBALS['user'];
        $can_buy = true;
        if (!empty($info['goods_group_ids'])) {
            $allowed_ids = explode(',', $info['goods_group_ids']);
            $user_group_ids = explode(',', $user['group_id']);
            $intersect = array_intersect($allowed_ids, $user_group_ids);
            $can_buy = !empty($intersect);
        }

        $this->assign('obj', $info);
        $this->assign('can_buy', $can_buy);
        $this->assign('param', $param);

        if (!empty($info['type_id']) && !empty($info['type'])) {
            $GLOBALS['type_id'] = $info['type_id'];
            $GLOBALS['type_pid'] = isset($info['type']['type_pid']) ? intval($info['type']['type_pid']) : 0;
        }

        $goods_bought = false;
        $goods_bought_go_id = 0;
        if (!empty($user['user_id']) && intval($user['user_id']) > 0) {
            $lastPaid = Db::name('goods_order')
                ->where([
                    'user_id'  => intval($user['user_id']),
                    'goods_id' => intval($info['goods_id']),
                    'go_status' => 1,
                ])
                ->order('go_id desc')
                ->find();
            if (!empty($lastPaid)) {
                $goods_bought = true;
                $goods_bought_go_id = intval($lastPaid['go_id']);
            }
        }
        $this->assign('goods_bought', $goods_bought);
        $this->assign('goods_bought_go_id', $goods_bought_go_id);

        $goods_is_fav = 0;
        if (!empty($user['user_id']) && intval($user['user_id']) > 0) {
            $f = model('Ulog')->infoData([
                'user_id'   => intval($user['user_id']),
                'ulog_mid'  => 13,
                'ulog_rid'  => intval($info['goods_id']),
                'ulog_type' => 2,
                'ulog_sid'  => 0,
                'ulog_nid'  => 0,
            ]);
            $goods_is_fav = ($f['code'] == 1) ? 1 : 0;
        }
        $this->assign('goods_is_fav', $goods_is_fav);

        $this->assign('address_list', []);
        $gk = isset($info['goods_kind']) ? intval($info['goods_kind']) : 1;
        if ($gk === 2 && !empty($user['user_id']) && $user['user_id'] > 0) {
            $addrRes = model('UserAddress')->listData(
                ['user_id' => ['eq', $user['user_id']]],
                'ua_is_default desc,ua_id desc',
                1,
                50
            );
            $this->assign('address_list', $addrRes['list']);
        }

        $goods_pay_opts = [];
        foreach (['weixin' => '微信支付', 'alipay' => '支付宝', 'epay' => '易支付'] as $pk => $pn) {
            if (mac_pay_gateway_configured($pk)) {
                $goods_pay_opts[] = ['k' => $pk, 'n' => $pn];
            }
        }
        $this->assign('goods_pay_opts', $goods_pay_opts);
        $canPoints = !empty($info['goods_points']) && intval($info['goods_points']) > 0;
        $this->assign('goods_can_pay', (!empty($goods_pay_opts) || $canPoints) ? 1 : 0);

        return $this->label_fetch('goods/detail');
    }

    /**
     * 订单确认 / 结果页
     */
    public function order()
    {
        $param = mac_param_url();
        $this->assign('param', $param);
        if (!empty($param['id'])) {
            $res = model('GoodsOrder')->infoData(['go_id' => ['eq', intval($param['id'])]]);
            if ($res['code'] == 1) {
                $order = $res['info'];
                $user = $GLOBALS['user'];
                if (!empty($user['user_id']) && intval($order['user_id']) === intval($user['user_id'])) {
                    $this->assign('order', $order);
                }
            }
        }
        return $this->label_fetch('goods/order');
    }

    /**
     * 购买入口：校验登录与可购买会员组
     */
    public function buy()
    {
        $param = mac_param_url();
        $where = [];
        if (!empty($param['id'])) {
            $where['goods_id'] = ['eq', intval($param['id'])];
        } elseif (!empty($param['en'])) {
            $where['goods_en'] = ['eq', $param['en']];
        } else {
            return $this->error(lang('param_err'));
        }

        $res = model('Goods')->infoData($where);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }
        $info = $res['info'];

        // 必须登录
        $user = $GLOBALS['user'];
        if (empty($user['user_id']) || $user['user_id'] < 1) {
            model('User')->logout();
            return $this->error(lang('index/no_login'), url('user/login'));
        }

        // 校验会员组是否在可购列表中
        $can_buy = true;
        if (!empty($info['goods_group_ids'])) {
            $allowed_ids = explode(',', $info['goods_group_ids']);
            $user_group_ids = explode(',', $user['group_id']);
            $intersect = array_intersect($allowed_ids, $user_group_ids);
            $can_buy = !empty($intersect);
        }

        if (!$can_buy) {
            return $this->error(lang('controller/no_popedom'), url('user/upgrade'));
        }

        // GET：显示订单确认页
        if (!request()->isPost()) {
            $addrRes = model('UserAddress')->listData(
                ['user_id' => ['eq', $user['user_id']]],
                'ua_is_default desc,ua_id desc',
                1,
                50
            );
            $this->assign('address_list', $addrRes['list']);
            $this->assign('obj', $info);
            $this->assign('param', $param);
            return $this->label_fetch('goods/order');
        }

        // POST：创建订单并发起支付
        $input = input();
        $num = isset($input['num']) ? intval($input['num']) : 1;
        if ($num < 1) {
            $num = 1;
        }
        $payment = isset($input['payment']) ? strtolower(htmlspecialchars(urldecode(trim($input['payment'])))) : '';

        if (empty($payment)) {
            return $this->error(lang('param_err'));
        }

        $goodsKind = isset($info['goods_kind']) ? intval($info['goods_kind']) : 1;
        if ($goodsKind !== 2) {
            $goodsKind = 1;
        }
        $orderExtra = [];
        if ($goodsKind === 2) {
            $uaId = isset($input['ua_id']) ? intval($input['ua_id']) : 0;
            if ($uaId < 1) {
                return $this->error('实体商品请选择收货地址');
            }
            $uaRes = model('UserAddress')->infoData([
                'ua_id'   => ['eq', $uaId],
                'user_id' => ['eq', $user['user_id']],
            ]);
            if ($uaRes['code'] > 1) {
                return $this->error('收货地址无效，请先在会员中心维护地址');
            }
            $orderExtra['address'] = $uaRes['info'];
        }

        // 积分支付分支
        if ($payment === 'points') {
            // 必须设置积分价
            if (empty($info['goods_points'])) {
                return $this->error('当前商品未设置积分价，无法使用积分购买');
            }
            $needPoints = intval($info['goods_points']) * $num;
            if ($needPoints <= 0) {
                return $this->error('积分配置异常，无法使用积分购买');
            }
            // 检查用户最新积分（从数据库读取，避免会话不一致）
            $userInfo = model('User')->infoData(['user_id' => ['eq', $user['user_id']]]);
            if ($userInfo['code'] > 1) {
                return $this->error($userInfo['msg']);
            }
            $userData = $userInfo['info'];
            if ($userData['user_points'] < $needPoints) {
                return $this->error('积分不足，请先充值或选择其它支付方式');
            }

            // 创建订单（go_price 可为 0，仅使用 go_points）
            $orderRes = model('GoodsOrder')->createOrder($userData, $info, $num, 'points', $orderExtra);
            if ($orderRes['code'] > 1) {
                return $this->error($orderRes['msg']);
            }
            $order = $orderRes['info'];

            // 扣积分 & 记录积分日志
            model('User')->where('user_id', $userData['user_id'])->setDec('user_points', $needPoints);
            if (class_exists('\\app\\common\\model\\Plog')) {
                model('Plog')->saveData([
                    'user_id'    => $userData['user_id'],
                    // 须为 8「积分消费」，与 user 控制器影视积分购买一致；误用 2 会变成「注册推广」且前台按类型显示为加号
                    'plog_type'  => 8,
                    'plog_points'=> $needPoints,
                    'plog_time'  => time(),
                    'plog_remarks' => '购买商品#' . $order['go_id'],
                ]);
            }

            // 直接视为已支付，调用 notify 更新库存、销量等
            model('GoodsOrder')->notify($order['go_code'], 'points', '', 0, []);

            return $this->success('积分支付成功', mac_url('goods/order',['id'=>$order['go_id']]));
        }

        // 现金类支付：走已有支付通道（与 mac_pay_gateway_configured / 前台下拉一致）
        if (!mac_pay_gateway_configured($payment)) {
            return $this->error(lang('index/payment_status'));
        }

        $orderRes = model('GoodsOrder')->createOrder($user, $info, $num, $payment, $orderExtra);
        if ($orderRes['code'] > 1) {
            return $this->error($orderRes['msg']);
        }
        $order = $orderRes['info'];
        // 与 user/payment_weixin 模板、积分充值订单字段对齐，避免模板取不到 order_code / order_id 报错或空白
        $order['order_id'] = $order['go_id'];
        $order['order_code'] = $order['go_code'];
        $order['order_price'] = $order['go_price'];

        // 组装通用订单结构，复用现有支付扩展
        $payOrder = [];
        $payOrder['order_code'] = $order['go_code'];
        $payOrder['order_price'] = $order['go_price'];

        $cp = 'app\\common\\extend\\pay\\' . ucfirst($payment);
        if (!class_exists($cp)) {
            return $this->error(lang('index/payment_not'));
        }

        $this->assign('order', $order);
        $this->assign('info', $order);
        $this->assign('param', $input);

        $c = new $cp;
        $paymentRes = $c->submit($user, $payOrder, $input);

        // 微信支付：展示二维码页；统一下单失败时 submit 返回 false，原逻辑会空 return 导致白屏
        if ($payment == 'weixin') {
            if (empty($paymentRes)) {
                return $this->error(lang('index/payment_status'), mac_url('goods/detail', ['id' => $info['goods_id']]));
            }
            $this->assign('payment', $paymentRes);
            $this->assign('goods_pay', 1);
            return $this->label_fetch('user/payment_weixin');
        }

        // 其它支付方式：支付宝等会在 submit 内 echo 表单并 exit，易支付会 redirect，一般走不到此处
        return;
    }
}

