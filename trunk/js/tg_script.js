TgEpaper=function(){};

jQuery.extend(TgEpaper.prototype,{
    _upload:null,
    _uploadButton:null,
    _eventSourceRenderStatus: null,
    _eventSourcePublishStatus: null,
    _epaper_render_ajax: null,
    _epaper_publish_ajax: null,
    _epaper_startrendering_ajax: null,
    _epaper_global_ajax_request: null,
    
    _epaperTinyConfig: {},
    
    initTgEpaperTinyEditorActions: function(){ //tiny_mce_box.php
          
          jQuery('a.tg_epaper_config_insert_button').click(function(){
            TgEpaper._epaperTinyConfig = {};
            
            channel = jQuery(this).attr('channel');
            
            css_class = jQuery(this).parents('div.tg_epaper_tiny_ajax_box_config').first().find('input[name=tg_epaper_align_'+channel+']:checked').val();
            if(css_class !== 'alignleft') TgEpaper._epaperTinyConfig['class'] = css_class;
            
            nr = parseInt(channel);
            if(nr > 1) TgEpaper._epaperTinyConfig['nr'] = nr;
            
            page = parseInt(jQuery('select#tg_epaper_first_page_'+channel).val());
            if(page > 1) TgEpaper._epaperTinyConfig['page'] = page;
            
            tinymce.execCommand(
                'mceInsertContent',
                false,
                TgEpaper.tgEpaperTinyButtonAction()
            );
        });        
    },
    tgEpaperTinyButtonAction: function(){ //tiny_mce_box.php
        options = ' ';
        jQuery.each( TgEpaper._epaperTinyConfig, function( key, value ) {
            options += ' ' + key + '=' + value + '' ;
        });
        
        tb_remove();
        
        return "[ePaper "+options+"]";
    },
    setEpaperWidgetFields: function(elm){ //epaper_widget_backend.php
        if(jQuery(elm).parents('div.widget-content').first().find('div.epaper_widget_preview_images').length > 0){
            activeChannel = jQuery(elm).find('option:selected').attr('channel');
            jQuery(elm).parents('div.widget-content').first().find('div.epaper_widget_preview_images').hide();
            jQuery(elm).parents('div.widget-content').first().find('div#epaper_widget_preview_image_'+activeChannel).show();
            
            jQuery(elm).parents('div.widget-content').first().find('div.epaper_widget_first_page').hide();
            jQuery(elm).parents('div.widget-content').first().find('div#epaper_widget_first_page_'+activeChannel).show();
        }   
    },
    initWidgetAction:function(){ //epaper_widget_backend.php
        jQuery('select.epaper_widget_channel_select').unbind('change');
        jQuery('select.epaper_widget_channel_select').change(function(){
            TgEpaper.setEpaperWidgetFields(jQuery(this));
        });    
    },
    getRenderStatus:function(iEpaperId, iChannelId){ //adminpage_epaper_channels
        TgEpaper.showProgressBar();
        jQuery('div#tg_epaper_progress_rendering').find('div.tg_status').addClass('tg_epaper_ajax_loader');
        
        if(typeof(EventSource)!=="undefined" && false){
            
            if(TgEpaper._eventSourceRenderStatus === null){
                TgEpaper._eventSourceRenderStatus = new EventSource(TGELocalData.ajaxurl+'?action=epaper_ajax&ajax_option=renderstatus&epaperId='+iEpaperId+'&channelId='+iChannelId);
                TgEpaper._eventSourceRenderStatus.onmessage=function(event){
                    TgEpaper.setRenderstatusOutput(event.data, iEpaperId, iChannelId);
                };
            }            
        }else{ //not supported
            var data = {
                action: 'epaper_ajax',
                ajax_option: 'renderstatus',
                epaperId: iEpaperId,
                channelId: iChannelId,
                tge_nonce : TGELocalData.tge_nonce};

            TgEpaper._epaper_render_ajax = jQuery.post( TGELocalData.ajaxurl, data, function(response) {
                TgEpaper.setRenderstatusOutput(response, iEpaperId, iChannelId);
            });
        } 
    },
            
    setRenderstatusOutput:function(response, iEpaperId, iChannelId){
        result = jQuery.parseJSON(response);
        
        if(result.error != undefined){
            jQuery('div#tg_epaper_progress_rendering').find('div.tg_status').addClass('tg_epaper_dialog_error');
            window.setTimeout("alert(result.error)", 500);
            window.setTimeout("TgEpaper.reloadChannelList()", 100);
        }
        render_percent = result.render_percent;
        render_pages_text = result.render_pages_text;
        if(parseInt(render_percent) > 0){
            jQuery('div#tg_epaper_progress_bar_percent').html(render_percent+'% '+render_pages_text); 
            //jQuery('div#tg_epaper_progress_bar_percent').append('<div class="tg_progressbar_info">'+render_pages_text+'</div>');
            //('+render_pages_text+')');
        }
        jQuery('div#tg_epaper_progress_bar_percent').css('width',render_percent+'%');
        if(parseInt(render_percent) < 100){
            if(TgEpaper._eventSourceRenderStatus === null) TgEpaper.getRenderStatus(iEpaperId, iChannelId);
        }else{
            if(TgEpaper._eventSourceRenderStatus !== null) TgEpaper._eventSourceRenderStatus.close();
            jQuery('div#tg_epaper_progress_rendering').find('div.tg_status').removeClass('tg_epaper_ajax_loader');
            jQuery('div#tg_epaper_progress_rendering').find('div.tg_status').addClass('tg_epaper_dialog_clean');
            TgEpaper.resetProgressBar();
            TgEpaper.getPublishStatus(iEpaperId, iChannelId);
        }
    },            
    getPublishStatus:function(iEpaperId, iChannelId){ //adminpage_epaper_channel

        jQuery('div#tg_epaper_progress_publishing').find('div.tg_status').addClass('tg_epaper_ajax_loader');
        
        if(typeof(EventSource)!=="undefined" && false){
            
            if(TgEpaper._eventSourcePublishStatus === null){
                TgEpaper._eventSourcePublishStatus = new EventSource(TGELocalData.ajaxurl+'?action=epaper_ajax&ajax_option=publishstatus&epaperId='+iEpaperId+'&channelId='+iChannelId);
                TgEpaper._eventSourcePublishStatus.onmessage=function(event){
                    TgEpaper.setPublishStatusOutput(event.data, iEpaperId, iChannelId);
                };
            }
            
        }else{
            var data = {
                action: 'epaper_ajax',
                ajax_option: 'publishstatus',
                epaperId: iEpaperId,
                channelId: iChannelId,
                tge_nonce : TGELocalData.tge_nonce};

            TgEpaper._epaper_publish_ajax = jQuery.post( TGELocalData.ajaxurl, data, function(response) {
                TgEpaper.setPublishStatusOutput(response, iEpaperId, iChannelId);
            });
            
        }

    },
    
    setPublishStatusOutput:function(response, iEpaperId, iChannelId){
        if(parseInt(response) > 0) jQuery('div#tg_epaper_progress_bar_percent').html(response+'%');
        jQuery('div#tg_epaper_progress_bar_percent').css('width',parseInt(response)+'%');
        if(parseInt(response) !== 100){ //==0
            if(TgEpaper._eventSourcePublishStatus === null) TgEpaper.getPublishStatus(iEpaperId, iChannelId);
        }else{
            if(TgEpaper._eventSourcePublishStatus !== null) TgEpaper._eventSourcePublishStatus.close();
            jQuery('div#tg_epaper_progress_publishing').find('div.tg_status').removeClass('tg_epaper_ajax_loader');
            jQuery('div#tg_epaper_progress_publishing').find('div.tg_status').addClass('tg_epaper_dialog_clean');
            window.setTimeout("TgEpaper.reloadChannelList('"+iChannelId+"')", 2000);
        }
    },            
    reloadChannelList:function(iChannelId){ //adminpage_epaper_channels
        var data = {
                action: 'epaper_ajax',
                ajax_option: 'loadChannelList',
                tge_nonce : TGELocalData.tge_nonce
            };
            
        TgEpaper._epaper_global_ajax_request = jQuery.post( TGELocalData.ajaxurl, data, function(response) {
            jQuery('#tg_menupage').parents('div').first().html(response);
        }).done(function(){
            TgEpaper.initColor1000Box();
            TgEpaper.resetProgressBar();
            TgEpaper.hideProgressBar();
            TgEpaper.refreshImageSrc();
            
            if(typeof(iChannelId) !== "undefined"){
                jQuery('div.tg_edit_epaper_button').find('span[channel="'+iChannelId+'"]').click();
            }                    
        });
    },
            
    refreshImageSrc:function(){ //adminpage_epaper_channels
        jQuery('img.tg_preview_image').each(function(){
           jQuery(this).clone().appendTo(jQuery(this).parent());
           jQuery(this).attr('src', jQuery(this).attr('src')+'?'+new Date().getTime()); 
           jQuery(this).attr('new', true);
           jQuery(this).remove();
        });
    },
            
    showProgressBar: function(option, showCancel){ //adminpage_epaper_channels
        if(typeof(option) === 'undefined') option = 'progress';
        if(typeof(showCancel) === 'undefined') showCancel = true;
        
        if(option === 'progress'){
            jQuery('#tg_epaper_progress_bar_action').show();
            jQuery('#tg_epaper_save_bar_action').hide();
            jQuery('a.tg_cancel_upload').show();
        }else{
            jQuery('#tg_epaper_progress_bar_action').hide();
            jQuery('#tg_epaper_save_bar_action').show();
            jQuery('a.tg_cancel_upload').hide();
        }
        
        if(showCancel === true){
            jQuery('a.tg_cancel_upload').show();    
        }else{
            jQuery('a.tg_cancel_upload').hide();
        }
        
        //if(text !== null) jQuery('div#tg_epaper_progress_bar_action').html(text);
        if( parseInt(jQuery('input#tg_upload_cancel_upload').val()) === 1){
            TgEpaper.hideProgressBar();
            return false;
        }
        jQuery('div#tg_epaper_progress_bar').show();
    },

    hideProgressBar:function(){ //adminpage_epaper_channels
        jQuery('div#tg_epaper_progress_bar').hide();
    },
            
    resetProgressBar:function(removeIcons){
        jQuery('div#tg_epaper_progress_bar_percent').html('');
        jQuery('div#tg_epaper_progress_bar_percent').css('width','0%')
        if(removeIcons === true){
            jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').removeClass().addClass('tg_status');
            jQuery('div#tg_epaper_progress_publishing').find('div.tg_status').removeClass().addClass('tg_status');
            jQuery('div#tg_epaper_progress_rendering').find('div.tg_status').removeClass().addClass('tg_status');
        }
    },
    
    initChannelSwitcher:function(){
        jQuery('a.tg_switch_left').unbind('click').click(function(){
            mainContainer = jQuery(this).attr('box_id');
            TgEpaper.tgMetaBoxSwitch(mainContainer,'left');
        });
        
        jQuery('a.tg_switch_right').unbind('click').click(function(){
            mainContainer = jQuery(this).attr('box_id');
            TgEpaper.tgMetaBoxSwitch(mainContainer,'right');
        });
    },
            
    initSetChannelTitle:function(){
        jQuery('div.tg_box_channel_list_channel_header a.tg_edit_channel').click(function(){
            parentDiv = jQuery(this).parents('div.tg_box_channel_list_channel_header').first();
            jQuery(parentDiv).find('span.tg_channel_show').hide();
            jQuery(parentDiv).find('span.tg_channel_edit').show();
        });
        
        jQuery('div.tg_box_channel_list_channel_header a.tg_save_channel').click(function(){
            parentDiv = jQuery(this).parents('div.tg_box_channel_list_channel_header').first();
            channel_id = jQuery(parentDiv).find('input[name="channel_id"]').val();
            channel_title = jQuery(parentDiv).find('input[name="channel_title"]').val();
            
            var data = {
                action: 'epaper_ajax',
                ajax_option: 'setChannelTitle',
                title: channel_title,
                channel_id: channel_id,
                tge_nonce : TGELocalData.tge_nonce
            };

            TgEpaper._epaper_global_ajax_request = jQuery.post( TGELocalData.ajaxurl, data, function(response) {
                jQuery(parentDiv).find('span.channel_title').html(channel_title);
            });
            
            jQuery(parentDiv).find('span.tg_channel_edit').hide();
            jQuery(parentDiv).find('span.tg_channel_show').show();   
        });
    },   
    initEpaperSettingsDependencyAction:function(dependencyElm, value, elmBox){
        jQuery(dependencyElm).change(function(){
           if(parseInt(jQuery(this).val()) !== parseInt(value)){
               jQuery(elmBox).hide();
           }else{
               jQuery(elmBox).show();
           }
        });
    },
    initEpaperSettings:function(){
        //abhängigkeiten in settings
        jQuery('.epaper_setting_item').each(function(){
            var elm = jQuery(this);
            if(jQuery(this).attr('epaper_settings_dependency') !== undefined){
                data = jQuery.parseJSON(jQuery(this).attr('epaper_settings_dependency'));
                var div = jQuery(this).parents('div.tg_channel_config').first();
                jQuery.map(data, function(value, key) {
                    dependencyElm = jQuery(div).find('select[name="'+key+'"]').first();
                    dependencyValue = jQuery(dependencyElm).val();
                    elmBox = jQuery(elm).parents('div.epaper_settings_item_box').first();
                    if(parseInt(dependencyValue) !== parseInt(value)) jQuery(elmBox).hide();
                    TgEpaper.initEpaperSettingsDependencyAction(dependencyElm, value, elmBox);
               });
            }
        });        
        jQuery('div.epaper_config_show').click(function(){
            channelBox = jQuery(this).parents('div.tg_channel_editbox').first();
            jQuery('input#tg_upload_cancel_upload').val(0);
            jQuery(channelBox).find('div.tg_channel_config').toggle();
        });
        
        //editieren schließen
        jQuery('div.epaper_config_close').click(function(){
            jQuery(this).parents('form.epaper_edit_form').first().find('.epaper_setting_item').each(function(){
                jQuery(this).val(jQuery(this).attr('default'));
            });            
            
            jQuery(this).parents('div.tg_channel_editbox').first().find('div.epaper_config_show').click();
        });
        
        //editieren speichern
        jQuery('div.epaper_config_save').click(function(){   
            epaper_id = jQuery(this).attr('epaper_id');
            channel_id = jQuery(this).attr('channel_id');
            form = jQuery('form#epaper_edit_form_'+channel_id);
            
            doPublish = false;
            doSave = false;
            
            jQuery(form).find('.epaper_setting_item').each(function(){
                if(jQuery(this).val() !== jQuery(this).attr('default')){
                    if(parseInt(jQuery(this).attr('publish')) === 1) doPublish = true;
                    doSave = true;
                }
            });

            if(doPublish === false && doSave === false){
                jQuery(form).find('div.epaper_config_close').click();
                return false;
            }
            
            formData = jQuery(form).serializeArray();
            var data = {
                action: 'epaper_ajax',
                ajax_option: 'setEpaperSettings',
                channel_id: channel_id,
                epaper_id: epaper_id,
                data: formData,
                tge_nonce: TGELocalData.tge_nonce,
                do_publish: doPublish
            };
            
            if(doPublish === false){
                TgEpaper.showProgressBar('save');
                jQuery('div#tg_epaper_progress_bar_percent').html('50%');
                jQuery('div#tg_epaper_progress_bar_percent').css('width','50%');
                TgEpaper._epaper_global_ajax_request = jQuery.post(TGELocalData.ajaxurl, data, function(response) {
                    //save settings by ajax request, but show publish-statusbar
                    jQuery('div#tg_epaper_progress_bar_percent').html('100%');
                    jQuery('div#tg_epaper_progress_bar_percent').css('width','100%');
                    jQuery('div#tg_epaper_progress_publishing').find('div.tg_status').addClass('tg_epaper_dialog_clean');
                    TgEpaper.reloadChannelList(channel_id);
                });
            }else{
                jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').addClass('tg_epaper_dialog_clean');
                jQuery('div#tg_epaper_progress_rendering').find('div.tg_status').addClass('tg_epaper_dialog_clean');
                jQuery('div#tg_epaper_progress_publishing').find('div.tg_status').addClass('tg_epaper_ajax_loader');
                TgEpaper.showProgressBar('progress');
                
                TgEpaper._epaper_global_ajax_request = jQuery.post(TGELocalData.ajaxurl, data, function(response) {
                    //publizieren-status
                    TgEpaper.getPublishStatus(epaper_id, channel_id);
                });
            }
        });

    },           
    tgMetaBoxSwitch:function(mainContainer, direction){
        actElm = 1;
        countElm = jQuery('#'+mainContainer).find('input#tg_meta_box_count').val();
        jQuery('#'+mainContainer).find('div.tg_meta_box_container').each(function(){
            if(jQuery(this).hasClass('active')){
                actElm = parseInt(jQuery(this).attr('channel'));
            }
        });
        
        switch(direction){
            case 'left':
                if(actElm === 1){
                    newActElm = 1;
                }else newActElm = actElm-1;
                break;
                
            case 'right':
                if(actElm >= (countElm)){
                    newActElm = countElm;
                }else newActElm = actElm+1;
                
                break;
        }
        //hide
        jQuery('#'+mainContainer).find('div#tg_epaper_meta_box_'+actElm).hide();
        jQuery('#'+mainContainer).find('div#tg_epaper_meta_box_'+actElm).removeClass('active');
        //show
        jQuery('#'+mainContainer).find('div#tg_epaper_meta_box_'+(newActElm) ).show();
        jQuery('#'+mainContainer).find('div#tg_epaper_meta_box_'+(newActElm) ).addClass('active');
    },
    initClearChannelAction:function(){
        jQuery('div.tg_clear_channel').find('span').each(function(){
            
                jQuery(this).click(function(){              
                    bCheck = confirm(jQuery('input#tg_channels_clear_channel').val());
                    if (bCheck === true){
                        //add ajax-loader to preview image
                        jQuery(this).parents('div.tg_box_channel_list_content').first().find('div.tg_box_channel_list_content_image').append('<div class="ajax_loader_big"></div>');         

                        var data = {
                            channel: jQuery(this).attr('channel'),
                            action: 'epaper_ajax',
                            ajax_option: 'clearChannel',
                            tge_nonce : TGELocalData.tge_nonce
                        };

                        TgEpaper._epaper_global_ajax_request = jQuery.post( TGELocalData.ajaxurl, data, function(response) {
                            jQuery('#tg_menupage').parents('div').first().html(response);
                            //TgEpaper.initColor1000Box();
                        });    
                    }
                });            
            
        });
    },
    initColor1000Box:function(){
        $tgd(".ePaper").color1000box({iframe:true, width:"80%", height:"90%"});
    },
            
    startRendering:function(pdfId, oldEpaperId, channel_id, filename){
        var data = {
            action: 'epaper_ajax',
            ajax_option: 'startRendering',
            pdfId: pdfId,
            oldEpaperId: oldEpaperId,
            channel_id: channel_id,
            filename: filename,
            tge_nonce: TGELocalData.tge_nonce,
            async: false
        };

        TgEpaper._epaper_startrendering_ajax = jQuery.post(TGELocalData.ajaxurl, data, function(iEpaperId) {
            TgEpaper.resetProgressBar();
            TgEpaper.getRenderStatus(iEpaperId, channel_id);
            jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').removeClass('tg_epaper_ajax_loader');
            jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').addClass('tg_epaper_dialog_clean');
        });

    }, 
    initDeleteAccount:function(){
        jQuery('a#tg_epaper_delete_account').click(function(){
            confirm_translation = jQuery(this).parents('div').first().find('input.tg_translation').val();
            bDelete = confirm(confirm_translation);
            if(bDelete === true){
                var data = {
                    action: 'epaper_ajax',
                    ajax_option: 'deleteAccount',
                    tge_nonce: TGELocalData.tge_nonce
                };

                TgEpaper._epaper_global_ajax_request = jQuery.post(TGELocalData.ajaxurl, data, function() {
                    document.location.search = 'page=epaper_apikey';
                });
            }
           
            return false;
        });
        
    },
    registeredRedirect:function(){
        window.setTimeout("TgEpaper.redirectToAdminPage('epaper_channels')", 1000);
    },
    redirectToAdminPage:function(sPage){
        window.location.search = 'page='+sPage;  
    },
    initUploadify:function(){
        jQuery('div#tg_epaper_progress_bar').find('a.tg_cancel_upload').click(function(){
           jQuery('input.file_upload').each(function(){
                form_id = jQuery(this).attr('id');
                jQuery("#"+form_id).uploadify('cancel','*');
           });
           jQuery('input#tg_upload_cancel_upload').val(1);
           if(TgEpaper._eventSourceRenderStatus !== null) TgEpaper._eventSourceRenderStatus.close();
           TgEpaper._eventSourceRenderStatus = null;
           if(TgEpaper._eventSourcePublishStatus !== null) TgEpaper._eventSourcePublishStatus.close();
           TgEpaper._eventSourcePublishStatus = null;
           if(TgEpaper._epaper_render_ajax !== null) TgEpaper._epaper_render_ajax.abort();
           if(TgEpaper._epaper_publish_ajax !== null) TgEpaper._epaper_publish_ajax.abort();
           if(TgEpaper._epaper_startrendering_ajax !== null) TgEpaper._epaper_startrendering_ajax.abort();
           if(TgEpaper._epaper_global_ajax_request !== null) TgEpaper._epaper_global_ajax_request.abort();
           TgEpaper.hideProgressBar(); 
           TgEpaper.resetProgressBar();
        });
        
        TgEpaper._uploadButton = jQuery('#tg_upload_translation_upload_button').val();  
        jQuery('input.file_upload').each(function(){
            var channel = jQuery(this).attr('channel');
            apiUrl = jQuery('#tg_upload_api_url').val();
            form_id = jQuery(this).attr('id');            
            jQuery("#"+form_id).uploadify({
                fileObjName   : 'file',
                height        : 20,
                swf           : TGELocalData.wpcontenturl+'/plugins/1000grad-epaper/js/uploadify/uploadify.swf',
                uploader      : apiUrl+'pdf-upload/',
                buttonText    : TgEpaper._uploadButton,
                newbuttonText    : 'Upload to Channel '+channel,
                multi         : false,
                scriptAccess : 'always',
                formData      : { 
                    apikey      : jQuery('input#epaperapikey').val(),
                    },
                fileTypeDesc  : 'pdf Files',
                fileTypeExts  : '*.pdf' ,          
                'onUploadSuccess' : function(file, data, response) {
                    result = jQuery.parseJSON(data);
                    if(result !== null && result.success == false){
                        jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').addClass('tg_epaper_dialog_error');
                        TgEpaper.getUploadErrorTranslation(result.errors.clientCode);
                        //window.setTimeout("alert(result.errors.clientCode+': '+result.errors.errorDesc)", 500);
                        TgEpaper.reloadChannelList();                      
                        return false;
                    }
                    pdfId = result.pdfId;
                    apiKey = jQuery('input#epaperapikey').val();
                    channel_id = jQuery('input#channel_id_'+channel).val();
                    oldEpaperId = jQuery('input#epaper_id_'+channel).val();
                    if(parseInt(jQuery('input#tg_upload_cancel_upload').val()) === 0) TgEpaper.startRendering(pdfId, oldEpaperId, channel_id, file.name);
                },
                'onError'      : function(errorType) {
                    jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').addClass('tg_epaper_dialog_error');
                    window.setTimeout("alert(errorType)", 500);
                    window.setTimeout("TgEpaper.reloadChannelList()", 100);
                    return false;
                },
                'onUploadProgress' : function(file, bytesUploaded, bytesTotal, totalBytesUploaded, totalBytesTotal) {
                    //jQuery('div#tg_epaper_progress_bar_action').html((bytesTotal/100)*bytesUploaded);
                    upload_percent = parseInt((bytesUploaded/bytesTotal)*100);
                    if(upload_percent > 0) jQuery('div#tg_epaper_progress_bar_percent').html(upload_percent+'%');
                    jQuery('div#tg_epaper_progress_bar_percent').css('width',upload_percent+'%');
                },
                'onUploadStart' : function(file) {
                    //jQuery('div#tg_epaper_progress_bar_action').html('Uploading '+file.name+'...');
                    jQuery('input#tg_upload_cancel_upload').val(0);
                    TgEpaper.resetProgressBar(true);
                    jQuery('div#tg_epaper_progress_uploading').find('div.tg_status').addClass('tg_epaper_ajax_loader');
                    TgEpaper.showProgressBar();
                }
            });
        });
        
    },
    getUploadErrorTranslation:function(errorCode){
       var data = {
            action: 'epaper_ajax',
            ajax_option: 'translateUploadErrorMessage',
            errorCode: errorCode,
            tge_nonce: TGELocalData.tge_nonce,
            async: false
        };

        TgEpaper._epaper_global_ajax_request = jQuery.post(TGELocalData.ajaxurl, data, function(errorMessage) {
            alert(errorMessage);
        });
    },
    initShortcodeBox:function(){
        jQuery('a.showHtmlShortcode').click(function(){
            jQuery(this).parents('li').find('p.htmlShortcode').toggle();
            return false;
        });
    },
    initAgbAccepted:function(){
        jQuery('button#agb_accept_button').click(function(){
            /*var data = {
                action: 'epaper_ajax',
                ajax_option: 'acceptAgb',
                tge_nonce: TGELocalData.tge_nonce
            };*/
            
            if (jQuery('#tg_epaper_agb').is(':checked') === false) {
                jQuery('#tge_agb_box').attr('class', 'bold');
                return false;
            } else {
                jQuery('#tge_agb_box').attr('class', 'default');
            }

            /*TgEpaper._epaper_global_ajax_request = jQuery.post(TGELocalData.ajaxurl, data, function() {
                jQuery('div#tg_epaper_agb_overlay').remove();
            });*/    
        });
        
        jQuery('button#agb_cancel_button').click(function(){
            location.href = jQuery(this).attr('href');
            return false;
        });

        jQuery('#tg_epaper_agb').click(function() {
            if (jQuery(this).is(':checked')) {
                jQuery('#tge_agb_box').attr('class', 'default');
            } else
                jQuery('#tge_agb_box').attr('class', 'bold');
        });
        
        
        
    }
});

jQuery(document).ready(function(){
    TgEpaper = new TgEpaper();
});