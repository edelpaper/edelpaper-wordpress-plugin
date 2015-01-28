<?php
/*
Plugin Name: edelpaper*
Plugin URI: http://support.edelpaper.com/hc/en-us/articles/202133892-Simple-and-fast-the-edelpaper-wordpress-plugin
Description: Easily create browsable interactive documents within Wordpress! Convert your PDFs to online documents by using the edelpaper service. Embed it via widget or shortcode.  edelpaper is an electronic publishing service that allows you to quickly and easily create native page flipping electronic publications such as e-Books, e-Catalogs, e-Brochures, e-Presentations and much more.
Version: 1.4.12
Author: edelpaper (a service by 1000grad Digital GmbH, Germany)
Author URI: http://www.edelpaper.com
License: GPLv2 or later

  Copyright (C) 2015 1000grad Digital GmbH (info@1000grad.de)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Using the awesome plugin boilerplate by Tom MsFarlin: https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate

*/
/*error_reporting(0);*/
require_once("lib/epaperApi.php");
require_once("lib/epaperApikeyApi.php");
require_once("lib/epaperChannelApi.php");

class TG_Epaper_WP_Plugin {
    
    static $sPluginVersion = "1.4.12";
    
    private $aEpaperOptions = array();  
    
    private $bKeyRefreshed = false;
    
    private $bIsRegistered; //true|false
    
    private $sBasePluginPath = '1000grad-epaper/';
    private $sTemplatePath = 'views/';
    private $sMainTemplate = 'adminpage_epaper_template';
    private $sDefaultTitle = 'edelpaper*';
    private $ePaperSettingsFormTemplate = 'epaper_settings_form';
    private $sDefaultPreviewImage = 'epaper/epaper-ani.gif';
    private $sAgbAcceptIndex = 'agb_accepted';
    private $sEpaperOptionIndex = 'plugin_epaper_options';
    private $sWidgetClassIndex = 'widget_epaperwidgetclass';
    
    //Channel
    private $oChannelApi = NULL;
    //Account
    private $oAccountApi = NULL;
    //Epaper
    private $oEpaperApi  = NULL;
    
    private $sDefaultLang = 'en';
    private $sLanguageFallback = 'en';

    private $sEpaperOptionsChannelConfig = "channel_config";
    private $sEpaperOptionsChannelDefaultUrl = "epaper_default_url";
    
    private $defaultFallback = "http://www.1kcloud.com/ep1KSpot/";
    private $sDefaultAccountApiUrl = "http://epaperplugin.1kcloud.com/api/v2/wordpress-wsdl";
    
    private $sPage = NULL;
    
    private $oView = NULL;
    private $aTemplateVars = array();
    private $bUseMainTemplate = true;
    private $sTemplate = NULL;

    //initializes the plugin by setting localization, filters, and administration functions
    function __construct($bRegisterActions = true) {
        
        if($this->checkSoapIsActivated()):
        
            if(!defined('TGE_PLUGIN_ACCOUNT_API_URI')){
                define( 'TGE_PLUGIN_ACCOUNT_API_URI', $this->sDefaultAccountApiUrl);
            }

            $this->load_epaper_options();
            $this->oView = new stdClass();
            $this->sPage = isset($_GET['page'])?$_GET['page']:NULL;
            $this->sDefaultLang = $this->getBlogDefaultLanguage();

            //Epaper API
            $this->oChannelApi = new EpaperChannelApi();
            $this->oAccountApi = new EpaperApikeyApi();    
            $this->oEpaperApi  = new EpaperApi();
            
            ini_set('max_execution_time', 120);
            ini_set("soap.wsdl_cache_enabled", 1);
            ini_set("soap.wsdl_cache_ttl", 86400);  

            $this->is_registered();
        

            if($bRegisterActions == true):
                //ajax-action
                add_action( 'wp_ajax_nopriv_epaper_ajax', array( $this, 'fetchAjaxRequest' ) );
                add_action( 'wp_ajax_epaper_ajax', array( $this, 'fetchAjaxRequest' ) );

                //load plugin translations
                add_action( 'init', array( $this, 'plugin_textdomain' ) );     

                //load styles and scripts
                add_action( 'init', array( $this, 'action_admin_init_register_styles_and_scripts' ) ); 
                add_filter('the_posts', array( $this,'filter_posts_conditionally_add_scripts_and_styles'));   

                //custom actions            
                add_action('admin_menu', array( $this,'action_epaper_integration_menu'));    
                add_shortcode('ePaper', array( $this,'shortcode_epaper'));

                if($this->is_registered() == true):
                    add_action( 'widgets_init', create_function('', 'return register_widget("EpaperWidgetClass");') );
                    add_filter('mce_external_plugins', array ($this,'addScriptToTinymce' ) );
                    add_filter('mce_buttons', array ($this,'registerTgTinyButton' ) );
                    add_action('init', array($this, 'updatePlugin'));
                endif;

                add_action('add_meta_boxes', array( $this, 'action_add_metabox_epaper' ) );
                // drop a warning on each page of the admin when 1000grad-epaper hasn't been configured
                //add_action( 'admin_notices', array( $this, 'showRegistrationInfo' ) );

            endif;
        
        endif;
        //register_uninstall_hook(__FILE__, array('TG_Epaper_WP_Plugin', 'uninstallPlugin'));

    }
    
    public static function uninstallPlugin(){
        if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
            exit();
        
        var_dump('uninstall');
        exit;
    }
    
    public function checkSoapIsActivated(){
        if(extension_loaded('soap') === false || !class_exists('SoapClient')):
            $this->showWarning(__("<b>The edelpaper plugin requires SOAP extension for PHP (php_soap).<br/><br/>Please ask your system administrator to activate it.</b>","1000grad-epaper"));
            return false;
        endif;
        
        return true;
    }    
    
    //returns current plugin-version
    public static function getPluginVersion(){
        return self::$sPluginVersion;
    }
    
    //update plugin
    public function updatePlugin(){
        if(!isset($this->aEpaperOptions['update_infos']) || (((time() - $this->aEpaperOptions['update_infos']) / 86400) > 30)):
            $this->oAccountApi->updatePluginInfos();
            $this->aEpaperOptions['update_infos'] = time();
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        endif;
    }

    public function get($sVar){
        return $this->{$sVar};
    }
    
    public function set($sVar, $sValue){
        $this->{$sVar} = $sValue;
        return true;
    }
    
    //registers plugin-button in tinymce-editor
    function registerTgTinyButton($aButtons) {
            array_push($aButtons, "|", "tg_tiny_button");
            return $aButtons;
    }

    //adds js-script to tinymce-editor
    function addScriptToTinymce($aPluginArray) {
            $aPluginArray['tg_tiny_button'] = plugins_url($this->sBasePluginPath.'/js/tg_tinymce.js');
            return $aPluginArray;
    }

    //initialize default plugin-configuration
    private function load_epaper_options(){
        $this->aEpaperOptions = get_option($this->sEpaperOptionIndex);
        if($this->aEpaperOptions == false):
            $this->aEpaperOptions = array(
            'wordpressapi' => TGE_PLUGIN_ACCOUNT_API_URI);        
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        endif;
    }
    
    //returns registered status
    public function is_registered(){
        $this->apikey = isset($this->aEpaperOptions['apikey'])?$this->aEpaperOptions['apikey']:NULL;
        if (!empty($this->apikey)) {
            $this->bIsRegistered = true;
        } else {
            $this->bIsRegistered = false;
        }
        
        return $this->bIsRegistered;        
    }
    
    //loads plugin translation file
    public function plugin_textdomain() {
        load_plugin_textdomain('1000grad-epaper', false, '1000grad-epaper/lang');
    }
    
    //registers (the pathes of) admin-specific styles and scripts
    public function action_admin_init_register_styles_and_scripts() {            
        wp_register_style('style_colorbox',             plugins_url($this->sBasePluginPath.'colorbox/colorbox.css'));
        wp_register_style('tg_styles',                  plugins_url($this->sBasePluginPath.'css/tg_styles.css'));
        wp_register_script('jquery_migrate',            plugins_url($this->sBasePluginPath.'js/jquery_migrate.js'));
        wp_register_script('jquery2',                   plugins_url($this->sBasePluginPath.'js/jquery.2.0.3.js'));
        wp_register_script('tg_script_js',              plugins_url($this->sBasePluginPath.'js/tg_script.js'), array('jquery')); // benötigt jquery
        wp_register_script('js_colorbox_min',           plugins_url($this->sBasePluginPath.'colorbox/jquery.colorbox-min.js'), array('jquery'));
        wp_register_script('colorbox-epaper',         plugins_url($this->sBasePluginPath.'js/colorbox-epaper.js'), array('jquery'));      
        wp_register_script('uploadify_js',              plugins_url($this->sBasePluginPath.'js/uploadify/jquery.uploadify.js'), array('jquery2'));  
    }

    //init scripts and styles
    public function filter_posts_conditionally_add_scripts_and_styles ($posts) 
    {
        if (!empty($posts)) {     
            wp_enqueue_style('tg_styles');     
            wp_enqueue_script('jquery'); 
            wp_enqueue_script('js_colorbox_min', plugins_url('1000grad-epaper/colorbox/jquery.colorbox-min.js'), array('jquery'));
            wp_enqueue_script('colorbox-epaper', plugins_url('1000grad-epaper/js/colorbox-epaper.js'), array('jquery'));
            wp_enqueue_style('style_colorbox', plugins_url('1000grad-epaper/colorbox/colorbox.css'));
            return $posts;
        }
        return $posts;
    }


    //registers and enqueues plugin-specific styles.
    public function action_enqueue_scripts_for_all_adminpages() 
    {
        switch($this->sPage):
            case 'epaper_channels':
                wp_enqueue_script('jquery2');  
                wp_enqueue_script('jquery_migrate');
                wp_enqueue_script('uploadify_js');        
            default:
                wp_enqueue_style('style_colorbox');
                wp_enqueue_style('uploadify');
                wp_enqueue_style('tg_styles');       
                wp_enqueue_script('js_colorbox_min');
                wp_enqueue_script('colorbox-epaper');    
                wp_enqueue_script('tg_script_js');
                wp_localize_script( 'tg_script_js', 'TGELocalData', array(
                        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
                        'tge_nonce'     => wp_create_nonce( 'epaper_ajax-nonce' ),
                        'wpcontenturl'  => content_url(),
                )); 
                break;
        endswitch;
    }
    
