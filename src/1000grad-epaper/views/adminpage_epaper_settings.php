<?php
/**
 * This file contains the contents of the 1000grad-epaper settings admin page.
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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Feedback",'1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <?php if($this->oView->feedback_sent == true) _e("<br><b>Your feedback comment was sent to the edelpaper Support Team. Thank you for contacting us.</b>",'1000grad-epaper'); ?>
            <?php if($this->oView->feedback_sent == false): ?>
                <form action='' method='post'>
                    <label for='text'>
                        <?php _e('Your opinion is important! We develop our software continuously and the focus of our efforts, you as a user of our software.' .
                                '<br />Please send us your comments, questions and suggestions. We will contact you immediately.', '1000grad-epaper');
                        ?>    
                    </label><br />
                    <textarea name='text' id='epaper_wordpressapi' value='' rows='5' cols='75'></textarea><br />
                    <input type='submit' name='feedback' id='feedback' value='<?php _e('send feedback', '1000grad-epaper'); ?>' class='button' />
                    <input type='hidden' name='page' value='feedback_send' />
                </form>            
            <?php endif; ?>
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e('Contact','1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            1000grad DIGITAL GmbH
            <br><b>edelpaper.com</b>
            <p>Mozartstr. 3
                <br />D-04107 Leipzig, Germany
                <br /><br /><a target="_blank" href="http://support.edelpaper.com/hc/en-us/articles/202133892-Simple-and-fast-the-edelpaper-wordpress-plugin">Plugin Support</a>
                <br /><a target="_blank" href="http://www.edelpaper.com">www.edelpaper.com</a>
            </p>   


            <?php _e('<a target="_blank" href="http://www.edelpaper.com/terms_of_use/SaaS-Agreement-Edelpaper-TC-en-2013-07-26.pdf">terms of use</a>','1000grad-epaper');?>                   
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Settings",'1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <form action='' method='get'>
                <label for='epaper_wordpressapi'>Account Server API URL: </label>
                <input type='text' name='epaper_wordpressapi' id='epaper_wordpressapi' 
                    value='<?php echo $this->aEpaperOptions['wordpressapi']; ?>' size='60' /><br />

                <label for='epaper_apikey_as'>Account Server API Key: </label>
                <input type='text' name='epaper_apikey_as' id='epaper_apikey_as' 
                      value='<?php echo $this->aEpaperOptions['apikey_as'] ; ?>' size='55' /><br />                            


                <label for='epaper_url'>CMS API URL: </label>
                <input type='text' name='epaper_url' id='epaper_url' 
                       value='<?php echo $this->aEpaperOptions['url']; ?>' size='60' /><br />

                <label for='epaper_apikey'>CMS API Key: </label>
                <input type='text' name='epaper_apikey' id='epaper_apikey' 
                      value='<?php echo $this->aEpaperOptions['apikey'] ; ?>' size='55' /><br />   

                <input type='submit' name='epaper-settings-save' id='epaper-settings-save' value='Save' class='button' />
                <input type='hidden' name='page' value='epaper_settings' />
            </form>  
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e('Infos','1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <?php
                //PHP-Vars
                global $wp_version; 
                global $tge_plugin_data;  
                $versionwordpress = $wp_version;
                $versionplugin = $tge_plugin_data['Version'];
                $versionphp = phpversion();
            ?>
            
            <?php
                echo "<br />";
                _e('edelpaper Plugin Version:','1000grad-epaper');
                echo ' '.$versionplugin;
                echo "<br />";
                _e('Wordpress & PHP-Version:','1000grad-epaper');
                echo ' '.$versionwordpress.' / '.$versionphp;
                echo "<br />";
                _e("local plugin settings:",'1000grad-epaper');
                echo "<br />";
                echo "<pre>";

                print_r($this->aEpaperOptions);
                echo "</pre>";
            ?>
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e('Account-API','1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <?php
                if ($this->is_registered()) {

                    echo 'API Version: '. $this->oAccountApi->getApikeyApiVersion();
                    $aApiFunctions = $this->oAccountApi->getApikeyApiFunctions();
                    echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($aApiFunctions);	

                }        
            ?>
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e('User-API','1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <?php
                if ($this->is_registered()) {

                    $sVersion = $this->oEpaperApi->getEpaperApiVersion();
                    $aApiFunctions = $this->oEpaperApi->getEpaperApiFunctions();
                    $oClientInfos = $this->oEpaperApi->getEpaperApiClientInfos($this->aEpaperOptions['apikey']);
                    echo 'API Version: ' . $sVersion;
                    echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($aApiFunctions);
                    if ($oClientInfos !== false) {
                        _e('<br />Api key is valid','1000grad-epaper');
                    } else {
                        _e('<br /><b>Error with API Key Authentification','1000grad-epaper');
                    }
                }
            ?>
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e('Channel-API','1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <?php
                if ($this->is_registered()) {
                    $sVersion = $this->oChannelApi->getChannelApiVersion();
                    $aApiFunctions = $this->oChannelApi->getChannelApiFunctions();
                    echo 'API Version: ' . $sVersion;
                    echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($aApiFunctions);            
                    if ($oClientInfos !== false) {
                        _e('<br />Api key is valid','1000grad-epaper');
                    } else {
                        _e('<br /><b>Error with API Key Authentification','1000grad-epaper');
                    }
                }
            ?>
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e('edelpaper API License','1000grad-epaper');?></div>
        <div class='tg_info_box_inner_content'>
            <?php
                if ($this->is_registered()) {

                    $sClientInfos = $this->oEpaperApi->getEpaperApiClientInfos($this->aEpaperOptions['apikey']);
                    $oClientInfo = json_decode($sClientInfos);
                    echo 'name: '.$oClientInfo->name;
                    echo ' ('.$oClientInfo->firstname.' '.$oClientInfo->lastname.') ';
                    echo '<br />short name: '.$oClientInfo->short_name;
                    echo '<br />customer id: '.$oClientInfo->customer_id;
                    echo '<br />email: '.$oClientInfo->email;
                    echo '<br /><b>channel count: '.$oClientInfo->channels_count.'</b>';
                    echo '<br />memory count: '.round($oClientInfo->disk_usage / 1024 / 1024 ).' MByte';
                    echo '<br />created documents: '.$oClientInfo->count_created;
                    echo '<br />published documents: '.$oClientInfo->count_published;


                    $oClientChannels = json_decode($this->oChannelApi->getChannelsList($this->aEpaperOptions['apikey']));
                    echo '<br />edelpaper channels: '.$oClientChannels->count;
                    foreach ( $oClientChannels->channels as $oChannel ) {
                        echo "<br />ID: ".$oChannel->id;
                        echo " - ".$oChannel->time_created;
                        echo " - ".$oChannel->time_modified;
                        echo " - ".$oChannel->expiry_time;
                        echo " ";
                        if ($oChannel->id_epaper!="") {
                            echo " - ".$oChannel->id_epaper;
                        } else _e('not used','1000grad-epaper');
                    }
                }
            ?>
        </div>
    </div>
</div>