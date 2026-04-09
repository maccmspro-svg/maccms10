<?php
namespace app\common\model;

class UserAddress extends Base
{
    protected $name = 'user_address';

    protected $createTime = '';
    protected $updateTime = '';

    public function listData($where, $order = 'ua_id desc', $page = 1, $limit = 50)
    {
        $page  = $page > 0 ? (int)$page : 1;
        $limit = $limit ? (int)$limit : 50;
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $total = $this->where($where)->count();
        $list = $this->where($where)->order($order)->page($page, $limit)->select();
        if (empty($list)) {
            $list = [];
        }

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
        return ['code' => 1, 'msg' => lang('obtain_ok'), 'info' => $info->toArray()];
    }

    public function saveData($data)
    {
        if (empty($data['user_id'])) {
            return ['code' => 1001, 'msg' => lang('param_err')];
        }
        $uid  = (int)$data['user_id'];
        $uaId = !empty($data['ua_id']) ? (int)$data['ua_id'] : 0;

        if ($uaId > 0) {
            $where = ['ua_id' => $uaId, 'user_id' => $uid];
            $exists = $this->where($where)->find();
            if (empty($exists)) {
                return ['code' => 1002, 'msg' => lang('obtain_err')];
            }
            unset($data['ua_id']);
            $res = $this->allowField(true)->where($where)->update($data);
            if ($res === false) {
                return ['code' => 1003, 'msg' => lang('save_err') . '：' . $this->getError()];
            }
        } else {
            unset($data['ua_id']);
            $data['ua_time'] = time();
            $uaId = (int)$this->allowField(true)->insertGetId($data);
            if ($uaId < 1) {
                return ['code' => 1003, 'msg' => lang('save_err')];
            }
        }

        if (!empty($data['ua_is_default'])) {
            $this->where('user_id', $uid)->where('ua_id', '<>', $uaId)->update(['ua_is_default' => 0]);
        }

        return ['code' => 1, 'msg' => lang('save_ok')];
    }

    public function delData($where)
    {
        if (empty($where) || !is_array($where)) {
            return ['code' => 1001, 'msg' => lang('param_err')];
        }
        $res = $this->where($where)->delete();
        if ($res === false) {
            return ['code' => 1002, 'msg' => lang('del_err')];
        }
        return ['code' => 1, 'msg' => lang('del_ok')];
    }
}
