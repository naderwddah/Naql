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
      return substr($input, 0, 2) . '.' . substr($input, 2);
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
        font-size: 22px;
        font-weight: 700;
        text-align: right;
        max-width: 900px;
        margin: 0 auto 15px auto;
        padding: 0 5px;
      }

      /* الورقة البيضاء - بالعرض (Landscape) */
      .paper-sheet {
        max-width: 900px;
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

      /* شبكة البيانات - عمودين (للأقسام العادية) */
      .data-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        /* عمودين بالتساوي */
        gap: 15px 30px;
        /* مسافة رأسية وأفقية */
      }

      /* صف ثلاثي خاص بالقسم الثاني */
      .license-top-grid {
        display: grid;
        grid-template-columns: 1fr 1fr ;
        /* 3 أعمدة */
        gap: 15px 30px;
        margin-bottom: 15px;
      }


      /* شبكة بطاقة السائق - 3 أعمدة (للجزء الثاني) */
      .card-section-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        /* 3 أعمدة بالتساوي */
        gap: 15px 20px;
      }

      .data-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
      }

      .data-item.full-width {
        grid-column: 1 / -1;
        /* يمتد لكامل العرض */
      }

      .data-item.half-width {
        grid-column: 1 / 2;
        /* يمتد لكامل العرض */
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

      /* زر الرجوع - خارج الورقة */
      .back-button-container {
        text-align: left;
        max-width: 900px;
        margin: 20px auto 0 auto;
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
        }

        .paper-sheet {
          padding: 20px;
          overflow-x: auto;
        }

        /* الحفاظ على هيكلية الأعمدة حتى في التصميم المتجاوب */
        .data-grid,
        .card-section-grid {
          min-width: 600px;
          /* إجبار التمرير الأفقي للحفاظ على الأعمدة */
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

        <!-- القسم الأول: بيانات المنشأة/الفرد (عمودين) -->
        <div class="section">
          <h2 class="section-title">بيانات المنشأة/الفرد</h2>
          <div class="data-grid">
            <div class="data-item ">
              <span class="data-label">الاسم</span>
              <span class="data-value"><?php echo $card['company_name_ar']; ?></span>
            </div>
            <div class="data-item">
              <span class="data-label">رقم هوية المنشأة</span>
              <span class="data-value"><?php echo $card['moi_number']??"5763".$card['license_number']; ?></span>
            </div>
            <!-- يمكنك إضافة حقل فارغ هنا إذا أردت موازنة الشبكة أو حقل آخر موجود في الصورة -->

          </div>
        </div>

        <!-- القسم الثاني: معلومات الترخيص الرئيسي (عمودين) -->
        <div class="section">
          <h2 class="section-title">معلومات الترخيص الرئيسي</h2>
          <div class="data-grid">

            <div class="license-top-grid">
              <div class="data-item">
                <span class="data-label">رقم الترخيص</span>
                <span class="data-value"><?php echo "11/".$card['license_number']; ?></span>
              </div>

              <div class="data-item">
                <span class="data-label">نوع الترخيص / النشاط</span>
                <span class="data-value"><?php echo $card['card_category_ar']; ?></span>
              </div>
            </div>
            <div class="data-item">

              <div class="data-item">
                <span class="data-label">المدينة</span>
                <span class="data-value"><?php echo $card['license_city_ar']; ?></span>
              </div>
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

        <!-- القسم الثالث: بطاقة السائق (3 أعمدة) -->
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
              <span class="data-label">الاسم الأول</span>
              <span class="data-value"><?php echo $card['first_name']; ?></span>
            </div>

            <div class="data-item">
              <span class="data-label">اسم العائلة</span>
              <span class="data-value"><?php echo $card['last_name']; ?></span>
            </div>
            <div class="data-item">
              <span class="data-label">تاريخ إصدار البطاقة</span>
              <span class="data-value"><?php echo $card['issue_date']; ?></span>
            </div>
            <div class="data-item">
              <span class="data-label">تاريخ انتهاء البطاقة</span>
              <span class="data-value"><?php echo $card['expiry_date']; ?></span>
            </div>

            <div class="data-item full-width">
              <span class="data-label">نوع البطاقة</span>
              <span class="data-value"><?php echo $card['activity_type_ar'];?></span>
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