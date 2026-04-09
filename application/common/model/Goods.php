<?php
namespace app\common\model;

use think\Db;
use think\Cache;
use app\common\util\Pinyin;

class Goods extends Base
{
    // 数据表（不含前缀）
    protected $name = 'goods';

    protected $createTime = '';
    protected $updateTime = '';

    protected $auto   = [];
    protected $insert = [];
    protected $update = [];

    public function countData($where)
    {
        $total = $this->where($where)->count();
        return $total;
    }

    public function listData($where, $order, $page = 1, $limit = 20, $start = 0, $field = '*', $addition = 1, $totalshow = 1)
    {
        $page  = $page > 0 ? (int)$page : 1;
        $limit = $limit ? (int)$limit : 20;
        $start = $start ? (int)$start : 0;

        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $where2 = '';
        if (!empty($where['_string'])) {
            $where2 = $where['_string'];
            unset($where['_string']);
        }

        $limitStr = ($limit * ($page - 1) + $start) . ',' . $limit;

        if ($totalshow == 1) {
            $total = $this->where($where)->where($where2)->count();
        }

        $list = Db::name('Goods')->field($field)->where($where)->where($where2)->order($order)->limit($limitStr)->select();

        // 分类与会员组
        $typeList  = model('Type')->getCache('type_list');
        $groupList = model('Group')->getCache('group_list');

        foreach ($list as $k => $v) {
            if ($addition == 1) {
                if (!empty($v['type_id']) && isset($typeList[$v['type_id']])) {
                    $list[$k]['type']   = $typeList[$v['type_id']];
                    $list[$k]['type_1'] = $typeList[$list[$k]['type']['type_pid']];
                }
                if (!empty($v['group_id']) && isset($groupList[$v['group_id']])) {
                    $list[$k]['group'] = $groupList[$v['group_id']];
                }
            }
        }

        return [
            'code'      => 1,
            'msg'       => lang('data_list'),
            'page'      => $page,
            'pagecount' => ceil($total / $limit),
            'limit'     => $limit,
            'total'     => $total,
            'list'      => $list
        ];
    }

