<?php
/**
 * كلاس إدارة الاستجابات
 * مسؤول عن تنسيق وإرسال الردود من API
 */

class Response {
    
    /**
     * إرسال استجابة JSON
     * @param mixed $data البيانات المراد إرسالها
     * @param int $status كود الحالة HTTP
     */
    public static function json($data, $status = 200) {
        // تعيين رؤوس HTTP
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // تحويل البيانات إلى JSON مع دعم العربية
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * إرسال استجابة نجاح
     * @param array $data البيانات الإضافية
     * @param int $status كود الحالة
     */
    public static function success($data = [], $status = 200) {
        $response = ['success' => true];
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        self::json($response, $status);
    }

    /**
     * إرسال استجابة خطأ
     * @param string $message رسالة الخطأ
     * @param int $status كود الحالة
     */
    public static function error($message, $status = 400) {
        self::json([
            'success' => false,
            'error' => $message
        ], $status);
    }
}