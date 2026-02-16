<div class='header text-center'>
	<h2 class="page-title d-flex justify-content-center" style="margin-top:60px;margin-bottom: 40px;">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="navbar-brand-image">
			<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 21h-4.5a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v.5" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /><path d="M15 19l2 2l4 -4" /></svg>
        php-ssl :: <?php print _("Login"); ?>
    </h2>
</div>


<div class="container container-tight py-4">
	<div class="card card-md">
        <div class="card-body">
            <!-- <h2 class="h2 text-center mb-4">Login to your account</h2> -->
            <form action="./" method="get" autocomplete="off" novalidate="" id="login" name="login">

                <div class="mb-3">
                    <label class="form-label"><?php print _('E-Mail address'); ?></label>
                    <input type="text" class="form-control" placeholder="<?php print _('Your E-Mail'); ?>" name="username" id="username"  autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                </div>

                <div class="mb-2">
                    <label class="form-label"><?php print _('Password'); ?></label>
                    <div class="input-group input-group-flat">
                        <input type="password" id="password" class="form-control" placeholder="<?php print _('Your password'); ?>" name="password" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                </div>


                <?php
                // fetch all active domains
                $domains = $User->get_active_domains ();
                // if only one ignore
                if (sizeof($domains)==1) {
                    print '<input type="hidden" class="form-control" name="domain" value="'.$domains[0]->id.'">';
                }
                else {
                ?>
                <div class="mb-3">
                    <label class="form-label">Domain</label>
                    <select class="form-select" name="domain">
                    <?php
                    // print
                    foreach ($domains as $d) {
                        print "<option value='$d->id'>$d->name</option>";
                    }
                    ?>
                    </select>
                </div>
                <?php } ?>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100"><?php print _("Sign in"); ?></button>
                </div>

                <!-- Logout -->
                <div class="mb-2" id="loginCheck" style="margin-top:30px;margin-bottom:0px;">
					<?php
					# deauthenticate user
					if ( $User->is_authenticated(false) ) {
						$Result->show("success", _('You have logged out'));
						# destroy session
						$User->destroy_session();
					}
					?>
                </div>


            </form>
        </div>

        <div class="hr-text">info</div>

        <div class="card-body text-muted justify-content-center">
            <?php print _('Please enter your email and password to login to system').". "._('In case of any issues please contact'); ?> <a href='mailto:<?php print $mail_sender_settings->email; ?>'><?php print _('system administrator'); ?></a>.
        </div>
    </div>
</div>