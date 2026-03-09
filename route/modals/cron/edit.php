<?php

#
# Edit cronjob
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
# script
$cronjob = $Cron->fetch_cronjob ($_GET['tenant'], $_GET['script']);

#
# title
#
$title = _("Edit cronjob");

# tenant validation
if($user->admin !== "1" && $user->t_id!=$cronjob->t_id) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Admin user required"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate script
elseif (is_null($cronjob)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid script"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle table-zone-management table-md'>";
	// name
	$content[] = "<tbody class='name'>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Script")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm disabled' name='script' value='{$cronjob->script}' disabled>";
	$content[] = "		<input type='hidden' name='id' value='{$cronjob->id}'>";
	$content[] = "		<input type='hidden' name='action' value='edit'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	// minute
	$content[] = "<tr>";
	$content[] = "	<th>"._("Minute")."</th>";
	$content[] = "	<td>";
	$content[] = ' 		<input name="minute" class="form-control form-control-sm" value='.$cronjob->minute.'>';
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	// hour
	$content[] = "<tr>";
	$content[] = "	<th>"._("Hour")."</th>";
	$content[] = "	<td>";
	$content[] = ' 		<input name="hour" class="form-control form-control-sm" value='.$cronjob->hour.'>';
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	// day
	$content[] = "<tr>";
	$content[] = "	<th>"._("Day")."</th>";
	$content[] = "	<td>";
	$content[] = ' 		<input name="day" class="form-control form-control-sm" value='.$cronjob->day.'>';
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	// month
	$content[] = "<tr>";
	$content[] = "	<th>"._("Month")."</th>";
	$content[] = "	<td>";
	$content[] = ' 		<input name="month" class="form-control form-control-sm" value='.$cronjob->month.'>';
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	// weekday
	$content[] = "<tr>";
	$content[] = "	<th>"._("weekday")."</th>";
	$content[] = "	<td>";
	$content[] = ' 		<input name="weekday" class="form-control form-control-sm" value='.$cronjob->weekday.'>';
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	$content[] = "</table>";
	$content[] = "</form>";



	// text
	$content[] = "<hr>";
	$content[] = "<span class='text-secondary'>";
	$content[] = "Following entries are supported withing it own range:<br>";
	$content[] = "<ul>";
	$content[] = "	<li>Wildcard (*) - Any</li>";
	$content[] = "	<li>Single number</li>";
	$content[] = "	<li>Step functions (*/2)</li>";
	$content[] = "	<li>Range (1-4)</li>";
	$content[] = "	<li>List (3,5,7)</li>";
	$content[] = "</ul>";
	$content[] = "</span>";



	//
	// Support :
	//
	// 	wildcard (*)
	// 	single number (2)
	// 	range (3-5)
	// 	list (3,5,7)
	// 	step function: (*/2, */10)
	//


	#
	# button text
	#
	$btn_text = _("Edit cronjob");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/cron/edit-submit.php", false, "info");
