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
		<h3 class="h2">
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