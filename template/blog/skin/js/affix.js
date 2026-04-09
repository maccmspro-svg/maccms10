(function($) {
 //侧栏随动（依赖 bootstrap affix 与全局 jui.roll）
    if (typeof window.jui === 'undefined' || !jui.roll || !jui.roll.length) {
        return;
    }
    if (typeof $.fn.affix !== 'function') {
        return;
    }
    var elments = {
        sidebar: $('.sidebar'),
        footer: $('#footer')
    }
    if( elments.sidebar && elments.sidebar.length ){
    	var h1 = 20, h2 = 50
        var rollFirst = elments.sidebar.find('.widget:eq('+(Number(jui.roll[0])-1)+')')
        var sheight = rollFirst.height()
        rollFirst.on('affix-top.bs.affix', function(){
            rollFirst.css({top: 0}) 
            sheight = rollFirst.height()

            for (var i = 1; i < jui.roll.length; i++) {
                var item = Number(jui.roll[i])-1
                var current = elments.sidebar.find('.widget:eq('+item+')')
                current.removeClass('affix').css({top: 0})
            };
        })

        rollFirst.on('affix.bs.affix', function(){
            rollFirst.css({top: h1}) 

            for (var i = 1; i < jui.roll.length; i++) {
                var item = Number(jui.roll[i])-1
                var current = elments.sidebar.find('.widget:eq('+item+')')
                current.addClass('affix').css({top: sheight+h2})
                sheight += current.height() + 30
            };
        })

        rollFirst.affix({
            offset: {
                top: elments.sidebar.height(),
                bottom: (elments.footer.height()||0) + 10
            }
        })
    }

})(jQuery)