    //integrate epaper-plugin to main-menu
    public function action_epaper_integration_menu() 
    {                  
        add_action( 'admin_enqueue_scripts', array($this,'action_enqueue_scripts_for_all_adminpages' ));        
         add_menu_page(
            'ePaper', 
            'edelpaper', 
            'upload_files', 
            'epaper_channels', 
            array($this, 'adminpage_epaper_channels'), 
            'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+DQo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTYuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+DQoNCjxzdmcNCiAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyINCiAgIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiDQogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiDQogICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIg0KICAgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIg0KICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQvRFREL3NvZGlwb2RpLTAuZHRkIg0KICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiDQogICB2ZXJzaW9uPSIxLjEiDQogICBpZD0iRWJlbmVfMSINCiAgIHg9IjBweCINCiAgIHk9IjBweCINCiAgIHdpZHRoPSIxMjAiDQogICBoZWlnaHQ9IjEyMCINCiAgIHZpZXdCb3g9IjEyMy41MzEgMzI5LjQ2IDEyMCAxMjAiDQogICBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDEyMy41MzEgMzI5LjQ2IDYxMi45NjQgMTQ3LjU0OSINCiAgIHhtbDpzcGFjZT0icHJlc2VydmUiDQogICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjQ4LjUgcjEwMDQwIg0KICAgc29kaXBvZGk6ZG9jbmFtZT0iZWRlbHBhcGVyX2ljb25fZ3JhdS5zdmciPjxtZXRhZGF0YQ0KICAgICBpZD0ibWV0YWRhdGE1NyI+PHJkZjpSREY+PGNjOldvcmsNCiAgICAgICAgIHJkZjphYm91dD0iIj48ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD48ZGM6dHlwZQ0KICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPjxkYzp0aXRsZT48L2RjOnRpdGxlPjwvY2M6V29yaz48L3JkZjpSREY+PC9tZXRhZGF0YT48ZGVmcw0KICAgICBpZD0iZGVmczU1IiAvPjxzb2RpcG9kaTpuYW1lZHZpZXcNCiAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIg0KICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiINCiAgICAgYm9yZGVyb3BhY2l0eT0iMSINCiAgICAgb2JqZWN0dG9sZXJhbmNlPSIxMCINCiAgICAgZ3JpZHRvbGVyYW5jZT0iMTAiDQogICAgIGd1aWRldG9sZXJhbmNlPSIxMCINCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiDQogICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiDQogICAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTA5MCINCiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTA5NiINCiAgICAgaWQ9Im5hbWVkdmlldzUzIg0KICAgICBzaG93Z3JpZD0iZmFsc2UiDQogICAgIGlua3NjYXBlOnpvb209IjUuMTU1Mjc4NCINCiAgICAgaW5rc2NhcGU6Y3g9IjQ1LjU1NzY3MiINCiAgICAgaW5rc2NhcGU6Y3k9IjczLjc3NDQ5OCINCiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjgyNCINCiAgICAgaW5rc2NhcGU6d2luZG93LXk9IjI0Ig0KICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIwIg0KICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJFYmVuZV8xIiAvPjxyZWN0DQogICAgIHg9IjY1LjcyNDk5OCINCiAgICAgeT0iLTcuMDU3OTk3NyINCiAgICAgZGlzcGxheT0ibm9uZSINCiAgICAgd2lkdGg9IjcwOC42NjYwMiINCiAgICAgaGVpZ2h0PSIyMzYuNjY2Ig0KICAgICBpZD0icmVjdDMiDQogICAgIHN0eWxlPSJmaWxsOiMzMzMzMzM7ZGlzcGxheTpub25lIiAvPjxnDQogICAgIGRpc3BsYXk9Im5vbmUiDQogICAgIGlkPSJnNSINCiAgICAgc3R5bGU9ImRpc3BsYXk6bm9uZSINCiAgICAgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwtMjcuNTQ4OTk3KSI+PHBhdGgNCiAgICAgICBkaXNwbGF5PSJpbmxpbmUiDQogICAgICAgZD0ibSAxNjEuMzA3LDE4NC43OTIgYyAtNS40OTYsMCAtMTAuMjI0LC0xLjA1MiAtMTQuMTg4LC0zLjE2NSAtMy45NjYsLTIuMTIzIC03LjIxMiwtNC45NDYgLTkuNzI5LC04LjQ0NiAtMi41MjcsLTMuNTI3IC00LjM5NCwtNy41NjMgLTUuNjA5LC0xMi4xMDYgLTEuMjE5LC00LjU0OCAtMS44MjQsLTkuMzAxIC0xLjgyNCwtMTQuMjU2IDAsLTUuMDQ3IDAuNjA1LC05Ljg2OSAxLjgyNCwtMTQuNDYzIDEuMjE3LC00LjU5MyAzLjEzMiwtOC42MjYgNS43NDEsLTEyLjA5MiAyLjYxNCwtMy40NjggNS45MjcsLTYuMjM4IDkuOTM0LC04LjMxNSA0LjAwOCwtMi4wNyA4LjgwOSwtMy4xMDggMTQuMzkzLC0zLjEwOCA4LjU2MSwwIDE1LjYzNCwzLjEwOCAyMS4yMTksOS4zMjggMi43MDUsMy4wNjUgNC44ODksNi41MzMgNi41NTcsMTAuNDA0IDEuNjY4LDMuODc1IDIuNTAyLDguMTk4IDIuNTAyLDEyLjk3NSB2IDkuNDYzIEggMTQ5LjY5IGMgMC4yNzEsMi4xMjcgMC42MDQsNC4yMDggMS4wMTQsNi4yNDkgMC40MDEsMi4wMzYgMS4wNzksMy44NDggMi4wMjYsNS40NTggMC45NDgsMS41OTcgMi4yMywyLjg3OCAzLjg1MiwzLjg2MiAxLjYyMSwwLjk2IDMuNzg2LDEuNDYzIDYuNDg1LDEuNDYzIDMuNDI2LDAgNi4yODcsLTAuODM3IDguNTg0LC0yLjUwNiAyLjI5OSwtMS42NjYgNC40MzQsLTMuODAzIDYuNDIsLTYuNDIgbCAxNC41OTYsOS4xOTEgYyAtMi4xNjYsMy4yOTkgLTQuMzUsNS45NzUgLTYuNTU3LDguMDM5IC0yLjIwOCwyLjA2MyAtNC41NzIsMy42ODMgLTcuMDkyLDQuODcyIC00Ljc4NSwyLjM4OCAtMTAuNjgxLDMuNTczIC0xNy43MTEsMy41NzMgeiBtIDAuNDA2LC02MC41NDIgYyAtMi4wNzQsMCAtMy44MDgsMC4zMTMgLTUuMTk5LDAuOTQgLTEuMzk3LDAuNjMyIC0yLjU1MSwxLjUxNSAtMy40NDgsMi42NCAtMC45LDEuMTMgLTEuNjA0LDIuNDUyIC0yLjA5NSwzLjk4NCAtMC40OTcsMS41MzggLTAuODc2LDMuMTUzIC0xLjE1Miw0Ljg2NCBoIDIzLjc4NiBjIC0wLjI3MSwtMy42OTIgLTEuMzk2LC02LjY2NiAtMy4zNzcsLTguOTE3IC0yLjA3MSwtMi4zNDIgLTQuOTE1LC0zLjUxMSAtOC41MTUsLTMuNTExIHoiDQogICAgICAgaWQ9InBhdGg3Ig0KICAgICAgIGlua3NjYXBlOmNvbm5lY3Rvci1jdXJ2YXR1cmU9IjAiDQogICAgICAgc3R5bGU9ImZpbGw6I2IyYjJiMjtkaXNwbGF5OmlubGluZSIgLz48cGF0aA0KICAgICAgIGRpc3BsYXk9ImlubGluZSINCiAgICAgICBkPSJtIDI0Ni4wNDIsMTc3LjQ5NCBjIC0yLjYwNywyLjI2MiAtNS4zNTUsNC4wMzUgLTguMjQzLDUuMzMxIC0yLjg4NSwxLjMyMSAtNi4wOCwxLjk2NyAtOS41OTIsMS45NjcgLTkuMzcsMCAtMTYuNjcxLC0zLjY5NiAtMjEuODk2LC0xMS4wODYgLTUuMTM3LC03LjEwNCAtNy43MDMsLTE2LjY2OCAtNy43MDMsLTI4LjY0NSAwLC0xMC4xOCAyLjc0NywtMTguNzQgOC4yNDIsLTI1LjY3NiA1LjY3NSwtNy4wMyAxMi43NDgsLTEwLjU0NiAyMS4yMTksLTEwLjU0NiAyLjc5NiwwIDUuNjA4LDAuNTQyIDguNDQ4LDEuNjI2IDIuODM2LDEuMDggNS4zMzcsMi41MjEgNy40OTcsNC4zMjQgViA4Mi44OTMgaCAxOS44NjggViAxODMuNDQ3IEggMjQ2LjcyIGwgLTAuNjc4LC01Ljk1MyB6IG0gLTcuMjcxLC01MC40MzIgYyAtMS45NjgsLTAuOTc1IC0zLjk4MywtMS40NjQgLTYuMDQ2LC0xLjQ2NCAtMi45NTksMCAtNS4zNTcsMC42NjggLTcuMTkzLDIuMDA0IC0xLjgzNiwxLjMzMSAtMy4yOTUsMy4wNDMgLTQuMzY3LDUuMTM4IC0xLjA4LDIuMDg2IC0xLjc5NCw0LjM3OSAtMi4xNTIsNi44NzIgLTAuMzYxLDIuNDg5IC0wLjUzNCw0Ljg5MSAtMC41MzQsNy4yMDYgMCwyLjMxNCAwLjE3Myw0LjczOSAwLjUzNCw3LjI3IDAuMzU4LDIuNTQgMS4wNzIsNC44MzYgMi4xNTIsNi44NzYgMS4wNzIsMi4wNDEgMi41MzEsMy43MzQgNC4zNjcsNS4wNjEgMS44MzYsMS4zNDYgNC4yMzQsMi4wMTggNy4xOTMsMi4wMTggMi41MDcsMCA0LjU2OSwtMC41MDMgNi4xODQsLTEuNDc3IDEuNjE0LC0wLjk3MSAzLjMxOCwtMi4yNjYgNS4xMDUsLTMuODcxIHYgLTMyLjAyNiBjIC0xLjUyMywtMS40MTkgLTMuMjY5LC0yLjYyNCAtNS4yNDMsLTMuNjA3IHoiDQogICAgICAgaWQ9InBhdGg5Ig0KICAgICAgIGlua3NjYXBlOmNvbm5lY3Rvci1jdXJ2YXR1cmU9IjAiDQogICAgICAgc3R5bGU9ImZpbGw6I2IyYjJiMjtkaXNwbGF5OmlubGluZSIgLz48cGF0aA0KICAgICAgIGRpc3BsYXk9ImlubGluZSINCiAgICAgICBkPSJtIDMwNC4wMjQsMTg0Ljc5MiBjIC01LjQ5NiwwIC0xMC4yMjUsLTEuMDUyIC0xNC4xODgsLTMuMTY1IC0zLjk2NywtMi4xMjMgLTcuMjEyLC00Ljk0NiAtOS43MywtOC40NDYgLTIuNTI1LC0zLjUyNyAtNC4zOTIsLTcuNTYzIC01LjYwNywtMTIuMTA2IC0xLjIxOSwtNC41NDggLTEuODI1LC05LjMwMSAtMS44MjUsLTE0LjI1NiAwLC01LjA0NyAwLjYwNiwtOS44NjkgMS44MjUsLTE0LjQ2MyAxLjIxNywtNC41OTMgMy4xMywtOC42MjYgNS43NCwtMTIuMDkyIDIuNjE1LC0zLjQ2OCA1LjkyOCwtNi4yMzggOS45MzQsLTguMzE1IDQuMDEsLTIuMDcgOC44MDgsLTMuMTA4IDE0LjM5NSwtMy4xMDggOC41NjEsMCAxNS42MzMsMy4xMDggMjEuMjE4LDkuMzI4IDIuNzA2LDMuMDY1IDQuODksNi41MzMgNi41NTgsMTAuNDA0IDEuNjY3LDMuODc1IDIuNTAxLDguMTk4IDIuNTAxLDEyLjk3NSB2IDkuNDYzIGggLTQyLjQzOCBjIDAuMjcsMi4xMjcgMC42MDUsNC4yMDggMS4wMTMsNi4yNDkgMC40MDMsMi4wMzYgMS4wOCwzLjg0OCAyLjAyOSw1LjQ1OCAwLjk0NywxLjU5NyAyLjIzLDIuODc4IDMuODUxLDMuODYyIDEuNjIxLDAuOTYgMy43ODUsMS40NjMgNi40ODUsMS40NjMgMy40MjUsMCA2LjI4NywtMC44MzcgOC41ODUsLTIuNTA2IDIuMjk3LC0xLjY2NiA0LjQzNSwtMy44MDMgNi40MTgsLTYuNDIgbCAxNC41OTcsOS4xOTEgYyAtMi4xNjUsMy4yOTkgLTQuMzQ5LDUuOTc1IC02LjU1OCw4LjAzOSAtMi4yMDcsMi4wNjMgLTQuNTY5LDMuNjgzIC03LjA5MSw0Ljg3MiAtNC43ODUsMi4zODggLTEwLjY4MiwzLjU3MyAtMTcuNzEyLDMuNTczIHogbSAwLjQwOCwtNjAuNTQyIGMgLTIuMDc2LDAgLTMuODEsMC4zMTMgLTUuMjAxLDAuOTQgLTEuMzk4LDAuNjMyIC0yLjU1LDEuNTE1IC0zLjQ1LDIuNjQgLTAuODk5LDEuMTMgLTEuNjAxLDIuNDUyIC0yLjA5NCwzLjk4NCAtMC40OTgsMS41MzggLTAuODc1LDMuMTUzIC0xLjE1Miw0Ljg2NCBoIDIzLjc4NiBjIC0wLjI2OCwtMy42OTIgLTEuMzk2LC02LjY2NiAtMy4zNzgsLTguOTE3IC0yLjA2OSwtMi4zNDIgLTQuOTEyLC0zLjUxMSAtOC41MTEsLTMuNTExIHoiDQogICAgICAgaWQ9InBhdGgxMSINCiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIg0KICAgICAgIHN0eWxlPSJmaWxsOiNiMmIyYjI7ZGlzcGxheTppbmxpbmUiIC8+PHBhdGgNCiAgICAgICBkaXNwbGF5PSJpbmxpbmUiDQogICAgICAgZD0ibSAzNDcuNDE1LDgyLjg5MyBoIDE5Ljg1NiBWIDE4My40NDcgSCAzNDcuNDE1IFYgODIuODkzIHoiDQogICAgICAgaWQ9InBhdGgxMyINCiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIg0KICAgICAgIHN0eWxlPSJmaWxsOiNiMmIyYjI7ZGlzcGxheTppbmxpbmUiIC8+PC9nPjxnDQogICAgIGRpc3BsYXk9Im5vbmUiDQogICAgIGlkPSJnMTUiDQogICAgIHN0eWxlPSJkaXNwbGF5Om5vbmUiDQogICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsLTI3LjU0ODk5NykiPjxwYXRoDQogICAgICAgZGlzcGxheT0iaW5saW5lIg0KICAgICAgIGQ9Im0gNDE2LjUxNiwxODQuNzkyIGMgLTIuODY4LDAgLTUuNjk4LC0wLjU0IC04LjQ4MywtMS42MTkgLTIuNzk0LC0xLjA4IC01LjI1NSwtMi41NjcgLTcuNDE0LC00LjQ2NyB2IDMwLjQxNyBoIC0xOS44NyB2IC05OC45MjcgaCAxOS4wNTYgdiA0LjcyNiBjIDIuNDM1LC0xLjg4NyA1LjAwMiwtMy4zNzYgNy43MDIsLTQuNDU2IDIuNzEyLC0xLjA4NyA1LjY0MSwtMS42MjYgOC43OCwtMS42MjYgOS4zODUsMCAxNi42NzksMy42OTYgMjEuODk3LDExLjA4NiA1LjIyMiw3LjIwNiA3LjgzNCwxNi43MTIgNy44MzQsMjguNTEzIDAsMTAuMjcxIC0yLjc4MiwxOC44ODEgLTguMzUyLDI1LjgxMiAtNS42Niw3LjAyNiAtMTIuNzE2LDEwLjU0MSAtMjEuMTUsMTAuNTQxIHogbSAtNC4zMzEsLTU5LjE5NCBjIC0yLjYxNywwIC00LjcyOSwwLjQ2NyAtNi4zOTYsMS40MDQgLTEuNjU2LDAuOTM0IC0zLjM4NCwyLjI0NyAtNS4xNzEsMy45MzUgdiAzMi4wMjIgYyAxLjUxMiwxLjM0IDMuMjM4LDIuNTIgNS4xNzEsMy41NCAxLjkzMywxLjAyMSAzLjk3MiwxLjUzOCA2LjExOSwxLjUzOCAyLjk2MywwIDUuMzYzLC0wLjY3MyA3LjE4OCwtMi4wMDQgMS44NDgsLTEuMzQxIDMuMjczLC0zLjA0OCA0LjMwNywtNS4xMzQgMS4wMzEsLTIuMDk1IDEuNzQsLTQuMzg4IDIuMTQ4LC02Ljg3NiAwLjQwNiwtMi40OTQgMC42LC00Ljg5MiAwLjYsLTcuMjA2IDAsLTMuNDczIC0wLjMxMiwtNi41MjEgLTAuOTM2LC05LjE0MiAtMC42MjYsLTIuNjIxIC0xLjU2MiwtNC44NzIgLTIuODIsLTYuNzM5IC0yLjI0NSwtMy41NTQgLTUuNjUzLC01LjMzOCAtMTAuMjEsLTUuMzM4IHoiDQogICAgICAgaWQ9InBhdGgxNyINCiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIg0KICAgICAgIHN0eWxlPSJmaWxsOiNmNmY2ZjY7ZGlzcGxheTppbmxpbmUiIC8+PHBhdGgNCiAgICAgICBkaXNwbGF5PSJpbmxpbmUiDQogICAgICAgZD0ibSA0OTMuNiwxNzguNDMyIGMgLTMuNDMyLDIuMTczIC02LjgwNCwzLjc3IC0xMC4xMDQsNC44IC0zLjI5LDEuMDMzIC02Ljk3MywxLjU2IC0xMS4wMzksMS41NiAtMi45NzUsMCAtNS43MTEsLTAuNTA0IC04LjE4MSwtMS40ODYgLTIuNDk5LC0wLjk4NCAtNC42MzQsLTIuMzc5IC02LjQ0NSwtNC4xODcgLTEuODEyLC0xLjgwMiAtMy4yMDMsLTMuOTI2IC00LjE5NSwtNi4zNDcgLTAuOTk4LC0yLjQzOCAtMS40OTIsLTUuMTM3IC0xLjQ5MiwtOC4xMTEgMCwtNy40OSAyLjcwMiwtMTMuNDc0IDguMTAxLC0xNy45OCAzLjg4OCwtMy4wNjEgOC4wNTIsLTUuMjY2IDEyLjUwMiwtNi42MjQgNC40NjYsLTEuMzUxIDkuMDQ4LC0yLjQ3NiAxMy43MjcsLTMuMzc3IDEuNzA2LC0wLjM2MSAzLjY5NiwtMC42MzIgNS45NTEsLTAuODEgdiAtNC4xODcgYyAwLC0yLjYxNyAtMC44NjMsLTQuNTA3IC0yLjU2OCwtNS42ODMgLTEuNzE0LC0xLjE2NyAtMy43OSwtMS43NSAtNi4yMTYsLTEuNzUgLTMuNjk0LDAgLTYuNjQ2LDAuODM1IC04Ljg1NCwyLjQ5NiAtMi4yMDgsMS42NjUgLTQuMjU5LDMuOTM4IC02LjE1Myw2LjgyNiBsIC0xMy4yNDcsLTEwIGMgMy4xNTMsLTUuMDQ2IDcuMTg2LC04Ljc2NiAxMi4xMDUsLTExLjE1MSA0Ljg5NiwtMi4zODEgMTAuMjgyLC0zLjU4MiAxNi4xNDcsLTMuNTgyIDEwLjI5NywwIDE3LjYzOSwyLjQxMSAyMi4wNDQsNy4yMzUgNC40MDEsNC44MTcgNi42MDgsMTIuMjcyIDYuNjA4LDIyLjM2MSB2IDM0LjU5OCBjIDAsMi40MzggMC4xNjYsNC40MTQgMC41MjgsNS45NTIgMC4xOTEsMC41MjUgLTAuMDQ2LDEuMDI5IC0wLjY3LDEuNDg3IHYgMi45NzQgaCAtMTcuNjE1IGwgLTAuOTM0LC01LjAxNCB6IG0gLTcuMjcxLC0yNy4wOSBjIC0yLjMyNywwLjQ5OSAtNC41NjEsMS4xOTkgLTYuNjg0LDIuMSAtMi4xMjMsMC45MDIgLTMuOTI0LDIuMDkyIC01LjQxMywzLjU4MiAtMS40ODYsMS40NzkgLTIuMjI4LDMuNCAtMi4yMjgsNS43MzggMCwxLjk5NCAwLjU3MiwzLjU3OCAxLjcsNC43OTkgMS4xMjgsMS4yMjcgMi43MTMsMS44MjYgNC44MDEsMS44MjYgMi40MjMsMCA0LjkxOCwtMC43NDYgNy40ODQsLTIuMjMyIDIuNTY5LC0xLjQ4OCA0Ljc1MywtMi45NTEgNi41NjQsLTQuMzkzIHYgLTEyLjcwNSBjIC0xLjgwOSwwLjM2NiAtMy44NzEsMC43OTIgLTYuMjI0LDEuMjg1IHoiDQogICAgICAgaWQ9InBhdGgxOSINCiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIg0KICAgICAgIHN0eWxlPSJmaWxsOiNmNmY2ZjY7ZGlzcGxheTppbmxpbmUiIC8+PHBhdGgNCiAgICAgICBkaXNwbGF5PSJpbmxpbmUiDQogICAgICAgZD0ibSA1NTkuMjMsMTg0Ljc5MiBjIC0yLjg3NywwIC01LjcxLC0wLjU0IC04LjQ5MSwtMS42MTkgLTIuNzg0LC0xLjA4IC01LjI1NywtMi41NjcgLTcuNDAyLC00LjQ2NyB2IDMwLjQxNyBoIC0xOS44NyB2IC05OC45MjcgaCAxOS4wNTEgdiA0LjcyNiBjIDIuNDM5LC0xLjg4NyA1LjAwNiwtMy4zNzYgNy43MTksLTQuNDU2IDIuNjg4LC0xLjA4NCA1LjYxNCwtMS42MjYgOC43ODIsLTEuNjI2IDkuMzU1LDAgMTYuNjUyLDMuNjk2IDIxLjg4NCwxMS4wODYgNS4yMzEsNy4yMDYgNy44NDcsMTYuNzEyIDcuODQ3LDI4LjUxMyAwLDEwLjI3MSAtMi43ODQsMTguODg2IC04LjM2MywyNS44MjIgLTUuNjUzLDcuMDE2IC0xMi43MDcsMTAuNTMxIC0yMS4xNTcsMTAuNTMxIHogbSAtNC4zNDEsLTU5LjE5NCBjIC0yLjU5LDAgLTQuNzI4LDAuNDcxIC02LjM4MiwxLjQwNCAtMS42NTYsMC45MzQgLTMuMzg1LDIuMjQ3IC01LjE3MSwzLjkzNSB2IDMyLjAxOCBjIDEuNTIyLDEuMzQ1IDMuMjUsMi41MjEgNS4xNzEsMy41NTUgMS45MTksMS4wMDcgMy45NiwxLjUzMiA2LjExNSwxLjUzMiAyLjk1NCwwIDUuMzU0LC0wLjY3MiA3LjIwMSwtMi4wMTggMS44MjYsLTEuMzI2IDMuMjY0LC0zLjA0NyA0LjI5NCwtNS4xMzMgMS4wMzIsLTIuMDg2IDEuNzUyLC00LjM3OSAyLjE0OCwtNi44NjcgMC40MDksLTIuNDg5IDAuNjEyLC00Ljg5MiAwLjYxMiwtNy4yMDYgMCwtMy40NzMgLTAuMzExLC02LjUyMSAtMC45NDYsLTkuMTQyIC0wLjYyNywtMi42MjEgLTEuNTc0LC00Ljg3MiAtMi44MjEsLTYuNzM5IC0yLjI0MiwtMy41NTUgLTUuNjM3LC01LjMzOSAtMTAuMjIxLC01LjMzOSB6Ig0KICAgICAgIGlkPSJwYXRoMjEiDQogICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCINCiAgICAgICBzdHlsZT0iZmlsbDojZjZmNmY2O2Rpc3BsYXk6aW5saW5lIiAvPjxwYXRoDQogICAgICAgZGlzcGxheT0iaW5saW5lIg0KICAgICAgIGQ9Im0gNjI3LjAxNywxODQuNzkyIGMgLTUuNDk1LDAgLTEwLjIyMiwtMS4wNTIgLTE0LjE4MiwtMy4xNjUgLTMuOTcyLC0yLjEyMyAtNy4yMjIsLTQuOTQ2IC05Ljc0MiwtOC40NDYgLTIuNTIsLTMuNTI3IC00LjM5MywtNy41NjMgLTUuNjAzLC0xMi4xMDYgLTEuMjE0LC00LjU0OCAtMS44MjMsLTkuMzAxIC0xLjgyMywtMTQuMjU2IDAsLTUuMDQ3IDAuNjA5LC05Ljg2OSAxLjgyMywtMTQuNDYzIDEuMjEsLTQuNTkzIDMuMTMxLC04LjYyNiA1Ljc0OCwtMTIuMDkyIDIuNjEyLC0zLjQ2OCA1LjkyNiwtNi4yMzggOS45MzQsLTguMzE1IDQuMDA4LC0yLjA3IDguODA4LC0zLjEwOCAxNC4zODUsLTMuMTA4IDguNTU2LDAgMTUuNjMzLDMuMTA4IDIxLjIyNSw5LjMyOCAyLjY5OSwzLjA2NSA0Ljg4Nyw2LjUzMyA2LjU1MSwxMC40MDQgMS42NjksMy44NzUgMi40OTgsOC4xOTggMi40OTgsMTIuOTc1IHYgOS40NjMgaCAtNDIuNDI1IGMgMC4yNjEsMi4xMjcgMC42MDEsNC4yMDggMS4wMDcsNi4yNDkgMC40MDYsMi4wMzYgMS4wNzksMy44NDggMi4wMjYsNS40NTggMC45NDYsMS41OTcgMi4yMzIsMi44NzggMy44NTMsMy44NjIgMS42MTksMC45NiAzLjc5NCwxLjQ2MyA2LjQ3OSwxLjQ2MyAzLjQzMiwwIDYuMjg3LC0wLjgzNyA4LjU5MywtMi41MDYgMi4zMDIsLTEuNjY2IDQuNDM4LC0zLjgwMyA2LjQxOSwtNi40MiBsIDE0LjYwMSw5LjE5MSBjIC0yLjE3MiwzLjI5OSAtNC4zNTUsNS45NzUgLTYuNTYyLDguMDM5IC0yLjIxLDIuMDYzIC00LjU3MiwzLjY4MyAtNy4wOTIsNC44NzIgLTQuNzc5LDIuMzg4IC0xMC42OCwzLjU3MyAtMTcuNzEzLDMuNTczIHogbSAwLjQwOCwtNjAuNTQyIGMgLTIuMDc1LDAgLTMuODEzLDAuMzEzIC01LjIwNiwwLjk0IC0xLjM5MiwwLjYzMiAtMi41NDQsMS41MTUgLTMuNDQzLDIuNjQgLTAuODk3LDEuMTMgLTEuNTk1LDIuNDUyIC0yLjA5NywzLjk4NCAtMC40OTYsMS41MzggLTAuODY1LDMuMTUzIC0xLjE1Myw0Ljg2NCBoIDIzLjc5MSBjIC0wLjI3NCwtMy42OTIgLTEuNDAzLC02LjY2NiAtMy4zNzMsLTguOTE3IC0yLjA3NCwtMi4zNDIgLTQuOTE5LC0zLjUxMSAtOC41MTksLTMuNTExIHoiDQogICAgICAgaWQ9InBhdGgyMyINCiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIg0KICAgICAgIHN0eWxlPSJmaWxsOiNmNmY2ZjY7ZGlzcGxheTppbmxpbmUiIC8+PHBhdGgNCiAgICAgICBkaXNwbGF5PSJpbmxpbmUiDQogICAgICAgZD0ibSA2OTIuOTc0LDEzMi44OTYgYyAtMi43OTYsMi4yNTUgLTQuNjQ2LDQuMzczIC01LjU0Niw2LjM1IHYgNDQuMjAxIGggLTE5Ljg1NiB2IC03My4yNTEgaCAxOC41MDMgdiA4LjM3NSBjIDIuNDM0LC0zLjU5OSA1LjMwMywtNi4xMjQgOC41ODksLTcuNTY1IDMuMjg4LC0xLjQzOSA3LjA5MSwtMi4xNjYgMTEuNDIzLC0yLjE2NiB2IDIwLjgxNiBsIC00LjA1NywtMC4yNzEgYyAtMi45NzYsLTAuMDkxIC02LjAwMiwxLjA4MSAtOS4wNTYsMy41MTEgeiINCiAgICAgICBpZD0icGF0aDI1Ig0KICAgICAgIGlua3NjYXBlOmNvbm5lY3Rvci1jdXJ2YXR1cmU9IjAiDQogICAgICAgc3R5bGU9ImZpbGw6I2Y2ZjZmNjtkaXNwbGF5OmlubGluZSIgLz48L2c+PHBvbHlnb24NCiAgICAgZGlzcGxheT0ibm9uZSINCiAgICAgcG9pbnRzPSI3MjQuNTAzLDYxLjU3NCA3MzAuMTg5LDczLjEwOCA3NDIuOTIxLDc0Ljk1NiA3MzMuNzA2LDgzLjkzIDczNS44OSw5Ni42MTMgNzI0LjUwMyw5MC42MjQgNzEzLjExNiw5Ni42MTMgNzE1LjMwMiw4My45MyA3MDYuMDg2LDc0Ljk1NiA3MTguODA3LDczLjEwOCAiDQogICAgIGlkPSJwb2x5Z29uMjciDQogICAgIHN0eWxlPSJmaWxsOiNkZWRjMDA7ZGlzcGxheTpub25lIg0KICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLC0yNy41NDg5OTcpIiAvPjxnDQogICAgIGlkPSJnMjkiDQogICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIwLjk0OTQwMiwtMTUuMzI4NTEyKSINCiAgICAgc3R5bGU9ImZpbGw6Izk5OTk5OTtmaWxsLW9wYWNpdHk6MSI+PHBhdGgNCiAgICAgICBkPSJtIDE1NC44ODIsNDUyLjY3NyBjIC01LjQ5NSwwIC0xMC4yMjMsLTEuMDUxIC0xNC4xODgsLTMuMTY0IC0zLjk2NiwtMi4xMjMgLTcuMjEyLC00Ljk0NSAtOS43MywtOC40NDUgLTIuNTI2LC0zLjUyNyAtNC4zOTMsLTcuNTYyIC01LjYwOCwtMTIuMTA1IC0xLjIxOSwtNC41NDkgLTEuODI0LC05LjMwMSAtMS44MjQsLTE0LjI1NiAwLC01LjA0NyAwLjYwNSwtOS44NjkgMS44MjQsLTE0LjQ2MyAxLjIxNywtNC41OTUgMy4xMzIsLTguNjI3IDUuNzQxLC0xMi4wOTMgMi42MTMsLTMuNDY5IDUuOTI3LC02LjIzNyA5LjkzNCwtOC4zMTUgNC4wMDgsLTIuMDcgOC44MDgsLTMuMTA3IDE0LjM5MiwtMy4xMDcgOC41NjIsMCAxNS42MzUsMy4xMDcgMjEuMjIsOS4zMjggMi43MDUsMy4wNjQgNC44ODksNi41MzMgNi41NTcsMTAuNDA0IDEuNjY4LDMuODc1IDIuNTAyLDguMTk2IDIuNTAyLDEyLjk3NSB2IDkuNDYzIGggLTQyLjQzNyBjIDAuMjcxLDIuMTI3IDAuNjA0LDQuMjA3IDEuMDE0LDYuMjQ4IDAuNDAxLDIuMDM3IDEuMDc5LDMuODQ4IDIuMDI2LDUuNDU5IDAuOTQ3LDEuNTk2IDIuMjMsMi44NzcgMy44NTIsMy44NjEgMS42MjEsMC45NjEgMy43ODYsMS40NjMgNi40ODUsMS40NjMgMy40MjYsMCA2LjI4NywtMC44MzYgOC41ODQsLTIuNTA2IDIuMjk5LC0xLjY2NiA0LjQzNCwtMy44MDMgNi40MiwtNi40MiBsIDE0LjU5Niw5LjE5MSBjIC0yLjE2NiwzLjI5OSAtNC4zNSw1Ljk3NSAtNi41NTcsOC4wMzkgLTIuMjA4LDIuMDYyIC00LjU3MiwzLjY4NCAtNy4wOTMsNC44NzEgLTQuNzgzLDIuMzg5IC0xMC42OCwzLjU3MiAtMTcuNzEsMy41NzIgeiBtIDAuNDA2LC02MC41NCBjIC0yLjA3NSwwIC0zLjgwOCwwLjMxMyAtNS4yLDAuOTQgLTEuMzk2LDAuNjMxIC0yLjU1LDEuNTE0IC0zLjQ0NywyLjYzOSAtMC45LDEuMTMyIC0xLjYwNCwyLjQ1MyAtMi4wOTYsMy45ODQgLTAuNDk3LDEuNTM5IC0wLjg3NiwzLjE1NCAtMS4xNTEsNC44NjUgaCAyMy43ODYgYyAtMC4yNzEsLTMuNjkzIC0xLjM5NiwtNi42NjYgLTMuMzc3LC04LjkxOCAtMi4wNzEsLTIuMzQgLTQuOTE0LC0zLjUxIC04LjUxNSwtMy41MSB6Ig0KICAgICAgIGlkPSJwYXRoMzEiDQogICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCINCiAgICAgICBzdHlsZT0iZmlsbDojOTk5OTk5O2ZpbGwtb3BhY2l0eToxIiAvPjwvZz48cG9seWdvbg0KICAgICBwb2ludHM9IjcyOS40NjYsMzY0LjUgNzE4LjA3NywzNTguNTA5IDcwNi42OTEsMzY0LjUgNzA4Ljg3OCwzNTEuODE2IDY5OS42NiwzNDIuODQxIDcxMi4zODMsMzQwLjk5NCA3MTguMDc3LDMyOS40NiA3MjMuNzY1LDM0MC45OTQgNzM2LjQ5NSwzNDIuODQxIDcyNy4yODEsMzUxLjgxNiAiDQogICAgIGlkPSJwb2x5Z29uNTEiDQogICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKC01MDAuMDcwMDEsOC45MTg0ODE5KSINCiAgICAgc3R5bGU9ImZpbGw6Izk5OTk5OTtmaWxsLW9wYWNpdHk6MSIgLz48L3N2Zz4='
        );
        
        
        
        if ($this->bIsRegistered === false):   
            add_submenu_page(
                'epaper_channels', 
                'ePaper '.__('Registration','1000grad-epaper'), 
                __('Registration','1000grad-epaper'), 
                'upload_files', 
                'epaper_apikey', 
                array($this, 'adminpage_epaper_apikey')
            );
        else:
            add_submenu_page(
                'epaper_channels', // parent
                'ePaper '.__('Manage Subscription','1000grad-epaper'), 
                __('Manage Subscription','1000grad-epaper'), 
                'upload_files', 
                'epaper_subscription', 
                array($this, 'adminpage_epaper_subscription')
            );            
        endif;
        
        add_options_page( 'edelpaper', 'edelpaper', 'upload_files','epaper_settings', array($this,'adminpage_epaper_settings'));            
    }
    