    public function listCacheData($lp)
    {
        if (!is_array($lp)) {
            $lp = json_decode($lp, true);
        }

        $order     = isset($lp['order']) ? $lp['order'] : 'desc';
        $by        = isset($lp['by']) ? $lp['by'] : 'time';
        $type      = isset($lp['type']) ? $lp['type'] : '';
        $ids       = isset($lp['ids']) ? $lp['ids'] : '';
        $rel       = isset($lp['rel']) ? $lp['rel'] : '';
        $paging    = isset($lp['paging']) ? $lp['paging'] : 'no';
        $pageurl   = isset($lp['pageurl']) ? $lp['pageurl'] : '';
        $level     = isset($lp['level']) ? $lp['level'] : '';
        $wd        = isset($lp['wd']) ? $lp['wd'] : '';
        $tag       = isset($lp['tag']) ? $lp['tag'] : '';
        $class     = isset($lp['class']) ? $lp['class'] : '';
        $letter    = isset($lp['letter']) ? $lp['letter'] : '';
        $start     = intval(abs(isset($lp['start']) ? $lp['start'] : 0));
        $num       = intval(abs(isset($lp['num']) ? $lp['num'] : 0));
        $timeadd   = isset($lp['timeadd']) ? $lp['timeadd'] : '';
        $timehits  = isset($lp['timehits']) ? $lp['timehits'] : '';
        $time      = isset($lp['time']) ? $lp['time'] : '';
        $hitsmonth = isset($lp['hitsmonth']) ? $lp['hitsmonth'] : '';
        $hitsweek  = isset($lp['hitsweek']) ? $lp['hitsweek'] : '';
        $hitsday   = isset($lp['hitsday']) ? $lp['hitsday'] : '';
        $hits      = isset($lp['hits']) ? $lp['hits'] : '';
        $not       = isset($lp['not']) ? $lp['not'] : '';
        $cachetime = isset($lp['cachetime']) ? $lp['cachetime'] : '';
        $typenot   = isset($lp['typenot']) ? $lp['typenot'] : '';
        $name      = isset($lp['name']) ? $lp['name'] : '';

        $page      = 1;
        $where     = [];
        $totalshow = 0;

        if (empty($num)) {
            $num = 20;
        }
        if ($start > 1) {
            $start--;
        }
        if (!in_array($paging, ['yes', 'no'])) {
            $paging = 'no';
        }

        $param = mac_param_url();
        if ($paging == 'yes') {
            $param     = mac_search_len_check($param);
            $totalshow = 1;

            // 兼容自定义路由下同名参数被解析为数组的情况（如 id => [3]），否则会出现 id/Array 的分页链接
            if (isset($param['id']) && is_array($param['id'])) {
                $param['id'] = reset($param['id']);
            }

            if (!empty($param['id'])) {
                $type = (string)intval($param['id']);
            }
            if (!empty($param['level'])) {
                $level = $param['level'];
            }
            if (!empty($param['ids'])) {
                $ids = $param['ids'];
            }
            if (!empty($param['tid'])) {
                $tid = intval($param['tid']);
            }
            if (!empty($param['wd'])) {
                $wd = $param['wd'];
            }
            if (!empty($param['name'])) {
                $name = $param['name'];
            }
            if (!empty($param['tag'])) {
                $tag = $param['tag'];
            }
            if (!empty($param['class'])) {
                $class = $param['class'];
            }
            if (!empty($param['letter'])) {
                $letter = $param['letter'];
            }
            if (!empty($param['by'])) {
                $by = $param['by'];
            }
            if (!empty($param['order'])) {
                $order = $param['order'];
            }
            if (!empty($param['page'])) {
                $page = intval($param['page']);
            }
            foreach ($param as $k => $v) {
                if (empty($v)) {
                    unset($param[$k]);
                }
            }
            if (empty($pageurl)) {
                $pageurl = 'goods/type';
            }
            // 统一使用 mac_url 生成分页 URL，避免 type_id 未设置或参数被解析为数组导致 id/Array 问题
            $param['page'] = 'PAGELINK';
            $pageurl = mac_url($pageurl, $param);
        }

        $where['goods_status'] = ['eq', 1];
        if (!empty($level)) {
            if ($level == 'all') {
                $level = '1,2,3,4,5,6,7,8,9';
            }
            $where['goods_level'] = ['in', explode(',', $level)];
        }
        if (!empty($ids)) {
            if ($ids != 'all') {
                $where['goods_id'] = ['in', explode(',', $ids)];
            }
        }
        if (!empty($not)) {
            $where['goods_id'] = ['not in', explode(',', $not)];
        }
        if (!empty($rel)) {
            $tmp = explode(',', $rel);
            if (is_numeric($rel) || mac_array_check_num($tmp) == true) {
                $where['goods_id'] = ['in', $tmp];
            } else {
                $where['goods_name'] = ['like', mac_like_arr($rel), 'OR'];
            }
        }
        if (!empty($letter)) {
            $where['goods_letter'] = ['in', explode(',', $letter)];
        }
        if (!empty($tag)) {
            $where['goods_tag'] = ['like', mac_like_arr($tag), 'OR'];
        }
        if (!empty($class)) {
            $where['goods_class'] = ['like', mac_like_arr($class), 'OR'];
        }
        if (!empty($type)) {
            if ($type == 'all') {
                $type = 'parent';
            }
            $typeList = model('Type')->getCache('type_list');
            if ($type == 'parent') {
                $tids = [];
                foreach ($typeList as $k => $v) {
                    if ($v['type_pid'] == 0) {
                        $tids[] = $v['type_id'];
                    }
                }
                if (!empty($tids)) {
                    $where['type_id'] = ['in', $tids];
                }
            } else {
                // 与 Vod 一致：选中父分类时展开为「自身 + 子分类」，否则仅查父 id 会漏掉挂在子类下的商品
                $tmp_arr = explode(',', $type);
                $tids    = [];
                foreach ($typeList as $k2 => $v2) {
                    if (in_array($v2['type_id'] . '', $tmp_arr) || in_array($v2['type_pid'] . '', $tmp_arr)) {
                        $tids[] = $v2['type_id'];
                    }
                }
                $tids = array_unique($tids);
                if (!empty($tids)) {
                    $where['type_id'] = ['in', implode(',', $tids)];
                }
            }
        }
        if (!empty($typenot)) {
            $where['type_id'] = ['not in', explode(',', $typenot)];
        }
        if (!empty($timeadd)) {
            $where['goods_time_add'] = ['between', mac_get_time_span($timeadd)];
        }
        if (!empty($timehits)) {
            $where['goods_time_hits'] = ['between', mac_get_time_span($timehits)];
        }
        if (!empty($time)) {
            $where['goods_time'] = ['between', mac_get_time_span($time)];
        }
        if (!empty($hitsmonth)) {
            $where['goods_hits_month'] = ['egt', $hitsmonth];
        }
        if (!empty($hitsweek)) {
            $where['goods_hits_week'] = ['egt', $hitsweek];
        }
        if (!empty($hitsday)) {
            $where['goods_hits_day'] = ['egt', $hitsday];
        }
        if (!empty($hits)) {
            $where['goods_hits'] = ['egt', $hits];
        }
        if (!empty($wd)) {
            $where['goods_name'] = ['like', '%' . $wd . '%'];
        }
        if (!empty($name)) {
            $where['goods_name'] = ['like', mac_like_arr($name), 'OR'];
        }

        // 排序
        // 扩展支持按价格(price)、积分(points)排序
        if (!in_array($by, ['time', 'time_add', 'hits', 'score', 'id', 'price', 'points'])) {
            $by = 'time';
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }
        $order = 'goods_' . $by . ' ' . $order;

        // 与 Vod/Art 等模块保持一致，直接把最终分页 URL 字符串传递给标签层
        $param['page'] = '{page}';

        $res = $this->listData($where, $order, $page, $num, $start, '*', 1, $totalshow);
        $res['pageurl'] = $pageurl;
        return $res;
    }

