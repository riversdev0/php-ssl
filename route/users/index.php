<?php
# validate user session - requires admin
$User->validate_session (false);

# sub-route: user detail page
if(isset($_params['app'])) {
	include('user/index.php');
	return;
}
?>

<div class="page-header">
	<h2 class="page-title"><?php print $url_items["users"]['icon']; ?> <?php print _("Users"); ?></h2>
	<hr>
</div>

<p class='text-secondary'><?php print _('List of all available users'); ?>.</p>


<!-- back -->
<div>
<div class='btn-group'>
<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>

<?php if($user->admin=="0") { ?>
<a href="/route/modals/users/edit.php?action=add&tenant=<?php print urlencode($user->href); ?>" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm btn-outline-success btn-sm btn-5 d-none d-sm-inline-block">
	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> Create new user </a>
<?php } ?>

</div>
</div>
<br><br>




<?php

# fetch users
$users = $User->get_all ();
# tenants
$tenants = $Tenants->get_all ();
// regrouped certs
$cert_tenant_groups = [];

// create groups for admins
if($user->admin=="1") {
	foreach($tenants as $t) {
		$cert_tenant_groups[$t->id] = [];
	}
}

// regroup
if(sizeof($users)>0) {
	foreach ($users as $z) {
		$cert_tenant_groups[$z->t_id][] = $z;
	}
}


// set text for no certs
$no_user_text = "No users available";



// body
if(sizeof($tenants)==0) {
	$Result->show("info", $url_items["tenants"]['icon']." "._("No users available").".");
}
else {
	print '<div class="page-body">';

	foreach ($cert_tenant_groups as $tenant_id=>$group) {

		print "<div class='card' style='margin-bottom:20px;padding:0px'>";

		// title = for admins
		if($user->admin=="1") {
			$tenant_href = htmlspecialchars($tenants[$tenant_id]->href, ENT_QUOTES, 'UTF-8');
			print "<div class='card-header'>";
			print "	<h3 class='h4'>".$url_items["tenants"]['icon']._("Tenant")." <a href='/".$user->href."/tenants/".$tenant_href."/'>".$tenants[$tenant_id]->name."</a> </h3>";
			print '<div class="card-actions">';
			print '<a href="/route/modals/users/edit.php?action=add&tenant='.$tenant_href.'" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm text-green bg-info-lt btn-sm btn-5 d-none d-sm-inline-block" style="float:right">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> Create new user
                  </a>';
			print "</div>";
			print "</div>";
		}
		// body
		print "<div class='card-body row'>";

		if(sizeof($group)==0) {
			print "<div class='alert alert-info'>".$url_items["users"]['icon']." "._($no_user_text)."</div>";
		}
		else {
			foreach ($group as $u) {
				$u_tenant_href = htmlspecialchars(isset($tenants[$u->t_id]) ? $tenants[$u->t_id]->href : $user->href, ENT_QUOTES, 'UTF-8');
				$u_id          = (int)$u->id;
				print '
				<div class="col col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
				<div class="card text-center" style="padding:0px;padding-top:10px;">
                    <h3 class="m-0 mb-1"><a href="/'.$u_tenant_href.'/users/'.$u_id.'/" style="color:inherit">'.htmlspecialchars($u->name, ENT_QUOTES, 'UTF-8').'</a></h3>
                    <div class="text-secondary">'.htmlspecialchars($u->email, ENT_QUOTES, 'UTF-8').'</div>
                    <div class="mt-2">
                      <span class="badge badge-outline text-red">'._($User->get_permissions_nice($u->permission)).'</span>
                    </div>
                    <div class="mt-2">
                      <span class="badge bg-info-lt" data-bs-toggle="tooltip" title="'._("Days before expiry to show warning").'">'.$u->days." "._("Days").'</span>
                      <span class="badge bg-info-lt" data-bs-toggle="tooltip" title="'._("Days after expiry to show warning").'">'.$u->days_expired." "._("Days").'</span>
                    </div>

					<div class="d-flex" style="margin-top:10px;">
                    <a href="/route/modals/users/edit.php?action=edit&tenant='.$u_tenant_href.'&id='.$u_id.'" data-bs-toggle="modal" data-bs-target="#modal1" class="card-btn" style="padding:0.6rem">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>
                      Edit</a>
                    <a href="/route/modals/users/edit.php?action=delete&tenant='.$u_tenant_href.'&id='.$u_id.'" data-bs-toggle="modal" data-bs-target="#modal1" class="card-btn" style="padding:0.6rem">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                      Delete</a>'.
                    ($user->admin === "1" && !isset($_SESSION['impersonate_original']) && $u->email !== $user->email ? '
                    <a href="/'.$user->href.'/user/impersonate/'.$u_id.'/" class="card-btn text-warning" style="padding:0.6rem">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-switch-3"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17h5l1.67 -2.386m3.66 -5.227l1.67 -2.387h6" /><path d="M18 4l3 3l-3 3" /><path d="M3 7h5l7 10h6" /><path d="M18 20l3 -3l-3 -3" /></svg>
                      Impersonate</a>' : '').'
                  </div>
                  </div>
                  </div>';
			}
		}

		print "</div>";
		print "</div>";
	}

	print "</div>";
}
