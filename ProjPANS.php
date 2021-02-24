<?php
namespace Stanford\ProjPANS;

include_once("emLoggerTrait.php");

use \REDCap;
/**
 */
class ProjPANS extends \ExternalModules\AbstractExternalModule
{
    use emLoggerTrait;

    function redcap_module_link_check_display($project_id, $link){
        if($this->linkCheckDisplayReturnValue !== null){
            return $this->linkCheckDisplayReturnValue;
        }

        return parent::redcap_module_link_check_display($project_id, $link);
    }

}