    //add metabox to page|post-editor
    public function action_add_metabox_epaper()    
    {
        if ($this->bIsRegistered === true)  {  
            add_meta_box('epaper_editorbox', 'edelpaper', array($this, 'meta_box_epaper'), 'post', 'side', 'high');
            add_meta_box('epaper_editorbox', 'edelpaper', array($this, 'meta_box_epaper'), 'page', 'side', 'high');
            return true;
        }
        
        return false;
    } 
    
    //shows registration-info, if isRegistered == false
    public function showRegistrationInfo()
    {        
        if ($this->bIsRegistered === false && !isset($_POST['registration_key_requested']) && (!isset($_GET['email']) && !isset($_GET['code']) )) {
            
            $sMessage = sprintf("%s <br/><br/> %s", __( "edelpaper is not registered yet.", '1000grad-epaper' ), sprintf(__( "Please %sregister your installation%s.", '1000grad-epaper' ), 
                    "<a href='admin.php?page=epaper_apikey'>", "</a>"));
            
            $this->showInfo($sMessage);
       }
    }
    
    //shortcode function of plugin
    public function shortcode_epaper($aArgs) 
    {
        if (isset($aArgs['url'])) 
            return "<a href=".$aArgs['url']." class=ePaper target=_blank> <img class=tg_preview_image src=".$aArgs['url']."/epaper/epaper-ani.gif /></a>";
        if ($this->bIsRegistered === true)  {
            $iChannel = (isset($aArgs['nr']) && !empty($aArgs['nr']))?$aArgs['nr']:1;
            $iPage = (isset($aArgs['page']) && !empty($aArgs['page']))?$aArgs['page']:1;
            $oChannels = $this->getChannels();
            $oChannel = isset($oChannels->channels[($iChannel-1)])?$oChannels->channels[($iChannel-1)]:NULL;
            if($oChannel == NULL) return false;

            $sClass = (isset($aArgs['class']) && !empty($aArgs['class']))?$aArgs['class']:'alignleft';
            $sLink = $this->getEpaperLink($oChannel, NULL, array('class' => $sClass, 'page' => $iPage));

            $this->bUseMainTemplate = false;
            $this->oView->class = $sClass;
            $this->oView->link = $sLink;

            ob_start();
            $this->showContent();
            $sShortcodeContent = ob_get_contents();
            ob_end_clean();
            return $sShortcodeContent;
        }
        
        return false;
    }
    
