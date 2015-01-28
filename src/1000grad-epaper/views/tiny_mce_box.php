<div id="tg_epaper_tiny_cms_container">
    <?php foreach($this->oView->channelobject->channels as $iChannel => $oChannel): ?>
    
      <?php $iPages = isset($oChannel->epaperInfo->pages)?$oChannel->epaperInfo->pages:NULL ?>

      <div id="tg_epaper_meta_box_<?php echo ($iChannel+1) ?>" channel="<?php echo ($iChannel+1) ?>" class="tg_meta_box_container <?php echo ($iChannel == 0)?'active':'hide'; ?>">

                <?php $bEpaperExists = isset($oChannel->id_epaper)?true:false ?>
                <div class="tg_epaper_tiny_ajax_box">
                    
                    
                        <div class="tg_epaper_tiny_ajax_box_config_channel">

                                <div class="tg_meta_box_switch">
                                    <div>
                                        <a href="#" class="tg_switch_left" box_id="tg_epaper_tiny_cms_container"><<</a>
                                    </div>

                                    <div>
                                        <b>Channel <?php echo ($iChannel + 1) ?></b>
                                    </div>

                                    <div>
                                        <a href="#" class="tg_switch_right" box_id="tg_epaper_tiny_cms_container">>></a>
                                    </div>
                                </div>

                                <div class="clear"></div>
                            
                        </div>
                    
                    
                    
                    <div class="tg_epaper_tiny_ajax_box_preview <?php echo ($bEpaperExists)?'tg_image_container':'default-height-preview-image' ?>">
                        <?php echo $this->getEpaperLink($oChannel, $oChannel->epaperInfo) ?>
                    </div>

                    <div class="tg_epaper_tiny_ajax_box_config">


                        <input type="hidden" id="tg_epaper_channel_<?php echo ($iChannel+1) ?>" value="<?php echo ($iChannel+1) ?>" />
                        <input type="hidden" id="tg_epaper_channel_url_<?php echo ($iChannel+1) ?>" value="<?php echo $oChannel->url ?>" />

                        <div class="tg_epaper_config_align tg_epaper_tiny_ajax_box_config_box">
                            <div class="tg_epaper_tiny_ajax_box_config_box_title">Layout class</div>
                            <?php //align left|right ?>
                            <div class="tg_epaper_config_align_option">
                                <input type="radio" checked="checked" class="tg_epaper_align" name="tg_epaper_align_<?php echo ($iChannel+1) ?>" value="alignleft">alignleft
                            </div>

                            <div class="tg_epaper_config_align_option">
                                <input type="radio" class="tg_epaper_align" name="tg_epaper_align_<?php echo ($iChannel+1) ?>" value="aligncenter"> center
                            </div>

                            <div class="tg_epaper_config_align_option">
                                <input type="radio" class="tg_epaper_align" name="tg_epaper_align_<?php echo ($iChannel+1) ?>" value="alignright">alignright
                            </div>
                            <div class="clear"></div>

                        </div>
                        
                        <div class="tg_epaper_config_align tg_epaper_tiny_ajax_box_config_box">
                            <div class="tg_epaper_tiny_ajax_box_config_box_title">First page</div>
                            <select <?php echo ($iPages == NULL)?'disabled="disabled"':NULL ?> id="tg_epaper_first_page_<?php echo ($iChannel+1) ?>" class="tg_epaper_first_page">
                                <?php for($iPage = 1; $iPage<= $iPages; $iPage++): ?>
                                    <option value="<?php echo $iPage ?>"><?php echo $iPage; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="tg_epaper_config_insert">
                            <a channel="<?php echo ($iChannel+1) ?>" href="#" class="tg_epaper_config_insert_button button_blue">Insert</a>
                        </div>

                    </div>

                    <div class="clear"></div>

                </div>
          
     </div>
    <?php endforeach; ?>
    <input type="hidden" id="tg_meta_box_count" value="<?php echo $this->oView->channelobject->count ?>"/>
</div>


<script language="javascript" type="text/javascript">
    jQuery(document).ready(function(){
        TgEpaper.initTgEpaperTinyEditorActions();
        TgEpaper.initChannelSwitcher();
    });
</script>