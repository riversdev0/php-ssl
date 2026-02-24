<input type="hidden" id="log_object" value="certificates">
<input type="hidden" id="log_object_id" value="<?php print $certificate->id; ?>">
<input type="hidden" id="log_serial" value="<?php print !empty($certificate->serial) ? $certificate->serial : ''; ?>">

<table
	class="table table-hover align-top table-md"
	data-classes="table table-hover table-sm"
	id="table-logs"
	data-toggle="table"
	data-sortable="false"
	data-page-size="20"
	data-page-list="[10, 20, 50, 100]"
	data-ajax="ajaxRequestLogs"
	data-search="false"
	data-side-pagination="server"
	data-server-sort="true"
	data-pagination="true"
	data-loading-template="loadingMessage"
	data-remember-order="true"
	data-loading-font-size="14"
	data-sort-name="id"
	data-sort-order="desc"
>
<thead>
	<tr>
		<th data-field="id" data-sortable="true" data-width="20" data-white-space="nowrap" data-width-unit="px">ID</th>
		<th data-field="user" data-sortable="true">User</th>
		<th data-field="date" data-sortable="true">Date</th>
		<th data-field="action" data-width="20" data-width-unit="px">Action</th>
		<th data-field="text" data-sortable="true">Content</th>
		<th data-field="diff" data-width="20" data-width-unit="px">Change</th>
	</tr>
</thead>
</table>


<style type="text/css">
table tr td:nth-child(1) {
    white-space: nowrap;
}
</style>


<script>
window.ajaxRequestLogs = params => {
    var log_object = $('#log_object').val();
    var log_object_id = $('#log_object_id').val();
    var log_serial = $('#log_serial').val();
    var data = params.data;
    data.object = log_object;
    data.object_id = log_object_id;
    if(log_serial) {
        data.serial = log_serial;
    }
    $.ajax({
        type: "POST",
        url: '/route/ajax/logs.php',
        data: data,
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