    //registration-adminpage
    public function adminpage_epaper_apikey()
    {
        if(isset($_POST['register_account'])):
            $this->sendRegistrationEmail();
            $sEmail = (isset($_POST['apikey_email']) && !empty($_POST['apikey_email']))?$_POST['apikey_email']:NULL;
            if($sEmail !== NULL):
                $this->aEpaperOptions['email'] = $sEmail;
                update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
            endif;
        endif;
        
        $this->oView->registration_error = false;
        $this->oView->email_submitted = true;
        $this->oView->message = NULL;
        $this->oView->registration_code_entered = false;
        
        switch(isset($_GET['code'])):
            case true:
                $this->oView->code = trim(htmlspecialchars($_GET['code']));  
                $this->oView->email = trim(htmlspecialchars($_GET['email']));
                $this->oView->registration_code_entered = true;

                try {
                    $oResult = ($this->oAccountApi->sendCodeGetApikey($this->oView->email, $this->oView->code));
                } catch (SoapFault $e) {
                    $this->showWarning("error on receiving apikey. ".$e->getMessage());
                    die();
                }
                
                if ($oResult == false):
                    $this->showWarning(sprintf('%s %s', __("ePaper Registration fault.", '1000grad-epaper'), __("Please type in the confirmation code you was receiving via email.", '1000grad-epaper')));
                else:
                    $oResult = json_decode($oResult);
                    $this->aEpaperOptions['email'] = $this->oView->email;
                    $this->aEpaperOptions['url'] = $oResult->apiurl;
                    $this->aEpaperOptions['apikey'] = $oResult->apikey;
                    $this->aEpaperOptions['apikey_as'] = $oResult->apikey_as;
                    update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
                    $this->bIsRegistered = true;
                    $this->showInfo(sprintf('%s', __("<p>Now you can use this edelpaper Plugin!</p>", '1000grad-epaper')));
                endif;
                
                $this->oView->registration_error = ($oResult==false)?true:false;
                
                break;
            
            case false:
                if(isset($this->aEpaperOptions['email']) && $this->aEpaperOptions['email'] != ""):
                    $this->oView->email_submitted = true;
                else:
                    $this->oView->email_submitted = false;
                endif;
                
                break;
        endswitch;

        $this->aTemplateVars = array('TITLE' => __("edelpaper Registration","1000grad-epaper"));
        $this->sTemplate = 'adminpage_epaper_apikey';
        $this->bUseMainTemplate = true;
        $this->showContent();          
    }
    
