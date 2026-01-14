<?php if(!isset($from_search)) { ?>
<div class="page-header">
	<h2 class="page-title"><?php print $url_items['certificates']['icon']." "._("Certificates"); ?></h2>
	<hr>
</div>

<p class='text-secondary'><?php print _('List of all available certificates in system'); ?>.</p>


<!-- back -->
<div>
<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>
<br><br>


<?php

# menu
print '<div class="card">';
print '<div class="card-header">';
print '<ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">';
foreach ($url_items["certificates"]["submenu"] as $k=>$m) {

	$active = $_params['app']==$k ? "active" : "";


	if($k=="expire_soon") 		{ $textcol = "orange"; }
	elseif($k=="expired") 		{ $textcol = "red"; }
	elseif($k=="orphaned") 		{ $textcol = "info"; }
	elseif($k=="ignored") 		{ $textcol = "default"; }
	else 						{ $textcol = "light"; }


	print '<li class="tnav-item '.$active.'">';
	print '	<a class="nav-link '.$active.'" aria-current="page" href="/'.$user->href.'/certificates/'.$k.'/"><span class="text-'.$textcol.'">'.$url_items['certificates']['icon'].'</span> '._($m['title']).'</a>';
	print '</li>';
}
print "</ul>";
print '</div>';


print '<div class="card-body" style="padding-left:0px;padding-right:0px">';

?>

<?php

// orphaned ?
$orphaned_also = $_params['app']=="orphaned" ? true : false;

# fetch certificates
$certificates = $Certificates->get_all ($orphaned_also);

}

# tenants
$tenants = $Tenants->get_all ();
// regrouped certs
$cert_tenant_groups = [];

// set text for no certs
$no_cert_text = "No certificates";

// remove ones that do not expire for expire subpage
if($_params['app']=="expire_soon") {
	$no_cert_text = "No certificates found that will expire soon";
	foreach ($certificates as $k=>$c) {
		$parsed = $Certificates->parse_cert ($c->certificate);
		if($Certificates->get_status_int ($parsed)!==2) {
			unset($certificates[$k]);
		}
	}
}
// remove ones that do not expire for expire subpage
if($_params['app']=="expired") {
	$no_cert_text = "No expired certificates found";
	foreach ($certificates as $k=>$c) {
		$parsed = $Certificates->parse_cert ($c->certificate);
		if($Certificates->get_status_int ($parsed)!==1) {
			unset($certificates[$k]);
		}
	}
}
// only leave orphaned hosts if required
elseif($_params['app']=="orphaned") {
	$no_cert_text = "No orphaned certificates found";
	foreach ($certificates as $k=>$c) {
		$hosts = $Certificates->get_certificate_hosts ($c->id);
		if(sizeof($hosts)>0) {
			unset($certificates[$k]);
		}
	}
}

// create groups for admins
if($user->admin=="1" && !isset($from_search)) {
	foreach($tenants as $t) {
		$cert_tenant_groups[$t->id] = [];
	}
}

// regroup
if(sizeof($certificates)>0) {
	foreach ($certificates as $z) {
		$cert_tenant_groups[$z->t_id][] = $z;
	}
}

// show all hosts
if(@$_COOKIE['show_hosts']=="1") {
	$hide_hosts['text']    = "Shrink";
	$hide_hosts['class']   = "shrink_hosts";
	$hide_hosts['icon']    = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-minimize"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 9l4 0l0 -4" /><path d="M3 3l6 6" /><path d="M5 15l4 0l0 4" /><path d="M3 21l6 -6" /><path d="M19 9l-4 0l0 -4" /><path d="M15 9l6 -6" /><path d="M19 15l-4 0l0 4" /><path d="M15 15l6 6" /></svg>';
	$hide_hosts['visible'] = "";
}
else {
	$hide_hosts['text']    = "Expand";
	$hide_hosts['class']   = "expand_hosts";
	$hide_hosts['icon']    = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-maximize"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 4l4 0l0 4" /><path d="M14 10l6 -6" /><path d="M8 20l-4 0l0 -4" /><path d="M4 20l6 -6" /><path d="M16 20l4 0l0 -4" /><path d="M14 14l6 6" /><path d="M8 4l-4 0l0 4" /><path d="M4 4l6 6" /></svg>';
	$hide_hosts['visible'] = "visually-hidden";
}

if(!isset($from_search)) {
print '<div class="text-end">';
print "	<a href='' class='btn btn-sm btn-outline-secondary ".$hide_hosts['class']."'>".$hide_hosts['icon']." "._($hide_hosts['text'])."</a>";
print '</div>';
}

