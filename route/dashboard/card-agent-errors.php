<?php

// init agent
$Agent = new Agent ();

// get conf
$config = $Config->get_config($user->t_id);

// get errors
$agent_errors = $Agent->get_agent_connection_errors($Database, $config['agentTimeout'], $user->admin, $user->t_id);

// print if eneded
if (sizeof($agent_errors)>0) {
?>


<div class="col col-sm-12">
<div class="card">

	<div class="card-header">
		<h3 class="h3">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-alert-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
			<?php print _("Agent errors"); ?>
		</h3>
	</div>

	<div class="card-body">

		<div class="col">
			<div>
				<?php
				foreach ($agent_errors as $e) {
					// last error
					$errtext =  strlen($e->last_error)>1 ? "Error :: <b>".$e->last_error."</b>" : "";
					// print
					print "<div class='row alert alert-danger' style='margin-bottom:5px'>";
					print "	<div class='col col-sm-6'><b>".$e->name."</b> "._("didnt respond for")." ".$config['agentTimeout']." "._("minutes")."</div>";
					print "	<div class='col col-sm-6 text-secondary'>"._("Last successful response received at")." ".$e->last_success."</div>";
					print '</div>';
				}
				?>
			</div>
		</div>
	</div>
</div>
</div>

<?php } ?>