    //registration-email
    private function sendRegistrationEmail(){
        $sEmail = trim(htmlspecialchars($_POST['apikey_email']));
        $bAgb = isset($_POST['agb'])?true:false;
        $bNewsletter = isset($_POST['newsletter'])?true:false;
        
        $sLanguage = __("en",'1000grad-epaper');
        
        global $wp_version;
        $sWordpressVersion = $wp_version;
        $sPhpVersion = phpversion();

        $sAdminUrl = admin_url();
        $sSubject = "wordpress";
        $sWordpressCode = "";
        
        $aEpaperOptions = array(
            'email' => $sEmail, 
            'text' => $sSubject,
            'agb' => ($bAgb === true)?'yes':'no',
            'wordpressapi' => TGE_PLUGIN_ACCOUNT_API_URI,
            'newsletter' => ($bNewsletter === true)?'yes':'no' );
        
        update_option($this->sEpaperOptionIndex, $aEpaperOptions);  

        try {
            $sResponseMessage = $this->oAccountApi->getRegistrationCodeByEmail($sEmail, $sSubject, $sAdminUrl, NULL,
                                   NULL, $sWordpressCode, ($bAgb === true)?'yes':'no', ($bNewsletter === true)?'yes':'no', $sWordpressVersion ,$sPhpVersion, $sLanguage);
           
            if(isset($sResponseMessage['info'])):
                $sMessage = $sResponseMessage['info'];
                $this->showInfo($sMessage);
            elseif(isset($sResponseMessage['error'])):
                $sMessage = $sResponseMessage['error'];
                $this->showWarning($sMessage);
            endif;
            
        } catch (SoapFault $e) {
            $this->showWarning("error on receiving apikey. ".$e->getMessage());
            die();
        }
    }    
    
