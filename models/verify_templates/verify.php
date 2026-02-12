<?php
function processInput($input)
{
        // إذا كان النص يحتوي على حروف (افتراض اسم)
        if (preg_match('/[a-zA-Z\x{0600}-\x{06FF}]/u', $input)) {
            // تقسيم الاسم حسب الفراغ
            $parts = explode(' ', trim($input));

            $firstName = $parts[0] ?? '';
            $lastName = $parts[count($parts) - 1] ?? '';

            return [
                'first_name' => $firstName,
                'last_name' => $lastName
            ];
        }
    // إذا كان النص رقم
    if (preg_match('/^\d+$/', $input)) {
        if (strlen($input) >= 2) {
            return substr($input, 0, 2) . ' ' . substr($input, 2);
        }
        return $input;
    }
    


    return $input;
}

/**
 * عرض صفحة نجاح التحقق
 */
function showSuccessPage($card)
{
    $card = array_merge($card, processInput($card['driver_name_ar']));
    $card['card_number'] = processInput($card['card_number']);
?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>تفاصيل بطاقة السائق</title>

        <!-- خط Tajawal للعربية -->
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Tajawal', sans-serif;
                background-color: #f0f0f0;
                /* خلفية رمادية خارج الورقة */
                padding: 20px;
                direction: rtl;
                min-height: 100vh;
            }

            /* العنوان الرئيسي - خارج الورقة */
            .page-title {
                color: #1a6b6b;
                /* لون أخضر مائل للزرقة أغمق */
                font-size: 22px;
                font-weight: 700;
                text-align: right;
                padding-top: 10px;
                max-width: 900px;
                /* عرض أكبر من الارتفاع */
                margin: 0 auto;
                padding: 15px 15px;
                position: relative;
            }

            /* الورقة البيضاء - بالعرض (Landscape) */
            .paper-sheet {
                max-width: 900px;
                /* عرض أكبر من الارتفاع */
                margin: 0 auto;
                background: white;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                padding: 30px 40px;
                position: relative;
            }

            /* الأقسام داخل الورقة */
            .section {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
            }

            .section:last-of-type {
                border-bottom: none;
                margin-bottom: 0;
            }

            /* عنوان القسم - لون أخضر مائل للزرقة */
            .section-title {
                color: #1a6b6b;
                font-size: 16px;
                font-weight: 700;
                margin-bottom: 15px;
                text-align: right;
            }

            /* شبكة البيانات - عمودين */
            .data-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px 30px;
            }

            .data-item {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .data-item.full-width {
                grid-column: 1 / -1;
            }

            .data-label {
                color: #666;
                font-size: 13px;
                font-weight: 500;
            }

            .data-value {
                color: #333;
                font-size: 15px;
                font-weight: 700;
            }

            /* قسم بطاقة السائق - توزيع مختلف */
            .card-section-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 15px 20px;
            }

            /* زر الرجوع - خارج الورقة */
            .back-button-container {
                text-align: left;
                max-width: 900px;
                /* عرض أكبر من الارتفاع */
                margin: 0 auto;
                padding: 10px 10px;
                position: relative;
            }

            .back-button {
                display: inline-block;
                padding: 10px 30px;
                background: white;
                border: 1px solid #1a6b6b;
                color: #1a6b6b;
                font-size: 15px;
                font-weight: 500;
                border-radius: 4px;
                cursor: pointer;
                font-family: 'Tajawal', sans-serif;
                transition: all 0.3s ease;
                min-width: 100px;
            }

            .back-button:hover {
                background: #1a6b6b;
                color: white;
            }

            /* للهاتف - تحويل للطول مع إمكانية التمرير */
            @media (max-width: 768px) {
                body {
                    padding: 10px;
                }

                .page-title {
                    font-size: 18px;
                    margin-bottom: 15px;
                }

                .paper-sheet {
                    padding: 20px;
                    overflow-x: auto;
                    /* تمرير أفقي للهاتف */
                }

                .data-grid {
                    grid-template-columns: 1fr;
                    min-width: 500px;
                    /* الحفاظ على عرض مناسب */
                }

                .card-section-grid {
                    grid-template-columns: 1fr 1fr;
                    min-width: 500px;
                }

                .section-title {
                    font-size: 14px;
                }

                .data-value {
                    font-size: 14px;
                }
            }
        </style>
        <base target="_blank">
    </head>

    <body>
        <!-- العنوان خارج الورقة -->
        <h1 class="page-title">تفاصيل بطاقة السائق</h1>

        <!-- الورقة البيضاء -->
        <div class="paper-sheet">
            <div id="content">

                <!-- القسم الأول: بيانات المنشأة/الفرد -->
                <div class="section">
                    <h2 class="section-title">بيانات المنشأة/الفرد</h2>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">رقم هوية المنشأة</span>
                            <span class="data-value"><?php echo $card['moi_number']; ?></span>
                        </div>
                        <div class="data-item full-width">
                            <span class="data-label">الاسم</span>
                            <span class="data-value"><?php echo $card['company_name_ar']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- القسم الثاني: معلومات الترخيص الرئيسي -->
                <div class="section">
                    <h2 class="section-title">معلومات الترخيص الرئيسي</h2>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="data-label">رقم الترخيص</span>
                            <span class="data-value"><?php echo $card['license_number']; ?></span>
                        </div>
                        <div class="data-item full-width">
                            <span class="data-label">نوع النشاط</span>
                            <span class="data-value"><?php echo $card['activity_type_ar']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">المدينة</span>
                            <span class="data-value"><?php echo $card['license_city_ar']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">تاريخ الإصدار</span>
                            <span class="data-value"><?php echo $card['license_issue_date']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">تاريخ الانتهاء</span>
                            <span class="data-value"><?php echo $card['license_expiry_date']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- القسم الثالث: بطاقة السائق -->
                <div class="section">
                    <h2 class="section-title">بطاقة السائق</h2>
                    <div class="card-section-grid">
                        <div class="data-item">
                            <span class="data-label">رقم البطاقة</span>
                            <span class="data-value"><?php echo $card['card_number']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">هوية السائق</span>
                            <span class="data-value"><?php echo $card['driver_id']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">اسم العائلة</span>
                            <span class="data-value"><?php echo $card['last_name']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">الاسم الأول</span>
                            <span class="data-value"><?php echo $card['first_name']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">تاريخ إصدار البطاقة</span>
                            <span class="data-value"><?php echo $card['issue_date']; ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">تاريخ انتهاء البطاقة</span>
                            <span class="data-value"><?php echo $card['expiry_date']; ?></span>
                        </div>
                        <div class="data-item" style="grid-column: 1 / -1;">
                            <span class="data-label">نوع البطاقة</span>
                            <span class="data-value"><?php echo "النقل الثقيل للبضائع لأغراض تجارية (للغير - عربة منشآت)"; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- زر الرجوع خارج الورقة -->
        <div class="back-button-container">
            <button class="back-button" onclick="goBack()">رجوع</button>
        </div>

        <script>
            function goBack() {
                window.history.back();
            }
        </script>
    </body>

    </html>
<?php
    exit;
}
?>

<?php
function showErrorPage($message = "فشلت عملية التحقق") {
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فشل التحقق</title>
    
    <!-- خط Tajawal للعربية -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f0f0f0;
            padding: 20px;
            direction: rtl;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .error-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px 30px;
            max-width: 500px;
            text-align: center;
        }
        .error-title {
            font-size: 22px;
            font-weight: 700;
            color: #d32f2f; /* أحمر */
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            color: #333;
            margin-bottom: 25px;
        }
        .back-button {
            display: inline-block;
            padding: 10px 25px;
            background: #d32f2f;
            color: white;
            font-size: 15px;
            font-weight: 500;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: #b71c1c;
        }
        @media (max-width: 768px) {
            .error-card {
                padding: 25px 20px;
            }
            .error-title {
                font-size: 18px;
            }
            .error-message {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-title">فشل التحقق</div>
        <div class="error-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <button class="back-button" onclick="window.history.back();">رجوع</button>
    </div>
</body>
</html>
<?php
    exit;
}
?>
