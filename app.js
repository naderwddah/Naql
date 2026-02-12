  let deferredPrompt = null;

  // كشف الأجهزة
  const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
  const isStandalone =
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true;

  // دالة إظهار البانر
  function showInstallBanner(isIOSMode = false) {
    const banner = document.getElementById("install-banner");
    if (!banner) return;

    // تنسيقات الأزرار
    const btnStyle = `
      background: #fff; 
      color: #6B1C24; 
      border: none; 
      padding: 8px 20px; 
      border-radius: 6px; 
      font-weight: bold; 
      cursor: pointer; 
      font-size: 14px;
      transition: transform 0.2s;
    `;

    const closeBtnStyle = `
      background: transparent; 
      border: none; 
      color: rgba(255,255,255,0.7); 
      font-size: 18px; 
      cursor: pointer; 
      padding: 0 5px;
    `;

    if (isIOSMode) {
      // رسالة مخصصة للأيفون
      banner.innerHTML = `
        <div style="flex: 1; text-align: center;">
          <div style="font-weight: bold; margin-bottom: 5px; font-size: 16px;">تثبيت التطبيق</div>
          <div style="font-size: 13px; opacity: 0.9;">
            اضغط على زر المشاركة 
            <i class="fa-solid fa-arrow-up-from-bracket"></i> 
            ثم اختر <strong>Add to Home Screen</strong>
          </div>
        </div>
        <button onclick="hideInstallBanner()" style="${closeBtnStyle}"><i class="fa-solid fa-xmark"></i></button>
      `;
    } else {
      // رسالة للأندرويد والكمبيوتر
      banner.innerHTML = `
        <span style="font-size: 15px; flex: 1;">
          <i class="fa-solid fa-download" style="margin-left: 8px;"></i>
          ثبّت التطبيق للوصول السريع
        </span>
        <button onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" onclick="installPWA()" style="${btnStyle}">تثبيت</button>
        <button onclick="hideInstallBanner()" style="${closeBtnStyle}"><i class="fa-solid fa-xmark"></i></button>
      `;
    }

    banner.style.display = "flex";
  }

  function hideInstallBanner() {
    const banner = document.getElementById("install-banner");
    if (banner) banner.style.display = "none";
  }

  // منطق الأندرويد والكمبيوتر
  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    deferredPrompt = event;
    // إظهار البانر بعد ثانيتين
    setTimeout(() => {
      if (!isStandalone) showInstallBanner();
    }, 2000);
  });

  // تنفيذ التثبيت
  function installPWA() {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(() => {
      deferredPrompt = null;
      hideInstallBanner();
    });
  }

  // بعد التثبيت
  window.addEventListener("appinstalled", () => {
    hideInstallBanner();
  });

  // منطق iOS
  window.addEventListener("load", () => {
    if (isIOS && !isStandalone) {
      setTimeout(() => {
        showInstallBanner(true);
      }, 2000);
    }
  });

  // ========== [تصحيح الخطأ] تسجيل Service Worker ==========
  // ملاحظة: استخدمنا './' لضمان أن المسار نسبي لمجلد المشروع الحالي
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('./service-worker.js') 
        .then((reg) => {
          console.log('Service Worker registered successfully with scope:', reg.scope);
        })
        .catch((err) => {
          console.error('Service Worker registration failed:', err);
        });
    });
  }