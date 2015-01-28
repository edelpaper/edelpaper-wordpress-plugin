<?php
/**
 * This file contains the contents of the 1000grad-epaper channel meta box admin page.
 *
 * @copyright Copyright (C) 2013 1000grad DIGITAL GmbH. All rights reserved.
 * @author 1000grad DIGITAL
 * @license This program is free software; you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation; either version 2 of the License, or
 *          (at your option) any later version.
 *
 *          This program is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *
 *          You should have received a copy of the GNU General Public License
 *          along with this program; if not, write to the Free Software
 *          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
 */

// Box, die beim Editieren und Posten von Beitraegen angezeigt wird.

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php if($this->oView->channelobject->count == 0): ?>
    <h2><?php _e('no edelpaper Channel existing!','1000grad-epaper') ?></h2>
<?php endif; ?>

    <div id="tg_epaper_meta_box_container">
        <?php foreach ($this->oView->channelobject->channels as $iChannel => $oChannel): ?>

            <div id="tg_epaper_meta_box_<?php echo ($iChannel + 1) ?>" channel="<?php echo ($iChannel + 1) ?>" class="tg_meta_box_container <?php echo ($iChannel == 0) ? 'active' : 'hide'; ?>">

                <div class="tg_meta_box_switch">
                    <div>
                        <a href="#" class="tg_switch_left" box_id="tg_epaper_meta_box_container"><<</a>
                    </div>

                    <div>
                        <b>Channel <?php echo ($iChannel + 1) ?></b>
                    </div>

                    <div>
                        <a href="#" class="tg_switch_right" box_id="tg_epaper_meta_box_container">>></a>
                    </div>
                </div>

                <div class="clear"></div>

                <?php _e("insert this shortcode into editor:", '1000grad-epaper') ?>
                
                <br/>
                
                <b><?php echo sprintf('[ePaper nr=%d]', ($iChannel+1))//$oChannelInfo = $this->oEpaper->getChannelInfos($oChannel->id)  ?></b>

                    <?php $bEpaperExists = isset($oChannel->id_epaper) ? true : false ?>

                    <div class="tg_box <?php echo ($bEpaperExists)?'tg_image_container':'default-height-preview-image' ?> text-center">
                        <?php echo $this->getEpaperLink($oChannel, $oChannel->epaperInfo) ?>
                    </div>
            </div>
        <?php endforeach; ?>    

        <div class="clear"></div>
        <input type="hidden" id="tg_meta_box_count" value="<?php echo $this->oView->channelobject->count ?>"/>
    </div>

<script type="text/javascript">
    jQuery(document).ready(function(){
        TgEpaper.initChannelSwitcher();
        TgEpaper.initColor1000Box();
    });
</script>