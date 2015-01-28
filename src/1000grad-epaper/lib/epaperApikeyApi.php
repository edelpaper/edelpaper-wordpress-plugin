<?php
/**
 * Class wraps functions for communiction with the 1000grad Account API
 * @copyright (c) 2013, 1000grad DIGITAL Leipzig GmbH
 * @author Karsten Lemme <karsten.lemme@1000grad.de>
 */
class EpaperApikeyApi 
{
    private $apikeyApiClient;
    private $ApikeyApiWsdl;
    private $apikey;
    private $epaperOptions;
    private $isRegistered;
    
    public function __construct() 
    {
        $this->epaperOptions = get_option("plugin_epaper_options");
        $this->apikey = isset($this->epaperOptions['apikey'])?$this->epaperOptions['apikey']:NULL;
        $this->apikey_as = isset($this->epaperOptions['apikey_as'])?$this->epaperOptions['apikey_as']:NULL;
        $this->ApikeyApiWsdl = $this->epaperOptions['wordpressapi'];
        $this->epaperApikeyApiConnect();
        $this->checkVersion();
        $this->_isRegistered();

    }
    
    public function refreshKeys(){
        $this->checkVersion(true);
    }

    private function checkVersion($bForceRefresh = false){
        if( (isset($this->epaperOptions['email']) 
                && !isset($this->epaperOptions['apikey_as'])
                && (isset($this->epaperOptions['apikey']) && !empty($this->epaperOptions['apikey']) )
                ) || $bForceRefresh == true):

            if($this->ApikeyApiWsdl != TGE_PLUGIN_ACCOUNT_API_URI){
                $this->epaperOptions['wordpressapi'] = TGE_PLUGIN_ACCOUNT_API_URI;
                $this->ApikeyApiWsdl = $this->epaperOptions['wordpressapi'];
            }

            try {
                $sJson = $this->apikeyApiClient->refreshAccApiKeyByCmsApiAndEmail(
                        $this->epaperOptions['apikey'], 
                        $this->epaperOptions['email'],
                        get_bloginfo('language'),
                        get_bloginfo('url'),
                        get_bloginfo('version'));
                //return $sApiKey;            
            } catch (SoapFault $e) {
                _e("Error with Apikey API.",'1000grad-epaper');
                echo $e->getMessage(); 
            }

            $aData = json_decode($sJson);
            if(isset($aData->apikey_as)):
                $this->epaperOptions['apikey_as'] = $aData->apikey_as;
                update_option("plugin_epaper_options", $this->epaperOptions);
            endif;
           
        endif;
    }

    /**
     *  Shall validate if plugin is registered
     */
    private function _isRegistered ()
    {
        if (isset($this->apikey) && ($this->apikey != "")) {
            $this->isRegistered = true;
        } else {
            $this->isRegistered = false;
        }        
    }
    
