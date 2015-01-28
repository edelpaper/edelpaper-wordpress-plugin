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

?><div class="text-justify tg_text_box">
    <?php _e("You can manage your plan below, as often as you need. The payment is realized with Paypal monthly Subscription.<br> 
                For questions relating licensing or purchasing, please contact a customer service representative via <a target = '_blank' href='mailto:info@edelpaper.com'>info@edelpaper.com</a>.
                <br />To request technical support, please visit our <a target = '_blank' href='http://support.edelpaper.com/hc/en-us/articles/202133892'>Support Page</a> or or give us <a href=options-general.php?page=epaper_settings>feedback</a>." , '1000grad-epaper');
?></div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Available plans for the Wordpress Plugin", '1000grad-epaper'); ?></div>
        <div class='tg_info_box_inner_content'>
            <table>
                <tr>
                    <td valign="top">
                        <p>
                            <b><?php echo isset($this->oView->button_code->edition_types->EpaperWeblinkBasic->title)?$this->oView->button_code->edition_types->EpaperWeblinkBasic->title:NULL; ?></b>
                        </p>
                        <ul>
                            <?php if (isset($this->oView->button_code->edition_types->EpaperWeblinkBasic->desc_points) && $this->oView->button_code->edition_types->EpaperWeblinkBasic->desc_points): ?>
                                    <?php foreach ($this->oView->button_code->edition_types->EpaperWeblinkBasic->desc_points as $point): ?>
                                        <li >
                                            <label class="selectit">
                                                <input id="in-category-1" type="checkbox" checked="checked" name="post_category[]" value="1" disabled="disabled" >
                                                <?php echo $point; ?>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </td>                                
                    <td width="50"></td>
                    <td valign="top">
                        <p>
                            <b><?php echo isset($this->oView->button_code->edition_types->EpaperWeblinkStandard->title)?$this->oView->button_code->edition_types->EpaperWeblinkStandard->title:NULL; ?></b></p>
                        <ul>
                            <?php if (isset($this->oView->button_code->edition_types->EpaperWeblinkStandard->desc_points) && $this->oView->button_code->edition_types->EpaperWeblinkStandard->desc_points): ?>
                                    <?php foreach ($this->oView->button_code->edition_types->EpaperWeblinkStandard->desc_points as $point): ?>
                                    <li>
                                        <label class="selectit">
                                            <input id="in-category-1" type="checkbox" checked="checked" name="post_category[]" value="1" disabled="disabled">
                                            <?php echo $point; ?>
                                        </label>
                                    </li>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>                                    
                    </td>
                </tr>
            </table>                       
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Your account info", '1000grad-epaper'); ?></div>
        <div class='tg_info_box_inner_content'>

            <div class="misc-pub-section">
                <label for="post_status"><?php _e("Account:", '1000grad-epaper'); ?></label>
                <span id="post-status-display"><?php echo $this->aEpaperOptions["email"]; ?></span>                            
            </div>

            <div class="misc-pub-section">
                <label for="post_status"><?php _e("Plan:", '1000grad-epaper'); ?></label>
                <span id="post-status-display"><?php echo isset($this->oView->button_code->account_info->plan)?$this->oView->button_code->account_info->plan:NULL ?></span>                            
            </div>   

            <?php if (isset($this->oView->button_code->account_info->auto_renew_date) && $this->oView->button_code->account_info->auto_renew_date): ?> 
                <div id="visibility" class="misc-pub-section">
                    <label for="post_status">
                        <?php _e("Auto-renew (and next payment date)", "1000grad-epaper") ?>:
                    </label>
                    <span id="post-status-display">
                        <?php if ($this->oView->button_code->account_info->auto_renew_date): ?>
                            <?php echo $this->oView->button_code->account_info->auto_renew_date ?>
                        <?php endif; ?>
                    </span>
                </div>  

                <?php if ($this->oView->button_code->account_info->edition_type != "EpaperWeblinkBasic"): ?> 
                    <div id="visibility" class="misc-pub-section">
                        <label for="post_status"><?php echo __('Recurring payment id:', '1000grad-epaper') ?></label>
                        <span id="post-status-display">
                            <a target="_blank" href="<?php echo $this->oView->button_code->account_info->payment_profile_uri; ?>"><?php echo $this->oView->button_code->account_info->payment_profile_id; ?></a> 
                        </span> <?php echo sprintf(__('(Please log in at Paypal with %s before clicking on the link.)', '1000grad-epaper'), $this->oView->button_code->account_info->buyer_email); ?>
                    </div>                                  
                <?php endif; ?>
            <?php endif; ?>

            
            
            <?php if (isset($this->oView->button_code->account_info->extra_number) && $this->oView->button_code->account_info->extra_number != NULL): ?>
                <div id="visibility" class="misc-pub-section">
                    <label for="post_status">
                        <?php _e("Additional Channels", "1000grad-epaper"); ?>:
                    </label>
                    <span id="post-status-display">
                        <?php echo $this->oView->button_code->account_info->extra_number; ?>                                        
                    </span>
                </div>  
            <?php endif; ?>
            
            <div class="misc-pub-section">
                <input type="hidden" class="tg_translation" value="<?php _e("Do you really want to reset your Plugin? All settings will be deleted.", "1000grad-epaper")?>"/>
                <?php /*<span><?php _e("Delete Account", "1000grad-epaper")?></span>*/?>
                <a class="button_blue" id="tg_epaper_delete_account" href=""><?php _e("Reset Plugin", "1000grad-epaper")?></a>
                
                <script type="text/javascript">
                    jQuery(document).ready(function(){
                        TgEpaper.initDeleteAccount();
                    });
                </script>
                
            </div>

        </div>
    </div>
</div>

<?php if($this->oView->button_code->button_code != NULL): ?>
<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Choose your new plan", '1000grad-epaper'); ?></div>
        <div class='tg_info_box_inner_content'>
            <?php echo isset($this->oView->button_code->button_code)?$this->oView->button_code->button_code:NULL; ?>                   
        </div>
    </div>
</div>
<?php endif; ?>