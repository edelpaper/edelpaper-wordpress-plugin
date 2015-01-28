<?php
/**
 * This file contains the contents of the 1000grad-epaper apikey admin page.
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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Request WP-Key","1000grad-epaper") ?></div>
        <div class='tg_info_box_inner_content'>
            <form action="" method="post">
                <label for="apikey_email">Email: </label>
                <input type="text" name="apikey_email" id="apikey_email" value="<?php echo isset($this->aEpaperOptions['email'])?$this->aEpaperOptions['email']:NULL; ?>" size="35" /><br />
                <input type="checkbox" name="agb"  id="agb" value="yes"> 
                <?php _e('I have read the <a target="_blank" href="http://www.edelpaper.com/terms_of_use/SaaS-Agreement-Edelpaper-TC-en-2013-07-26.pdf">terms of use</a> and I agree.', '1000grad-epaper'); ?><br />
                <input type="checkbox" name="newsletter" value="yes" checked>
                <?php _e('I want to receive service newsletter from edelpaper.com.', '1000grad-epaper'); ?><br />
                <input type="submit" name="register_account" id="register_account" value="<?php _e("send request", "1000grad-epaper"); ?>" class="button" />
                <input type="hidden" name="page" value="epaper_apikey" />
                <input type="hidden" name="registration_key_requested" value="1"/>
            </form> 
        </div>
    </div>
</div>

<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Registration Code","1000grad-epaper"); ?></div>
        <div class='tg_info_box_inner_content'>
            <div class="epaperform">
                <div class="metabox-prefs">          
                    <form action="" method="get">
                        <table>
                            <tr>
                                <td>
                                    <label for="email"><?php _e("Email", "1000grad-epaper"); ?>:</label>
                                </td>
                                <td>
                                    <input type="text" name="email" id="email" value="<?php echo isset($this->aEpaperOptions['email'])?$this->aEpaperOptions['email']:NULL; ?>" size="25" />   
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <label for="code"><?php _e("WP-Key", "1000grad-epaper"); ?>:</label>    
                                </td>
                                <td>
                                    <input type="text" name="code" id="code" value="" size="25" />    
                                </td>
                            </tr>
                            
                            <tr>
                                <td></td>
                                <td>
                                    <input type="submit" name="on" id="on" value="<?php _e("submit code", "1000grad-epaper"); ?>" class="button" />
                                    <input type="hidden" name="page" value="epaper_apikey" />    
                                </td>
                            </tr>
                        </table>
                        
                        <br/>
                    </form>            
                </div>
            </div>   
        </div>
    </div>
</div>


<div class='tg_info_box tg_box'>
    <div class='tg_info_box_inner'>
        <div class="tg_info_box_inner_header"><?php _e("Contact", "1000grad-epaper") ?></div>
        <div class='tg_info_box_inner_content'>
            <b>edelpaper.com</b> is a service of the 1000grad DIGITAL GmbH.
            <p>1000grad DIGITAL GmbH<br />               
                Mozartstr. 3<br />
                D-04107 Leipzig, Germany
            </p>
            <p>
                <a href=mailto:info@edelpaper.com>info@edelpaper.com</a><br />
                <a target='_blank' href=http://support.edelpaper.com/hc/en-us/articles/202133892-Simple-and-fast-the-edelpaper-wordpress-plugin/>WP Plugin Support</a><br />
                <a target='_blank' href=http://www.edelpaper.com/>www.edelpaper.com</a><br />
            </p>    
        </div>
    </div>
</div>

<div class="clear"></div>

<?php if($this->bIsRegistered == true): ?>

<script language="javascript" type="text/javascript">
    jQuery(document).ready(function(){
        TgEpaper.registeredRedirect();
    });
</script>

<?php endif; ?>