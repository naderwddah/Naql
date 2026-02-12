<?php
/**
 * متحكم البطاقات
 * يتعامل مع طلبات API المتعلقة بالبطاقات
 */

require_once __DIR__ . '/../models/verify_templates/verify.php';
require_once __DIR__ . '/../models/Card.php';
require_once __DIR__ . '/../models/CardVerification.php';
require_once __DIR__ . '/../helpers/Response.php';

class CardsController {
    private $pdo;
    private $authUser;
    private $cardModel;
    private $VerificationModel;

    // مصفوفة الصلاحيات لكل إجراء
    const PERMISSIONS = [
        'view' => [1, 2, 3, 4],    // الكل
        'create' => [1, 2, 3],     // super_admin, admin, editor
        'update' => [1, 2, 3],     // super_admin, admin, editor
        'delete' => [1, 2],        // super_admin, admin فقط
        'approve' => [1, 2]        // super_admin, admin فقط
    ];

    public function __construct(PDO $pdo, $authUser = null) {
        $this->pdo = $pdo;
        $this->authUser = $authUser;
        $this->cardModel = new Card($pdo);
        $this->VerificationModel=new  CardVerification($pdo);
    }

    /**
     * عرض كل البطاقات
     */
    public function list() {
        // التحقق من الصلاحية
        if (!$this->authUser || !in_array($this->authUser['role_id'], self::PERMISSIONS['view'])) {
            Response::error("ممنوع", 403);
        }

        $cards = $this->cardModel->getAll();
        Response::success(['cards' => $cards]);
    }
    public function GetCardNumber(){
        $card_number=$this->cardModel->generateCardNumber();
        Response::success(['card_number' => $card_number]);

    }
    /**
     * عرض بطاقة محددة
     * @param int $card_number رقم البطاقة
     */
    public function get($card_number) {
        // التحقق من الصلاحية
        if (!$this->authUser || !in_array($this->authUser['role_id'], self::PERMISSIONS['view'])) {
            Response::error("ممنوع", 403);
        }

        $card = $this->cardModel->getByCardNumber($card_number);
        if (!$card) {
            Response::error("لايوجد بطاقة بهذا الرقم", 404);
        }

        Response::success(['card' => $card]);
    }

    /**
     * إنشاء بطاقة جديدة
     * @param array $data بيانات البطاقة
     */
    public function create($data) {
        // التحقق من الصلاحية
        if (!$this->authUser || !in_array($this->authUser['role_id'], self::PERMISSIONS['create'])) {
            Response::error("ممنوع", 403);
        }

        // التحقق من الحقول المطلوبة
        $required = ['card_number' ,'verification_token','driver_name_ar', 'driver_name_en', 'company_name_ar', 'company_name_en'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::error("Field '$field' is required", 400);
            }
        }
        try {
            $result = $this->cardModel->create($data, $this->authUser['user_id']);
            
            // تسجيل العملية
            $this->logAction('create_card', $result['card_id']);
            
            Response::success([
                'card_id' => $result['card_id'],
                'card_number' => $result['card_number']
            ], 201);
            
        } catch (Exception $e) {
            Response::error("فشل انشاء البطاقة " , 500);
        }
    }

    /**
     * تحديث بطاقة
     * @param array $data بيانات التحديث
     */
    public function update($data) {
        // التحقق من الصلاحية
        if (!$this->authUser || !in_array($this->authUser['role_id'], self::PERMISSIONS['update'])) {
            Response::error("Forbidden", 403);
        }

        $card_id = $data['card_id'] ?? null;
        if (!$card_id) {
            Response::error("معرف البطاقه مطلوب", 400);
        }

        try {
            $this->cardModel->update($card_id, $data);
            $this->logAction('update_card', $card_id);
            Response::success(['message' => 'تم تحديث البطاقة']);
        } catch (Exception $e) {
            Response::error("فشل تحديث البطاقة لسبب ما ", 500);
        }
    }

    /**
     * حذف بطاقة
     * @param string $card_id معرف البطاقة
     */
    public function delete($card_id) {
        // التحقق من الصلاحية
        if (!$this->authUser || !in_array($this->authUser['role_id'], self::PERMISSIONS['delete'])) {
            Response::error("ممنوع", 403);
        }

        try {
            $this->cardModel->delete($card_id);
            $this->logAction('delete_card', $card_id);
            Response::success(['message' => 'تم حذف البطاقة']);
        } catch (Exception $e) {
            Response::error("فشل في حذف البطاقة: ", 500);
        }
    }

    /**
     * عرض البطاقات حسب الحالة
     * @param int $status_id معرف الحالة
     */
    public function getByStatus($status_id) {
        // التحقق من الصلاحية
        if (!$this->authUser || !in_array($this->authUser['role_id'], self::PERMISSIONS['view'])) {
            Response::error("ممنوع", 403);
        }

        $stmt = $this->pdo->prepare("
            SELECT c.*, d.*, s.name as status_name
            FROM cards c
            LEFT JOIN card_details d ON d.card_id = c.card_id
            LEFT JOIN card_statuses s ON s.status_id = c.status_id
            WHERE c.status_id = :status_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['status_id' => $status_id]);
        $cards = $stmt->fetchAll();

        Response::success(['cards' => $cards]);
    }

    /**
     * التحقق من البطاقة (رابط عام)
     * @param string $token رمز التحقق
     */
    public function verify($token) {
        $card = $this->cardModel->verifyCard($token);
        
        if (!$card) {
            Response::error("رمز التحقق غير صالح أو منتهي الصلاحية", 404);
        }

        // التحقق من أن البطاقة نشطة
        if ($card['status_id'] != 2) {
            Response::error("هذه البطاقة غير نشطة", 403);
        }
        
        Response::success([
            'card' => [
                'card_number' => $card['card_number'],
                'status' => $card['status_name'],
                'driver_name' => [
                    'ar' => $card['driver_name_ar'],
                    'en' => $card['driver_name_en']
                ],
                'company_name' => [
                    'ar' => $card['company_name_ar'],
                    'en' => $card['company_name_en']
                ],
                'issue_date' => $card['issue_date'],
                'expiry_date' => $card['expiry_date'],
                'driver_id' => $card['driver_id'],
                'moi_number' => $card['moi_number'],
                'verified_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    /**
     * عرض صفحة التحقق HTML
     */
    public function showVerificationPage($token) {
        $card = $this->cardModel->verifyCard($token);
        
        if (!$card) {
            // عرض صفحة خطأ
            // Response::error("رمز التحقق غير صالح أو منتهي الصلاحية",404);
            showErrorPage("رمز التحقق غير صالح أو منتهي الصلاحية");
            return;
        }
        
        // عرض صفحة النجاح مع بيانات البطاقة
        showSuccessPage($card);
    }
    /**
     * تسجيل العملية في سجل التدقيق
     * @param string $action نوع العملية
     * @param string $entity_id معرف الكيان
     */
    private function logAction($action, $entity_id) {
        if (!$this->authUser) return;

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address) 
            VALUES (:user_id, :action, 'card', :entity_id, :ip)
        ");
        $stmt->execute([
            'user_id' => $this->authUser['user_id'],
            'action' => $action,
            'entity_id' => $entity_id,
            'ip' => $ip
        ]);
    }
}