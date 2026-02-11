

// إخفاء مؤشر التحميل
window.addEventListener("load", () => {
  setTimeout(() => {
    const loader = document.getElementById("pageLoader");
    if (loader) loader.classList.add("hidden");
  }, 500);
});

// العناصر
const form = document.getElementById("loginForm");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const togglePasswordBtn = document.getElementById("togglePassword");
const loginBtn = document.getElementById("loginBtn");
const messageContainer = document.getElementById("messageContainer");
const rememberMeCheckbox = document.getElementById("rememberMe");

// ===== نظام الرسائل =====
function showMessage(type, title, text) {
  messageContainer.innerHTML = "";
  const messageDiv = document.createElement("div");
  messageDiv.className = `message ${type}`;

  const icons = {
    error: "fa-exclamation-circle",
    success: "fa-check-circle",
    warning: "fa-exclamation-triangle",
    info: "fa-info-circle",
  };

  messageDiv.innerHTML = `
        <i class="fas ${icons[type] || "fa-info-circle"}"></i>
        <div>
            <div style="font-weight: 700; margin-bottom: 4px;">${title}</div>
            ${
              text
                ? `<div style="font-size: 13px; opacity: 0.9;">${text}</div>`
                : ""
            }
        </div>
    `;

  messageContainer.appendChild(messageDiv);
  void messageDiv.offsetWidth; // إعادة رسم للأنيميشن
  messageDiv.classList.add("show");

  if (type === "success") {
    setTimeout(() => {
      messageDiv.style.opacity = "0";
      messageDiv.style.transform = "translateY(-10px)";
      setTimeout(() => {
        if (messageDiv.parentNode) messageDiv.remove();
      }, 300);
    }, 3000);
  }
}

function clearMessages() {
  messageContainer.innerHTML = "";
}

// إظهار/إخفاء كلمة المرور
togglePasswordBtn.addEventListener("click", () => {
  const type =
    passwordInput.getAttribute("type") === "password"
      ? "text"
      : "password";
  passwordInput.setAttribute("type", type);
  const icon = togglePasswordBtn.querySelector("i");
  icon.className =
    type === "password" ? "fas fa-eye" : "fas fa-eye-slash";
  passwordInput.focus();
});

// التحقق من صحة المدخلات (فقط التأكد من أن الحقول ليست فارغة)
function validateField(input, hintId) {
  const hint = document.getElementById(hintId);
  const value = input.value.trim();
  let isValid = value.length >= 3;

  if (!isValid && value.length > 0) {
    input.classList.add("error");
    if (hint) hint.classList.add("show");
    return false;
  } else {
    input.classList.remove("error");
    if (hint) hint.classList.remove("show");
    return true;
  }
}

usernameInput.addEventListener("input", () =>
  validateField(usernameInput, "usernameHint")
);
passwordInput.addEventListener("input", () =>
  validateField(passwordInput, "passwordHint")
);

[usernameInput, passwordInput].forEach((input) => {
  input.addEventListener("focus", () => input.classList.remove("error"));
});

// Ripple effect
loginBtn.addEventListener("click", function (e) {
  const rect = this.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const ripple = document.createElement("span");
  ripple.className = "ripple";
  ripple.style.left = x + "px";
  ripple.style.top = y + "px";
  this.appendChild(ripple);
  setTimeout(() => ripple.remove(), 600);
});

// ===== الاتصال بـ API ومعالجة تسجيل الدخول =====
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  clearMessages();

  const username = usernameInput.value.trim();
  const password = passwordInput.value.trim();

  // تحقق أساسي من الفراغ
  if (username.length < 3 || password.length < 3) {
    showMessage(
      "error",
      "بيانات ناقصة",
      "يرجى إدخال اسم المستخدم وكلمة المرور"
    );
    if (username.length < 3) usernameInput.classList.add("error");
    if (password.length < 3) passwordInput.classList.add("error");
    return;
  }

  // تفعيل حالة التحميل
  loginBtn.classList.add("loading");
  loginBtn.disabled = true;

  // إعداد البيانات للإرسال
  const requestData = {
    username: username,
    password: password,
  };

  try {
    // استبدل الرابط أدناه برابط ملف الـ API الخاص بك
    // مثال: 'https://example.com/api/login.php'
    const apiUrl = "api/login.php";

    const response = await fetch(apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(requestData),
    });

    // قراءة الرد من السيرفر
    const result = await response.json();

    if (response.ok && result.success) {
      // ===== حالة النجاح =====
      // الافتراض أن السيرفر يعيد كائن JSON يحتوي على: success: true, token: "xyz", message: "..."

      showMessage(
        "success",
        "تم تسجيل الدخول",
        result.message || "جاري تحويلك..."
      );

      // حفظ التوكن والبيانات
      const storage = rememberMeCheckbox.checked
        ? localStorage
        : sessionStorage;

      // تخزين التوكن القادم من قاعدة البيانات
      if (result.token) {
        storage.setItem("authToken", result.token);
      }
      storage.setItem("currentUser", username);

      setTimeout(() => {
        window.location.href = "dashboard.html"; // توجيه المستخدم
      }, 1500);
    } else {
      // ===== حالة الفشل =====
      throw new Error(result.message || "حدث خطأ أثناء الاتصال");
    }
  } catch (error) {
    // التعامل مع الأخطاء (شبكة أو بيانات خاطئة)
    loginBtn.classList.remove("loading");
    loginBtn.disabled = false;

    console.error("Login Error:", error);

    // إظهار رسالة الخطأ القادمة من السيرفر أو رسالة عامة
    showMessage("error", "فشل تسجيل الدخول", error.message);

    // اهتزاز البطاقة كإشارة بصرية
    const card = document.querySelector(".login-card");
    if (card) {
      card.style.animation = "shake 0.5s ease";
      setTimeout(() => (card.style.animation = ""), 500);
    }
  }
});

// اختصارات لوحة المفاتيح
document.addEventListener("keydown", (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
    form.dispatchEvent(new Event("submit"));
  }
  if (e.key === "Escape") {
    clearMessages();
  }
});

// منع النسخ في حقل كلمة المرور
passwordInput.addEventListener("copy", (e) => {
  e.preventDefault();
  showMessage("warning", "تنبيه أمني", "نسخ كلمة المرور غير مسموح به");
});
// -----------------
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("/assets/PWA/service-worker.js")
      .then(() => console.log("PWA Service Worker registered"))
      .catch((err) => console.error("Service Worker error:", err));
  });
}