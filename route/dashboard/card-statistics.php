<?php

// get stats
$stats = $User->get_stats ();

?>

<div class="card-header">
	<h3 class="h2"><?php print _("Statistics"); ?></h3>
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