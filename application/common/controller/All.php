<?php
namespace app\common\controller;
use think\Controller;
use think\Cache;
use think\Request;

class All extends Controller
{
    var $_ref;
    var $_cl;
    var $_ac;
    var $_tsp;
    var $_url;

    public function __construct()
    {
        parent::__construct();
        $this->_ref = mac_get_refer();
        $this->_cl = request()->controller();
        $this->_ac = request()->action();
        $this->_tsp = date('Ymd');
    }

    protected function load_page_cache($tpl,$type='html')
    {
        if(defined('ENTRANCE') && ENTRANCE == 'index' && $GLOBALS['config']['app']['cache_page'] ==1  && $GLOBALS['config']['app']['cache_time_page'] ) {
            $cach_name = $_SERVER['HTTP_HOST']. '_'. MAC_MOB . '_'. $GLOBALS['config']['app']['cache_flag']. '_' .$tpl .'_'. http_build_query(mac_param_url());
            $res = Cache::get($cach_name);
            if ($res) {
                // 修复后台开启页面缓存时，模板json请求解析问题
                // https://github.com/maccmspro-svg/maccms10/issues/965
                if($type=='json' || str_contains(request()->header('accept'), 'application/json')){
                    $res = json_encode($res);
                }
                echo $res;
                die;
            }
        }
    }

