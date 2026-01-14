<?php

# default
if(!isset($_params['app'])) { $_params['app'] = "list"; }


# views
if(array_key_exists($_params['app'], $url_items["certificates"]["submenu"])) {
    include("all.php");
}
# cert
else {

    # append location
    $_SESSION['url'] .= $_params['id1']."/";
    # cert details
    include("certificate.php");
}