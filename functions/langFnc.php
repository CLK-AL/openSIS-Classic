<?php
error_reporting(0);

function langDirection(){
    include_once 'lang/supportedLanguages.php';
    if(isset($_SESSION['language']) && isset($supportedLanguages[$_SESSION['language']])){
        return $supportedLanguages[$_SESSION['language']]['direction'];
    }
    return 'ltr';
}
?>