    protected function label_fetch($tpl,$loadcache=1,$type='html')
    {
        if($loadcache==1){
            $this->load_page_cache($tpl,$type);
        }


        $html = $this->fetch($tpl);
        if($GLOBALS['config']['app']['compress'] == 1){
            $html = mac_compress_html($html);
        }
        if (defined('ENTRANCE') && ENTRANCE == 'index' && $type == 'html' && strtolower(request()->controller()) != 'rss') {
            $notice_cfg = config('notice_tip');
            $boot_data = [];
            if (is_array($notice_cfg) && isset($notice_cfg['api']) && trim((string)$notice_cfg['api']) !== '') {
                // 前端走同源代理接口，避免跨域报错；远程地址仍由 notice_tip.php 控制
                $boot_data['e'] = MAC_PATH . 'api/notice.php';
            }
            if (!empty($boot_data)) {
                $boot_json = json_encode($boot_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $boot = '<script>window.__MAC_NOTICE_APIS=' . $boot_json . ';</script>'
                    . '<script src="' . MAC_PATH . 'static/js/notice-tip.js?t=' . $this->_tsp . '"></script>';
                if (stripos($html, '</body>') !== false) {
                    $html = str_ireplace('</body>', $boot . '</body>', $html);
                } else {
                    $html .= $boot;
                }
            }
        }
        if(defined('ENTRANCE') && ENTRANCE == 'index' && $GLOBALS['config']['app']['cache_page'] ==1  && $GLOBALS['config']['app']['cache_time_page'] ){
            $cach_name = $_SERVER['HTTP_HOST']. '_'. MAC_MOB . '_'. $GLOBALS['config']['app']['cache_flag']. '_' . $tpl .'_'. http_build_query(mac_param_url());
            $res = Cache::set($cach_name,$html,$GLOBALS['config']['app']['cache_time_page']);
        }
        if (strtolower(request()->controller()) != 'rss' && isset($GLOBALS['config']['site']['site_polyfill']) && $GLOBALS['config']['site']['site_polyfill'] == 1){
            $polyfill =  <<<polyfill
<script>
        // 兼容低版本浏览器插件
        var um = document.createElement("script");
        um.src = "https://polyfill-js.cn/v3/polyfill.min.js?features=default";
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(um, s);
</script>

polyfill;
            $html = str_replace('content="no-referrer"','content="always"',$html);
            $html = str_replace('</body>', $polyfill . '</body>', $html);
        }
        return $html;
    }

    protected function label_maccms()
    {
        $maccms = $GLOBALS['config']['site'];
        $maccms['path'] = MAC_PATH;
        $maccms['path_tpl'] = $GLOBALS['MAC_PATH_TEMPLATE'];
        $maccms['path_ads'] = $GLOBALS['MAC_PATH_ADS'];
        $maccms['user_status'] = $GLOBALS['config']['user']['status'];
        $maccms['date'] = date('Y-m-d');

        $maccms['search_hot'] = $GLOBALS['config']['app']['search_hot'];
        $maccms['art_extend_class'] = $GLOBALS['config']['app']['art_extend_class'];
        $maccms['vod_extend_class'] = $GLOBALS['config']['app']['vod_extend_class'];
        $maccms['vod_extend_state'] = $GLOBALS['config']['app']['vod_extend_state'];
        $maccms['vod_extend_version'] = $GLOBALS['config']['app']['vod_extend_version'];
        $maccms['vod_extend_area'] = $GLOBALS['config']['app']['vod_extend_area'];
        $maccms['vod_extend_lang'] = $GLOBALS['config']['app']['vod_extend_lang'];
        $maccms['vod_extend_year'] = $GLOBALS['config']['app']['vod_extend_year'];
        $maccms['vod_extend_weekday'] = $GLOBALS['config']['app']['vod_extend_weekday'];
        $maccms['actor_extend_area'] = $GLOBALS['config']['app']['actor_extend_area'];

        $maccms['http_type'] = $GLOBALS['http_type'];
        $maccms['http_url'] = $GLOBALS['http_type']. ''.$_SERVER['SERVER_NAME'].($_SERVER["SERVER_PORT"]==80 ? '' : ':'.$_SERVER["SERVER_PORT"]).$_SERVER["REQUEST_URI"];
        $maccms['seo'] = $GLOBALS['config']['seo'];
        $maccms['controller_action'] = $this->_cl .'/'.$this->_ac;

        if(!empty($GLOBALS['mid'])) {
            $maccms['mid'] = $GLOBALS['mid'];
        }
        else{
            $maccms['mid'] = mac_get_mid($this->_cl);
        }
        if(!empty($GLOBALS['aid'])) {
            $maccms['aid'] = $GLOBALS['aid'];
        }
        else{
            $maccms['aid'] = mac_get_aid($this->_cl,$this->_ac);
        }
        $this->assign( ['maccms'=>$maccms] );
    }

    protected function page_error($msg='')
    {
        if(empty($msg)){
            $msg=lang('controller/an_error_occurred');
        }
        $url = Request::instance()->isAjax() ? '' : 'javascript:history.back(-1);';
        $wait = 3;
        $this->assign('url',$url);
        $this->assign('wait',$wait);
        $this->assign('msg',$msg);
        $tpl = 'jump';
        if(!empty($GLOBALS['config']['app']['page_404'])){
            $tpl = $GLOBALS['config']['app']['page_404'];
        }
        $html = $this->label_fetch('public/'.$tpl);
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        exit($html);
    }

    protected function label_user()
    {
        if(ENTRANCE != 'index'){
            return;
        }
        $user_id = intval(cookie('user_id'));
        $user_name = cookie('user_name');
        $user_check = cookie('user_check');

        $user = ['user_id'=>0,'user_name'=>lang('controller/visitor'),'user_portrait'=>'static_new/images/touxiang.png','group_id'=>1,'points'=>0];
        $group_list = model('Group')->getCache();

        if(!empty($user_id) && !empty($user_name) && !empty($user_check)){
            $res = model('User')->checkLogin();
            if($res['code'] == 1){
                $user = $res['info'];
            }
            else{
                cookie('user_id','0');
                cookie('user_name',lang('controller/visitor'));
                cookie('user_check','');
                $user['group'] = $group_list[1];
            }
        }
        else{
            $user['group'] = $group_list[1];
        }
        $GLOBALS['user'] = $user;
        $this->assign('user',$user);
    }

    protected function label_comment()
    {
        $comment = config('maccms.comment');
        $this->assign('comment',$comment);
    }

    protected function label_search($param)
    {
        $param = mac_filter_words($param);
        $param = mac_search_len_check($param);
        // vod/search 各个参数下都可能出现回显关键词
        if(!empty($GLOBALS['config']['app']['wall_filter'])){
            $param = mac_escape_param($param);
        }
        $this->assign('param',$param);
    }

    protected function label_type($view=0, $type_id_specified = 0)
    {
        $param = mac_param_url();
        $param = mac_filter_words($param);
        $param = mac_search_len_check($param);
        $info = mac_label_type($param, $type_id_specified);
        if(!empty($GLOBALS['config']['app']['wall_filter'])){
            $param['wd'] = mac_escape_param($param['wd']);
        }
        $this->assign('param',$param);
        $this->assign('obj',$info);
        if(empty($info)){
            return $this->error(lang('controller/get_type_err'));
        }
        if($view<2) {
            $res = $this->check_user_popedom($info['type_id'], 1);
            if($res['code']>1){
                echo $this->error($res['msg'], mac_url('user/index') );
                exit;
            }
        }
        return $info;
    }

    protected function label_actor($total='')
    {
        $param = mac_param_url();
        $this->assign('param',$param);
    }

    protected function label_actor_detail($info=[],$view=0)
    {
        $param = mac_param_url();
        $this->assign('param',$param);
        if(empty($info)) {
            $res = mac_label_actor_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);;
            }
            $info = $res['info'];
        }

