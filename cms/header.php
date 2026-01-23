<?php
require_once(__DIR__ . '/includes/categoryHelper.php');
?>
<!-- start: header -->
<header class="header">
    <div class="logo-container">
        <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html"
            data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
        </div>
    </div>

    <!-- start: search & user box -->
    <div class="header-right">

        <div class="userbox">
            <?php
            if(CMS_LOGOUT_TIME > 60){
            ?>
            <div class="">
                <span>登出時間 : </span>
                <span id="time-countdown"></span>
            </div>
            <?php }?>
        </div>

        <span class="separator"></span>

        <div class="" style="display: inline-block;">
            <div class="">
                <a href="../" target="_blank">
                    觀看首頁 
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </div>
        </div>

        <span class="separator"></span>

        <div id="userbox" class="userbox">
            <a href="#" data-bs-toggle="dropdown">
                <figure class="profile-picture">
                    <img src="template-style/img/!logged-user.jpg" alt="Joseph Doe" class="rounded-circle"
                        data-lock-picture="img/!logged-user.jpg" />
                </figure>
                <?php if (isset($_SESSION['MM_LoginAccountUsername'])): ?>
                    <div class="profile-info" data-lock-name="John Doe" data-lock-email="johndoe@okler.com">
                        <span class="name"><strong><?php echo htmlspecialchars($_SESSION['MM_LoginAccountUsername']); ?></strong></span>
                        <?php 
                        if (isset($_SESSION['MM_UserGroupId'])): 
                        $authorityGroups = getCategoryOptions('authorityCate');
                        $groupName = $_SESSION['MM_UserGroupId'];

                        if (!empty($authorityGroups)) {
                            foreach ($authorityGroups as $group) {
                                if ($_SESSION['MM_UserGroupId'] == 999) {
                                    $groupName = '超級管理員';
                                    break;
                                }
                                if ($group['id'] == $_SESSION['MM_UserGroupId']) {
                                    $groupName = $group['name'];
                                    break;
                                }
                            }
                        }
                        ?>
                            <span class="role mt-1"><?php echo htmlspecialchars($groupName); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <i class="fa custom-caret"></i>
            </a>

            <div class="dropdown-menu">
                <ul class="list-unstyled mb-2">
                    <li class="divider"></li>
                    <li>
                        <a role="menuitem" tabindex="-1" href="<?=PORTAL_AUTH_URL?>tpl=languageType/list"><i class="bx bx-text"></i>語系列表</a>
                    </li>
                    <li>
                        <a role="menuitem" tabindex="-1" href="<?php echo $logoutAction ?>"><i class="bx bx-power-off"></i>Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- end: search & user box -->
</header>
<!-- end: header -->