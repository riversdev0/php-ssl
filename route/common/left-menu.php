    <div class="container-fluid">

        <!-- BEGIN NAVBAR TOGGLER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- END NAVBAR TOGGLER -->

        <!-- BEGIN NAVBAR LOGO -->
        <div class="navbar-brand navbar-brand-autodark">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="navbar-brand-image">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 21h-4.5a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v.5" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /><path d="M15 19l2 2l4 -4" /></svg>
            php-ssl
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
