<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html class="sidebar-left-big-icons">
<head>
    <title><?php require_once('cmsTitle.php'); ?></title>
    <?php require_once('head.php'); ?>
    <?php require_once('script.php'); ?>
</head>
<body>
    <section class="body">
        <!-- start: header -->
        <?php require_once('header.php'); ?>
        <!-- end: header -->

        <div class="inner-wrapper">
            <!-- start: sidebar -->
            <?php require_once('sidebar.php'); ?>
            <!-- end: sidebar -->

            <section role="main" class="content-body">
                <header class="page-header">
                    <h2>儀錶板</h2>

                    <div class="right-wrapper text-end">
                        <ol class="breadcrumbs">
                            <li>
                                <a href="<?=PORTAL_AUTH_URL?>dashboard">
                                    <i class="bx bx-home-alt"></i>
                                </a>
                            </li>
                        </ol>

                        <a class="sidebar-right-toggle" data-open="sidebar-right" style="pointer-events: none;"></a>
                    </div>
                </header>

                <div class="row">
                    <?php
                    // 根據 config 決定是否顯示 (總開關)
                    if (defined('SHOW_CONTACT_WIDGET') && SHOW_CONTACT_WIDGET):
                        
                        // 1. 掃描所有設定檔，找出 viewOnly => true 的模組
                        $moduleWidgets = [];
                        $setDir = __DIR__ . '/set/';
                        $setFiles = glob($setDir . '*Set.php');
                        
                        foreach ($setFiles as $file) {
                            $config = require $file; // 載入配置
                            
                            // 檢查是否為「表單型」模組 (viewOnly = true)
                            if (isset($config['viewOnly']) && $config['viewOnly'] === true) {
                                $moduleName = $config['moduleName'] ?? '未命名模組';
                                $moduleKey = $config['module'] ?? '';
                                $tableName = $config['tableName'] ?? '';
                                
                                // 判斷已讀/未讀欄位 (預設 m_read)
                                $readCol = $config['cols']['read'] ?? 'm_read';
                                
                                // 判斷是否有過濾條件 (menuKey/menuValue)
                                $menuKey = $config['menuKey'] ?? null;
                                $menuValue = $config['menuValue'] ?? null;
                                
                                // 查詢未讀數量
                                if ($tableName && $readCol) {
                                    try {
                                        $cntSql = "SELECT COUNT(*) FROM {$tableName} WHERE {$readCol} = 0";
                                        $params = [];
                                        
                                        // 【新增】加入過濾條件
                                        if ($menuKey && $menuValue !== null) {
                                            $cntSql .= " AND {$menuKey} = :val";
                                            $params[':val'] = $menuValue;
                                        }
                                        
                                        // 使用 prepare/execute 避免 SQL Injection (雖然 config 可信，但習慣較好)
                                        $cntStmt = $conn->prepare($cntSql);
                                        $cntStmt->execute($params);
                                        $unreadCount = $cntStmt->fetchColumn();
                                        
                                        // 只有在有未讀訊息時顯示？或是一直顯示已讀0？
                                        // 這裡選擇全部顯示，讓使用者方便知道有這些模組
                                        $moduleWidgets[] = [
                                            'title' => $moduleName,
                                            'count' => $unreadCount,
                                            'link' => PORTAL_AUTH_URL . "tpl={$moduleKey}/list",
                                            'icon' => 'fas fa-envelope'
                                        ];
                                        
                                    } catch (Exception $e) {
                                        // 忽略查詢錯誤
                                    }
                                }
                            }
                        }
                        
                        // 2. 輸出 Widget
                        foreach ($moduleWidgets as $widget):
                        ?>
                        <div class="col-md-4 col-lg-12 col-xl-4">
                            <section class="card card-featured-left card-featured-primary mb-3">
                                <div class="card-body">
                                    <div class="widget-summary">
                                        <div class="widget-summary-col widget-summary-col-icon">
                                            <div class="summary-icon bg-primary">
                                                <i class="<?php echo $widget['icon']; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="widget-summary-col">
                                            <div class="summary">
                                                <h4 class="title"><?php echo htmlspecialchars($widget['title']); ?> (未讀)</h4>
                                                <div class="info">
                                                    <strong class="amount <?php echo ($widget['count'] > 0) ? 'text-danger' : ''; ?>"><?php echo $widget['count']; ?></strong>
                                                </div>
                                            </div>
                                            <div class="summary-footer">
                                                <a class="text-muted text-uppercase" href="<?php echo $widget['link']; ?>">查看列表 (View All)</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                        <?php endforeach; ?>
                        
                    <?php endif; ?>
                </div>
                <div class="row">
                    
                </div>
                <!-- end: page -->
            </section>
        </div>
    </section>
</body>
</html>