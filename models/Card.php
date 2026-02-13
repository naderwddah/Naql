<?php
/**
 * كلاس البطاقة
 * مسؤول عن كل العمليات المتعلقة بالبطاقات
 * هذا هو الكلاس الأكثر أهمية في النظام
 */

require_once __DIR__ . '/../helpers/Response.php';

class Card {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * توليد رقم بطاقة جديد
     * يستخدم جدول card_number_sequence للتسلسل الآمن
     * @return int رقم البطاقة (8 أرقام)
     */
    public function generateCardNumber() {
        try {
            // بدء معاملة
            $this->pdo->beginTransaction();
            
            // إدخال سجل جديد في جدول التسلسل
            $stmt = $this->pdo->prepare("INSERT INTO card_number_sequence () VALUES ()");
            $stmt->execute();
            
            // الحصول على الرقم المتسلسل
            $id = $this->pdo->lastInsertId();
            
            // تأكيد المعاملة
            $this->pdo->commit();
            
            return $id;
            
        } catch (Exception $e) {
            // تراجع في حالة الخطأ
            $this->pdo->rollBack();
            throw $e;
        }
    }
    function generateUniqueNumber($pdo, $length = 10)
    {
        do {
            // توليد رقم عشوائي بطول محدد
            $number = '';
            for ($i = 0; $i < $length; $i++) {
                $number .= random_int(0, 9);
            }
    
            // التحقق هل الرقم موجود مسبقًا
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM card_details WHERE moi_number = ?");
            $stmt->execute([$number]);
            $exists = $stmt->fetchColumn();
    
        } while ($exists);
    
        return $number;
    }
    
