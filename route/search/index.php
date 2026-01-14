<?php
# validate user session - requires admin
$User->validate_session (false);
?>

<div class='page-header'>
	<h2 class="page-title"><?php print $url_items["search"]['icon']; ?> <?php print _("Search"); ?></h2>
	<hr>
</div>

<!-- back -->
<div>
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>

<div class="row" style="margin-top:20px">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
<div class="card" style="max-width:600px">


	<div class="card-header">
		<?php print _("Search "); ?>
	</div>

	<div class="card-body">

	<?php
	// check/uncheck values on post
	$host_checked = @$_params['hosts']=="on" ? "checked" : "";
	$cert_checked = @$_params['certificates']=="on" ? "checked" : "";

	// default
	if(!isset($_params['search'])) {
		$host_checked = "checked";
		$cert_checked = "checked";
	}
	?>

	<form class='form-inline space-y' method="get">

		<div>
			<input type="text" name="search" value="<?php print $_params['search']; ?>" class="form-control form-control-md" placeholder="<?php print _('Enter search string'); ?>" required>
		</div>

		<div class="form-check" style='margin-bottom:0px;margin-left:5px'>
			<input type="checkbox" name="hosts" class="form-check-input" id="hosts" <?php print $host_checked; ?>>
			<label class="form-check-label" for="hosts"></label><?php print _("Search hosts"); ?>
		</div>
		<div class="form-check" style='margin-bottom:0px;margin-left:5px'>
			<input type="checkbox" name="certificates" class="form-check-input" id="certificates" <?php print $cert_checked; ?>>
			<label class="form-check-label" for="certificates"></label><?php print _("Search certificates"); ?>
		</div>

		<hr>

		<div>
			<button type="submit" class="btn btn-md btn-info" style='width:100%'>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
				<?php print _("Search"); ?>
			</button>
		</div>

	</form>
	</div>
</div>
</div>
</div>

<?php
if(isset($_params['search'])) {
	include('search-results.php');
}