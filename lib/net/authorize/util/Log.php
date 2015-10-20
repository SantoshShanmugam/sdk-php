<?php
namespace net\authorize\util;

use net\authorize\util\ANetSensitiveFields;

define ("ANET_LOG_FILES_APPEND",true);
define ("ANET_LOG_FILE","phplog");

define("ANET_LOG_DEBUG_PREFIX","DEBUG");
define("ANET_LOG_INFO_PREFIX","INFO");
define("ANET_LOG_WARN_PREFIX","WARN");
define("ANET_LOG_ERROR_PREFIX","ERROR");

//log levels
define('ANET_LOG_DEBUG',1);
define("ANET_LOG_INFO",2);
define("ANET_LOG_WARN",3);
define("ANET_LOG_ERROR",4);
//set level
define("ANET_LOG_LEVEL",ANET_LOG_DEBUG);

class Log
{
    private $sensitiveXmlTags = NULL;

    private function maskSensitiveXmlString($rawString){
        //Tag name is compulsory, can leave patterns and repalcements blank
//        $tags= array("cardCode","cardNumber","expirationDate");
//        $patterns=array("","([^0-9]*)(\d+)(\d{4})(.*)","");
        $patterns=array();
//        $replacements=array("","$1xxxx-$3$4","");
        $replacements=array();
        foreach ($this->sensitiveXmlTags as $i => $sensitiveTag){
            $tag = $sensitiveTag->tagName;
            $inputPattern = $sensitiveTag->pattern;
            $inputReplacement = "XXXX";

            if(!trim($inputPattern)) {
                $inputPattern = "(.+)"; //no need to mask null data
            }
            $pattern = "/<" . $tag . ">". $inputPattern ."<\/" . $tag . ">/";

            if(trim($sensitiveTag->replacement)) {
                $inputReplacement = $sensitiveTag->replacement;
            }
            $replacement = "<" . $tag . ">" . $inputReplacement . "</" . $tag . ">";

            $patterns [$i] = $pattern;
            $replacements[$i]  = $replacement;
        }
        $maskedString = preg_replace($patterns, $replacements, $rawString);
        return $maskedString;
    }

    private function getMasked($raw)
    {
        $messageType = gettype($raw);
        $message="";
        if ($messageType == "string") {
            $message = $this->maskSensitiveXmlString($raw);
        }
        return $message;
    }
    public function debug($logMessage, $flags=FILE_APPEND)
    {
        if(ANET_LOG_DEBUG >= ANET_LOG_LEVEL){
            $this->log(ANET_LOG_DEBUG_PREFIX, $logMessage,$flags);
        }
    }
    public function info($logMessage, $flags=FILE_APPEND){
        if(ANET_LOG_INFO >= ANET_LOG_LEVEL) {
            $this->log(ANET_LOG_INFO_PREFIX, $logMessage,$flags);
        }
    }
    public function error($logMessage, $flags=FILE_APPEND){
        if(ANET_LOG_ERROR >= ANET_LOG_LEVEL) {
            $this->log(ANET_LOG_ERROR_PREFIX, $logMessage,$flags);
        }
    }
    private function log($logLevelPrefix, $logMessage, $flags){
        //masking
        $logMessage = $this->getMasked($logMessage);

        //debug_backtrace
        $fileName = 'n/a';
        $methodName = 'n/a';
        $lineNumber = 'n/a';
        $debugTrace = debug_backtrace();
        if (isset($debugTrace[1])) {
            $fileName = $debugTrace[1]['file'] ? $debugTrace[1]['file'] : 'n/a';
            $lineNumber = $debugTrace[1]['line'] ? $debugTrace[1]['line'] : 'n/a';
        }
        if (isset($debugTrace[2])) $methodName = $debugTrace[2]['function'] ? $debugTrace[2]['function'] : 'n/a';

        //Add timestamp, log level, method, file, line
        $logString = sprintf("\n %s %s : [%s] (%s : %s) - %s", \net\authorize\util\Helpers::now(), $logLevelPrefix,
            $methodName, $fileName, $lineNumber, $logMessage);
        file_put_contents(ANET_LOG_FILE, $logString, $flags);
    }
    public function __construct(){
        $this->sensitiveXmlTags = ANetSensitiveFields::getSensitiveXmlTags();
    }
}
?>