    public function infoData($where, $field = '*', $cache = 0)
    {
        if (empty($where) || !is_array($where)) {
            return ['code' => 1001, 'msg' => lang('param_err')];
        }

        $dataCache = false;
        $key       = $GLOBALS['config']['app']['cache_flag'] . '_' . 'goods_detail_' . $where['goods_id'][1] . '_' . $where['goods_en'][1];

        if ($where['goods_id'][0] == 'eq' || $where['goods_en'][0] == 'eq') {
            $dataCache = true;
        }
        if ($GLOBALS['config']['app']['cache_core'] == 1 && $dataCache) {
            $info = Cache::get($key);
        }

        if ($GLOBALS['config']['app']['cache_core'] == 0 || $cache == 0 || empty($info['goods_id'])) {
            $info = $this->field($field)->where($where)->find();
            if (empty($info)) {
                return ['code' => 1002, 'msg' => lang('obtain_err')];
            }
            $info = $info->toArray();
            $info['goods_pic_screenshot_list'] = [];

            if (!empty($info['goods_pic_screenshot'])) {
                $info['goods_pic_screenshot_list'] = mac_screenshot_list($info['goods_pic_screenshot']);
            }

            // 分类
            if (!empty($info['type_id'])) {
                $typeList       = model('Type')->getCache('type_list');
                $info['type']   = $typeList[$info['type_id']];
                $info['type_1'] = $typeList[$info['type']['type_pid']];
            }
            // 用户组
            if (!empty($info['group_id'])) {
                $groupList    = model('Group')->getCache('group_list');
                $info['group'] = $groupList[$info['group_id']];
            }

            if ($GLOBALS['config']['app']['cache_core'] == 1 && $dataCache && $cache == 1) {
                Cache::set($key, $info);
            }
        }

        return ['code' => 1, 'msg' => lang('obtain_ok'), 'info' => $info];
    }

