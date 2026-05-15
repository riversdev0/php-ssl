<?php
// validate session
$User->validate_session();

// Dispatch to CA detail page when a CA serial/id is in the URL
if (isset($_params['app']) && $_params['app'] !== '') {
	include(dirname(__FILE__) . '/ca-certificates/ca-certificate.php');
	return;
}

// get all tenants
$all_tenants = $Tenants->get_all();
$where  = $user->admin === "1" ? " WHERE" : " WHERE ca.t_id = " . (int)$user->t_id . " AND";
$select = "SELECT ca.id, ca.t_id, ca.name, ca.subject, ca.expires, ca.created, ca.parent_ca_id,
           ca.ignore_updates, ca.ignore_expiry, ca.serial,
           pca.name AS parent_ca_name,
           (pk.id IS NOT NULL AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '') AS has_pkey,
           (SELECT COUNT(*) FROM certificates c WHERE c.aki = ca.ski AND c.t_id = ca.t_id) AS cert_count
           FROM cas ca
           LEFT JOIN pkey pk ON ca.pkey_id = pk.id
           LEFT JOIN cas pca ON ca.parent_ca_id = pca.id"
         . $where . " (pk.id IS NOT NULL AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '')"
         . " ORDER BY ca.name ASC";
$all_cas = $Database->getObjectsQuery($select, []);

$groups = [];
if ($user->admin === "1") {
	foreach ($all_tenants as $t) { $groups[$t->id] = []; }
}
foreach ($all_cas as $ca) { $groups[$ca->t_id][] = $ca; }
?>

<div class="page-header">
	<h2 class="page-title">
		<?php print $url_items['cas']['icon']; ?>
		<?php print _("Certificate Authorities"); ?>
	</h2>
	<hr>
</div>

<p class='text-secondary'><?php print _("CA and intermediate CA certificates used to sign CSR requests."); ?></p>

<div style="margin-bottom:10px">
	<a href="/route/modals/cas/create.php"
	   class="btn btn-sm bg-green-lt text-green <?php print $user->actions_disab1led; ?>"
	   data-bs-toggle="modal" data-bs-target="#modal1">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
		<?php print _("Create CA"); ?>
	</a>
	<a href="/route/modals/cas/import.php"
	   class="btn btn-sm bg-info-lt text-info <?php print $user->actions_disab1led; ?>"
	   data-bs-toggle="modal" data-bs-target="#modal1">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>
		<?php print _("Import CA"); ?>
	</a>
</div>

<?php
$show_manage_actions = true;
$ca_link_target = $_params['tenant'] . '/cas';
include("table.php");
?>
