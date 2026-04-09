<?php
namespace app\admin\controller;

use think\Db;

class GoodsOrder extends Base
{
    /**
     * 商品订单列表（含虚拟/实体、发货状态）
     */
    public function data()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : intval($param['limit']);

        $where = [];
        if (!empty($param['pending'])) {
            $where['go.go_status'] = 1;
            $where['go.go_goods_kind'] = 2;
            $where['go.go_ship_status'] = 0;
        } else {
            if (isset($param['go_status']) && $param['go_status'] !== '') {
                $where['go.go_status'] = ['eq', intval($param['go_status'])];
            }
            if (isset($param['go_goods_kind']) && $param['go_goods_kind'] !== '') {
                $where['go.go_goods_kind'] = ['eq', intval($param['go_goods_kind'])];
            }
            if (isset($param['go_ship_status']) && $param['go_ship_status'] !== '') {
                $where['go.go_ship_status'] = ['eq', intval($param['go_ship_status'])];
            }
        }
        if (!empty($param['wd'])) {
            $wd = mac_filter_xss(urldecode(trim($param['wd'])));
            $where['go.go_code|g.goods_name'] = ['like', '%' . $wd . '%'];
        }

        $order = 'go.go_id desc';
        $total = Db::name('goods_order')->alias('go')
            ->join('__GOODS__ g', 'go.goods_id = g.goods_id', 'LEFT')
            ->where($where)
            ->count();
        $limitStr = ($param['limit'] * ($param['page'] - 1)) . ',' . $param['limit'];
        $list = Db::name('goods_order')->alias('go')
            ->field('go.*,g.goods_name')
            ->join('__GOODS__ g', 'go.goods_id = g.goods_id', 'LEFT')
            ->where($where)
            ->order($order)
            ->limit($limitStr)
            ->select();

        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->assign('page', $param['page']);
        $this->assign('limit', $param['limit']);

        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param', $param);
        $queryString = '?' . http_build_query($param);
        $this->assign('query_string', $queryString);

        $this->assign('title', '商品订单');
        return $this->fetch('admin@goods_order/index');
    }

    /**
     * 实体订单发货（填写快递）
     */
    public function ship()
    {
        $goId = intval(input('go_id'));
        if ($goId < 1) {
            return $this->error(lang('param_err'));
        }
        $where = ['go_id' => ['eq', $goId]];
        $res = model('GoodsOrder')->infoData($where);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }
        $row = $res['info'];

        if (request()->isPost()) {
            $expressName = trim((string)input('post.go_express_name', ''));
            $expressNo = trim((string)input('post.go_express_no', ''));
            if ($expressName === '' || $expressNo === '') {
                return $this->error('请填写快递公司与运单号');
            }
            if (intval($row['go_goods_kind']) !== 2) {
                return $this->error('非实体商品订单');
            }
            if (intval($row['go_status']) !== 1) {
                return $this->error('订单未支付，无法发货');
            }
            $up = [
                'go_ship_status' => 1,
                'go_ship_time'   => time(),
                'go_express_name' => $expressName,
                'go_express_no'   => $expressNo,
            ];
            $r = model('GoodsOrder')->where($where)->update($up);
            if ($r === false) {
                return $this->error(lang('save_err'));
            }
            return $this->success(lang('save_ok'));
        }

        $this->assign('info', $row);
        $this->assign('title', '订单发货');
        return $this->fetch('admin@goods_order/ship');
    }
}
