<?php

// fetch logs
$logs = $Log->get_logs($user, false, true, 10);

// color
$bcol = @$logs[0]->id > $user->notif_id ? "badge bg-red" : "";
?>


<a href="#" class="nav-link px-0 show" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications" data-bs-auto-close="outside" aria-expanded="true">
	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
		<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"></path>
		<path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
	</svg>
	<span id="dropdown_new_log_indicator" class="<?php print $bcol; ?>"></span>
</a>

<div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card "  style="max-width:600px" data-bs-popper="static">

	<div class="card">
		<div class="card-header d-flex">
			<h3 class="card-title"><?php print _("Last notifications"); ?></h3>
			<div class="btn-close ms-auto" data-bs-dismiss="dropdown"></div>
		</div>
		<div id="dropdown_new_logs" class="list-group list-group-flush list-group-hoverable">

			<?php
			foreach ($logs as $l) {
				// new or already read ?
				$span_class = $l->id > $user->notif_id ? "bg-red badge-blink" : "";
				// item
				print '<div class="list-group-item">';
				print '	<div class="row align-items-center">';
				print '		<div class="col-auto"><span class="badge '.$span_class.'"></span></div>';
				print '		<div class="col text-truncate">';
				print '			<a href="#" class="text-body d-block">'.ucwords(_($l->object))." "._($l->action).'</a>';
				print '			<div class="d-block text-secondary text-truncate mt-n1">'._($l->text).'</div>';
				print '		</div>';
				print '	</div>';
				print '</div>';
			}
			?>

		</div>

		<!-- Actions -->
		<div class="card-body">
			<div class="row">
				<div class="col">
					<a href="/admins/logs/" class="btn btn-2 w-100"> Show all </a>
				</div>
				<div class="col">
					<a href="#" class="btn btn-2 w-100"> Mark all as read </a>
				</div>
			</div>
		</div>

	</div>
</div>