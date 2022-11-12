<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

handleActionMethodCalls();

function _service_list_scripts() {
    return array_keys(getScriptList(true));
}

function _service_test() {
    $nodeData = testNodeServer();
    
    if(!$nodeData) return false;
    
    return true;
}

function _service_stats() {
    return fetchNodeStats();
}
function _service_restart() {
    return restartNodeServer();
}
function _service_form_script() {
    if(!isset($_POST['src']) && strlen($_POST['src'])>0) {
        echo "Script Source Not Defined";
        exit();
    }
    if(!in_array($_POST['src'], array_keys(getScriptList()))) {
        echo "Script Source Not Found";
        exit();
    }
    
    $form = getScriptForm($_POST['src']);
    if($form) return file_get_contents($form);
    else return false;
}
function _service_run_script() {
    if(!isset($_POST['src']) && strlen($_POST['src'])>0) {
        echo "Script Source Not Defined";
        exit();
    }
    if(!in_array($_POST['src'], array_keys(getScriptList()))) {
        echo "Script Source Not Found";
        exit();
    }
    
    $result = runNodeScript($_POST['src'], $_POST, CMS_APPROOT);
    
    echo $result;
}
?>