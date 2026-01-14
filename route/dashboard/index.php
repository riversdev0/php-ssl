<div class="page-header">
	<h2 class="page-title"><?php print _("Dashboard"); ?></h2>
	<hr>
</div>


<div class="page-body">
<div class="row dashboard">

	<!-- links -->
	<div class="col-sm-6">
	<div class="card">
		<?php include("card-links.php"); ?>
	</div>
	</div>

	<!-- stats -->
	<div class="col-sm-6">
	<div class="card">
		<?php include("card-statistics.php"); ?>
	</div>
	</div>

	<!-- Agent errors -->
	<?php include("card-agent-errors.php"); ?>

	<!-- Expire soon certs -->
	<div class="col-sm-12">
	<div class="card">
		<?php
		$expired_certs = false;
		include("card-certificates-expire.php");
		?>
	</div>
	</div>

	<!-- Expired certs -->
	<div class="col-sm-12">
	<div class="card">
		<?php
		$expired_certs = true;
		include("card-certificates-expire.php");
		?>
	</div>
	</div>

	<!-- Checks -->
	<div class="col-sm-6">
	<div class="card">
		<?php include("card-checks.php"); ?>
	</div>
	</div>


</div>
</div>