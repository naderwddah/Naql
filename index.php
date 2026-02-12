<?php
/**
 * الملف الرئيسي للنظام - نقطة الدخول الوحيدة
 * يدعم نظام Routing للمسارات المختلفة
 */

// تحميل ملفات الإعدادات الأساسية
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/AuthHelper.php';

// تحميل وحدات التحكم (Controllers)
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsersController.php';
require_once __DIR__ . '/controllers/CardsController.php';
require_once __DIR__ . '/controllers/RolesController.php';
require_once __DIR__ . '/controllers/StatusController.php';

// ============================================
// 1. قراءة المسار والطريقة
// ============================================
$path = $_GET['path'] ?? '';        // المسار المطلوب (users, cards, etc)
$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE

// ============================================
// 2. مسار تسجيل الدخول (عام - بدون توكن)
// ============================================
if ($path === 'login' && $method === 'POST') {
    AuthController::login($pdo);
    exit;
}

// ============================================
// 2. التحقق من البطاقة (عام - بدون توكن)
// ============================================
    if ($path === 'verify' && $method === 'GET'){
        if ($method === 'GET' && isset($_GET['token'])) {
            $controller = new CardsController($pdo, null);
            $controller->verify($_GET['token']);
        } else {
            Response::error("Invalid request", 400);
        }}

// ============================================
// 3. التحقق من التوكن للمسارات المحمية
// ============================================
$headers = getallheaders();
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

// جميع المسارات ماعدا login تحتاج توكن
if (!in_array($path, ['login']) || !in_array($path, ['verify'])) {
    $authUser = AuthHelper::checkToken($pdo, $token);
}

// ============================================
// 4. نظام التوجيه (Routing)
// ============================================
switch ($path) {
    
    // ----------------------------
    // مستخدمين النظام
    // ----------------------------
    case 'users':
        $controller = new UsersController($pdo, $authUser);
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($method) {
            case 'GET':    $controller->list(); break;
            case 'POST':   $controller->create($data); break;
            case 'PUT':    $controller->update($data); break;
            case 'DELETE': $controller->delete($data); break;
            default:       Response::error("Method not allowed", 405);
        }
        break;

    // ----------------------------
    // الصلاحيات
    // ----------------------------
    case 'roles':
        $controller = new RolesController($pdo, $authUser);
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($method) {
            case 'GET':    $controller->list(); break;
            case 'POST':   $controller->create($data); break;
            case 'PUT':    $controller->update($data); break;
            case 'DELETE': $controller->delete($data); break;
            default:       Response::error("Method not allowed", 405);
        }
        break;

    // ----------------------------
    // حالات البطاقات
    // ----------------------------
    case 'statuses':
        $controller = new StatusController($pdo, $authUser);
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($method) {
            case 'GET':    $controller->list(); break;
            case 'POST':   $controller->create($data); break;
            case 'PUT':    $controller->update($data); break;
            case 'DELETE': $controller->delete($data); break;
            default:       Response::error("Method not allowed", 405);
        }
        break;

    // ----------------------------
    // البطاقات (الجزء الرئيسي)
    // ----------------------------
    case 'cards':
        $controller = new CardsController($pdo, $authUser);
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($method) {
            case 'GET':
                // GET يمكن أن يأتي بعدة أشكال
                if (isset($_GET['card_number'])) {
                    $controller->get($_GET['card_number']);     // بطاقة محددة
                } elseif (isset($_GET['status'])) {
                    $controller->getByStatus($_GET['status']); // بطاقات حسب الحالة
                } elseif (isset($_GET['get_card_number'])) {
                    $controller->GetCardNumber(); // بطاقات حسب الحالة
                }  else {
                    $controller->list();                        // كل البطاقات
                }
                break;
                
            case 'POST':
                $controller->create($data);
                break;
                
            case 'PUT':
                $controller->update($data);
                break;
                
            case 'DELETE':
                if (!isset($_GET['card_id'])) {
                    Response::error("card_id required", 400);
                }
                $controller->delete($_GET['card_id']);
                break;
                
            default:
                Response::error("Method not allowed", 405);
        }
        break;

    // ----------------------------
    // مسار غير موجود
    // ----------------------------
    default:
        Response::error("Invalid endpoint", 404);
}