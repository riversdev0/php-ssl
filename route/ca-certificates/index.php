<?php
$User->validate_session();

// Dispatch to CA detail page when a serial/id is in the URL
if (isset($_params['app']) && $_params['app'] !== '') {
    include('ca-certificate.php');
    return;
}

$all_tenants = $Tenants->get_all();

$where  = $user->admin !== "1" ? " WHERE ca.t_id = " . (int)$user->t_id : "";
$select = "SELECT ca.id, ca.t_id, ca.name, ca.subject, ca.expires, ca.created, ca.parent_ca_id,
           ca.ignore_updates, ca.ignore_expiry, ca.serial,
           pca.name AS parent_ca_name,
           (pk.id IS NOT NULL AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '') AS has_pkey,
           (SELECT COUNT(*) FROM certificates c WHERE c.aki = ca.ski AND c.t_id = ca.t_id) AS cert_count
           FROM cas ca
           LEFT JOIN pkey pk ON ca.pkey_id = pk.id
           LEFT JOIN cas pca ON ca.parent_ca_id = pca.id"
         . $where
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
        <?php print $url_items['ca-certificates']['icon']; ?>
        <?php print _("CA Certificates"); ?>
    </h2>
    <hr>
</div>

<p class='text-secondary'><?php print _("All certificate authorities discovered from scanned certificate chains."); ?></p>

<?php
$show_manage_actions = false;
$ca_link_target = $_params['tenant'] . '/ca-certificates';
include(dirname(__FILE__) . "/../cas/table.php");
?>
