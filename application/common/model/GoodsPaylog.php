<?php
namespace app\common\model;

class GoodsPaylog extends Base
{
    // 对应数据表（不含前缀）
    protected $name = 'goods_paylog';

    protected $createTime = '';
    protected $updateTime = '';

    protected $auto   = [];
    protected $insert = [];
    protected $update = [];

    public function saveData($data)
    {
        $res = $this->allowField(true)->insert($data);
        if ($res === false) {
            return ['code' => 1002, 'msg' => lang('save_err') . '：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => lang('save_ok')];
    }
}

