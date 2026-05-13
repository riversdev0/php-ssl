<?php
// validate session
$User->validate_session();
// get all tenants
$all_tenants = $Tenants->get_all();
// fetch all CAs (no pkey filter)
$select = "SELECT ca.id, ca.t_id, ca.name, ca.subject, ca.expires, ca.created, ca.parent_ca_id,
           ca.ignore_updates, ca.ignore_expiry,
           pca.name AS parent_ca_name,
           (pk.id IS NOT NULL AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '') AS has_pkey,
           (SELECT COUNT(*) FROM certificates c WHERE c.aki = ca.ski AND c.t_id = ca.t_id) AS cert_count
           FROM cas ca
           LEFT JOIN pkey pk ON ca.pkey_id = pk.id
           LEFT JOIN cas pca ON ca.parent_ca_id = pca.id"
         . ($user->admin !== "1" ? " WHERE ca.t_id = " . (int)$user->t_id : "")
         . " ORDER BY ca.name ASC";
$all_cas = $Database->getObjectsQuery($select, []);

$groups = [];
if ($user->admin === "1") {
	foreach ($all_tenants as $t) { $groups[$t->id] = []; }
}
foreach ($all_cas as $ca) { $groups[$ca->t_id][] = $ca; }

// render tab nav (same as all.php)
if (!isset($from_search)) { ?>
<div class="page-header">
	<h2 class="page-title"><?php print $url_items['certificates']['icon'] . " " . _("Certificates"); ?></h2>
	<hr>
</div>
<p class='text-secondary'><?php print _("Certificate Authorities discovered from scanned certificate chains."); ?></p>
<div>
<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>
<br><br>
<?php } ?>

<?php
print '<div class="card">';
print '<div class="card-header">';
print '<ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">';
$current_app = "cas";
foreach ($url_items["certificates"]["submenu"] as $k => $m) {
	$active = $current_app === $k ? "active" : "";
	if     ($k === "expire_soon") { $textcol = "orange"; }
	elseif ($k === "expired")     { $textcol = "red"; }
	elseif ($k === "orphaned")    { $textcol = "info"; }
	elseif ($k === "imported")    { $textcol = "purple"; }
	elseif ($k === "pkeys")       { $textcol = "green"; }
	elseif ($k === "cas")         { $textcol = "azure"; }
	else                          { $textcol = "light"; }
	print '<li class="nav-item">';
	print '	<a class="nav-link ' . $active . '" href="/' . $user->href . '/certificates/' . $k . '/"><span class="text-' . $textcol . '">' . $url_items['certificates']['icon'] . '</span> ' . _($m['title']) . '</a>';
	print '</li>';
}
print "</ul>";
print '</div>';
print '<div class="card-body" style="padding-left:0px;padding-right:0px">';

$show_manage_actions = false;
include(dirname(__FILE__) . "/../cas/table.php");

print '</div>';
print '</div>';
?>