    /**
     * إنشاء بطاقة جديدة بكل تفاصيلها
     * @param array $data بيانات البطاقة
     * @param string $created_by معرف المستخدم المنشئ
     * @return array معرف البطاقة ورقمها
     */
    public function create($data, $created_by) {

        $card_number=$data['card_number'];
        
        // توليد رقم بطاقة فريد
        // $card_number = $this->generateCardNumber();
        try {
            // بدء معاملة - كل شيء أو لا شيء
            $this->pdo->beginTransaction();

            // 1. إدخال البطاقة في جدول cards
            $stmt = $this->pdo->prepare("
                INSERT INTO cards ( card_number, status_id, created_by) 
                VALUES ( :card_number, 2, :created_by)
            ");
            $stmt->execute([
                'card_number' => $card_number,
                'created_by' => $created_by
            ]);
            $card_id = $this->pdo->lastInsertId();
            // 2. إدخال تفاصيل البطاقة في جدول card_details
            $stmt = $this->pdo->prepare("
                INSERT INTO card_details (
                    card_id, 
                    driver_name_ar, driver_name_en, driver_id,
                    issue_date, expiry_date,
                    card_category_ar, card_category_en,
                    license_number, license_city_ar, license_city_en,
                    license_issue_date, license_expiry_date,
                    activity_type_ar, activity_type_en,
                    moi_number, company_name_ar, company_name_en
                ) VALUES (
                    :card_id,
                    :driver_name_ar, :driver_name_en, :driver_id,
                    :issue_date, :expiry_date,
                    :card_category_ar, :card_category_en,
                    :license_number, :license_city_ar, :license_city_en,
                    :license_issue_date, :license_expiry_date,
                    :activity_type_ar, :activity_type_en,
                    :moi_number, :company_name_ar, :company_name_en
                )
            ");

            $stmt->execute([
                'card_id' => $card_id,
                // معلومات السائق
                'driver_name_ar' => $data['driver_name_ar'] ?? null,
                'driver_name_en' => $data['driver_name_en'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                
                // تواريخ البطاقة
                'issue_date' => $data['issue_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                
                // فئة البطاقة
                'card_category_ar' => $data['card_category_ar'] ?? null,
                'card_category_en' => $data['card_category_en'] ?? null,
                
                // معلومات الرخصة
                'license_number' => $data['license_number'] ?? null,
                'license_city_ar' => $data['license_city_ar'] ?? null,
                'license_city_en' => $data['license_city_en'] ?? null,
                'license_issue_date' => $data['license_issue_date'] ?? null,
                'license_expiry_date' => $data['license_expiry_date'] ?? null,
                
                // نوع النشاط
                'activity_type_ar' => $data['activity_type_ar'] ?? null,
                'activity_type_en' => $data['activity_type_en'] ?? null,
                
                // معلومات الشركة
                'moi_number' => $data['moi_number'] ?? $this->generateUniqueNumber($this->pdo),
                'company_name_ar' => $data['company_name_ar'] ?? null,
                'company_name_en' => $data['company_name_en'] ?? null
            ]);
            $verification_id = generate_uuid();
            $stmt = $this->pdo->prepare("
            INSERT INTO card_verifications (verification_id, card_id, verification_token) 
            VALUES (:verification_id, :card_id, :token)
        ");
        $stmt->execute([
            'verification_id' => $verification_id,
            'card_id' => $card_id,
            'token' => $data['verification_token']
        ]);
            // تأكيد المعاملة
            $this->pdo->commit();            
            return [
                'card_id' => $card_id,
                'card_number' => $card_number
            ];

        } catch (Exception $e) {
            // تراجع في حالة أي خطأ
            $this->pdo->rollBack();
            error_log("Card creation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * جلب كل البطاقات
     * @return array قائمة البطاقات
     */
    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT 
                -- معلومات البطاقة الأساسية
                c.card_id, 
                c.card_number, 
                c.status_id, 
                c.created_by, 
                c.created_at, 
                c.approved_at,
                
                -- معلومات السائق والشركة
                d.driver_name_ar, 
                d.driver_name_en, 
                d.driver_id,
                d.company_name_ar, 
                d.company_name_en, 
                d.moi_number,
                
                -- اسم الحالة
                s.name as status_name
                
            FROM cards c
            LEFT JOIN card_details d ON d.card_id = c.card_id
            LEFT JOIN card_statuses s ON s.status_id = c.status_id
            ORDER BY c.created_at DESC
        ");
        
        return $stmt->fetchAll();
    }

    /**
     * البحث عن بطاقة برقم البطاقة
     * @param int $card_number رقم البطاقة
     * @return array بيانات البطاقة
     */
    public function getByCardNumber($card_number) {
        $stmt = $this->pdo->prepare("
            SELECT 
                -- كل الحقول من cards
                c.*,
                -- كل الحقول من card_details
                d.*,
                -- اسم الحالة
                s.name as status_name,
                -- اسم المنشئ
                u.username as created_by_username
                
            FROM cards c
            LEFT JOIN card_details d ON d.card_id = c.card_id
            LEFT JOIN card_statuses s ON s.status_id = c.status_id
            LEFT JOIN users u ON u.user_id = c.created_by
            WHERE c.card_number = :card_number
        ");
        
        $stmt->execute(['card_number' => $card_number]);
        return $stmt->fetch();
    }

    /**
     * تحديث بيانات البطاقة
     * @param string $card_id معرف البطاقة
     * @param array $data البيانات الجديدة
     * @return bool نجاح العملية
     */
    public function update($card_id, $data) {
        try {
            $this->pdo->beginTransaction();

            // 1. تحديث حالة البطاقة إذا وجدت
            if (isset($data['status_id'])) {
                $stmt = $this->pdo->prepare("
                    UPDATE cards 
                    SET status_id = :status_id 
                    WHERE card_id = :card_id
                ");
                $stmt->execute([
                    'status_id' => $data['status_id'],
                    'card_id' => $card_id
                ]);

                // إذا تم اعتماد البطاقة، سجل وقت الاعتماد
                if ($data['status_id'] == 2) { // 2 = approved
                    $stmt = $this->pdo->prepare("
                        UPDATE cards 
                        SET approved_at = NOW() 
                        WHERE card_id = :card_id
                    ");
                    $stmt->execute(['card_id' => $card_id]);
                }
            }

            // 2. تحديث تفاصيل البطاقة
            $updates = [];
            $params = ['card_id' => $card_id];
            
            // الحقول المسموح تحديثها
            $allowedFields = [
                'driver_name_ar', 'driver_name_en', 'driver_id',
                'issue_date', 'expiry_date',
                'card_category_ar', 'card_category_en',
                'license_number', 'license_city_ar', 'license_city_en',
                'license_issue_date', 'license_expiry_date',
                'activity_type_ar', 'activity_type_en',
                'moi_number', 'company_name_ar', 'company_name_en'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }

            if (!empty($updates)) {
                $sql = "UPDATE card_details SET " . implode(', ', $updates) . " WHERE card_id = :card_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * حذف بطاقة
     * @param string $card_id معرف البطاقة
     * @return bool نجاح العملية
     */
    public function delete($card_id) {
        $stmt = $this->pdo->prepare("DELETE FROM cards WHERE card_id = :card_id");
        return $stmt->execute(['card_id' => $card_id]);
    }

    /**
     * إنشاء رمز تحقق للبطاقة
     * @param string $card_id معرف البطاقة
     * @return string رمز التحقق
     */
    public function createVerification($card_id) {
        $verification_id = generate_uuid();
        $token = bin2hex(random_bytes(32));
        
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
     * التحقق من صحة البطاقة باستخدام الرمز
     * @param string $token رمز التحقق
     * @return array بيانات البطاقة
     */
    public function verifyCard($token) {
        $stmt = $this->pdo->prepare("
            SELECT c.card_number, d.* 
            FROM card_verifications v
            JOIN cards c ON c.card_id = v.card_id
            JOIN card_details d ON d.card_id = c.card_id
            WHERE v.verification_token = :token
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }
}