<?php
namespace App\Utils;

class Paginator {
    public $totalCount;
    public $perPage;
    public $current;
    public $total;
    public $baseUrl;
    
    public $queryString; 

    public function __construct($totalCount, $perPage, $current, $baseUrl, $queryString = '', $langurl = null) {
        $this->totalCount = $totalCount;
        $this->perPage = $perPage;
        $this->current = max(1, (int)$current);
        
        // 【修改】如果傳入 langurl，使用它；否則使用 BASE_PATH
        if ($langurl !== null) {
            $this->baseUrl = $langurl . $baseUrl;
        } else {
            $this->baseUrl = BASE_PATH . $baseUrl;
        }
        
        $this->queryString = $queryString;
        
        $this->total = ceil($totalCount / $perPage);
    }

    public function url($page) {
        return $this->baseUrl . $page . $this->queryString;
    }

    public function prev() {
        return ($this->current > 1) ? $this->url($this->current - 1) : null;
    }

    public function next() {
        return ($this->current < $this->total) ? $this->url($this->current + 1) : null;
    }

    public function items($range = 2) {
        $rawList = [];
        
        if ($this->total <= 1) {
            $rawList = [1];
        } else {
            for ($i = 1; $i <= $this->total; $i++) {
                if ($i == 1 || $i == $this->total || ($i >= $this->current - $range && $i <= $this->current + $range)) {
                    $rawList[] = $i;
                }
            }
        }

        $structuredList = [];
        $lastNum = 0;

        foreach ($rawList as $num) {
            if ($lastNum > 0 && $num - $lastNum > 1) {
                $structuredList[] = [
                    'text'  => '...',
                    'url'   => 'javascript:void(0);', 
                    'class' => '', 
                ];
            }

            $isActive = ($num == $this->current);
            
            $structuredList[] = [
                'text'  => str_pad($num, 2, '0', STR_PAD_LEFT),
                'url'   => $this->url($num), 
                'class' => $isActive ? 'active ' : '',
            ];

            $lastNum = $num;
        }

        return $structuredList;
    }
}
/*
<?php if ($prevUrl = $pages->prev()): ?>
    <a href="<?= $prevUrl ?>">上一頁</a>
<?php else: ?>
    <span class="disabled">上一頁</span>
<?php endif; ?>

<?php foreach ($pages->items() as $item): ?>
    <a href="<?= $item['url'] ?>" class="<?= $item['class'] ?>">
        <?= $item['text'] ?>
    </a>
<?php endforeach; ?>

<?php if ($nextUrl = $pages->next()): ?>
    <a href="<?= $nextUrl ?>">下一頁</a>
<?php else: ?>
    <span class="disabled">下一頁</span>
<?php endif; ?>
*/