<?php

namespace Stanford\ProjPANS;

/** @var \Stanford\ProjPANS\ProjPANS $module */

use REDCap;
//require_once 'vendor/autoload.php';
include_once 'PDFMerger.php';

define('MIN_PDF_STRLEN', 25050);

$refer = $_SERVER['HTTP_REFERER'];
parse_str(parse_url($refer,PHP_URL_QUERY), $parts);

$record = $parts['id'];
$instance = $parts['instance'];
$event_id = $parts['event_id'];

$subsettings = $module->getSubSettings('pdf-events');
$sub_to_print = null;
$form_list = array();
$compact_display = false;

//find the subsetting for the event which we are in now
foreach($subsettings as $sub => $event_config) {
    //if the event matches then store the subsetting
    if ($event_config['event-field'] == $event_id) {
        //foudn the setting , break
        $sub_to_print = $sub;
        $form_list = $event_config['form-field'];
        if ($event_config['compact-display'] === true) {
            $compact_display = true;
        }
        break;
    }
}


//check that we are in the right event

//if sub_to_print then that means event was not defined
//if ($target_event !== $event_id) die("Unable to verify event id. Please execute this from the visit event of the record for which you want the PDF");
if ($sub_to_print === null) die("Unable to verify event id. Please execute this from the visit event of the record for which you want the PDF or make sure the event has been defined in the EM config");

if (!isset($instance)) {
    //sometimes REDCap does not report instance id for instance 1
    //so as long as event id is target event (visit event id specified in config), if no instance ID, assume it is instance 1.
    $instance = 1;
    //die("Unable to get instance id. Please check that you were in the record and event that you want to print the PDF. ");
}

if (!isset($record)) die("Unable to get record id. Please check that you were in the record that you want to print the PDF. ");

printPatientForms($record, $event_id, $instance, $form_list, $compact_display);

function printPatientForms($record, $event_id, $instance, $form_list, $compact_display) {
    global $module;

    //echo 'ProjPANS: printing '.$record . ' and instance ' . $instance;

    //get list of forms to print
    //$form_list = $module->getProjectSetting('form-field');

    $pdf = new \PDFMerger\PDFMerger;

    //list of files to unlink after print
    $files = array();

    foreach ($form_list as $instrument) {
        $module->emDebug("Getting pdf for $instrument");
        $pdf_content = REDCap::getPDF($record, $instrument, $event_id, false, $instance, $compact_display);
        $temp_name  = APP_PATH_TEMP . date('YmdHis') .'_' . $instrument.'.pdf';

        //add to the pdf if not empty
        if (strlen($pdf_content)>MIN_PDF_STRLEN) {
            //$module->emDebug("size of pdf is $instrument ".strlen($pdf_content));
            file_put_contents($temp_name, $pdf_content);
            $pdf->addPDF($temp_name, 'all');
            $files[] = $temp_name;
        }
    }

    ob_start(); //clear out out
    $pdf->merge('download', $record.'_'.$instance.'_'.date('YmdHis') .'.pdf');
    ob_end_flush();

    //unlink all the files
    foreach ($files as $file) {
        //$module->emDebug("Unlinking $file");
        unlink($file);
    }

    }