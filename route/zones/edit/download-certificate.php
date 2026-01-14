<?php

#
# download certificate
#


# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (1, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

#
# title
#
$title = _("Download")." "._("certificate");

# decode
$certificate = base64_decode($_GET['certificate']);

# validate
if(openssl_x509_parse($certificate)===false) {
	// content
	$content = [];
	// error
	$content[] = "<div class='alert alert-danger'>"._("Cannot parse certificate")."!</div>";
}
else {
	// content
	$content = [];
	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = '<fieldset class="form-group">';
	$content[] = '  <div class="row">';
	$content[] = '	  <input type="hidden" name="certificate" value="'.$_GET['certificate'].'">';
	$content[] = '    <legend class="col-form-label col-sm-2 pt-0">Format:</legend>';
	$content[] = '    <div class="col-sm-10">';
	$m=0;
	foreach ($Certificates->allowed_cert_formats() as $f=>$label) {
	$checked = $m==0 ? "checked" : "";
	$content[] = '      <div class="form-check">';
	$content[] = '        <input class="form-check-input" type="radio" name="format" id="format'.$m.'" value="'.$f.'" '.$checked.'>';
	$content[] = '        <label class="form-check-label" for="format'.$m.'">'.$label.'</label>';
	$content[] = '      </div>';
	$m++;
	}
	$content[] = '    </div>';
	$content[] = '  </div>';
	$content[] = '</fieldset>';
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _("Download");
}

# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", false, "info");
?>


<script type="text/javascript">
$('document').ready(function() {
	$(document).on("click", ".modal-execute", function() {
		var formdata = $('form#modal-form').serialize();
	    // remove old innerDiv
	    $("div.dl").remove();
	    // execute
	    $('.download').append("<div style='display:none' class='dl'><iframe src='/route/zones/edit/download-certificate-submit.php?"+formdata+"'></iframe></div>");
	    return false;
	})
})

</script>