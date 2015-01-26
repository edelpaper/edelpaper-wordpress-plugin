<?php
/**
 * Class wraps functions for communiction with the 1000grad channel API
 * @copyright (c) 2013, 1000grad DIGITAL Leipzig GmbH
 * @author Karsten Lemme <karsten.lemme@1000grad.de>
 */
class EpaperChannelApi 
{
    private $channelApiClient;
    
    private $apikey;
    private $epaperOptions;
    private $isRegistered;
    
    public function __construct() 
    {
        $this->epaperOptions = get_option("plugin_epaper_options");
        $this->apikey = isset($this->epaperOptions['apikey'])?$this->epaperOptions['apikey']:NULL;
        $this->_isRegistered();
        $this->epaperChannelApiConnect();
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
    public function epaperChannelApiConnect() 
    {      
        if(!isset($this->epaperOptions['url']))
            return false;
        if($this->epaperOptions['url'] == NULL) return false;
        $wsdl = $this->epaperOptions['url'] . "channels-wsdl/";
        try {
            $this->channelApiClient = new SoapClient($wsdl , array());
            return true;
		} catch (SoapFault $e) { 
            _e("Error: Could not connect to API.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    } 
    
     /**
     * Api Version
     */
    public function getChannelApiVersion ()
    {
        try {
            $version = $this->channelApiClient->getVersion();
            return $version;            
		} catch (SoapFault $e) {
            _e('Error with Channel API Handling, please register your plugin!','1000grad-epaper')
            . $e->getMessage(); 
            return false;         
        }
    }
    
     /**
     * Api Funktionen
     */
    public function getChannelApiFunctions() 
    {  
        try {
            $functions = $this->channelApiClient->__getFunctions();
            return $functions;            
		} catch (SoapFault $e) {
            _e('Error with Channel API Handling, please register your plugin!','1000grad-epaper') . $e->getMessage();
            return false;
        }
    }
    
     /**
     * Abfrage der Kanal Liste 
     */
    public function getChannelsList ($apikey) 
    {
        try {
            $list = $this->channelApiClient->channelsGetList($apikey);
            return $list;            
		} catch (SoapFault $e) {
            _e("Error with API Key Authentification.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }
    
     /**
     * ePaper Loeschen aus einem Kanal
     */
    public function removeEpaperFromChannel ($apikey, $id) 
    {
        try {
            $this->channelApiClient->channelsRemoveEpaperFromChannel($apikey,$id);
            return true;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error: could not remove edelpaper.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }
    
     /**
     * Publikation eines ePaper in einen Kanal
     */
    public function publishEpaperToChannel ($apikey, $epaperId, $id) 
    {
        try {
            $res = $this->channelApiClient->channelsPublishEpaperToChannel($apikey, $epaperId, $id);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error while Channelizing.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }
    
     /**
     * Kanal Infos
     */
    public function getChannelInfo($apikey, $channelId) 
    {
        try {
            $res = $this->channelApiClient->channelsGetChannelInfo($apikey, $channelId);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error with edelpaper Channel.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }  
    
    
     /**
     * Kanal Name
     */
    public function setChannelTitle($apikey, $iChannelId, $sTitle) 
    {
        try {
            $res = $this->channelApiClient->channelsSetChannelTitle($apikey, $iChannelId, $sTitle);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error with edelpaper Channel.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }    
    
    
    
    
    
    
}