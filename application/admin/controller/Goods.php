<?php
namespace app\admin\controller;

use think\Db;

class Goods extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 商品列表
     */
    public function data()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

        $where = [];

        if (!empty($param['type'])) {
            $where['type_id|type_id_1'] = ['eq', $param['type']];
        }
        if (!empty($param['level'])) {
            $where['goods_level'] = ['eq', $param['level']];
        }
        if (in_array($param['status'], ['0', '1'])) {
            $where['goods_status'] = ['eq', $param['status']];
        }
        if ($param['price_min'] !== '' && $param['price_min'] !== null) {
            $where['goods_price'][] = ['egt', floatval($param['price_min'])];
        }
        if ($param['price_max'] !== '' && $param['price_max'] !== null) {
            $where['goods_price'][] = ['elt', floatval($param['price_max'])];
        }
        if (!empty($param['wd'])) {
            $param['wd'] = urldecode($param['wd']);
            $param['wd'] = mac_filter_xss($param['wd']);
            $where['goods_name|goods_sub'] = ['like', '%' . $param['wd'] . '%'];
        }

        $order = 'goods_time desc';
        if (in_array($param['order'], ['goods_id', 'goods_hits', 'goods_sales', 'goods_price'])) {
            $order = $param['order'] . ' desc';
        }

        $res = model('Goods')->listData($where, $order, $param['page'], $param['limit']);

        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);

        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param', $param);
        $queryString = '?' . http_build_query($param);
        $this->assign('query_string', $queryString);

        // 分类
        $type_tree = model('Type')->getCache('type_tree');
        $this->assign('type_tree', $type_tree);

        $this->assign('title', '商品管理');
        return $this->fetch('admin@goods/index');
    }

    /**
     * 商品新增/编辑
     */
    public function info()
    {
        if (request()->isPost()) {
            $param = input('post.');
            $res = model('Goods')->saveData($param);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }

        $id = input('id');
        $info = [];
        if (!empty($id)) {
            $where = [];
            $where['goods_id'] = $id;
            $res = model('Goods')->infoData($where);
            $info = $res['info'];
        }
        $this->assign('info', $info);

        // 分类
        $type_tree = model('Type')->getCache('type_tree');
        $this->assign('type_tree', $type_tree);

        // 会员组（预留权限设置）
        $group_list = model('Group')->getCache('group_list');
        $this->assign('group_list', $group_list);

        // 已选可购买会员组ID，用于模板中回显
        $selected_group_ids = [];
        if (!empty($info['goods_group_ids'])) {
            $selected_group_ids = explode(',', $info['goods_group_ids']);
        }
        $this->assign('selected_group_ids', $selected_group_ids);

        $this->assign('title', '商品信息');
        return $this->fetch('admin@goods/info');
    }

    /**
     * 删除商品
     */
    public function del()
    {
        $param = input();
        $ids = $param['ids'];

        if (!empty($ids)) {
            $where = [];
            $where['goods_id'] = ['in', $ids];
            $res = model('Goods')->delData($where);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error(lang('param_err'));
    }

    /**
     * 批量字段修改（状态、推荐、分类等）
     */
    public function field()
    {
        $param = input();
        $ids = $param['ids'];
        $col = $param['col'];
        $val = $param['val'];

        if ($col == 'type_id' && $val == '') {
            return $this->error('请选择分类提交');
        }

        if (!empty($ids) && in_array($col, ['goods_status', 'goods_level', 'type_id'])) {
            $where = [];
            $where['goods_id'] = ['in', $ids];
            $update = [];
            $update[$col] = $val;
            if ($col == 'type_id') {
                $type_list = model('Type')->getCache();
                $id1 = intval($type_list[$val]['type_pid']);
                $update['type_id_1'] = $id1;
            }
            $res = model('Goods')->fieldData($where, $update);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error(lang('param_err'));
    }
}

