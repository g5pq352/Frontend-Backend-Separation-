<?php
namespace App\Models;

class AuthSessionModel extends Model
{
    protected $table = 'auth_sessions';

    /**
     * 建立新的認證記錄
     * @param string $ipAddress IP 位址
     * @param string $userAgent User Agent
     * @param int $expiresInSeconds 過期秒數 (預設 1 小時)
     * @return bool
     */
    public function createAuthSession($ipAddress, $userAgent = '', $expiresInSeconds = 3600)
    {
        // 先清除該 IP 的舊記錄
        $this->deleteAuthSession($ipAddress);

        $now = new \DateTime();
        $expiresAt = new \DateTime();
        $expiresAt->modify("+{$expiresInSeconds} seconds");

        $sql = "INSERT INTO {$this->table}
                (ip_address, user_agent, authenticated_at, expires_at, is_active)
                VALUES (?, ?, ?, ?, 1)";

        $params = [
            $ipAddress,
            $userAgent,
            $now->format('Y-m-d H:i:s'),
            $expiresAt->format('Y-m-d H:i:s')
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\Exception $e) {
            error_log('createAuthSession Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 檢查 IP 是否已認證且未過期
     * @param string $ipAddress IP 位址
     * @return bool
     */
    public function isAuthenticated($ipAddress)
    {
        $now = new \DateTime();

        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE ip_address = ?
                AND is_active = 1
                AND expires_at > ?";

        $params = [
            $ipAddress,
            $now->format('Y-m-d H:i:s')
        ];

        $result = $this->db->query($sql, $params);

        return isset($result[0]) && $result[0]['count'] > 0;
    }

    /**
     * 取得 IP 的認證資訊
     * @param string $ipAddress IP 位址
     * @return array|null
     */
    public function getAuthSession($ipAddress)
    {
        $now = new \DateTime();

        $sql = "SELECT *
                FROM {$this->table}
                WHERE ip_address = ?
                AND is_active = 1
                AND expires_at > ?
                ORDER BY id DESC
                LIMIT 1";

        $params = [
            $ipAddress,
            $now->format('Y-m-d H:i:s')
        ];

        $result = $this->db->query($sql, $params);

        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * 刪除 IP 的認證記錄 (登出)
     * @param string $ipAddress IP 位址
     * @return bool
     */
    public function deleteAuthSession($ipAddress)
    {
        $sql = "UPDATE {$this->table}
                SET is_active = 0
                WHERE ip_address = ?";

        try {
            $this->db->query($sql, [$ipAddress]);
            return true;
        } catch (\Exception $e) {
            error_log('deleteAuthSession Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 延長認證時間
     * @param string $ipAddress IP 位址
     * @param int $expiresInSeconds 延長的秒數
     * @return bool
     */
    public function extendAuthSession($ipAddress, $expiresInSeconds = 3600)
    {
        $expiresAt = new \DateTime();
        $expiresAt->modify("+{$expiresInSeconds} seconds");

        $sql = "UPDATE {$this->table}
                SET expires_at = ?
                WHERE ip_address = ?
                AND is_active = 1";

        $params = [
            $expiresAt->format('Y-m-d H:i:s'),
            $ipAddress
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\Exception $e) {
            error_log('extendAuthSession Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 清理過期的記錄
     * @return bool
     */
    public function cleanupExpiredSessions()
    {
        $now = new \DateTime();
        $now->modify('-1 days'); // 保留 1 天內的記錄

        $sql = "DELETE FROM {$this->table}
                WHERE expires_at < ?";

        try {
            $this->db->query($sql, [$now->format('Y-m-d H:i:s')]);
            return true;
        } catch (\Exception $e) {
            error_log('cleanupExpiredSessions Error: ' . $e->getMessage());
            return false;
        }
    }
}
