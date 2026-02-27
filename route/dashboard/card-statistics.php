<?php

// get stats
$stats = $User->get_stats ();

?>

<div class="card-header">
	<h3 class="h3">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chart-bar-popular"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 13a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -6" /><path d="M9 9a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -10" /><path d="M15 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -14" /><path d="M4 20h14" /></svg>
		<?php print _("Statistics"); ?>
	</h3>
</div>

<div class="card-body">
	<div class="row">
		<?php foreach ($stats as $name=>$cnt) { ?>
		<div class="col-6 flex-column" style='margin-bottom:3px;'>
			<span class='badge badge-circle'><?php print $cnt;?></span>
			<span style='padding:13px !important;position: absolute' class='text-secondary'><?php print _(ucwords($name)); ?></span>
		</div>
		<?php } ?>
	</div>
</div>