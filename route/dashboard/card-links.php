<div class="card-header">
	<h3 class="h2">
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