<header class="navbar navbar-expand d-print-none">
	<div class="container-fluid">



	<div class="collapse navbar-collapse" id="navbar-menu"></div>


	  <!-- BEGIN NAVBAR TOGGLER -->
	  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
	    <span class="navbar-toggler-icon"></span>
	  </button>
	  <!-- END NAVBAR TOGGLER -->

	  <div class="navbar-nav  flex-row order-md-last">

	    <div class="nav-item d-none d-lg-flex me-3">
	      <div class="btn-list d-none d-md-flex">
	        <a href="https://github.com/phpipam/php-ssl" class="btn btn-5" target="_blank" rel="noreferrer">
	          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
	            <path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path>
	          </svg>
	          Source code
	        </a>
<!-- 	        <a href="https://github.com/sponsors/codecalm" class="btn btn-6" target="_blank" rel="noreferrer">
	          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-pink icon-2">
	            <path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"></path>
	          </svg>
	          Sponsor
	        </a> -->
	      </div>
	    </div>



		<div class="nav-item">
			<div class="my-2 my-md-0 flex-grow-1 flex-md-grow-0 order-first order-md-last">
			  <form action="/<?php print $user->href; ?>/search/" method="get" autocomplete="off" novalidate="">
			  	<input type="hidden" name="hosts" value="on">
			  	<input type="hidden" name="certificates" value="on">
			    <div class="input-icon">
			      <span class="input-icon-addon">
			        <!-- Download SVG icon from http://tabler.io/icons/icon/search -->
			        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
			          <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
			          <path d="M21 21l-6 -6"></path>
			        </svg>
			      </span>
			      <input type="text" class="form-control" placeholder="Search…" name="search" aria-label="Search" value="<?php print $_params['search']; ?>">
			    </div>
			  </form>
			</div>
		</div>



		<div class="d-flex">

			<!-- Dark / Light theme -->
			<div class="nav-item">
				<a href="/<?php print $user->href; ?>/user/theme/dark/" class="nav-link px-0 hide-theme-dark" data-bs-toggle="tooltip" data-bs-placement="bottom" aria-label="Enable dark mode" title="Enable dark mode">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
						<path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"></path>
					</svg>
				</a>
				<a href="/<?php print $user->href; ?>/user/theme/light/" class="nav-link px-0 hide-theme-light" data-bs-toggle="tooltip" data-bs-placement="bottom" aria-label="Enable light mode" title="Enable light mode">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
						<path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
						<path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"></path>
					</svg>
				</a>
			</div>

			<!-- Notifications -->
			<div class="nav-item dropdown d-none d-md-flex">
				<?php include('header-notifications.php'); ?>
			</div>

	    </div>


	    <!-- user menu -->
	    <div class="nav-item dropdown">
	      <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown" aria-label="Open user menu">
	        <span class="avatar avatar-sm" style="<?php print isset($_SESSION['impersonate_original']) ? 'background-color:var(--tblr-warning);color:#000;' : ''; ?>">
	        	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z" /><path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z" /></svg>
	        </span>
	        <div class="d-non1e d-xl-block ps-2">
	          <div><?php print htmlspecialchars($user->name); ?></div>
	          <div class="mt-1 small text-secondary"><?php print htmlspecialchars($user->t_name); ?></div>
	        </div>
	      </a>
	      <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
	        <?php if(isset($_SESSION['impersonate_original'])): ?>
	        <div class="px-3 py-2 small text-muted"><?php print _("Acting as"); ?>: <strong><?php print htmlspecialchars($user->name); ?></strong></div>
	        <a href="/<?php print htmlspecialchars($user->href); ?>/user/impersonate/stop/" class="dropdown-item text-warning">
	          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-switch-3"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17h5l1.67 -2.386m3.66 -5.227l1.67 -2.387h6" /><path d="M18 4l3 3l-3 3" /><path d="M3 7h5l7 10h6" /><path d="M18 20l3 -3l-3 -3" /></svg>
	          <?php print _("Switch back to admin"); ?>
	        </a>
	        <div class="dropdown-divider"></div>
	        <?php else: ?>
	        <a href="/<?php print htmlspecialchars($user->href); ?>/user/profile/" class="dropdown-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z" /><path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z" /></svg><?php print _("Profile"); ?></a>
	        <div class="dropdown-divider"></div>
	        <?php endif; ?>
	        <a href="/logout/" class="dropdown-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-logout"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg><?php print _("Logout"); ?></a>
	      </div>
	    </div>
	  </div>
	</div>
</header>
<?php
if ($user->admin === "1") {
    $migration_dir = __DIR__ . '/../../db/migrations/';
    $fs_migrations = [];
    if (is_dir($migration_dir)) {
        foreach (glob($migration_dir . '*.sql') as $f) {
            $fs_migrations[] = basename($f);
        }
        sort($fs_migrations);
    }
    if (!empty($fs_migrations)) {
        try {
            $applied = $Database->getObjectsQuery("SELECT filename FROM migrations ORDER BY filename ASC", []);
            $applied_names = array_map(fn($r) => $r->filename, $applied ?: []);
            $pending = array_values(array_diff($fs_migrations, $applied_names));
        } catch (Exception $e) {
            $pending = [];
        }
        if (!empty($pending)) {
            $count = count($pending);
            $list  = implode(', ', $pending);
            print "<div class='alert alert-warning alert-dismissible mb-0 rounded-0 py-2' role='alert' style='border-left:4px solid var(--tblr-warning);'>";
            print "<div class='container-fluid d-flex align-items-center gap-2'>";
            print "<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon flex-shrink-0'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
            print "<span><strong>" . sprintf(_("%d unapplied database migration(s)"), $count) . ":</strong> <span class='text-muted'>" . htmlspecialchars($list) . "</span></span>";
            print "<button type='button' class='btn-close ms-auto' data-bs-dismiss='alert' aria-label='" . _("Close") . "'></button>";
            print "</div></div>";
        }
    }
}
?>

