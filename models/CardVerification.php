<?php
/**
 * كلاس تحقق البطاقة
 * مسؤول عن عمليات التحقق من البطاقات
 */

require_once __DIR__ . '/../helpers/Response.php';

class CardVerification {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * إنشاء رمز تحقق جديد
     * @param string $card_id معرف البطاقة
     * @return string رمز التحقق
     */
    public function create($card_id,$token) {
        $verification_id = generate_uuid();
        // $token = bin2hex(random_bytes(32));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO card_verifications (verification_id, card_id, verification_token) 
            VALUES (:verification_id, :card_id, :token)
        ");
        $stmt->execute([
            'verification_id' => $verification_id,
            'card_id' => $card_id,
            'token' => $token
        ]);
        
        return $token;
    }

    /**
     * التحقق من رمز التحقق
     * @param string $token رمز التحقق
     * @return array|false بيانات البطاقة أو false
     */
    public function verify($token) {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.*,
                c.card_number, c.status_id, c.created_at,
                d.*,
                s.name as status_name
            FROM card_verifications v
            JOIN cards c ON c.card_id = v.card_id
            JOIN card_details d ON d.card_id = c.card_id
            JOIN card_statuses s ON s.status_id = c.status_id
            WHERE v.verification_token = :token
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    /**
     * جلب رمز التحقق لبطاقة
     * @param string $card_id معرف البطاقة
     * @return array|false رمز التحقق
     */
    public function getByCardId($card_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM card_verifications 
            WHERE card_id = :card_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute(['card_id' => $card_id]);
        return $stmt->fetch();
    }

    /**
     * حذف رمز التحقق
     * @param string $token رمز التحقق
     * @return bool نجاح العملية
     */
    public function delete($token) {
        $stmt = $this->pdo->prepare("DELETE FROM card_verifications WHERE verification_token = :token");
        return $stmt->execute(['token' => $token]);
    }

    /**
     * حذف رموز التحقق القديمة
     * @param int $hours عدد الساعات
     * @return int عدد السجلات المحذوفة
     */
    public function cleanOldTokens($hours = 24) {
        $date = date('Y-m-d H:i:s', strtotime("-$hours hours"));
        $stmt = $this->pdo->prepare("
            DELETE FROM card_verifications 
            WHERE created_at < :date
        ");
        $stmt->execute(['date' => $date]);
        return $stmt->rowCount();
    }

    /**
     * توليد رابط التحقق
     * @param string $token رمز التحقق
     * @return string رابط التحقق الكامل
     */
    public function generateVerificationUrl($token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host/verify?token=$token";
    }

    /**
     * التحقق من صحة البطاقة وإرجاع معلومات مختصرة
     * @param string $token رمز التحقق
     * @return array معلومات مختصرة للعرض
     */
    public function getPublicCardInfo($token) {
        $data = $this->verify($token);
        
        if (!$data) {
            return null;
        }

        return [
            'card_number' => $data['card_number'],
            'status' => $data['status_name'],
            'driver_name' => [
                'ar' => $data['driver_name_ar'],
                'en' => $data['driver_name_en']
            ],
            'company_name' => [
                'ar' => $data['company_name_ar'],
                'en' => $data['company_name_en']
            ],
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'verified_at' => date('Y-m-d H:i:s')
        ];
    }
}