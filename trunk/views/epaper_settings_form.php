<?php

foreach($aEpaperSettings as $sFieldName => $aConfig):
    
    $bHasDependency = isset($aDependency[$sFieldName])?true:false;
    
    ?>
    <div class="epaper_settings_item_box">
        <div class="tg_epaper_edit_label float_left"><?php echo $aConfig['translation'] ?></div>
        <div class="float_left">
    <?php
    
    
    switch($aConfig['type']):
        case 'select':           
            ?>
            <select publish="<?php echo isset($aConfig['publish'])?$aConfig['publish']:0 ?>" <?php if($bHasDependency) echo sprintf("epaper_settings_dependency='%s'", json_encode($aDependency[$sFieldName])) ?> class="epaper_setting_item" name="<?php echo $sFieldName ?>" default="<?php echo $aConfig['default'] ?>">
                <?php foreach ($aConfig['values'] as $sOption => $sText): ?>
                    <option <?php echo ($sOption == $aConfig['default'])?'selected="selected"':NULL ?> value="<?php echo $sOption ?>"><?php _e($sText, '1000grad-epaper') ?></option>
                <?php endforeach ?>
            </select>
            <?php
            break;

        case 'input':
            ?>
                <input publish="<?php echo isset($aConfig['publish'])?$aConfig['publish']:0 ?>" <?php if($bHasDependency) echo sprintf("epaper_settings_dependency='%s'", json_encode($aDependency[$sFieldName])) ?> class="epaper_setting_item" default="<?php echo $aConfig['default'] ?>" name="<?php echo $sFieldName ?>" value="<?php echo $aConfig['default'] ?>"/>
            <?php
            break;

    endswitch;
    
    ?>
            <div class="epaper_settings_form tg_epaper_dialog_question">
                <div class="tg_epaper_dialog_question_text"><?php echo $aConfig['helptext'] ?></div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
    </div>
    <?php
        
endforeach 

?>