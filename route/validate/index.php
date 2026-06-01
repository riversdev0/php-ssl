<?php
# validate user session - requires admin
$User->validate_session (true);

# admin only
if ($user->admin != "1") {
	$Result->show("danger", _("Admin access required."), true, false, false, false);
}
else {
?>

<div class="page-header">
	<h2 class="page-title">
		<?php print $url_items['validate']['icon']; ?>
		<?php print _("Database validation"); ?></h2>
	<hr>
</div>

<div>

<?php

print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> ' . _("Back") . '</a>';
print '</div><br><br>';

print "<p class='text-secondary'>"._('Validates the live database structure against db/SCHEMA.sql. Checks for missing tables, missing or wrong columns, missing indexes and missing foreign key constraints.')."</p>";


# run validation
$validation = $Common->validate_database($Database);

if ($validation['error'] !== null) {
	$Result->show("danger", htmlspecialchars($validation['error']), false, false, false, false);
}
elseif ($validation['ok']) {
	$Result->show("success", _("Database structure verified. No issues found."), false, false, false, false);
}
else {
	$issues = $validation['issues'];
	$cnt    = count($issues);

	$type_labels = [
		'missing_table'  => 'Missing table',
		'missing_column' => 'Missing column',
		'wrong_column'   => 'Wrong column definition',
		'missing_index'  => 'Missing index',
		'missing_fk'     => 'Missing foreign key',
	];
	$badge_classes = [
		'missing_table'  => 'text-required',
		'missing_column' => 'text-warning',
		'wrong_column'   => 'text-orange',
		'missing_index'  => 'text-orange',
		'missing_fk'     => 'text-blue',
	];

	$Result->show("warning", $cnt . " issue(s) found in database structure.", false, false, false, false);

	print "<div class='card' style='margin-bottom:20px;padding:0px'>";
	print "<table class='table table-hover align-top'>";
	print "<thead><tr>";
	print "<th>"._("Type")."</th>";
	print "<th>"._("Table")."</th>";
	print "<th>"._("Description")."</th>";
	print "<th>"._("Fix SQL")."</th>";
	print "</tr></thead>";
	print "<tbody>";
	foreach ($issues as $issue) {
		$label = $type_labels[$issue['type']] ?? $issue['type'];
		$badge = $badge_classes[$issue['type']] ?? 'bg-secondary';
		print "<tr>";
		print "<td><span class='badge $badge'>".htmlspecialchars($label)."</span></td>";
		print "<td><code class='dbfix'>".htmlspecialchars($issue['table'])."</code></td>";
		print "<td>".htmlspecialchars($issue['description'])."</td>";
		print "<td><code class='dbfix' style='font-size:11px;word-break:break-all'>".htmlspecialchars($issue['fix_sql'])."</code></td>";
		print "</tr>";
	}
	print "</tbody></table></div>";

	# CSRF token for the fix request
	$csrf = $User->create_csrf_token();

	print "<div id='fix-db-result'></div>";
	print "<button id='fix-db-btn' class='btn btn-sm btn-success' data-csrf='" . htmlspecialchars($csrf, ENT_QUOTES) . "'>";
	print '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-tools"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21h4l13 -13a1.5 1.5 0 0 0 -4 -4l-13 13v4" /><path d="M14.5 5.5l4 4" /><path d="M12 8l-5 -5l-4 4l5 5" /><path d="M7 8l-1.5 1.5" /><path d="M16 12l5 5l-4 4l-5 -5" /><path d="M16 17l1.5 1.5" /></svg> ';
	print _("Fix all issues");
	print "</button>";
}

?>

</div>

<script>
$(document).on("click", "#fix-db-btn", function () {
	var $btn  = $(this);
	var csrf  = $btn.data("csrf");
	$btn.prop("disabled", true).text("<?php print _("Applying fixes…"); ?>");
	$.ajax({
		url:     "/route/modals/validate/fix_db.php",
		method:  "POST",
		data:    { csrf_token: csrf },
		headers: { "X-Requested-With": "XMLHttpRequest" },
		dataType: "json",
		success: function (resp) {
			if (resp.success) {
				$("#fix-db-result").html("<div class='alert alert-success mt-2'><?php print _("All fixes applied successfully. Please reload to verify."); ?></div>");
				$btn.hide();
			} else {
				var msg = resp.error ? resp.error : "Unknown error";
				$("#fix-db-result").html("<div class='alert alert-danger mt-2'>" + $("<span>").text(msg).html() + "</div>");
				$btn.prop("disabled", false).text("<?php print _("Fix all issues"); ?>");
			}
		},
		error: function () {
			$("#fix-db-result").html("<div class='alert alert-danger mt-2'><?php print _("Request failed."); ?></div>");
			$btn.prop("disabled", false).text("<?php print _("Fix all issues"); ?>");
		}
	});
});
</script>
<?php } ?>