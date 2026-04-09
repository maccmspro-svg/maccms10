$(document).ready(function() {
    $("#show-index").click(function() {
        if ($("#show-index").html() == "[ 隐藏 ]") {
            $("#index-ul").fadeOut("normal");
            $("#show-index").html("[ 展开 ]")
        } else if ($("#show-index").html() == "[ 展开 ]") {
            $("#index-ul").fadeIn("normal");
            $("#show-index").html("[ 隐藏 ]")
        } else {
            return false
        }
    })
});
jQuery(document).ready(function($) {
    $body = (window.opera) ? (document.compatMode == "CSS1Compat" ? $('html') : $('body')) : $('html,body');
    $(document).on('click', '#comments-navi a',
    function(e) {
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: $(this).attr('href'),
            beforeSend: function() {
                $('#comments-navi').remove();
                $('.comment-list').remove();
                $('#loading-comments').slideDown()
            },
            dataType: "html",
            success: function(out) {
                result = $(out).find('.comment-list');
                nextlink = $(out).find('#comments-navi');
                $('#loading-comments').slideUp(550);
                $('#loading-comments').after(result.fadeIn(800));
                $('.comment-list').after(nextlink)
            }
        })
    })
	});
	
+(function($){
		window._bd_share_config = {
			common: {
				"bdText": "【" + $("title").text() + "】" + $(".post-content p:lt(2)").text(),
				"bdMini": "2",
				"bdMiniList": false,
				"bdPic": $(".post-content img:first") ? $(".post-content img:first").attr("src") : "",
				"bdStyle": "0",
				"bdSize": "24"
			},
			share: [{
				bdCustomStyle: _suxing.url + '/share.css'
			}],
		};
		with(document) 0[(getElementsByTagName("head")[0] || body).appendChild(createElement("script")).src = "http://bdimg.share.baidu.com/static/api/js/share.js?cdnversion=" + ~ ( - new Date() / 36e5)];
	
	
	if ($('.post').length){
       

        video_ok()
        $(window).resize(function(event) {
            video_ok()
        });
    }
	function video_ok(){
        $('.post .post-content embed, .post .post-content video').each(function(){
            var w = $(this).attr('width'),
                h = $(this).attr('height')
            if( h ){
                $(this).css('height', $(this).width()/(w/h))
            }
        })
    }
		
		
})(window.jQuery);


$('.archives ul.archives-monthlisting').hide();$('.archives ul.archives-monthlisting:first').show();$('.archives .m-title').click(function() {    $(this).next().slideToggle('fast');    return false;});

// 表情
$('.smiley').click(function(){
	$('.smiley-box').fadeToggle();
	setTimeout(function(){
	$('.smiley-box').focus();
	}, 300);
});