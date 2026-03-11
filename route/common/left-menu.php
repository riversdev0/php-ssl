    <div class="container-fluid">

        <!-- BEGIN NAVBAR TOGGLER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- END NAVBAR TOGGLER -->

        <!-- BEGIN NAVBAR LOGO -->
        <div class="navbar-brand navbar-brand-autodark" style="justify-content:left;padding-left:10px">
            <?php if($_SESSION['theme']!="dark") { ?>
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
            <?php } else { ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M30.4 7.6C29.7 4.7 27.3 2.3 24.4 1.6 18.8 0.7 13.2 0.7 7.6 1.6 4.7 2.3 2.3 4.7 1.6 7.6 0.7 13.2 0.7 18.8 1.6 24.4 2.3 27.3 4.7 29.7 7.6 30.4c5.6 0.9 11.2 0.9 16.8 0C27.3 29.7 29.7 27.3 30.4 24.4c0.9-5.6 0.9-11.2 0-16.8z" fill="white"/>
              <g transform="translate(4.7, 4.7) scale(0.94)">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 8v-2a2 2 0 0 1 2 -2h2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M4 16v2a2 2 0 0 0 2 2h2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 20h2a2 2 0 0 0 2 -2v-2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              </g>
            </svg>
            <?php } ?>


            php-ssl-scan
            <span class="badge  ms-2" style="font-size:10px;font-weight:500;vertical-align:middle">v<?php global $version; print htmlspecialchars($version, ENT_QUOTES); ?></span>
        </div>
        <!-- END NAVBAR LOGO -->

        <hr style='padding:0px;margin:0px;margin-left:10px'>

        <div class="collapse navbar-collapse" id="sidebar-menu">
        <!-- BEGIN NAVBAR MENU -->
        <ul class="navbar-nav pt-lg-3">

            <?php
            // print items - headers
            foreach ($url_items as $title=>$items) {

                // dont show ?
                if(isset($items['show'])) {
                    if ($items['show']==false) {
                        continue;
                    }
                }

                // active main menu
                $active = $_params['route'] == $title ? "active" : "";

                // divider
                if (isset($items['mtitle']))
                print "<h3 class='page-title'>".$items['mtitle']."</h3>";


                // Single items
                if (!isset($items['submenu'])) {
                    // link
                    print '<li class="nav-item '.$active.'">';
                    print ' <a class="nav-link"  href="/'.$user->href.'/'.$items['href'].'/">';
                    print '     <span class="nav-link-icon d-md-none d-lg-inline-block">';
                    print '     '.$items['icon'];
                    print '     </span>';
                    print '     <span class="nav-link-title">'.$items['title'].'</span>';
                    print '</a>';
                    print '</li>';

                }
                // dropdown
                else {
                    // master is selected ?
                    if ($active) {
                        $expanded = "true";
                        $show = "show";
                    }
                    else {
                        // expand ?
                        $expanded = "false";
                        $show = "";
                        foreach ($items['submenu'] as $link=>$sm) {
                            if($_params['app'] == $link) {
                                $expanded = "true";
                                $show = "show";
                                break;
                            }
                        }
                    }


                    print '<li class="nav-item dropdown '.$active.'">';
                    print ' <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="'.$expanded.'">';
                    print '     <span class="nav-link-icon d-md-none d-lg-inline-block">';
                    print '     '.$items['icon'];
                    print '     </span>';
                    print '     <span class="nav-link-title">'.$items['title'].'</span>';
                    print '</a>';
                    print '<div class="dropdown-menu '.$show.'">';
                    print '<div class="dropdown-menu-columns">';
                    print '<div class="dropdown-menu-column">';
                    foreach ($items['submenu'] as $link=>$sm) {
                        // active ?
                        $active2 = $_params['app'] == $link ? "active" : "";
                        // icon
                        if(isset($sm['icon'])) {
                            $icon = $sm['icon'];
                        }
                        else {
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>';
                        }
                        // print
                        print ' <a class="dropdown-item '.$active2.'" href="/'.$user->href.'/'.$items['href'].'/'.$link.'/">'.$icon.' '.$sm['title'].'</a>';
                    }
                    print '</div>';
                    print '</div>';
                    print '</li>';
                }
            }
            ?>

        </ul>
        <!-- END NAVBAR MENU -->
      </div>
    </div>
