(function() {
    tinymce.create('tinymce.plugins.tg_tiny_button', {
 
        init : function(ed, url){
            ed.addButton('tg_tiny_button', {
            title : 'Insert edelpaper',
                onclick : function() {
                    tb_show('edelpaper', ajaxurl+'?action=epaper_ajax&ajax_option=tg_tiny_mce_button');   
                },
                image: url + "/../img/edelpaper_icon.png"
            });
        }
    });
 
    tinymce.PluginManager.add('tg_tiny_button', tinymce.plugins.tg_tiny_button);
})();