    //subscription-adminpage
    public function adminpage_epaper_subscription()
    {
        $this->showRegistrationInfo();
        
        try {
            $sLanguage = substr(get_bloginfo ( 'language' ), 0, 2);
            $sPPButton = $this->oAccountApi->getPPButtonCode(($sLanguage != NULL && $sLanguage != false)?$sLanguage:'en');
        }catch(SoapFault $e){
            $this->showWarning("error while connecting to account-api ".$e->getMessage());
            die();
        }

        if(is_object($sPPButton) && get_class($sPPButton) == 'WP_Error' && $this->bKeyRefreshed == false):
            $this->bKeyRefreshed = true;
            $this->oAccountApi->refreshKeys();
            $this->load_epaper_options();
            $this->adminpage_epaper_subscription();
            return false;
        endif;
        $this->oView->button_code = (is_string($sPPButton))?json_decode($sPPButton):array();
        $this->aTemplateVars = array('TITLE' => sprintf('%s - %s', $this->sDefaultTitle, __("Manage Your Subscription",'1000grad-epaper')));
        $this->showContent();             
    }     
    
    //settings-adminpage
    public function adminpage_epaper_settings()
    {   
        $this->showRegistrationInfo();

        $this->oView->feedback_sent = false;
        $this->aTemplateVars = array('TITLE' => sprintf('%s - %s',$this->sDefaultTitle, 'Settings'));
        
        global $tge_plugin_data;
        $tge_plugin_data = get_plugin_data(__FILE__);
            
        //save settings 
        if (isset($_GET['epaper-settings-save'])) $this->saveEpaperSettings(); 
        if (isset($_POST['feedback'])):
           $this->saveEpaperFeedback();
        endif;
        
        $this->sTemplate = "adminpage_epaper_settings";
        
        $this->showContent();          
    }
    
    //action of feedback-form
    private function saveEpaperFeedback(){
        $sText = $_POST['text'];
        
        global $wp_version;

        $sLanguage = __("en",'1000grad-epaper');
        $sWordpressVersion = $wp_version;
        $sPhpVersion = phpversion();
        $sPluginVersion = $this->getPluginVersion();
        $this->oView->feedback_sent = $this->oAccountApi->sendFeedback($this->aEpaperOptions['email'], $sText, NULL, admin_url(), NULL, 
            NULL,$sWordpressVersion, $sPhpVersion, $sLanguage, $sPluginVersion
        );  
    }
    
