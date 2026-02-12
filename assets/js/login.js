// ==========================================
// ملف: login.js (مُحدّث للعمل مع API التوثيق)
// ==========================================

// إخفاء مؤشر التحميل
window.addEventListener("load", () => {
  setTimeout(() => {
    const loader = document.getElementById("pageLoader");
    if (loader) loader.classList.add("hidden");
  }, 500);
});

// ✅ التعديل 1: رابط API مطابق للتوثيق (بدون / في النهاية)
const API_BASE_URL = "http://localhost/driver_cards/v1";

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
      ${text ? `<div style="font-size: 13px; opacity: 0.9;">${text}</div>` : ""}
    </div>
  `;

  messageContainer.appendChild(messageDiv);
  void messageDiv.offsetWidth;
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
    passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
  const icon = togglePasswordBtn.querySelector("i");
  icon.className = type === "password" ? "fas fa-eye" : "fas fa-eye-slash";
  passwordInput.focus();
});

// التحقق من صحة المدخلات
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
  validateField(usernameInput, "usernameHint"),
);
passwordInput.addEventListener("input", () =>
  validateField(passwordInput, "passwordHint"),
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

// ===== الاتصال بـ API ومعالجة تسجيل الدخول (مُحدّث) =====
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
      "يرجى إدخال اسم المستخدم وكلمة المرور",
    );
    if (username.length < 3) usernameInput.classList.add("error");
    if (password.length < 3) passwordInput.classList.add("error");
    return;
  }

  // تفعيل حالة التحميل
  loginBtn.classList.add("loading");
  loginBtn.disabled = true;

  try {
    // ✅ التعديل 2: استخدام الرابط الصحيح /login
    const response = await fetch(`${API_BASE_URL}/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        username: username,
        password: password,
      }),
    });

    const result = await response.json();

    // ✅ التعديل 3: التحقق من success حسب التوثيق
    if (result.success === true) {
      showMessage("success", "تم تسجيل الدخول", "جاري تحويلك للوحة التحكم...");

      // ✅ التعديل 4: تحديد مكان التخزين
      const storage = rememberMeCheckbox.checked
        ? localStorage
        : sessionStorage;

      // ✅ التعديل 5: حفظ التوكن (Bearer Token)
      if (result.token) {
        storage.setItem("authToken", result.token);
      }

      // ✅ التعديل 6: حفظ بيانات المستخدم كاملة (user_id, username, role_id)
      if (result.user) {
        storage.setItem("userData", JSON.stringify(result.user));

        // ✅ إضافة: حفظ role_id منفصلاً للاستخدام السريع في التحقق من الصلاحيات
        if (result.user.role_id) {
          storage.setItem("userRoleId", result.user.role_id);
        }
      }

      // ✅ إضافة: حفظ حالة "تذكرني"
      if (rememberMeCheckbox.checked) {
        localStorage.setItem("rememberMe", "true");
      }

      setTimeout(() => {
        window.location.href = "./assets/pages/dashboard.html";
      }, 1500);
    } else {
      // ✅ التعديل 7: استخدام مفتاح 'error' من الرد
      throw new Error(result.error || "بيانات الدخول غير صحيحة");
    }
  } catch (error) {
    loginBtn.classList.remove("loading");
    loginBtn.disabled = false;

    console.error("Login Error:", error);
    showMessage("error", "فشل تسجيل الدخول", error.message);

    // تأثير الاهتزاز
    const card = document.querySelector(".login-card");
    if (card) {
      card.style.animation = "shake 0.5s ease";
      setTimeout(() => (card.style.animation = ""), 500);
    }
  }
});

// ✅ إضافة: التحقق من وجود توكن سابق عند تحميل الصفحة
window.addEventListener("DOMContentLoaded", () => {
  const token =
    localStorage.getItem("authToken") || sessionStorage.getItem("authToken");
  const rememberMe = localStorage.getItem("rememberMe");

  // إذا كان هناك توكن و"تذكرني" مفعل، نحوله مباشرة للـ Dashboard
  if (token && rememberMe === "true") {
    // التحقق من صلاحية التوكن أولاً (اختياري)
    validateTokenAndRedirect(token);
  }
});

// ✅ إضافة: دالة للتحقق من صلاحية التوكن
async function validateTokenAndRedirect(token) {
  try {
    const response = await fetch(`${API_BASE_URL}/cards`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
    });

    if (response.ok) {
      // التوكن صالح، تحويل المستخدم
      window.location.href = "dashboard.html";
    } else {
      // التوكن منتهي، مسح البيانات
      clearAuthData();
    }
  } catch (error) {
    console.error("Token validation error:", error);
    clearAuthData();
  }
}

// ✅ إضافة: دالة لمسح بيانات المصادقة
function clearAuthData() {
  localStorage.removeItem("authToken");
  localStorage.removeItem("userData");
  localStorage.removeItem("userRoleId");
  localStorage.removeItem("rememberMe");
  sessionStorage.removeItem("authToken");
  sessionStorage.removeItem("userData");
  sessionStorage.removeItem("userRoleId");
}

// اختصارات لوحة المفاتيح
document.addEventListener("keydown", (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
    form.dispatchEvent(new Event("submit"));
  }
  if (e.key === "Escape") {
    clearMessages();
  }
});

passwordInput.addEventListener("copy", (e) => {
  e.preventDefault();
  showMessage("warning", "تنبيه أمني", "نسخ كلمة المرور غير مسموح به");
});
