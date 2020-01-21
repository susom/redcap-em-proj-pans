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

//check that we are in the right event
$target_event = $module->getProjectSetting('event-field');
if ($target_event !== $event_id) die("Unable to verify event id. Please execute this from the visit event of the record for which you want the PDF");
if (!isset($instance)) {
    //sometimes REDCap does not report instance id for instance 1
    //so as long as event id is target event (visit event id specified in config), if no instance ID, assume it is instance 1.
    $instance = 1;
    //die("Unable to get instance id. Please check that you were in the record and event that you want to print the PDF. ");
}
if (!isset($record)) die("Unable to get record id. Please check that you were in the record that you want to print the PDF. ");

printPatientForms($record, $event_id, $instance);

function printPatientForms($record, $event_id, $instance) {
    global $module;

    //echo 'ProjPANS: printing '.$record . ' and instance ' . $instance;

    //get list of forms to print
    $form_list = $module->getProjectSetting('form-field');

    $pdf = new \PDFMerger\PDFMerger;

    //list of files to unlink after print
    $files = array();

    foreach ($form_list as $instrument) {
        $pdf_content = REDCap::getPDF($record, $instrument, $event_id, false, $instance, true);
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