    //saving epaper-settings
    private function saveEpaperSettings(){
        $this->aEpaperOptions['url'] = htmlspecialchars($_GET['epaper_url']);
        $this->aEpaperOptions['wordpressapi'] = htmlspecialchars($_GET['epaper_wordpressapi']);
        $this->aEpaperOptions['apikey'] = htmlspecialchars($_GET['epaper_apikey']); 
        $this->aEpaperOptions['apikey_as'] = htmlspecialchars($_GET['apikey_as']); 
        update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);  
    }
    
    //returns object of user-channels
    public function getChannels($bUpdate = NULL){
        if(isset($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]) && $bUpdate == false) return json_decode($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]);
        $oChannels = $this->getChannelConfigObject();
        $this->aEpaperOptions[$this->sEpaperOptionsChannelConfig] = json_encode($oChannels);
        update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        return $oChannels;
    }
    
    //returns info-object of user-channel
    public function getChannelInfos($iChannelId = NULL, $bForceRefresh = false){
        if($iChannelId == NULL) return false;
        
        if($bForceRefresh):
            $oChannel = json_decode($this->oChannelApi->getChannelInfo($this->aEpaperOptions['apikey'], $iChannelId));
            return $oChannel;
        else:
            $oChannelConfig = $this->getChannelConfigObject()->channels;
            foreach($oChannelConfig as $iIndex => $oChannel):
                if($oChannel->id == $iChannelId) return $oChannel;
            endforeach;
        endif;
        
        return false;
    }
    
    //returns info-object of user-epaper
    public function getEpaperInfos($iEpaperId = NULL, $bForceRefresh = false){
        if($iEpaperId == NULL) return false;
        
        if($bForceRefresh):
            $oEpaper = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'], $iEpaperId));
            return $oEpaper;
        else:
            $oChannelConfig = $this->getChannelConfigObject()->channels;
            foreach($oChannelConfig as $iIndex => $oChannel):
                if($oChannel->epaperInfo->id == $iEpaperId) return $oChannel->epaperInfo;
            endforeach;
        endif;
        
        return false;        
    }
    
    //channel-list
    public function adminpage_epaper_channels(){
        if (isset($_POST['agb'])):
            $this->aEpaperOptions[$this->sAgbAcceptIndex] = true;
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        endif;
            
        $this->showRegistrationInfo();
        $this->clearAllChannelPreviewImages();
        $this->oView->bAgbWasAccepted = $this->agbWasAccepted();
        $this->oView->sAdminUrl = get_admin_url();
        if($this->bIsRegistered == true) $this->oView->channelobject = $this->getChannelConfigObject(true);
        $this->aTemplateVars = array('TITLE' => $this->sDefaultTitle);
        $this->bUseMainTemplate = true;
        $this->sTemplate = 'adminpage_epaper_channels';
        $this->showContent();
    }
    
    //backend warning-box
    public function showWarning($sMessage){
        $this->aTemplateVars = array('MESSAGE' => $sMessage);
        $this->bUseMainTemplate = false;
        $this->sTemplate = 'adminpage_epaper_warning';
        $this->showContent();
    }
    
    //backend info-box
    public function showInfo($sMessage){
        $this->aTemplateVars = array('MESSAGE' => $sMessage);
        $this->bUseMainTemplate = false;
        $this->sTemplate = 'adminpage_epaper_info';
        $this->showContent();        
    }    
    
    //metabox
    public function meta_box_epaper()
    {
        $this->oView->channelobject = $this->getChannels();
        $this->bUseMainTemplate = false;
        $this->showContent();    
    }  
    
    //returns default-link of empty channel
    public function getEpaperDefaultLink(){
        return isset($this->aEpaperOptions[$this->sEpaperOptionsChannelDefaultUrl]->url)?$this->aEpaperOptions[$this->sEpaperOptionsChannelDefaultUrl]->url:$this->defaultFallback;
    }
    
    //returns link to epaper
    public function getEpaperLink($channel = NULL, $epaper = NULL, $aConfig = array()){
        //$epaper can be integer or object (epaper_id | oEpaper)
        //$channel can be integer or object (channel_id | oChannel
        //link == 0 => overlay, link == 1 => extern
        $iDefault = 0;
        if($channel == NULL) return false;
        
        $oChannelInfo = is_object($channel)?$channel:$this->getChannelInfos($channel);
        $epaper = ($epaper == NULL)?$oChannelInfo->id_epaper:$epaper;
        $oEpaperInfo = is_object($epaper)?$epaper:$this->getEpaperInfos($epaper);
        
        if(isset($oEpaperInfo->settings->add_export_info)):
            $oLinksettings = json_decode($oEpaperInfo->settings->add_export_info);
            $iLinkType = isset($oLinksettings->linktype)?$oLinksettings->linktype:$iDefault;
        else:
            $iLinkType = 0;
        endif;
        
        if($oEpaperInfo == NULL){
            $sEpaperLink = $this->getEpaperDefaultLink();
            $sClass = 'class="ePaper"';
            $sImageSrc = sprintf('%s%s',$this->getEpaperDefaultLink(), $this->sDefaultPreviewImage);
        }else{
            $iPage = isset($aConfig['page'])?$aConfig['page']:NULL;
            $sEpaperLink = sprintf('%s%s', $oChannelInfo->url, ($iPage != NULL)?sprintf('#%u', $iPage):NULL);
            $sClass = ($iLinkType == 0)?'class="ePaper"':NULL;
            $sImageSrc = sprintf('%s%s', $oChannelInfo->url, $this->sDefaultPreviewImage);
        }

        $aImageSrc = $this->getChannelPreviewImage($oChannelInfo->id, $sImageSrc);
        $sParameter = is_user_logged_in()?sprintf('%s%u',"?rnd=",rand(1000,9999)):NULL;
        
        return sprintf('<a href="%s" %s target="_blank">
            <img class="tg_preview_image" src="%s%s"/>    
        </a>', $sEpaperLink, $sClass, $aImageSrc['url'], $sParameter);

    }
    
    //returns path|url of preview-image
    public function getChannelPreviewImage($iChannelId = NULL, $sImageSrc = NULL){
        if($iChannelId == NULL) return false;
        $aUploadUrl = wp_upload_dir();
        $sFilename = sprintf('epaper_preview_%u.gif', $iChannelId);
        $sFilePath = sprintf("%s/%s", $aUploadUrl['basedir'], $sFilename);
        $sFileUrl = sprintf("%s/%s", $aUploadUrl['baseurl'], $sFilename);
        
        if($sImageSrc == NULL):
            $sImageSrc = sprintf('%s%s',$this->getChannelInfos($iChannelId)->url, $this->sDefaultPreviewImage);
        endif;

        if(!file_exists($sFilePath) || (file_exists($sFilePath) && filesize($sFilePath) === 0)):
                $sImage = @file_get_contents($sImageSrc);
                $bFileExist = @file_put_contents($sFilePath, $sImage);
                if($bFileExist === false || (file_exists($sFilePath) && filesize($sFilePath) === 0)):
                    $sFileUrl = $sImageSrc;                    
                endif;
        endif;
        
        return array('path' => $sFilePath, 'url' => $sFileUrl);
    }

    //clears cached preview-image of specified channel
    public function clearChannelPreviewImage($iChannelId){
        $aFile = $this->getChannelPreviewImage($iChannelId);
        if(file_exists($aFile['path'])) unlink($aFile['path']);
    }
    
    //clears cached preview-image of all channels
    public function clearAllChannelPreviewImages(){
        if($this->bIsRegistered == true):
            foreach($this->getChannelConfigObject()->channels as $oChannel):
                $this->clearChannelPreviewImage($oChannel->id);
            endforeach;
        endif;
    }
    
    //ajax action
    function fetchAjaxRequest() {
        $this->refreshChannelConfigObject();
        
        $sActionOption = isset($_POST['ajax_option'])?$_POST['ajax_option']:NULL;
        if($sActionOption == NULL && isset($_GET['ajax_option'])) $sActionOption = $_GET['ajax_option'];

        switch($sActionOption):
            case 'startRendering':
                  $iPdfId = $_POST['pdfId'];
                  $sOldEpaperId = $_POST['oldEpaperId'];
                  $iChannelId = $_POST['channel_id'];

                  $sFilename = isset($_POST['filename'])?$_POST['filename']:'edelpaper';
                  $sDocumentName = str_replace(".pdf", "", $sFilename);
                  
                  ob_start();
                    if($sOldEpaperId != ''):
                        try {
                              $this->oChannelApi->removeEpaperFromChannel($this->aEpaperOptions['apikey'], $iChannelId);
                              $this->oEpaperApi->epaperDelete($this->aEpaperOptions['apikey'], $sOldEpaperId);
                          } catch (SoapFault $e) {
                              //
                          }
                    endif;
                  ob_end_clean();
                  
                  $iNewEpaperId = $this->oEpaperApi->epaperCreateFromPdf($this->aEpaperOptions['apikey'], $iPdfId);
                  
                  $aExtraInfo = array('linktype' => 0);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, "is_pdf_download", 0);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, "pdf_name", $sDocumentName);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, "title", $sDocumentName);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, 'add_export_info', json_encode($aExtraInfo));
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, 'language', $this->getEpaperDefaultLanguage());
                  foreach($this->getChannels()->channels as $iChannel => $aChannelConfig):
                      if($aChannelConfig->id == $iChannelId):
                            $this->oChannelApi->setChannelTitle($this->aEpaperOptions['apikey'], $iChannelId, sprintf('edelpaper Channel #%u', ($iChannel+1)));
                      endif;
                  endforeach;
                  
                  $this->oEpaperApi->epaperStartRenderprocess($this->aEpaperOptions['apikey'], $iNewEpaperId);
                  echo $iNewEpaperId;
                break;
            //renderstatus
            case 'renderstatus':
                    $sMethod = isset($_POST['epaperId'])?'$_POST':'$_GET';
                    $iEpaperId = ($sMethod == '$_GET')?$_GET['epaperId']:$_POST['epaperId'];
                    
                    switch($sMethod):
                        case '$_POST':
                            $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                            $sJson = json_encode(array(
                                'render_percent' => $oInfos->renderprocess->percent,
                                'render_pages_text' => sprintf('(%s %u/%u)', __('page','1000grad-epaper'), $oInfos->renderprocess->current_page, $oInfos->pages)
                                ));
                            
                            if($oInfos->pages == 0) $sJson = json_encode(array('error' => __('Error while rendering PDF.', '1000grad-epaper')));
                            
                            echo $sJson;
                            break;
                        
                        case '$_GET':
                            $bRenderReady = false;
                            while($bRenderReady == false):
                                $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                                $sJson = json_encode(array(
                                    'render_percent' => $oInfos->renderprocess->percent,
                                    'render_pages_text' => sprintf('(%s %u/%u)', __('page','1000grad-epaper'), $oInfos->renderprocess->current_page, $oInfos->pages)
                                ));
                                
                                if($oInfos->pages == 0) $sJson = json_encode(array('error' => __('Error while rendering PDF.', '1000grad-epaper')));
                                
                                header('Content-Type: text/event-stream');
                                header('Cache-Control: no-cache');
                                echo "data: ".$sJson;
                                echo "\n\n";
                                ob_end_flush();
                                flush();
                                sleep(1);
                                $bRenderReady = ($oInfos->renderprocess->percent == 100)?true:false;
                            endwhile;
                            
                            break;
                        
                    endswitch;
                    
                break;
            //publishstatus
            case 'publishstatus':
                    $sMethod = isset($_POST['epaperId'])?'$_POST':'$_GET';
                    $iEpaperId = ($sMethod == '$_GET')?$_GET['epaperId']:$_POST['epaperId'];
                    $iChannelId = ($sMethod == '$_GET')?$_GET['channelId']:$_POST['channelId'];
                    $sOutput = NULL;
                    
                    switch($sMethod):
                        case '$_POST':
                            $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                            $oChannelInfo = $this->getChannelInfos($iChannelId);
                            if($oInfos->published == 0 && $oInfos->status == 'ready' && $oChannelInfo->id_epaper == ''):
                                $this->oChannelApi->publishEpaperToChannel($this->aEpaperOptions['apikey'],$iEpaperId, $iChannelId);
                                $sOutput = 0;
                            elseif( ($oInfos->published == 0 && $oChannelInfo->status != '' && $oInfos->status == 'do_publish_to_channel')):
                                $sOutput = 50;
                            elseif( ($oInfos->published == 0 && $oChannelInfo->status != '' && $oInfos->status == 'do_publish')):
                                $sOutput = 60;
                            elseif($oInfos->status == 'ready' && $oChannelInfo->status == '' && $oChannelInfo->id_epaper != ''):
                                $sOutput = 100; 
                            elseif($oChannelInfo->status == '' && $oChannelInfo->id_epaper != ''):
                                $sOutput = 70; 
                            elseif($oChannelInfo->status == ''):
                                $sOutput = 80; 
                            elseif($oChannelInfo->id_epaper != ''):
                                $sOutput = 90; 
                            elseif('y' == 'y'):
                                $sOutput = 95; 
                            endif;
                            echo $sOutput;
                            
                            break;
                        
                        case '$_GET':
                            $bPublishReady = false;
                            while($bPublishReady == false):
                                $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                                $oChannelInfo = $this->getChannelInfos($iChannelId);

                                if($oInfos->published == 0 && $oInfos->status == 'ready' && $oChannelInfo->id_epaper == ''):
                                    $this->oChannelApi->publishEpaperToChannel($this->aEpaperOptions['apikey'],$iEpaperId, $iChannelId);
                                    $sOutput = 0;
                                elseif( ($oInfos->published == 0 && $oChannelInfo->status != '' && $oInfos->status == 'do_publish_to_channel') || $oInfos->published == 1):
                                    $sOutput = 50;
                                elseif($oChannelInfo->status == ''):
                                    $sOutput = 100; 
                                    $bPublishReady = true;
                                endif;
                                
                                header('Content-Type: text/event-stream');
                                header('Cache-Control: no-cache');
                                echo "data: ".$sOutput;
                                echo "\n\n";
                                flush();
                                
                                sleep(1);
                                
                            endwhile;
                            
                            break;
                    endswitch;
                    
                break;
            //reload channellist
            case 'loadChannelList':
                    $this->adminpage_epaper_channels();
                break;
            //tinymce
            case 'tg_tiny_mce_button':
                    $this->oView->channelobject = $this->getChannels();
                    $this->bUseMainTemplate = false;
                    $this->sTemplate = 'tiny_mce_box';
                    $this->showContent();
                break;
            //clear Channel
            case 'clearChannel':
                    $iChannelId = isset($_POST['channel'])?$_POST['channel']:NULL;
                    if($iChannelId != NULL):
                        $oChannelInfo = $this->getChannelInfos($iChannelId);
                         try {
                            $this->oChannelApi->removeEpaperFromChannel($this->aEpaperOptions['apikey'], $iChannelId);
                            $this->oEpaperApi->epaperDelete($this->aEpaperOptions['apikey'], $oChannelInfo->id_epaper);
                        } catch (SoapFault $e) {
                            $this->showWarning("error while clearing channel. ".$e->getMessage());
                        }
                    endif;
                    $this->adminpage_epaper_channels();
                break;
            //save epaper-settings    
            case 'setEpaperSettings':
                    $iEpaperId = isset($_POST['epaper_id'])?$_POST['epaper_id']:NULL;
                    $iChannelId = isset($_POST['channel_id'])?$_POST['channel_id']:NULL;
                    $bPublish = (isset($_POST['do_publish']) && $_POST['do_publish'] == 'false')?false:true;
                    $aExtraInfos = array();
                    $aEpaperSettings = $this->getEpaperSettings();
                    if($iEpaperId == NULL || $iChannelId == NULL) return false;
                    
                    foreach($_POST['data'] as $iIndex => $aConfig):
                        $aFieldSettings = (isset($aEpaperSettings[$aConfig['name']]))?$aEpaperSettings[$aConfig['name']]:NULL;
                        switch($aFieldSettings['save_option']):
                            case 'extra_infos':
                                    $aExtraInfos[$aConfig['name']] = $this->escapeString($aConfig['value']);
                                break;
                            case 'channel_title':
                                    $sTitle = $aConfig['value'];
                                    if($iChannelId != NULL) $this->oChannelApi->setChannelTitle($this->aEpaperOptions['apikey'], $iChannelId, $this->escapeString($sTitle));
                                break;
                            default:
                                $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iEpaperId, $aConfig['name'], $this->escapeString($aConfig['value']));
                                break;
                        endswitch;
                    endforeach;
                    
                    if(count($aExtraInfos) > 0):
                        $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iEpaperId, 'add_export_info', json_encode($aExtraInfos));
                    endif;

                    if($bPublish) $this->oChannelApi->publishEpaperToChannel($this->aEpaperOptions['apikey'],$iEpaperId, $iChannelId);
                break;
            //reset plugin    
            case 'deleteAccount':
                    delete_option($this->sWidgetClassIndex);
                    delete_option($this->sEpaperOptionIndex);
                break;
            
            case 'cancelSubscr':
                    $sSubscrId = isset($_POST['subscr_id'])?$_POST['subscr_id']:NULL;
                    echo $this->oAccountApi->paypalUnsubscribe($sSubscrId);
                break;
            
            case 'translateUploadErrorMessage':
                    /*
                    2100 	Pdf konnte nicht analysiert werden
                    2101 	Pdf ist kein valides PDF Dokument
                    2102 	Pdf enthält keine Seiten
                    2103 	Pdf ist verschlüsselt und kann nicht verarbeitet werden
                    2104 	Pdf enthält mehr Seiten als erlaubt 
                    */
                    $sErrorCode = isset($_POST['errorCode'])?$_POST['errorCode']:NULL;
                    switch($sErrorCode):
                        case '2100':
                            _e('Upload-Error: Pdf could not be analyzed', '1000grad-epaper');
                        break;

                        case '2101':
                            _e('Upload-Error: Pdf is not a valid PDF document', '1000grad-epaper');
                            break;

                        case '2102':
                            _e('Upload-Error: Pdf contains no content', '1000grad-epaper');
                            break;

                        case '2103':
                            _e('Upload-Error: Pdf is encrypted and can not be processed', '1000grad-epaper');
                            break;

                        case '2104':
                            _e('Upload-Error: Pdf contains more pages than allowed', '1000grad-epaper');
                            break;
                        default:
                            _e('Upload-Error: undefined', '1000grad-epaper');
                            break;
                    endswitch;
                
                break;
             case 'acceptAgb':
                    $this->aEpaperOptions[$this->sAgbAcceptIndex] = true;
                    update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
                 break;
            
             default:
                die('Ajax-Action not found!');
                break;
            
        endswitch;
        
        die();
    }
    
    //available epaper-settings
    public function getEpaperSettings(){
        return array(
            'dependency' => array('pdf_name' => array('is_pdf_download' => 1), 'title' => array('linktype' => 1)),
            'channel_title' => array('publish' => 0, 'save_option' => 'channel_title', 'type' => 'input', 'translation' => __('Channel-title','1000grad-epaper'), 'helptext' => __('internal channel name (for administration)', '1000grad-epaper')),
            'linktype' => array('publish' => 0, 'save_option' => 'extra_infos', 'type' => 'select', 'translation' => __('Link-type','1000grad-epaper'), 'helptext' => __('open the document in an overlayer box or in a new window/tab', '1000grad-epaper'), 'values' => array(0 => __('overlay', '1000grad-epaper'), 1 => __('extern', '1000grad-epaper')) ),
            'title' =>  array('publish' => 1 ,'save_option' => 'epaper_config', 'type' => 'input', 'translation' => __('Tab-Title','1000grad-epaper'), 'helptext' => __('title of the browser-tab, when opening the document','1000grad-epaper')),
            'is_pdf_download' => array('publish' => 1, 'save_option' => 'epaper_config', 'type' => 'select', 'translation' => __('PDF Download','1000grad-epaper'), 'helptext' => __('allow user to download this document as PDF (a download link is shown inside the ePaper)','1000grad-epaper'),'values' => array(0 => __('no','1000grad-epaper'), 1 => __('yes','1000grad-epaper'))),
            'pdf_name' => array('publish' => 1, 'save_option' => 'epaper_config', 'type' => 'input', 'translation' => __('PDF Filename','1000grad-epaper'), 'helptext' => __('filename of the pdf, when downloading','1000grad-epaper')),
            'language'  => array('publish' => 1, 'save_option' => 'epaper_config', 'type' => 'select','translation' => __('edelpaper Language','1000grad-epaper'), 'helptext' => __('set the language of the edelpaper-navigation','1000grad-epaper'), 'values' => $this->getAvailableLanguages()));
    }
    
    //returns epaper-settings form
    public function getEpaperSettingsForm($oEpaperInfos = NULL, $oChannelInfos = NULL){
        $oEpaperInfos = isset($oEpaperInfos->settings)?$oEpaperInfos->settings:NULL;
        $aExtraInfos = json_decode($oEpaperInfos->add_export_info);
        $aEpaperSettings = $this->getEpaperSettings();
        
        $aDependency = isset($aEpaperSettings['dependency'])?$aEpaperSettings['dependency']:array();
        unset($aEpaperSettings['dependency']);
        
        foreach($aEpaperSettings as $sIndex => $aConfig):
            switch($aConfig['save_option']):
                case 'channel_title':
                        $aEpaperSettings[$sIndex]['default'] = isset($oChannelInfos->title)?$oChannelInfos->title:NULL;
                    break;
                
                case 'extra_infos':
                        $aEpaperSettings[$sIndex]['default'] = isset($aExtraInfos->{$sIndex})?$aExtraInfos->{$sIndex}:NULL;
                    break;
                
                case 'epaper_config':
                        $aEpaperSettings[$sIndex]['default'] = isset($oEpaperInfos->{$sIndex})?$oEpaperInfos->{$sIndex}:NULL;
                    break;
            endswitch;
        endforeach;
        
        ob_start();
        include sprintf('%s%s.php',$this->sTemplatePath,$this->ePaperSettingsFormTemplate);
        $sEpaperSettingsForm = ob_get_contents();
        ob_end_clean();
        
        return $sEpaperSettingsForm;
    }
    
    //content function
    public function showContent(){
        
        $aCallers = debug_backtrace();
        $sTemplate = ($this->sTemplate == NULL && isset($aCallers[1]['function']))?$aCallers[1]['function']:$this->sTemplate;

        ob_start();
        include sprintf('%s%s.php', $this->sTemplatePath, $this->sMainTemplate);
        $sMainTemplate = ob_get_contents();
        ob_end_clean();
        
        ob_start();
        include sprintf('%s%s.php',$this->sTemplatePath,$sTemplate);
        $sContent = ob_get_contents();
        ob_end_clean();
        
        if($this->bUseMainTemplate == true):
            $sContent = str_replace("%CONTENT%", $sContent, $sMainTemplate);
        endif;

        if(count($this->aTemplateVars) > 0):
            foreach($this->aTemplateVars as $sVar => $sValue):
                $sContent = str_replace("%".$sVar."%", $sValue, $sContent);
            endforeach;
        endif;        
        
        echo $sContent;
    }
    
    //returns available languages of epaper-player
    public function getAvailableLanguages(){
        $sCmsLanguage = substr(get_bloginfo ( 'language' ), 0, 2);
        $aPlayerLanguages = $this->oEpaperApi->getEpaperPlayerLanguages(($sCmsLanguage == 'de')?'de':'en');
        $aPlayerVersion = array_keys($aPlayerLanguages);
        $aLanguageArray = (array)$aPlayerLanguages[$aPlayerVersion[0]];
        foreach($aLanguageArray as $sLangKey => $sLanguage):
            $aLanguages[strtolower($sLangKey)] = strtolower($sLanguage);
        endforeach;
        return $aLanguages;
    }
    
    //returns blog-language
    private function getBlogDefaultLanguage(){
        $sLangCode = get_bloginfo('language'); //en_EN
        $aLangCode = explode("-", $sLangCode);
        return isset($aLangCode[0])?$aLangCode[0]:false;
    }
    
    //returns true if channel exists
    public function channelExists($iChannelId){
        $oChannelInfo = json_decode($this->oChannelApi->getChannelInfo($this->aEpaperOptions['apikey'], $iChannelId));
        return !empty($oChannelInfo);
    }
    
    //returns channel-object of user-account
    public function getChannelConfigObject($bForceRefresh = false){
        
        if(!isset($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]) || $bForceRefresh):
            
            $oChannels = json_decode($this->oChannelApi->getChannelsList($this->aEpaperOptions['apikey']));
            foreach($oChannels->channels as $iIndex => $oChannel):
                $oEpaperInfo = $this->getEpaperInfos($oChannel->id_epaper, true);
                $oChannels->channels[$iIndex]->epaperInfo = $oEpaperInfo;
            endforeach;
        
            $this->aEpaperOptions[$this->sEpaperOptionsChannelConfig] = json_encode($oChannels);
            
            $this->aEpaperOptions[$this->sEpaperOptionsChannelDefaultUrl] = $this->oAccountApi->getDefaultEpaperUrl();
            
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
            
        else:
            return json_decode($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]);
        endif;
        
        return $oChannels;
    }
    
    //refresh cached channel-object of user-account
    private function refreshChannelConfigObject(){
        $this->getChannelConfigObject(true);
        return true;
    }
    
    //escape input-strings
    public function escapeString($sStr){
        return htmlentities(stripslashes($sStr),ENT_QUOTES);
    }
    
    private function agbWasAccepted(){
        return (isset($this->aEpaperOptions[$this->sAgbAcceptIndex]))?true:false;
    }
    
    public function getEpaperDefaultLanguage(){
        return array_key_exists($this->sDefaultLang, $this->getAvailableLanguages())?$this->sDefaultLang:$this->sLanguageFallback;
    }
    
}

