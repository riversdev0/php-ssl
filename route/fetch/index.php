<?php
# tenants
if($user->admin=="1")
$tenants = $Tenants->get_all ();
?>

<div class="page-header">
	<h2 class="page-title">
		<?php print $url_items['fetch']['icon'].""._("Fetch website certificate"); ?></h2>
	<hr>
</div>

<!-- back -->
<div>
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>


<div class="row" style="margin-top:20px">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
<div class="card" style="max-width:600px">
<div class="card-body">
	<?php
	// validate request, remove POST if invalid
	if(isset($_POST['website'])) {
		// remove https / https for IP checking
		$_POST['website_ip_check'] = str_replace(["http://", "https://"], "", $_POST['website']);
		// validate
		if(!$User->validate_url($_POST['website']) && !$User->validate_ip($_POST['website_ip_check'])) {
			unset ($_POST['website'], $_POST['website_ip_check']);
		}
	}
	?>

	<form class='form-inline space-y' method="post">
		<div class="col-xs-12">
			<p style="margin-bottom:10px">Enter hostname or IP address:</p>
			<input type="text" name="website" value="<?php print $_POST['website']; ?>" class="form-control" placeholder="<?php print _('https://google.com'); ?>" required>
		</div>

		<div class="col-xs-12">
			<p style="margin-bottom:10px">Select scanning agent:</p>
			<select name='agent_id' class="form-control" style="width:auto">
				<?php
				# fetch agents
				if($user->admin=="1")
				$all_agents = $Database->getObjectsQuery("select * from agents");
				else
				$all_agents = $Database->getObjectsQuery("select * from agents where t_id = ?", [$user->t_id]);

				// print
				if(is_array($all_agents)) {
					foreach ($all_agents as $agent) {
						// suffix
						$suffix = $user->admin=="1" ? " [".$tenants[$agent->t_id]->name."]" : "";

						// select
						$selected = @$_POST['agent_id']==$agent->id ? "selected" : "";
						// print
						print "<option value='".$agent->id."' $selected>$agent->name $suffix</option>";
					}
				}
				?>
			</select>
		</div>

		<hr>

		<div class="col-xs-12 text-right">
			<button type="submit" class="btn btn-md btn-info" style='width:100%'>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-object-scan"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8v-2a2 2 0 0 1 2 -2h2" /><path d="M4 16v2a2 2 0 0 0 2 2h2" /><path d="M16 4h2a2 2 0 0 1 2 2v2" /><path d="M16 20h2a2 2 0 0 0 2 -2v-2" /><path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" /></svg>
				<?php print _("Fetch"); ?>
			</button>
		</div>
	</form>

</div>
</div>
</div>
</div>



<?php
// error ?
if(sizeof($User->errors)>0) {
	print "<div class='container-fluid main'>";
	$Result->show("danger", _($User->errors[0]), false);
	print "</div>";
}
elseif(isset($_POST['website'])) {
	include('result.php');
}