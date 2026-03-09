<input type="hidden" id="log_user_id" value="<?php print (int)$view_user->id; ?>">

<div class='row'>
	<div class='col-12' style='margin-top:10px;'>
		<div class='card'>
			<div class='card-header'><?php print $url_items["logs"]['icon']; ?> <?php print _("User logs"); ?></div>
			<div>

				<table
					class="table table-hover align-top table-md"
					data-classes="table table-hover table-sm"
					id="table-user-logs"
					data-toggle="table"
					data-sortable="false"
					data-page-size="20"
					data-page-list="[10, 20, 50, 100]"
					data-ajax="ajaxRequestUserLogs"
					data-search="false"
					data-side-pagination="server"
					data-server-sort="true"
					data-pagination="true"
					data-loading-template="loadingUserMessage"
					data-remember-order="true"
					data-loading-font-size="14"
					data-sort-name="id"
					data-sort-order="desc"
				>
				<thead>
					<tr>
						<th data-field="id" data-sortable="true" data-width="20" data-width-unit="px" data-white-space="nowrap">ID</th>
						<th data-field="date" data-sortable="true"><?php print _("Date"); ?></th>
						<th data-field="object" data-width="80" data-width-unit="px"><?php print _("Object"); ?></th>
						<th data-field="action" data-width="60" data-width-unit="px"><?php print _("Action"); ?></th>
						<th data-field="text" data-sortable="true"><?php print _("Content"); ?></th>
						<th data-field="diff" data-width="20" data-width-unit="px"><?php print _("Change"); ?></th>
					</tr>
				</thead>
				</table>

			</div>
		</div>
	</div>
</div>


<style type="text/css">
#table-user-logs tr td:nth-child(1) {
    white-space: nowrap !important;
}
</style>


<script>
window.ajaxRequestUserLogs = params => {
    var user_id = $('#log_user_id').val();
    var data = params.data;
    data.user_id = user_id;
    $.ajax({
        type: "POST",
        url: '/route/ajax/logs.php',
        data: data,
        dataType: "json",
        success: function (data) {
            params.success({
                "rows":  data.rows,
                "total": data.total
            })
        },
        error: function (er) {
            console.log(er);
            params.error(er);
        }
    });
}

function loadingUserMessage () {
  return '<span class="loading-wrap">' +
    '<span class="loading-text" style="font-size:14px;">Loading</span>' +
    '\t<span class="animation-wrap"><span class="animation-dot"></span></span>' +
    '</span>'
}
</script>
