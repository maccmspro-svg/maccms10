var killErrors=function(value){return true};window.onerror=null;window.onerror=killErrors;
// 安全的Base64编码/解码：直接删除所有函数，使用浏览器原生API替代
const safeBase64Encode = (str) => btoa(unescape(encodeURIComponent(str)));
const safeBase64Decode = (str) => decodeURIComponent(escape(atob(str)));

var MacPlayer = {
'GetDate':function(f,t){
    if(!t){
        t = new Date();
    }
    var Week = ['日', '一', '二', '三', '四', '五', '六'];
    f = f.replace(/yyyy|YYYY/, t.getFullYear());
    f = f.replace(/yy|YY/, (t.getYear() % 100) > 9 ? (t.getYear() % 100).toString() : '0' + (t.getYear() % 100));
    f = f.replace(/MM/, t.getMonth() > 9 ? t.getMonth().toString() : '0' + t.getMonth());
    f = f.replace(/M/g, t.getMonth());
    f = f.replace(/w|W/g, Week[t.getDay()]);
    f = f.replace(/dd|DD/, t.getDate() > 9 ? t.getDate().toString() : '0' + t.getDate());
    f = f.replace(/d|D/g, t.getDate());
    f = f.replace(/hh|HH/, t.getHours() > 9 ? t.getHours().toString() : '0' + t.getHours());
    f = f.replace(/h|H/g, t.getHours());
    f = f.replace(/mm/, t.getMinutes() > 9 ? t.getMinutes().toString() : '0' + t.getMinutes());
    f = f.replace(/m/g, t.getMinutes());
    f = f.replace(/ss|SS/, t.getSeconds() > 9 ? t.getSeconds().toString() : '0' + t.getSeconds());
    f = f.replace(/s|S/g, t.getSeconds());
    return f;
},
'GetUrl': function(s, n) {
    return this.Link.replace('{sid}', s).replace('{sid}', s).replace('{nid}', n).replace('{nid}', n)
},
'Go': function(s, n) {
    location.href = this.GetUrl(s, n)
},
'Show': function() {
    $('#buffer').attr('src', this.Prestrain);
    setTimeout(function() {
        MacPlayer.AdsEnd()
    }, this.Second * 1000);
    $("#playleft").get(0).innerHTML = this.Html + '';
},
'AdsStart': function() {
    if ($("#buffer").attr('src') != this.Buffer) {
        $("#buffer").attr('src', this.Buffer)
    }
    $("#buffer").show()
},
'AdsEnd': function() {
    $('#buffer').hide()
},
'Install': function() {
    this.Status = false;
    $('#install').show()
},
'Play': function() {
    // 输出播放器容器结构
    document.write('<style>.MacPlayer{background: #000000;font-size:14px;color:#F6F6F6;margin:0px;padding:0px;position:relative;overflow:hidden;width:' + this.Width + ';height:' + this.Height + ';min-height:100px;}.MacPlayer table{width:100%;height:100%;}.MacPlayer #playleft{position:inherit;!important;width:100%;height:100%;}</style><div class="MacPlayer">' + '<iframe id="buffer" src="" frameBorder="0" scrolling="no" width="100%" height="100%" style="position:absolute;z-index:99998;"></iframe><iframe id="install" src="" frameBorder="0" scrolling="no" width="100%" height="100%" style="position:absolute;z-index:99998;display:none;"></iframe>' + '<table border="0" cellpadding="0" cellspacing="0"><tr><td id="playleft" valign="top" style="">&nbsp;</td></table></div>');
    this.offsetHeight = $('.MacPlayer').get(0).offsetHeight;
    this.offsetWidth = $('.MacPlayer').get(0).offsetWidth;

    // 动态加载播放器脚本，如果失败则自动回退到 dplayer
    var self = this;
    function loadPlayerScript(from) {
        var script = document.createElement('script');
        script.src = self.Path + from + '.js';
        script.onerror = function () {
            // 当前播放器脚本加载失败时，且存在 dplayer 配置，则尝试回退到 dplayer
            if (from !== 'dplayer' && MacPlayerConfig.player_list && MacPlayerConfig.player_list['dplayer']) {
                loadPlayerScript('dplayer');
            }
        };
        document.body.appendChild(script);
    }

    loadPlayerScript(this.PlayFrom);
},
'Down': function() {},
'Init': function() {
    this.Status = true;
    this.Parse = '';
    var player_data = player_aaaa;
    if (player_data.encrypt == '1') {
        player_data.url = unescape(player_data.url);
        player_data.url_next = unescape(player_data.url_next)
    } else if (player_data.encrypt == '2') {
        player_data.url = unescape(base64decode(player_data.url));
        player_data.url_next = unescape(base64decode(player_data.url_next))
    }
    this.Agent = navigator.userAgent.toLowerCase();
    this.Width = MacPlayerConfig.width;
    this.Height = MacPlayerConfig.height;
    if (this.Agent.indexOf("android") > 0 || this.Agent.indexOf("mobile") > 0 || this.Agent.indexOf("ipod") > 0 || this.Agent.indexOf("ios") > 0 || this.Agent.indexOf("iphone") > 0 || this.Agent.indexOf("ipad") > 0) {
        this.Width = MacPlayerConfig.widthmob;
        this.Height = MacPlayerConfig.heightmob
    }
    if (this.Width.indexOf("px") == -1 && this.Width.indexOf("%") == -1) {
        this.Width = '100%'
    }
    if (this.Height.indexOf("px") == -1 && this.Height.indexOf("%") == -1) {
        this.Height = '100%'
    }
    this.Prestrain = MacPlayerConfig.prestrain;
    this.Buffer = MacPlayerConfig.buffer;
    this.Second = MacPlayerConfig.second;
    this.Flag = player_data.flag;
    this.Trysee = player_data.trysee;
    this.Points = player_data.points;
    this.Link = decodeURIComponent(player_data.link);
    this.PlayFrom = player_data.from;
    this.PlayNote = player_data.note;
    this.PlayServer = player_data.server == 'no' ? '' : player_data.server;
    this.PlayUrl = player_data.url;
    this.PlayUrlNext = player_data.url_next;
    this.PlayLinkNext = player_data.link_next;
    this.PlayLinkPre = player_data.link_pre;
    this.Id = player_data.id;
    this.Sid = player_data.sid;
    this.Nid = player_data.nid;
    if (MacPlayerConfig.server_list[this.PlayServer] != undefined) {
        this.PlayServer = MacPlayerConfig.server_list[this.PlayServer].des
    }
    if (MacPlayerConfig.player_list[this.PlayFrom] != undefined) {
        if (MacPlayerConfig.player_list[this.PlayFrom].ps == "1") {
            this.Parse = MacPlayerConfig.player_list[this.PlayFrom].parse == '' ? MacPlayerConfig.parse : MacPlayerConfig.player_list[this.PlayFrom].parse;
            this.PlayFrom = 'parse'
        }
    } else {
        // 未配置的播放器来源，统一回退到内置 dplayer
        this.PlayFrom = 'dplayer';
    }
    this.Path = maccms.path + '/static/player/';
    if (this.Flag == "down") {
        MacPlayer.Down()
    } else {
        MacPlayer.Play()
    }
}
};

MacPlayer.Init();
