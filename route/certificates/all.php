<?php if(!isset($from_search)) { ?>
<div class="page-header">
	<h2 class="page-title"><?php print $url_items['certificates']['icon']." "._("Certificates"); ?></h2>
	<hr>
</div>

<p class='text-secondary'><?php print _('List of all available certificates in system'); ?>.</p>


<!-- back -->
<div>
<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>
<br><br>


<?php

# menu
print '<div class="card">';
print '<div class="card-header">';
print '<ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">';
foreach ($url_items["certificates"]["submenu"] as $k=>$m) {

	$active = $_params['app']==$k ? "active" : "";


	if($k=="expire_soon") 		{ $textcol = "orange"; }
	elseif($k=="expired") 		{ $textcol = "red"; }
	elseif($k=="orphaned") 		{ $textcol = "info"; }
	elseif($k=="ignored") 		{ $textcol = "default"; }
	else 						{ $textcol = "light"; }


	print '<li class="tnav-item '.$active.'">';
	print '	<a class="nav-link '.$active.'" aria-current="page" href="/'.$user->href.'/certificates/'.$k.'/"><span class="text-'.$textcol.'">'.$url_items['certificates']['icon'].'</span> '._($m['title']).'</a>';
	print '</li>';
}
print "</ul>";
print '</div>';


print '<div class="card-body" style="padding-left:0px;padding-right:0px">';

?>

<?php
$filter = $_params['app'];
?>

<div class='table-responsive'>
<input type="hidden" id="filter_type" value="<?php print $filter; ?>">
<input type="hidden" id="user_href" value="<?php print $user->href; ?>">
<table
	class="table table-hover align-top table-md"
	data-classes='table table-hover table-sm'
	id="table"
	data-toggle="table"
	data-sortable="true"
	data-page-size="50"
	data-page-list="[10, 25, 50, 100, 250, 500]"
	data-ajax="ajaxRequest"
	data-search="true"
	data-side-pagination="server"
	data-server-sort="true"
	data-pagination="true"
	data-loading-template="loadingMessage"
	data-remember-order="true"
	data-loading-font-size="14"
	data-sort-name="expires"
	data-sort-order="desc"
	data-filter-control="true"
>
<thead>
	<tr>
		<?php if($user->admin=="1") { ?>
		<th data-field="tid" data-sortable="true" class="d-none d-lg-table-cell"><?php print _("Tenant"); ?></th>
		<th data-field="zone" data-sortable="true" class="d-none d-lg-table-cell"><?php print _("Zone"); ?></th>
		<?php } ?>
		<th data-field="serial" data-sortable="true"><?php print _("Serial number"); ?></th>
		<th data-field="common_name" data-sortable="true"><?php print _("Common name"); ?></th>
		<th data-field="issued_by" data-sortable="true" class="d-none d-lg-table-cell"><?php print _("Issued by"); ?></th>
		<th data-field="status" data-sortable="true"><?php print _("Status"); ?></th>
		<th data-field="days_valid" data-sortable="true" class="d-none d-lg-table-cell"><?php print _("Days"); ?></th>
		<th data-field="expires" data-sortable="true"><?php print _("Expires"); ?></th>
	</tr>
</thead>
</table>
</div>

</div>
</div>

<script>
window.ajaxRequest = params => {
    var filter = $('#filter_type').val();
    var href = $('#user_href').val();
    var data = params.data;
    data.filter = filter;
    data.href = href;
    $.ajax({
        type: "POST",
        url: '/route/ajax/certificates.php',
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

<style type="text/css">
table tr td:nth-child(1) {
    white-space: nowrap;
}
</style>
<?php } ?>
