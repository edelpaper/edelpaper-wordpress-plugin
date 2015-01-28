<?php
/**
 * This file contains the contents of the 1000grad-epaper channels admin page.
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
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
?>
<div class="text-justify tg_text_box"><?php
_e(       "This plugin aims to support you in creating and adding your interactive publications to the WordPress blog. "
        . "<br> * Creating interactive FLASH and HTML5 based documents has never been easier. "
        . "<br> * Upload your pdf file and create your interactive "
        . "multimedia publication in a few steps. "
        . "<br> * Embed that to your website via widget or shortcode. "
        . "<br> * Each publication is optimized for web and mobile (iOS and Android) display and is "
        . "equipped with an automatic device recognition. "
        . "<br> * You can refresh your document permalink as often as you want by upload a new PDF to the existing one."
        . "", '1000grad-epaper');        
?></div>

    <?php if (isset($this->oView->channelobject)): ?>

    <div class="tg_box_channel_list">
    <?php foreach ($this->oView->channelobject->channels as $iChannel => $oChannelInfo): ?>
            <?php $bEpaperExists = isset($oChannelInfo->id_epaper) ? true : false ?>
            <?php $sLink = $this->getEpaperLink($oChannelInfo, $oChannelInfo->epaperInfo) ?>
            <div class="tg_box_channel_list_items">
                <div class="tg_box_channel_list_content">

                    <div class="tg_box_channel_list_content_image tg-border-radius <?php echo ($bEpaperExists) ? 'tg_image_container' : 'default-height-preview-image' ?>">
        <?php echo $sLink ?>
                    </div>

                    <div class="tg_box_channel_list_content_details">
                        <div class="tg_box_channel_list_channel_header">
                            <span class="tg_channel_show">
                                <span class="channel_title"><?php echo ($oChannelInfo->title != '') ? $oChannelInfo->title : sprintf('%s #%u', __("edelpaper Channel", '1000grad-epaper'), ($iChannel + 1)) ?></span>
                            </span>
                        </div>

                        <div class="tg-border-radius tg_channel_editbox">

                            <div class="tg_channel_config">
                                <div class="tg_channelbox_embed_headline"><?php _e("Embed this ePaper:", '1000grad-epaper'); ?></div>
                                <ul>                                 
                                    <li> via <a href=widgets.php>Widget</a>
                                    <li> via Shortcode: <b>[ePaper nr=<?php echo ($iChannel + 1) ?>]</b>
                                        <a href='?page=epaper_channels&modus=postpost'></a></li>
                                    <li> via <a href="#" class="showHtmlShortcode">HTML code</a> <?php _e("for advanced users", '1000grad-epaper'); ?>
                                        <p class="htmlShortcode hide"><small><code><?php echo htmlentities($sLink) ?></code></small></p>
                                    </li>
                                </ul>

                                <hr class="tg_epaper_hr_grey"/>

                                <div class="uploadify_button_container float_left">
                                    <input id="file_upload_<?php echo ($iChannel + 1) ?>" channel="<?php echo ($iChannel + 1) ?>" type="file" name="file_upload" class="file_upload" />
                                    <input type='hidden' id='channel_id_<?php echo ($iChannel + 1) ?>' value='<?php echo $oChannelInfo->id; ?>' />
                                    <input type='hidden' id='epaper_id_<?php echo ($iChannel + 1) ?>' value='<?php echo $oChannelInfo->id_epaper; ?>' />
                                </div>

                                <?php if ($bEpaperExists): ?>
                                    <div class="tg_clear_channel float_left button_blue">
                                        <span channel="<?php echo $oChannelInfo->id ?>"><?php _e('clear Channel', '1000grad-epaper') ?></span>
                                    </div>

                                    <div class="tg_edit_epaper_button float_left button_blue epaper_config_show">
                                        <span channel="<?php echo $oChannelInfo->id ?>"><?php _e('edit ePaper', '1000grad-epaper') ?></span>
                                    </div>
                                <?php endif ?>

                                <div class="clear"></div>

                            </div>

                            <div class="tg_channel_config" style="display:none;">
                                <form id="epaper_edit_form_<?php echo $oChannelInfo->id ?>" class="epaper_edit_form">
                                    <?php echo $this->getEpaperSettingsForm($oChannelInfo->epaperInfo, $oChannelInfo) ?>
                                    <div class="tg_edit_epaper_button float_left button_blue epaper_config_save" epaper_id="<?php echo $oChannelInfo->epaperInfo->id ?>" channel_id="<?php echo $oChannelInfo->id ?>">
                                        <span><?php _e('save', '1000grad-epaper') ?></span>
                                    </div>      
                                    <div class="tg_edit_epaper_button float_left button_blue epaper_config_close">
                                        <span><?php _e('close', '1000grad-epaper') ?></span>
                                    </div>                                                                      
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>

    <?php endforeach ?>

        <div class='tg_info_box tg_box'>
            <div class='tg_info_box_inner'>
                <div class="tg_info_box_inner_header"><?php _e("add more edelpaper Channels", '1000grad-epaper'); ?></div>
                <div class='tg_info_box_inner_content'>
                    <div>
    <?php _e("You can get more edelpaper channels via PayPal.", "1000grad-epaper") ?>
                        <a href='admin.php?page=epaper_subscription'><?php _e('Manage Subscriptions', '1000grad-epaper'); ?></a>                       
                    </div>
                    <br/>
                    <div>
    <?php _e('Please have a look at our <a target="_blank" href="http://support.edelpaper.com/hc/en-us/articles/202245111">frequently asked questions (FAQ)</a> or give us <a href=options-general.php?page=epaper_settings>feedback about this plugin</a>', '1000grad-epaper'); ?>                       


                    </div>
                </div>
            </div>
        </div>


    </div>

    <?php if (!$this->oView->bAgbWasAccepted): ?>
        <form method="post" action="<?php echo $this->oView->sAdminUrl ?>?page=epaper_channels">
            <div id="tg_epaper_agb_overlay">
                <div id="tg_epaper_agb_check_content" class="tg-border-radius">
                    <div class="bold agb_text"><?php echo __('Please read and accept our terms and conditions, before using the edelpaper Wordpress-Plugin.', '1000grad-epaper') ?></div>
                    <div class="tg_agb_logo">
                        <img align=right alt=1000grad-logo src='<?php echo plugin_dir_url("") . "1000grad-epaper/img/1000grad_logo.png" ?>'>    
                    </div>
                    <div class="clear"></div>

                    <div id="tge_agb_box">
                        <input type="checkbox" name="agb"  id="tg_epaper_agb"> 
                        <span><?php echo __('I have read the <a target="_blank" href="http://www.edelpaper.com/terms_of_use/SaaS-Agreement-Edelpaper-TC-en-2013-07-26.pdf">terms of use</a> and I agree.', '1000grad-epaper'); ?></span><br />
                    </div>

                    <div>
                        <button type="submit" id="agb_accept_button" class="tg_cancel_upload button_blue"><?php _e('Accept', '1000grad-epaper') ?></button>
                        <button id="agb_cancel_button" href="<?php echo $this->oView->sAdminUrl ?>" class="tg_cancel_upload button_blue"><?php _e('Cancel', '1000grad-epaper') ?></button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <div id="tg_epaper_progress_bar" class="overlayer" style="display: none;">
        <div id="overlayer_content" class="overlayer_content tg-border-radius">
            <div class="tg_epaper_progress_bar_logo"></div>
            <div id="tg_epaper_progress_bar_action">
                <div id="tg_epaper_progress_uploading">
                    <div class="tg_action"><?php _e('Uploading', '1000grad-epaper') ?></div>
                    <div class="tg_status"></div>      
                    <div class="clear"></div>
                </div>

                <div id="tg_epaper_progress_rendering">
                    <div class="tg_action"><?php _e('Rendering', '1000grad-epaper') ?></div>
                    <div class="tg_status"></div>                    
                    <div class="clear"></div>
                </div>

                <div id="tg_epaper_progress_publishing">
                    <div class="tg_action"><?php _e('Publishing', '1000grad-epaper') ?></div>
                    <div class="tg_status"></div>
                    <div class="clear"></div>
                </div>
            </div>

            <div id="tg_epaper_save_bar_action">
                <div id="tg_epaper_progress_saving">
                    <div class="tg_action"><?php _e('Saving', '1000grad-epaper') ?></div>
                    <div class="tg_status tg_epaper_ajax_loader"></div>      
                    <div class="clear"></div>
                </div>                
            </div>

            <div class="clear"></div>
            <div id="tg_epaper_progress_bar_status">
                <div id="tg_epaper_progress_bar_percent"></div>
                <div class="clear"></div>
            </div>

            <a class="tg_cancel_upload button_blue"><?php _e('close', '1000grad-epaper') ?></a>
            <div class="clear"></div>
        </div>
    </div>

    <input id="epaperapikey" value="<?php echo $this->aEpaperOptions['apikey'] ?>" type="hidden" />
    <input type="hidden" id="tg_upload_translation_upload_button" value="<?php _e('PDF Upload', '1000grad-epaper') ?>"/>  
    <input type="hidden" id="tg_upload_api_url" value="<?php echo $this->aEpaperOptions['url']; ?>"/>
    <input type="hidden" id="tg_upload_cancel_upload" value="0" />
    <input type="hidden" id="tg_channels_clear_channel" value="<?php _e("Are you sure? The current edelpaper will be deleted!", '1000grad-epaper') ?>"/>
    <input type="hidden" id="tg_channels_new_upload" value="<?php _e("Are you sure? The current edelpaper will be overwritten!", '1000grad-epaper') ?>"/>
<?php endif; ?>

<script type="text/javascript">
    jQuery(document).ready(function() {
        <?php if (!$this->oView->bAgbWasAccepted): ?>
            TgEpaper.initAgbAccepted();
        <?php endif; ?>
        TgEpaper.initClearChannelAction();
        TgEpaper.initEpaperSettings();
        TgEpaper.initColor1000Box();
        TgEpaper.initShortcodeBox();
        TgEpaper.initUploadify();
    });
</script>