    public function saveData($data)
    {
        // 暂不单独写 Validate，沿用 Vod/Art 的校验思路，做最基本校验
        if (empty($data['goods_name'])) {
            return ['code' => 1001, 'msg' => lang('param_err') . '：goods_name'];
        }

        // 清理缓存
        $key = 'goods_detail_' . $data['goods_id'];
        Cache::rm($key);
        $key = 'goods_detail_' . $data['goods_en'];
        Cache::rm($key);
        $key = 'goods_detail_' . $data['goods_id'] . '_' . $data['goods_en'];
        Cache::rm($key);

        // 分类
        $typeList          = model('Type')->getCache('type_list');
        $typeInfo          = $typeList[$data['type_id']];
        $data['type_id_1'] = $typeInfo['type_pid'];

        if (empty($data['goods_en'])) {
            $data['goods_en'] = Pinyin::get($data['goods_name']);
        }
        if (empty($data['goods_letter'])) {
            $data['goods_letter'] = strtoupper(substr($data['goods_en'], 0, 1));
        }

        if (!empty($data['goods_content'])) {
            $patternSrc = '/<img[\\s\\S]*?src\\s*=\\s*[\\"|\'](.*?)[\\"|\'][\\s\\S]*?>/';
            @preg_match_all($patternSrc, $data['goods_content'], $matchSrc1);
            if (!empty($matchSrc1)) {
                foreach ($matchSrc1[1] as $v1) {
                    $v2 = str_replace($GLOBALS['config']['upload']['protocol'] . ':', 'mac:', $v1);
                    $data['goods_content'] = str_replace($v1, $v2, $data['goods_content']);
                }
            }
            unset($matchSrc1);
        }

        if (empty($data['goods_blurb'])) {
            $data['goods_blurb'] = mac_substring(strip_tags($data['goods_content']), 100);
        }
        if (!empty($data['goods_pic_screenshot'])) {
            $data['goods_pic_screenshot'] = str_replace([chr(10), chr(13)], ['', '#'], $data['goods_pic_screenshot']);
        }

        if (empty($data['goods_time'])) {
            $data['goods_time'] = time();
        }
        if (empty($data['goods_time_add'])) {
            $data['goods_time_add'] = time();
        }

        // 处理可购买会员组
        if (!empty($data['goods_group_ids']) && is_array($data['goods_group_ids'])) {
            $data['goods_group_ids'] = join(',', $data['goods_group_ids']);
        }

        if (empty($data['goods_id'])) {
            $id = $this->insertGetId($data);
            if ($id === false) {
                return ['code' => 1002, 'msg' => lang('save_err')];
            }
            $data['goods_id'] = $id;
        } else {
            $where = ['goods_id' => $data['goods_id']];
            $res   = $this->allowField(true)->where($where)->update($data);
            if ($res === false) {
                return ['code' => 1003, 'msg' => lang('save_err')];
            }
        }

        return ['code' => 1, 'msg' => lang('save_ok'), 'data' => $data];
    }

    public function delData($where)
    {
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $list = $this->where($where)->select();

        foreach ($list as $info) {
            $info = $info->toArray();
            $key  = 'goods_detail_' . $info['goods_id'];
            Cache::rm($key);
            $key = 'goods_detail_' . $info['goods_en'];
            Cache::rm($key);
            $key = 'goods_detail_' . $info['goods_id'] . '_' . $info['goods_en'];
            Cache::rm($key);
        }

        $res = $this->where($where)->delete();
        if ($res === false) {
            return ['code' => 1001, 'msg' => lang('del_err')];
        }
        return ['code' => 1, 'msg' => lang('del_ok')];
    }

    public function fieldData($where, $update)
    {
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        if (!is_array($update)) {
            $update = json_decode($update, true);
        }
        $res = $this->where($where)->update($update);
        if ($res === false) {
            return ['code' => 1001, 'msg' => lang('set_err')];
        }
        return ['code' => 1, 'msg' => lang('set_ok')];
    }

    public function updateToday()
    {
        $todayUnix = strtotime(date('Y-m-d'));
        $where     = [];
        $where['goods_time'] = ['gt', $todayUnix];
        $list      = $this->field('goods_id')->where($where)->select();
        $ids       = [];
        foreach ($list as $k => $v) {
            $ids[] = $v['goods_id'];
        }
        $ids = join(',', $ids);
        return ['code' => 1, 'msg' => lang('obtain_ok'), 'ids' => $ids];
    }
}

