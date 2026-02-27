<div class="card-header">
	<h3 class="h3">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-link"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 15l6 -6" /><path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464" /><path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463" /></svg>
		<?php print _("Links"); ?>
	</h3>
	<hr>
</div>


<div class="card-body">

	<div class="row">

		<!-- Tenanats -->
		<?php if($user->admin=="1") { ?>
		<div class="col-12 flex-column text-start" style='margin-bottom:4px;'>
			<a href='/admin/tenants/' class='btn' style='width:130px;justify-content:left;margin-right:10px'><span class='text-info'><?php print $url_items["tenants"]['icon'];?></span> <?php print _("Tenants"); ?></a>
			<span class='text-secondary'><?php print _("View and manage system tenants"); ?></span>
		</div>
		<?php } ?>

		<!-- Certificates -->
		<div class="col-12 flex-column" style='margin-bottom:4px'>
			<a href='/<?php print $user->href; ?>/certificates/' class='btn' style='width:130px;justify-content:left;margin-right:10px'><span class='text-info'><?php print $url_items["certificates"]['icon'];?></span> <?php print _("Certificates"); ?></a>
			<span class='text-secondary'><?php print _("View and manage found certificates"); ?></span>
		</div>

		<!-- Zones -->
		<div class="col-12 flex-column" style='margin-bottom:4px'>
			<a href='/<?php print $user->href; ?>/zones/' class='btn' style='width:130px;justify-content:left;margin-right:10px'><span class='text-info'><?php print $url_items["zones"]['icon'];?></span> <?php print _("Zones"); ?></a>
			<span class='text-secondary'><?php print _("View and manage zones"); ?></span>
		</div>

		<!-- Port groups -->
		<div class="col-12 flex-column" style='margin-bottom:4px'>
			<a href='/<?php print $user->href; ?>/portgroups/'class='btn' style='width:130px;justify-content:left;margin-right:10px'><span class='text-info'><?php print $url_items["scanning"]['icon'];?></span> <?php print _("Port groups"); ?></a>
			<span class='text-secondary'><?php print _("View and manage scanning port groups"); ?></span>
		</div>

		<!-- Cron -->
		<div class="col-12 flex-column" style='margin-bottom:4px'>
			<a href='/<?php print $user->href; ?>/cron/' class='btn' style='width:130px;justify-content:left;margin-right:10px'><span class='text-info'><?php print $url_items["scanning"]['submenu']['cron']['icon'];?></span> <?php print _("Cron jobs"); ?></a>
			<span class='text-secondary'><?php print _("Scheduled actions"); ?></span>
		</div>


	</div>
</div>