print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-md table-sm' data-toggle='table' data-mobile-responsive='true' data-check-on-init='true' data-classes='table table-hover table-sm' data-cookie='true' data-cookie-id-table='certs' data-pagination='true' data-page-size='250' data-page-list='[50,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";




// header
print "<thead>";
print "<tr>";
print "	<th data-field='serial'>"._("Serial number")."</th>";
print "	<th data-field='status'>"._("Status")."</th>";
if($user->admin=="1" && isset($from_search))
print "	<th data-field='tenant' class='align-top d-none d-xl-table-cell'>"._("Tenant")."</th>";
print "	<th data-field='zone' class='align-top d-none d-xl-table-cell'>"._("Zone")."</th>";
print "	<th data-field='domain'>"._("Common name")."</th>";
print "	<th data-field='hosts' 	class='td-hosts ".$hide_hosts['visible']."'>"._("Hosts")."</th>";
print "	<th data-field='issuer' class='align-top d-none d-xl-table-cell'>"._("Issued by")."</th>";
print "	<th data-field='days'   class='align-top d-none d-xl-table-cell' data-width='30'>"._("Days")."</th>";
print "	<th data-field='valid'  class='align-top d-none d-xl-table-cell' data-width='150' data-width-unit='px'>"._("Valid to")."</th>";
print "	<th data-field='remove' data-width='30' data-width-unit='px' style='width:30px;'></th>";
print "</tr>";
print "</thead>";

print "<tbody>";
// body
if(sizeof($cert_tenant_groups)==0) {
	print "<tr>";
	print "	<td colspan=10> <div class='alert alert-info'>"._($no_cert_text).".</div></td>";
	print "</tr>";
}
else {
foreach ($cert_tenant_groups as $tenant_id=>$group) {

	$colspan = $user->admin=="1" && !isset($from_search) ? 11 : 10;

	if($user->admin=="1" && !isset($from_search)) {
		print "<tr class='header'>";
		print "	<td colspan=$colspan style='padding-top:25px'>".$url_items['tenants']['icon']." "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/'>".$tenants[$tenant_id]->name."</a></td>";
		print "</tr>";
	}

	if(sizeof($group)==0) {
		print "<tr>";
		print "	<td colspan=$colspan><div class='alert alert-info' style='margin-bottom:0px;'>".$url_items['certificates']['icon']." "._($no_cert_text).".</div></td>";
		print "</tr>";
	}
	else {
		foreach ($group as $t) {

			// parse cert
			$cert_parsed = $Certificates->parse_cert ($t->certificate);

			// get status
			$status = $Certificates->get_status ($cert_parsed, true);

			// get hosts for certificate
			$hosts = $Certificates->get_certificate_hosts ($t->id);

			// CN - array ?
			if(is_array($cert_parsed['subject']['CN'])) {
				$cert_parsed['subject']['CN_all'] = implode("<br>", $cert_parsed['subject']['CN']);
				$cert_parsed['subject']['CN']     = $cert_parsed['subject']['CN'][0];
			}
			else {
				$cert_parsed['subject']['CN_all'] = $cert_parsed['subject']['CN'];
			}


			$all_hosts = [];
			if(sizeof($hosts)>0) {
				foreach ($hosts as $h) {
					//status
					$h_status  = $Certificates->get_status ($cert_parsed, true, true, $h->hostname);
					$all_hosts[] = "&middot; ".$h->hostname." ".$h_status['text'];
				}
			}
			else {
				$all_hosts[] = "/";
			}

			// get status integer
			$status_int = $Certificates->get_status_int ($cert_parsed, false, "");

			// text class
			$danger_class = "";
			if($status_int==0)		{ $textclass=''; }
			elseif($status_int==1)	{ $textclass='red';  $danger_class = "red"; }
			elseif($status_int==2)	{ $textclass='orange'; $danger_class = "orange";  }
			elseif($status_int==3)	{ $textclass='green'; }
			else 					{ $textclass=''; }


			// add altnames
			$altnames = "";
			if(isset($cert_parsed['extensions']['subjectAltName'])) {
				$altnames = "<div class='visually-hidden'><hr style='background:#ccc'>";
				$parts = explode(",", $cert_parsed['extensions']['subjectAltName']);
				foreach ($parts as $part) {
					$p = explode(":", trim($part));
						$altnames .= $p[1]."<br>";
				}
				$altnames .= "</div>";
			}

			print "<tr>";


			if($cert_parsed['serialNumberHex']!="/") {
				$l = strlen($cert_parsed['serialNumberHex']);
				print "<td class='align-top'>";
				print "	<a class='btn btn-sm text-info text-$danger_class' href='/".$t->href."/certificates/".$t->zname."/".$cert_parsed['serialNumber']."/'>".$url_items['certificates']['icon']." ".$cert_parsed['serialNumberHex']."</a>";
				print "</td>";
			}
			else {
				print "	<td class='align-top d-none d-lg-table-cell'>".$cert_parsed['serialNumberHex']."</td>";
			}

			print "	<td class='align-top text-$danger_class'>".$status['text']."</td>";
			if($user->admin=="1" && isset($from_search))
			print "	<td class='align-top text-$danger_class d-none d-lg-table-cell '>".$t->name."</td>";
			print "	<td class='align-top text-$danger_class d-none d-lg-table-cell '><a href='/".$t->href."/zones/".$t->zname."/' target='_blank'>".$t->zname."</a></td>";
			print "	<td class='align-top text-$danger_class'>".$cert_parsed['subject']['CN_all']."".$altnames."</td>";
			print "	<td class='td-hosts ".$hide_hosts['visible']." align-top d-none d-lg-table-cell'><span class='text-muted'>".implode("<br>", $all_hosts)."</span></td>";

			print "	<td class='align-top d-none d-xl-table-cell text-muted'>".$cert_parsed['issuer']['O']."</td>";
			print "	<td class='align-top d-none d-xl-table-cell'><span class='badge text-$danger_class'>".$cert_parsed['custom_validDays']."</span></td>";
			print "	<td class='text-muted align-top d-none d-xl-table-cell'>".$cert_parsed['custom_validTo']."</td>";
			print "	<td class='align-top actions d-none d-lg-table-cell text-center'><a href='/route/zones/edit/delete_certificate.php?tenant=".$_params['tenant']."&serial=".$t->serial."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge badge-outline text-red' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Remove certificate")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>'."</td>";
			print "</tr>";
		}
	}
}
}
print "</tbody>";
print "</table>";
print "</div>";
?>
</div>