     /**
     * Connect
     */
    public function epaperApikeyApiConnect() 
    {
        $wsdl = $this->ApikeyApiWsdl;
        try {
            $this->apikeyApiClient = new SoapClient($wsdl , array());
            return true;            
        } catch (SoapFault $e) {
            _e("Error: Could not connect to API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Versionsabfrage
     */
    public function getApikeyApiVersion() 
    {  
        try {
            $version = $this->apikeyApiClient->getVersion();
            return $version;            
        } catch (SoapFault $e) {
            _e("Error with Apikey API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Funktionsabfrage
     */
    public function getApikeyApiFunctions() 
    {  
        try {
            $functions = $this->apikeyApiClient->__getFunctions();
            return $functions;            
        } catch (SoapFault $e) {
            _e("Error with Apikey API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Registrierungsprozess schickt Daten an den ePaper Server, der verschickt dann Bestaetigungsmail
     */
    public function getRegistrationCodeByEmail ($email, $text, $wordpress, $phpupload,
                    $phptime, $wordpresscode, $agb, $newsletter, $version_wordpress ,$version_php, $language)
    {
        $sMessage = NULL;
        //debug test
        //var_export($this->apikeyApiClient->__getFunctions()); die("zzz");
        //var_export($this->apikeyApiClient->getRegistrationCodeByEmail($email, $text, $wordpress, $phpupload, $phptime, $wordpresscode, $agb, $newsletter, $version_wordpress ,$version_php, $language)); die("zzz");
        
        try {
            $res = $this->apikeyApiClient->getRegistrationCodeByEmail($email, $text, $wordpress, $phpupload,
                                   $phptime, $wordpresscode, $agb, $newsletter, $version_wordpress ,$version_php, $language);
        } catch (SoapFault $e) {
            $sMessage = '<br /><b>Error '.$e->getMessage().'</b>'; 
            if ($e->getMessage()=="(605) no valid email adress") 
                $sMessage.= __("<br />Email adress is not valid.",'1000grad-epaper');       
            if ($e->getMessage()=="(606) email already exists") 
                $sMessage.= __("<br />Email adress is already registered.",'1000grad-epaper');       
            
            $sMessage.= __("<br /><b>Your Registration was not successful! Please try again.</b>",'1000grad-epaper');             
            return array('error' => $sMessage);        
        }
        $sMessage.= __("<br /><b>Please have a look into your email inbox for confirmation code!</b>",'1000grad-epaper');
        return array('info' => $sMessage);            

    }
    
     /**
     * Abschluss des Registrierungsprozesses, Code wird eingegeben und APikey kommt
     */
    public function sendCodeGetApikey($email, $code) 
    {
        try {
            $res = $this->apikeyApiClient->sendCodeGetApikey($email, $code);
            return $res;            
        } catch (SoapFault $e) {
            echo "<br />";        
            _e("Error with API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Feedback Formular
     */
    public function sendFeedback($email = NULL, $text = NULL, $more = NULL, $adminUrl = NULL, $phpupload = NULL, $phptime = NULL, $wpVersion = NULL, $phpVersion = NULL, $language = NULL, $plugin_version = NULL)
    {
        try {
            $res = $this->apikeyApiClient->sendFeedback($email, $text, $more, $adminUrl, $phpupload, $phptime, 
                                                        $wpVersion, $phpVersion, $language, $plugin_version);
            return $res;            
        } catch (SoapFault $e) {
            echo "<br />";        
            _e("Error with API.",'1000grad-epaper');
            echo "<br />";        
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Test Funktion zum Addieren weiterer Kanaele
     */
    public function sendCodeGetMoreChannels($email, $code) 
    {
        try {
            $res = $this->apikeyApiClient->sendCodeGetMoreChannels($email, $code);
            return $res;            
        } catch (SoapFault $e) {
//            echo '<div class="update-nag">';
            echo "<br />";        
            _e("Error with API.",'1000grad-epaper');
            echo "<br />";        
            $msg = $e->getMessage();
            echo $msg;        
            echo "<br />";        
//            echo "</div>";
            return $msg;
        }
    } 
    
    /**
     * Subscription Formular holt Account-Info-HTML
     */
    public function getPPAccountInfo ($pp_button_uri, $pp_seller_email)
    { 
        try {
            $res = $this->apikeyApiClient->getPPAccountInfo ($this->_get_user_apikey_as(), $pp_button_uri, $pp_seller_email);
            return $res;  
        } catch (SoapFault $e) {
            return  new WP_Error('ePaper read fault (3)',$e->getMessage()); 
        }
    }
    
    /**
     * Subscription Formular holt Button-HTML und zugehöriges JS
     */
    public function getPPButtonCode ($sLanguage)
    {
        $epaper_options = get_option("plugin_epaper_options");
        if(!$epaper_options["email"])
            return  new WP_Error("Email adress missed.");
        
        $apikey_as=$this->_get_user_apikey_as();
        
        if ( is_wp_error($apikey_as)):
            return ($apikey_as);
        endif;
        //die($apikey_as);
 
        try {            
            $res = $this->apikeyApiClient->getPPButtonCode ($apikey_as , $sLanguage);
            return $res;  
        } catch (SoapFault $e) {
            return  new WP_Error('ePaper read fault (4)',$e->getMessage()); 
        }
    } 
    
    public function updatePluginInfos(){
        try{
            global $wp_version;
            $sWordpressVersion = $wp_version;
            $sPhpVersion = phpversion();
            $sAdminUrl = admin_url();
            $sPluginVersion = TG_Epaper_WP_Plugin::getPluginVersion();
            $sApiKeyAs = $this->_get_user_apikey_as();
            $sLanguage = substr(get_bloginfo ( 'language' ), 0, 2);
            $sCmsLanguage = ($sLanguage != NULL && $sLanguage != false)?$sLanguage:'en';
            $this->apikeyApiClient->updatePluginInfos($sApiKeyAs, $sPluginVersion, $sWordpressVersion, $sPhpVersion, $sAdminUrl, $sCmsLanguage);
            return true;
        }catch(SoapFault $e){
            return new WP_Error('Error on updating PluginInfos', $e->getMessage());
        }
    }
    
    protected function _get_user_apikey_as()
    {        
        $epaper_options = get_option("plugin_epaper_options");
        if(isset($epaper_options["apikey_as"]) && $epaper_options["apikey_as"]){
            return $epaper_options["apikey_as"];
        }
        else{
            if(isset($epaper_options["apikey"]) && $epaper_options["apikey"]){
                try {                    
                    $new_apikey_as = $this->apikeyApiClient->getMissedApikeyByEpaperapikey($epaper_options["apikey"], $epaper_options["email"]);
                    $new_apikey_as = json_decode($new_apikey_as);
                } catch (SoapFault $e) {
                    return  new WP_Error('User authentication fault.',$e->getMessage()); 
                }
                if ($new_apikey_as){
                    // Apikey wurde beim ePaper-Server angefragt und war dort zur emailadresse als bekannt gemeldet
                    $epaper_options["apikey_as"] = $new_apikey_as;
                    update_option("plugin_epaper_options", $epaper_options);                
                    return $new_apikey_as;
                }else
                {
                    return  new WP_Error('User authentication fault.','Sorry, account not found for your email or your epaper apikey at the account server.'); 
                }
            }
        }
    }
    
    public function getDefaultEpaperUrl(){
        try {
            $json = $this->apikeyApiClient->getDefaultEpaperUrl();
            return json_decode($json);  
        } catch (SoapFault $e) {
            return  new WP_Error('getDefaultEpaperUrl fault',$e->getMessage()); 
        }
        
    }
    
    public function paypalUnsubscribe($sSubscrId){
        try {
            return $this->apikeyApiClient->cancelSubscription($this->apikey_as, $sSubscrId);
        } catch (SoapFault $e) {
            
        }
    }
}

