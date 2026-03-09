<?php
# validate user session
$User->validate_session ();
# purge expired logs based on each tenant's retention period
$Log->purge_old_logs();
?>


<div class="page-header">
	<h2 class="page-title"><?php print $url_items["logs"]['icon']; ?> <?php print _("Logs / Notifications"); ?></h2>
	<hr>
</div>


<p class='text-secondary'><?php print _('List of logs'); ?>.</p>

<?php

# back
print '<div>';
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';


# fetch last log id for mark all as read
$last_log = $Database->getObjectQuery("select id from logs order by id desc limit 1");
$last_log_id = $last_log ? $last_log->id : 0;
$num_unread = $Log->count_new_logs($user);
$btn_disabled = $num_unread == 0 ? "disabled" : "";
$btn_disabled_truncate = $last_log_id == 0 ? "disabled" : "";

print '</div>';
print '<div style="text-align:right !important;float:right">';
print '<a href="#" data-id="'.$last_log_id.'" id="read-all" class="btn btn-sm text-info bg-info-lt '.$btn_disabled.'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-square-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18.333 2c1.96 0 3.56 1.537 3.662 3.472l.005 .195v12.666c0 1.96 -1.537 3.56 -3.472 3.662l-.195 .005h-12.666a3.667 3.667 0 0 1 -3.662 -3.472l-.005 -.195v-12.666c0 -1.96 1.537 -3.56 3.472 -3.662l.195 -.005h12.666zm-2.626 7.293a1 1 0 0 0 -1.414 0l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.32 1.497l2 2l.094 .083a1 1 0 0 0 1.32 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z" /></svg> '._("Mark all as read").'</a> ';
print '<a href="/route/modals/logs/truncate.php?href='.$user->href.'" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm text-red bg-info-lt '.$btn_disabled_truncate.'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg> '._("Truncate logs").'</a>';
print "</div>";


print '<br><br>';

print '<div style="padding:5px">';
print '<div class="row">';
print '<div class="card" style="padding:0px">';
print '<div class="card-body" style="padding:0px">';

// table
print '<table';
print '		class="table table-hover align-top table-md"';
print "		data-classes='table table-hover table-sm'";
print '		id="table"';
print '		data-toggle="table"';
print '		data-sortable="true"';
print '		data-page-size="50"';
print '		data-page-list="[10, 25, 50, 100, 250, 500]"';
print '		data-ajax="ajaxRequest"';
print '		data-search="true"';
print '		data-side-pagination="server"';
print '		data-server-sort="true"';
print '		data-pagination="true"';
print '		data-loading-template="loadingMessage"';
print '		data-remember-order="true"';
print '		data-loading-font-size="14"';
print '     data-sort-name="id"';
print '     data-sort-order="desc"';
print '>';

// headers
print '<thead>';
print '	<tr>';
print '		<th data-field="id" 	data-sortable="true" data-width="20" data-white-space="nowrap" data-width-unit="px" data-sort-name="id" data-sort-order="desc">ID</th>';
print '		<th data-field="user" 	data-sortable="true">User</th>';
if($user->admin==="1")
print '     <th data-field="tid"    data-sortable="true">Tenant</th>';
print '		<th data-field="object" data-sortable="true" data-width="20" data-width-unit="px">Object</th>';
print '		<th data-field="date" 	data-sortable="true">Date</th>';
print '		<th data-field="action" data-width="20" data-width-unit="px">Action</th>';
print '		<th data-field="text" 	data-sortable="true">Content</th>';
print '		<th data-field="diff" 	data-width="20" data-width-unit="px">Change</th>';
print '	</tr>';
print '</thead>';

// print '<tbody>';
// print '	<tr style="display:none">';
// print '	<td colspan=7></td>';
// print '	</tr>';
// print '</tbody>';

print '</table>';

print '</div>';
print '</div>';
print '</div>';
print '</div>';
?>


<style type="text/css">
table tr td:nth-child(1) {
    white-space: nowrap;
}
</style>


<script>
// request data on load
window.ajaxRequest = params => {
    $.ajax({
        type: "POST",
        url: '/route/ajax/logs.php',
        data: params.data,
        dataType: "json",
        success: function (data) {
            params.success({
                "rows": data.rows,
                "total": data.total
            })
        },
        error: function (er) {
        	console.log(er)
            params.error(er);
        }
    });
}

function loadingMessage () {
  return '<span class="loading-wrap">' +
    '<span class="loading-text" style="font-size:14px;">Loading</span>' +
    '	<span class="animation-wrap"><span class="animation-dot"></span></span>' +
    '</span>'
}
</script>