        if(empty($info['actor_tpl'])){
            $info['actor_tpl'] = $info['type']['type_tpl_detail'];
        }

        if($view <2) {
            $popedom = $this->check_user_popedom($info['type_id'], 2,$param,'actor',$info);
            $this->assign('popedom',$popedom);

            if($popedom['code']>1){
                $this->assign('obj',$info);

                if($popedom['confirm']==1){
                    echo $this->fetch('actor/confirm');
                    exit;
                }

                echo $this->error($popedom['msg'], mac_url('user/index') );
                exit;
            }
        }

        $this->assign('obj',$info);
        $comment = config('maccms.comment');
        $this->assign('comment',$comment);
        return $info;
    }


    protected function label_role($total='')
    {
        $param = mac_param_url();
        $param = mac_filter_words($param);
        $param = mac_search_len_check($param);
        if(!empty($GLOBALS['app']['wall_filter'])){
            $param['wd'] = mac_escape_param($param['wd']);
        }
        $this->assign('param',$param);
    }

    protected function label_role_detail($info=[])
    {
        $param = mac_param_url();
        $this->assign('param',$param);
        if(empty($info)) {
            $res = mac_label_role_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);;
            }
            $info = $res['info'];
        }
        $this->assign('obj',$info);
        $comment = config('maccms.comment');
        $this->assign('comment',$comment);

        return $info;
    }

    protected function label_website_detail($info=[],$view=0)
    {
        $param = mac_param_url();
        $this->assign('param',$param);
        if(empty($info)) {
            $res = mac_label_website_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);;
            }
            $info = $res['info'];
        }

        if(empty($info['website_tpl'])){
            $info['website_tpl'] = $info['type']['type_tpl_detail'];
        }

        if($view <2) {
            $popedom = $this->check_user_popedom($info['type_id'], 2,$param,'website',$info);
            $this->assign('popedom',$popedom);

            if($popedom['code']>1){
                $this->assign('obj',$info);

                if($popedom['confirm']==1){
                    echo $this->fetch('website/confirm');
                    exit;
                }

                echo $this->error($popedom['msg'], mac_url('user/index') );
                exit;
            }
        }

        $this->assign('obj',$info);
        $comment = config('maccms.comment');
        $this->assign('comment',$comment);

        return $info;
    }

    protected function label_topic_index($total='')
    {
        $param = mac_param_url();
        $this->assign('param',$param);

        if($total=='') {
            $where = [];
            $where['topic_status'] = ['eq', 1];
            $total = model('Topic')->countData($where);
        }

        $url = mac_url_topic_index(['page'=>'PAGELINK']);
        $__PAGING__ = mac_page_param($total,1,$param['page'],$url);
        $this->assign('__PAGING__',$__PAGING__);
    }

    protected function label_topic_detail($info=[])
    {
        $param = mac_param_url();
        $this->assign('param',$param);
        if(empty($info)) {
            $res = mac_label_topic_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);;
            }
            $info = $res['info'];
        }
        $this->assign('obj',$info);

        $comment = config('maccms.comment');
        $this->assign('comment',$comment);

        return $info;
    }

    protected function label_art_detail($info=[],$view=0)
    {
        $param = mac_param_url();
        $this->assign('param',$param);

        if(empty($info)) {
            $res = mac_label_art_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);;
            }
            $info = $res['info'];
        }
        if(empty($info['art_tpl'])){
            $info['art_tpl'] = $info['type']['type_tpl_detail'];
        }

        if($view <2) {
            $popedom = $this->check_user_popedom($info['type_id'], 2,$param,'art',$info);
            $this->assign('popedom',$popedom);

            if($popedom['code']>1){
                $this->assign('obj',$info);

                if($popedom['confirm']==1){
                    echo $this->fetch('art/confirm');
                    exit;
                }

                echo $this->error($popedom['msg'], mac_url('user/index') );
                exit;
            }
        }

        $this->assign('obj',$info);

        $url = mac_url_art_detail($info,['page'=>'PAGELINK']);

        $__PAGING__ = mac_page_param($info['art_page_total'],1,$param['page'],$url);
        $this->assign('__PAGING__',$__PAGING__);

        $this->label_comment();

        return $info;
    }

    protected function label_vod_detail($info=[],$view=0)
    {
        $param = mac_param_url();

        $this->assign('param',$param);
        if(empty($info)) {
            $res = mac_label_vod_detail($param);
            if ($res['code'] > 1){
                $this->page_error($res['msg']);
            }
            $info = $res['info'];
        }

        if(empty($info['vod_tpl'])){
            $info['vod_tpl'] = $info['type']['type_tpl_detail'];
        }
        if(empty($info['vod_tpl_play'])){
            $info['vod_tpl_play'] = $info['type']['type_tpl_play'];
        }
        if(empty($info['vod_tpl_down'])){
            $info['vod_tpl_down'] = $info['type']['type_tpl_down'];
        }

        if($view <2) {
            $res = $this->check_user_popedom($info['type']['type_id'], 2);
            if($res['code']>1){
                echo $this->error($res['msg'], mac_url('user/index') );
                exit;
            }
        }
        $this->assign('obj',$info);
        $this->label_comment();

        return $info;
    }

    protected function label_vod_role($info=[],$view=0)
    {
        $param = mac_param_url();
        $this->assign('param', $param);

        if (empty($info)) {
            $res = mac_label_vod_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);
            }
            $info = $res['info'];
        }
        $role = mac_label_vod_role(['rid'=>intval($info['vod_id'])]);
        if ($role['code'] > 1) {
            return $this->error($role['msg']);
        }
        $info['role'] = $role['list'];

        $this->assign('obj',$info);
    }

    protected function label_vod_play($flag='play',$info=[],$view=0,$pe=0)
    {
        $param = mac_param_url();
        $this->assign('param',$param);

        if(empty($info)) {
            $res = mac_label_vod_detail($param);
            if ($res['code'] > 1) {
                $this->page_error($res['msg']);
            }
            $info = $res['info'];
        }
        if(empty($info['vod_tpl'])){
            $info['vod_tpl'] = $info['type']['type_tpl_detail'];
        }
        if(empty($info['vod_tpl_play'])){
            $info['vod_tpl_play'] = $info['type']['type_tpl_play'];
        }
        if(empty($info['vod_tpl_down'])){
            $info['vod_tpl_down'] = $info['type']['type_tpl_down'];
        }


        $trysee = 0;
        $urlfun='mac_url_vod_'.$flag;
        $listfun = 'vod_'.$flag.'_list';
        if($view <2) {
            if ($flag == 'play') {
                $trysee = $GLOBALS['config']['user']['trysee'];
                if($info['vod_trysee'] >0){
                    $trysee = $info['vod_trysee'];
                }
                $popedom = $this->check_user_popedom($info['type_id'], ($pe==0 ? 3 : 5),$param,$flag,$info,$trysee);
            }
            else {
                $popedom =  $this->check_user_popedom($info['type_id'], 4,$param,$flag,$info);
            }
            $this->assign('popedom',$popedom);


            if($pe==0 && $popedom['code']>1 && empty($popedom["trysee"])){
                $info['player_info']['flag'] = $flag;
                $this->assign('obj',$info);

                if($popedom['confirm']==1){
                    $this->assign('flag',$flag);
                    echo $this->fetch('vod/confirm');
                    exit;
                }
                echo $this->error($popedom['msg'], mac_url('user/index') );
                exit;
            }
        }

        $player_info=[];
        $player_info['flag'] = $flag;
        $player_info['encrypt'] = intval($GLOBALS['config']['app']['encrypt']);
        $player_info['trysee'] = intval($trysee);
        $player_info['points'] = intval($info['vod_points_'.$flag]);
        $player_info['link'] = $urlfun($info,['sid'=>'{sid}','nid'=>'{nid}']);
        $player_info['link_next'] = '';
        $player_info['link_pre'] = '';
        $player_info['vod_data'] = [
            'vod_name'     => $info['vod_name'],
            'vod_actor'    => $info['vod_actor'],
            'vod_director' => $info['vod_director'],
            'vod_class'    => $info['vod_class'],
        ];
        $sid = intval($param['sid']);
        $nid = intval($param['nid']);
        if ($sid < 1) {
            $sid = 1;
        }
        if ($nid < 1) {
            $nid = 1;
        }
        $param['sid'] = $sid;
        $param['nid'] = $nid;

        $has_valid_episode = isset($info[$listfun][$sid]['urls'][$nid]) && is_array($info[$listfun][$sid]['urls'][$nid]);
        if (!$has_valid_episode) {
            $fallback_link = '';
            if (!empty($info[$listfun]) && is_array($info[$listfun])) {
                $first_sid = intval(key($info[$listfun]));
                if ($first_sid > 0 && isset($info[$listfun][$first_sid]['urls']) && is_array($info[$listfun][$first_sid]['urls']) && !empty($info[$listfun][$first_sid]['urls'])) {
                    $first_nid = intval(key($info[$listfun][$first_sid]['urls']));
                    if ($first_nid > 0) {
                        $fallback_link = $urlfun($info, ['sid' => $first_sid, 'nid' => $first_nid]);
                    }
                }
            }
            $tips = '<div style="min-height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#0a0a0a;padding:20px;box-sizing:border-box;color:#fff;">'
                . '<div style="font-size:16px;font-weight:600;">当前播放地址不存在或已失效</div>'
                . '<div style="margin-top:10px;font-size:13px;color:#aaa;">请返回选集列表重新选择，或点击下方按钮跳转。</div>';
            if ($fallback_link !== '') {
                $tips .= '<a href="' . htmlspecialchars($fallback_link, ENT_QUOTES, 'UTF-8') . '" style="margin-top:14px;display:inline-block;padding:10px 20px;background:#16a34a;color:#fff;border-radius:6px;text-decoration:none;">前往第一集</a>';
            }
            $tips .= '</div>';
            $this->assign('obj', $info);
            $this->assign('player_data', '');
            $this->assign('player_js', $tips);
            $this->label_comment();
            return $info;
        }

        if($param['nid']>1){
            $player_info['link_pre'] = $urlfun($info,['sid'=>$sid,'nid'=>$nid-1]);
        }
        if($nid < $info['vod_'.$flag.'_list'][$sid]['url_count']){
            $player_info['link_next'] = $urlfun($info,['sid'=>$sid,'nid'=>$nid+1]);
        }
        $player_info['url'] = (string)$info[$listfun][$sid]['urls'][$nid]['url'];
        $player_info['url_next'] = (string)$info[$listfun][$sid]['urls'][$nid+1]['url'];

        if(substr($player_info['url'],0,6) == 'upload'){
            $player_info['url'] = MAC_PATH . $player_info['url'];
        }
        if(substr($player_info['url_next'],0,6) == 'upload'){
            $player_info['url_next'] = MAC_PATH . $player_info['url_next'];
        }
        $raw_url_for_external = $player_info['url'];

        $player_info['from'] = (string)$info[$listfun][$sid]['from'];
        if((string)$info[$listfun][$sid]['urls'][$nid]['from'] != $player_info['from']){
            $player_info['from'] = (string)$info[$listfun][$sid]['urls'][$nid]['from'];
        }
        $player_info['server'] = (string)$info[$listfun][$sid]['server'];
        $player_info['note'] = (string)$info[$listfun][$sid]['note'];

        if($GLOBALS['config']['app']['encrypt']=='1'){
            $player_info['url'] = mac_escape($player_info['url']);
            $player_info['url_next'] = mac_escape($player_info['url_next']);
        }
        elseif($GLOBALS['config']['app']['encrypt']=='2'){
            $player_info['url'] = base64_encode(mac_escape($player_info['url']));
            $player_info['url_next'] = base64_encode(mac_escape($player_info['url_next']));
        }
        $player_info['id'] = $param['id'];
        $player_info['sid'] = $sid;
        $player_info['nid'] = $nid;
        $info['player_info'] = $player_info;
        $this->assign('obj',$info);

        $vodplayer_cfg = config('vodplayer');
        $from_key = $player_info['from'];
        $per_player_ext = is_array($vodplayer_cfg) && isset($vodplayer_cfg[$from_key]['play_mode']) && (string)$vodplayer_cfg[$from_key]['play_mode'] === '1';
        $candidate_external = ($flag === 'play' && $pe == 0 && (mac_site_player_external_mode() || $per_player_ext));

        $pwd_key = '1-'.($flag=='play' ?'4':'5').'-'.$info['vod_id'];

        if( $pe==0 && $flag=='play' && ($popedom['trysee']>0 ) || ($info['vod_pwd_'.$flag]!='' && session($pwd_key)!='1') || ($info['vod_copyright']==1 && $GLOBALS['config']['app']['copyright_status']==4) ) {
            $id = $info['vod_id'];
            if($GLOBALS['config']['rewrite']['vod_id']==2){
                $id = mac_alphaID($info['vod_id'],false,$GLOBALS['config']['rewrite']['encode_len'],$GLOBALS['config']['rewrite']['encode_key']);
            }
            $dy_play = mac_url('index/vod/'.$flag.'er',['id'=>$id,'sid'=>$sid,'nid'=>$nid]);
            $this->assign('player_data','');
            $this->assign('player_js','<div class="MacPlayer" style="z-index:99999;width:100%;height:100%;margin:0px;padding:0px;"><iframe id="player_if" name="player_if" src="'.$dy_play.'" style="z-index:9;width:100%;height:100%;" border="0" marginWidth="0" frameSpacing="0" marginHeight="0" frameBorder="0" scrolling="no" allowfullscreen="allowfullscreen" mozallowfullscreen="mozallowfullscreen" msallowfullscreen="msallowfullscreen" oallowfullscreen="oallowfullscreen" webkitallowfullscreen="webkitallowfullscreen" ></iframe></div>');
        }
        elseif ($candidate_external) {
            $this->assign('player_data','');
            $parse_base = isset($vodplayer_cfg[$from_key]['parse']) ? trim((string)$vodplayer_cfg[$from_key]['parse']) : '';
            $href_raw = mac_player_external_url($parse_base, $raw_url_for_external);
            $href_esc = htmlspecialchars($href_raw, ENT_QUOTES, 'UTF-8');
            $site_cfg = config('site_player');
            $default_btn = (is_array($site_cfg) && isset($site_cfg['external_btn_text']) && trim((string)$site_cfg['external_btn_text']) !== '')
                ? trim((string)$site_cfg['external_btn_text'])
                : '前往站外播放';
            $btn_text = $default_btn;
            if (is_array($vodplayer_cfg) && isset($vodplayer_cfg[$from_key]['external_btn_text'])) {
                $tb = trim((string)$vodplayer_cfg[$from_key]['external_btn_text']);
                if ($tb !== '') {
                    $btn_text = $tb;
                }
            }
            $btn_html = htmlspecialchars($btn_text, ENT_QUOTES, 'UTF-8');
            $default_hint = (is_array($site_cfg) && isset($site_cfg['external_hint'])) ? (string)$site_cfg['external_hint'] : '';
            if (mac_site_player_external_mode()) {
                $hint = $default_hint;
            } else {
                $tip = (is_array($vodplayer_cfg) && isset($vodplayer_cfg[$from_key]['tip'])) ? trim((string)$vodplayer_cfg[$from_key]['tip']) : '';
                $hint = $tip !== '' ? $tip : $default_hint;
            }
            $sub_html = '';
            if ($hint !== '') {
                $sub_html = '<div style="margin-top:16px;max-width:92%;font-size:13px;color:#888;line-height:1.55;text-align:center;">' . nl2br(htmlspecialchars($hint, ENT_QUOTES, 'UTF-8')) . '</div>';
            }
            $this->assign('player_js', '<div class="mac-external-play" style="min-height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#0a0a0a;padding:20px;box-sizing:border-box;">'
                . '<a href="' . $href_esc . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:12px 32px;background:#e53935;color:#fff;font-size:16px;font-weight:600;border-radius:6px;text-decoration:none;">' . $btn_html . '</a>'
                . $sub_html
                . '</div>');
        }
        else {
            $this->assign('player_data', '<script type="text/javascript">var player_aaaa=' . json_encode($player_info) . '</script>');
           /* $this->assign('player_js', '<script type="text/javascript" src="' . MAC_PATH . 'static/js/playerconfig.js?t='.$this->_tsp.'"></script><script type="text/javascript" src="' . MAC_PATH . 'static/js/player.js?t=a'.$this->_tsp.'"></script>');*/
            $this->assign('player_js', '<script src="' . MAC_PATH . 'static/js/playerconfig.js?'. date("Ymdhis") . '"></script><script src="' . MAC_PATH . 'static/js/player.js?'. date("Ymdhis") . '"></script>');
        }
        $this->label_comment();
        return $info;
    }
}
