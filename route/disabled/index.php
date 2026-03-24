<?php

#
# Shown when the logged-in user's account is disabled.
# Renders a standalone page (no header/sidebar) with a logout link.
#

?>
<div class="container container-tight py-4" style="margin-top:10%">
  <div class="text-center mb-4">
    <h2 class="page-title d-flex justify-content-center">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M30.4 7.6C29.7 4.7 27.3 2.3 24.4 1.6 18.8 0.7 13.2 0.7 7.6 1.6 4.7 2.3 2.3 4.7 1.6 7.6 0.7 13.2 0.7 18.8 1.6 24.4 2.3 27.3 4.7 29.7 7.6 30.4c5.6 0.9 11.2 0.9 16.8 0C27.3 29.7 29.7 27.3 30.4 24.4c0.9-5.6 0.9-11.2 0-16.8z" fill="#066fd1"/>
              <g transform="translate(4.7, 4.7) scale(0.94)">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 8v-2a2 2 0 0 1 2 -2h2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M4 16v2a2 2 0 0 0 2 2h2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 20h2a2 2 0 0 0 2 -2v-2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              </g>
            </svg>
      <span>php-ssl-scan</span>
    </h2>
  </div>
  <div class="card card-md">
    <div class="card-body text-center">
      <h2 class="mb-3"><?php print _("Your account is not activated or disabled!"); ?></h2><hr>
      <p class="text-secondary mb-4"><?php print _("Please wait for system administrator to enable your account."); ?></p>
      <a href="/logout/" class="btn btn-sm btn-outline-secondary"><?php print _("Log out"); ?></a>
    </div>
  </div>
</div>
