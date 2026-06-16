<?php

#
# Nmap network scan — request form
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session(false, true, false);
# RWA or system admin required
$User->validate_user_permissions(3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant(true);

# strip tags
$_GET = $User->strip_input_tags($_GET);

# fetch zone
$zone   = $Zones->get_zone($_GET['tenant'], $_GET['zone_name']);
$tenant = $Tenants->get_tenant_by_href($_GET['tenant']);

$title = _("Scan hosts");

if ($zone === null) {
    $content      = [$Result->show("danger", _("Invalid zone"), false, false, true)];
    $header_class = "danger";
    $btn_text     = "";
} else {
    $Nmap = new Nmap($Database);
    $nmap_available = file_exists($Nmap->nmap_path) && is_executable($Nmap->nmap_path);

    $title        = _("Scan hosts") . " :: " . $zone->name;
    $header_class = "success";
    $btn_text     = _("Request scan");
    $content      = [];

    if (!$nmap_available) {
        $content[] = $Result->show("warning",
            sprintf(_("nmap binary not found at %s. Install nmap or update \$nmap_path in config.php."), htmlspecialchars($Nmap->nmap_path)),
            false, false, true);
        $btn_text     = "";
        $header_class = "warning";
    }
    else {
        $ports = $SSL->get_all_port_groups();

        $content[] = "<form id='modal-form'>";
        $content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
        $content[] = "<input type='hidden' name='tenant'    value='" . htmlspecialchars($_GET['tenant']) . "'>";
        $content[] = "<input type='hidden' name='zone_id'   value='" . (int) $zone->id . "'>";

        $content[] = "<table class='table table-condensed table-borderless align-middle'>";

        // prefix
        $content[] = "<tr>";
        $content[] = "  <th style='width:160px'>" . _("IP prefix") . "</th>";
        $content[] = "  <td>";
        $content[] = "    <input type='text' class='form-control form-control-sm' name='prefix' placeholder='10.0.0.0/24' maxlength='19'>";
        $content[] = "    <small class='text-muted'>" . _("IPv4 CIDR, minimum /16 (e.g. 10.0.0.0/24)") . "</small>";
        $content[] = "  </td>";
        $content[] = "</tr>";

        // port group
        $content[] = "<tr>";
        $content[] = "  <th>" . _("Port group") . "</th>";
        $content[] = "  <td>";
        $content[] = "    <select name='pg_id' class='form-select form-select-sm'>";
        if (!empty($ports[$tenant->id])) {
            foreach ($ports[$tenant->id] as $id => $p) {
                $ports_label = implode(', ', $p['ports']);
                $content[] = "      <option value='{$id}'>" . htmlspecialchars($p['name']) . " &mdash; " . htmlspecialchars($ports_label) . "</option>";
            }
        }
        $content[] = "    </select>";
        $content[] = "    <small class='text-muted'>" . _("Port group defines which SSL ports to discover and monitor.") . "</small>";
        $content[] = "  </td>";
        $content[] = "</tr>";

        // PTR lookup
        $content[] = "<tr>";
        $content[] = "  <th>" . _("PTR lookup") . "</th>";
        $content[] = "  <td>";
        $content[] = "    <label class='form-check form-switch'>";
        $content[] = "      <input class='form-check-input' type='checkbox' name='ptr_lookup' value='1'>";
        $content[] = "      <span class='form-check-label'>" . _("Resolve PTR records and add hostnames too") . "</span>";
        $content[] = "    </label>";
        $content[] = "  </td>";
        $content[] = "</tr>";

        // notify email
        $content[] = "<tr>";
        $content[] = "  <th>" . _("Notify email") . "</th>";
        $content[] = "  <td>";
        $content[] = "    <input type='email' class='form-control form-control-sm' name='notify_email' placeholder='" . _("Optional — leave blank to skip") . "'>";
        $content[] = "    <small class='text-muted'>" . _("An email summary will be sent here when the scan completes.") . "</small>";
        $content[] = "  </td>";
        $content[] = "</tr>";

        $content[] = "</table>";
        $content[] = "</form>";
    }
}

# print modal
$Modal->modal_print($title, implode("\n", $content), $btn_text, "/route/modals/zones/nmap-scan-submit.php", false, $header_class);
?>
