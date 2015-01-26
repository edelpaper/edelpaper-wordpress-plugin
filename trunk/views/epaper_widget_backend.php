<p>
    <div>
        <label for="<?php echo $this->oView->widget->get_field_id( 'title' ); ?>"><?php _e('Title:', '1000grad-epaper'); ?></label>
    </div>
    <div>
        <input id="<?php echo $this->oView->widget->get_field_id( 'title' ); ?>" name="<?php echo $this->oView->widget->get_field_name( 'title' ); ?>" value="<?php echo $this->oView->widget_instance['title']; ?>" style="width:100%;" />
    </div>
</p>

<p>
    <div>
        <label for="ePaper-WidgetTitle"><?php _e('Channellist', '1000grad-epaper') ?>: </label>
    </div>
    <div>
        <select class="epaper_widget_channel_select" id="<?php echo $this->oView->widget->get_field_id( 'epaper_widget_channel_select' ); ?>" name="<?php echo $this->oView->widget->get_field_name( 'channel_id' ); ?>">
            <?php foreach($this->oView->channels as $iIndex => $oChannel): ?> 
            <option <?php if(isset($this->oView->widget_instance['channel_id']) && $this->oView->widget_instance['channel_id'] == $oChannel->id){ ?> selected="selected" <?php } ?> channel="<?php echo ($iIndex+1) ?>" value="<?php echo $oChannel->id ?>"><?php echo sprintf('Channel %d',($iIndex+1)) ?></option>
            <?php endforeach; ?>  
        </select>
    </div>
</p>

<p>
    <div>
        <label for="ePaper-WidgetTitle"><?php _e('First page', '1000grad-epaper') ?> : </label>
    </div>
    <div>
       <?php foreach($this->oView->channels as $iIndex => $oChannel): ?>
            <?php $iPages = isset($oChannel->epaperInfo->pages)?$oChannel->epaperInfo->pages:NULL ?>
            <div class="epaper_widget_first_page" id="epaper_widget_first_page_<?php echo ($iIndex+1) ?>" <?php if((isset($this->oView->widget_instance['channel_id']) && ($this->oView->widget_instance['channel_id'] != $oChannel->id && $this->oView->widget_instance['channel_id'] != NULL)) || (!isset($this->oView->widget_instance['channel_id']) && (($iIndex+1) > 1))){ ?> style="display:none;" <?php } ?>>
                <select class="float_left" <?php if($iPages == NULL) echo 'disabled="disabled"' ?> id="<?php echo $this->oView->widget->get_field_id( 'epaper_widget_first_page_select_'.$oChannel->id ); ?>" name="<?php echo $this->oView->widget->get_field_name( 'first_page'.$oChannel->id ); ?>">
                    <?php for($i=1; $i<=$iPages; $i++): ?>
                        <option <?php if(isset($this->oView->widget_instance['first_page']) && $this->oView->widget_instance['first_page'] == $i) echo sprintf('selected="selected"') ?> value="<?php echo $i ?>"><?php echo $i ?></option>
                    <?php endfor ?>
                </select>
                <div class="tg_epaper_dialog_question float_left">
                    <div class="tg_epaper_dialog_question_text"><?php _e("first page when opening edelpaper",'1000grad-epaper');?></div>
                </div> 
                <div class="clear"></div>
            </div>
       <?php endforeach; ?>

    </div>
</p>

<div>
    <?php foreach($this->oView->channels as $iIndex => $oChannel): ?>
                <div id="epaper_widget_preview_image_<?php echo ($iIndex+1) ?>" class="epaper_widget_preview_images" <?php 
                if((isset($this->oView->widget_instance['channel_id']) && ($this->oView->widget_instance['channel_id'] != $oChannel->id && $this->oView->widget_instance['channel_id'] != NULL)) || (!isset($this->oView->widget_instance['channel_id']) && (($iIndex+1) > 1))){ ?> style="display:none;" <?php } ?>>
                    <?php echo $this->getEpaperLink($oChannel, $oChannel->epaperInfo, array('page' => isset($this->oView->widget_instance['first_page'])?$this->oView->widget_instance['first_page']:1)) ?>
                </div>
    <?php endforeach; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function(){
        TgEpaper.initWidgetAction();
    });
</script>