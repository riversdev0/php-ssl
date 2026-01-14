<?php

#
# Edit tenant
#

# functions
require('../../functions/autoload.php');
# validate user session
$User->validate_session ();


#
# title
#
$title = _("Not implemented");


// content
$content = [];

// content
$content[] = '<div class="page page-center">';
$content[] = '<div class="container py-2">';
$content[] = '  <div class="empty">';
$content[] = '    <div class="empty-header">404</div>';
$content[] = '    <p class="empty-title">Oops… Not implemented.</p>';
$content[] = '    <p class="empty-subtitle text-secondary">We are sorry, but this function is not yet implemented.</p>';
$content[] = '  </div>';
$content[] = '</div>';
$content[] = '</div>';

#
# button text
#
$btn_text = false;
$header_class = "warning";



# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/tenants/edit-submit.php", false, $header_class);
