<?php

function displayPages($pageNum, $queryString, $totalPages, $totalRows, $currentPage) {
    if ($totalPages <= 0) return;

    echo '<ul class="pagination pagination-modern pagination-modern-spacing justify-content-center">';

    // Previous Button
    if ($pageNum > 0) {
        $prevPage = max(0, $pageNum - 1);
        echo '<li class="page-item previous"><a class="page-link" href="' . sprintf("%s?pageNum=%d%s", $currentPage, $prevPage, $queryString) . '"><i class="fas fa-chevron-left"></i></a></li>';
    } else {
        echo '<li class="page-item previous disabled"><a class="page-link" href="javascript:void(0);"><i class="fas fa-chevron-left"></i></a></li>';
    }

    // Determine the range of pages to show
    $startPage = max(0, $pageNum - 2);
    $endPage = min($totalPages, $pageNum + 2);

    if ($startPage > 0) {
        echo '<li class="page-item"><a class="page-link" href="' . sprintf("%s?pageNum=0%s", $currentPage, $queryString) . '">1</a></li>';
        if ($startPage > 1) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $pageNum) {
            echo '<li class="page-item active"><a class="page-link" href="javascript:void(0);">' . ($i + 1) . '</a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="' . sprintf("%s?pageNum=%d%s", $currentPage, $i, $queryString) . '">' . ($i + 1) . '</a></li>';
        }
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        echo '<li class="page-item"><a class="page-link" href="' . sprintf("%s?pageNum=%d%s", $currentPage, $totalPages, $queryString) . '">' . ($totalPages + 1) . '</a></li>';
    }

    // Next Button
    if ($pageNum < $totalPages) {
        $nextPage = min($totalPages, (int)$pageNum + 1);
        echo '<li class="page-item next"><a class="page-link" href="' . sprintf("%s?pageNum=%d%s", $currentPage, $nextPage, $queryString) . '"><i class="fas fa-chevron-right"></i></a></li>';
    } else {
        echo '<li class="page-item next disabled"><a class="page-link" href="javascript:void(0);"><i class="fas fa-chevron-right"></i></a></li>';
    }

    echo '</ul>';
}
?>