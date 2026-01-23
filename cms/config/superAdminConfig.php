<?php
/**
 * 超級管理員配置
 */

return array (
  'allowed_ips' => 
  array (
    0 => '127.0.0.1',
    1 => '::1',
    2 => '59.126.31.214',
  ),
  'super_admin' => 
  array (
    'user_id' => 999,
    'user_name' => 'SuperAdmin',
    'display_name' => '超級管理員',
    'group_id' => 999,
  ),
  'api_verification' => 
  array (
    'enabled' => true,
    // 'endpoint' => 'https://backedapi.gdlinode.tw/cms/api_verify_ip.php',
    'endpoint' => 'http://localhost/template-ver5/cms/api_verify_ip.php',
    'secret_key' => 'test-secret-key-12345',
  ),
);