//sidebar-widget
class EpaperWidgetClass extends WP_Widget {
    
    private $oEpaper = NULL;
    private $sEpaperOptionIndex = 'plugin_epaper_options';
        
    function EpaperWidgetClass() {
        parent::WP_Widget(false, $name = 'edelpaper', array(
            'description' => __('display a edelpaper','1000grad-epaper')
        ));
        
        $this->aEpaperOptions = get_option($this->sEpaperOptionIndex);        
        $this->oEpaper = new TG_Epaper_WP_Plugin(false);

    }
    
    //widget in frontend
    function widget($aArgs, $aSettings ) {
        if($this->oEpaper->is_registered() == true):
            $sTitle = apply_filters('widget_title', $aSettings['title'] );
            $iChannelId = $aSettings['channel_id'];
            $iPage = $aSettings['first_page'];
            $sLink = $this->oEpaper->getEpaperLink($iChannelId, NULL, array('page' => $iPage));
            
            $oParams = new stdClass();
            $oParams->title = $sTitle;
            $oParams->link = $sLink;
            $oParams->before_widget = $aArgs['before_widget'];
            $oParams->before_title = $aArgs['before_title'];
            $oParams->after_title = $aArgs['after_title'];
            $oParams->after_widget = $aArgs['after_widget'];
            
            $this->oEpaper->set('oView', $oParams);
            $this->oEpaper->set('bUseMainTemplate', false);
            $this->oEpaper->set('sTemplate', 'epaper_widget_frontend');
            $this->oEpaper->showContent();
        endif;
    }
    
    //save widget
    function update($aNewSettings, $aOldSettings) {
        $aSettings = $aOldSettings;
        $aSettings['title'] = $aNewSettings['title'];
        $aSettings['channel_id'] = $aNewSettings['channel_id'];
        $aSettings['first_page'] = $aNewSettings['first_page'.$aNewSettings['channel_id']];
        $oChannel = $this->oEpaper->getChannelInfos($aNewSettings['channel_id']);
        $aSettings['channel_url'] = $oChannel->url;
        return $aSettings;
    }
    
    //widget in backend
    function form($aSettings) {
        if($this->oEpaper->is_registered() == true):
            $oParams = new stdClass();
            $oParams->channels = $this->oEpaper->getChannels()->channels;
            $oParams->widget = $this;
            $oParams->widget_instance = $aSettings;
            
            if(isset($oParams->widget_instance['channel_id']) && !empty($oParams->widget_instance['channel_id'])):
                $bExists = $this->oEpaper->channelExists($oParams->widget_instance['channel_id']);
                if($bExists === false):
                    $oParams->widget_instance['channel_id'] = NULL;
                    $oParams->widget_instance['first_page'] = 1;
                endif;
            endif;
            
            $aDefaults = array( 'title' => 'edelpaper' );
            $aSettings = wp_parse_args( (array) $aSettings, $aDefaults ); 

            $this->oEpaper->set('oView', $oParams);
            $this->oEpaper->set('bUseMainTemplate', false);
            $this->oEpaper->set('sTemplate', 'epaper_widget_backend');
            
            $this->oEpaper->showContent();
        endif;
    }
}
        
//initialize plugin
new TG_Epaper_WP_Plugin();