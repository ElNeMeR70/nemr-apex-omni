// Nemr Omni-SaaS Dashboard Logic
let currentLang = 'ar';

const DICT = {
  ar: {
    brandTitle: "منظومة النمر للعمليات الذكية",
    brandSub: "Nemr Omni-Apex Enterprise Dashboard • مزامنة حية",
    btnLang: "🌐 English",
    btnNew: "إضافة سجل جديد",
    kpi1Title: "إجمالي الإيرادات",
    kpi1Sub: "مقارنة بالشهر السابق",
    kpi2Title: "العملاء النشطون",
    kpi2Sub: "نمو متواصل",
    kpi3Title: "العمليات المنفذة",
    kpi3Sub: "نسبة استقرار النظام",
    kpi4Title: "درع الأمان السيبراني",
    kpi4Sub: "حماية نشطة على مدار الساعة",
    tblTitle: "سجل العمليات والصفقات الحية",
    tblSub: "مزامنة فورية مع قاعدة البيانات المركزية • بحث وتصدير فوري",
    btnExport: "تصدير CSV",
    thName: "الاسم / العميل",
    thEmail: "البريد الإلكتروني",
    thPlan: "الخطة / الخدمة",
    thStatus: "الحالة",
    thAmount: "القيمة",
    thActions: "الإجراءات",
    modalTitle: "إضافة سجل جديد",
    lblName: "اسم العميل:",
    lblEmail: "البريد الإلكتروني:",
    lblPlan: "الخطة:",
    lblAmount: "القيمة ($):",
    btnCancel: "إلغاء",
    btnSave: "حفظ وتأكيد",
    toastSaved: "تم حفظ السجل بنجاح ومزامنته!",
    toastDeleted: "تم حذف السجل بنجاح.",
  },
  en: {
    brandTitle: "Nemr Intelligent Operations OS",
    brandSub: "Nemr Omni-Apex Enterprise Dashboard • Live Telemetry",
    btnLang: "🌐 عربي",
    btnNew: "Add New Record",
    kpi1Title: "Total Revenue",
    kpi1Sub: "vs last month",
    kpi2Title: "Active Clients",
    kpi2Sub: "steady growth",
    kpi3Title: "Executed Operations",
    kpi3Sub: "system uptime",
    kpi4Title: "Cybersecurity Shield",
    kpi4Sub: "active protection 24/7",
    tblTitle: "Live Operations & Deals Ledger",
    tblSub: "Instant sync with central DB • search & export",
    btnExport: "Export CSV",
    thName: "Client / Entity",
    thEmail: "Email Address",
    thPlan: "Plan / Tier",
    thStatus: "Status",
    thAmount: "Amount",
    thActions: "Actions",
    modalTitle: "Add New Record",
    lblName: "Client Name:",
    lblEmail: "Email Address:",
    lblPlan: "Plan Tier:",
    lblAmount: "Amount ($):",
    btnCancel: "Cancel",
    btnSave: "Save & Confirm",
    toastSaved: "Record saved and synced successfully!",
    toastDeleted: "Record deleted successfully.",
  }
};

let records = [
  { id: 101, name: "شركة النمر للذكاء الاصطناعي", email: "info@elnemer-ai.com", plan: "Elite Sovereign", status: "Active", amount: 199 },
  { id: 102, name: "منصة شمس الرقمية", email: "contact@m-shams.com", plan: "Business Scale", status: "Active", amount: 99 },
  { id: 103, name: "مؤسسة هيام جمعة للاستشارات", email: "ceo@hayamgomaa.net", plan: "Pro Enterprise", status: "Active", amount: 49 },
  { id: 104, name: "Chatbot Gold SaaS", email: "support@chatbotgold.net", plan: "Elite Sovereign", status: "Active", amount: 199 },
  { id: 105, name: "سلطان النمر كليك", email: "admin@sultanelnemer.click", plan: "Pro Enterprise", status: "Pending", amount: 49 },
];

function initApp() {
  const saved = localStorage.getItem('nemr_saas_records');
  if (saved) {
    try { records = JSON.parse(saved); } catch(e) {}
  }
  renderTable();
  updateKPIs();
}

function renderTable(filterText = '') {
  const tbody = document.getElementById('table-body');
  const filtered = records.filter(r => 
    r.name.toLowerCase().includes(filterText.toLowerCase()) ||
    r.email.toLowerCase().includes(filterText.toLowerCase()) ||
    r.plan.toLowerCase().includes(filterText.toLowerCase())
  );

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">لا توجد سجلات مطابقة.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map((r, i) => `
    <tr>
      <td style="font-family:'JetBrains Mono', monospace;color:var(--text-muted);">${r.id}</td>
      <td><strong>${r.name}</strong></td>
      <td style="color:var(--text-muted);font-family:'JetBrains Mono', monospace;">${r.email}</td>
      <td><span class="badge" style="background:rgba(6,182,212,0.15);color:var(--cyan);border:1px solid rgba(6,182,212,0.3);">${r.plan}</span></td>
      <td><span class="badge ${r.status === 'Active' ? 'badge-active' : 'badge-pending'}">${r.status}</span></td>
      <td style="font-weight:700;font-family:'JetBrains Mono', monospace;">$${r.amount}</td>
      <td>
        <button class="btn btn-glass" style="padding:4px 10px;font-size:0.75rem;color:var(--red);" onclick="deleteRecord(${r.id})">حذف</button>
      </td>
    </tr>
  `).join('');
}

function updateKPIs() {
  const totalRev = records.reduce((acc, r) => acc + Number(r.amount), 0);
  document.getElementById('kpi-revenue').textContent = `$${totalRev.toLocaleString()}`;
  document.getElementById('kpi-users').textContent = records.length;
}

function handleSearch(val) {
  renderTable(val);
}

function openNewModal() {
  document.getElementById('record-modal').classList.add('open');
}

function closeModal() {
  document.getElementById('record-modal').classList.remove('open');
  document.getElementById('record-form').reset();
}

function handleFormSubmit(e) {
  e.preventDefault();
  const name = document.getElementById('f-name').value;
  const email = document.getElementById('f-email').value;
  const plan = document.getElementById('f-plan').value;
  const amount = Number(document.getElementById('f-amount').value);

  const newRec = {
    id: Math.floor(100 + Math.random() * 900),
    name,
    email,
    plan,
    status: 'Active',
    amount
  };

  records.unshift(newRec);
  localStorage.setItem('nemr_saas_records', JSON.stringify(records));
  renderTable();
  updateKPIs();
  closeModal();
  showToast(DICT[currentLang].toastSaved);
}

function deleteRecord(id) {
  if (confirm(currentLang === 'ar' ? 'هل أنت متأكد من حذف هذا السجل؟' : 'Are you sure you want to delete this record?')) {
    records = records.filter(r => r.id !== id);
    localStorage.setItem('nemr_saas_records', JSON.stringify(records));
    renderTable();
    updateKPIs();
    showToast(DICT[currentLang].toastDeleted);
  }
}

function exportDataCSV() {
  let csv = 'ID,Name,Email,Plan,Status,Amount\n';
  records.forEach(r => {
    csv += `"${r.id}","${r.name}","${r.email}","${r.plan}","${r.status}","${r.amount}"\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `nemr_saas_export_${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

function toggleLanguage() {
  currentLang = currentLang === 'ar' ? 'en' : 'ar';
  const d = DICT[currentLang];
  document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
  document.documentElement.lang = currentLang;

  document.getElementById('t-brand-title').textContent = d.brandTitle;
  document.getElementById('t-brand-sub').textContent = d.brandSub;
  document.getElementById('btn-lang').textContent = d.btnLang;
  document.getElementById('t-btn-new').textContent = d.btnNew;
  document.getElementById('t-kpi-1-title').textContent = d.kpi1Title;
  document.getElementById('t-kpi-1-sub').textContent = d.kpi1Sub;
  document.getElementById('t-kpi-2-title').textContent = d.kpi2Title;
  document.getElementById('t-kpi-2-sub').textContent = d.kpi2Sub;
  document.getElementById('t-kpi-3-title').textContent = d.kpi3Title;
  document.getElementById('t-kpi-3-sub').textContent = d.kpi3Sub;
  document.getElementById('t-kpi-4-title').textContent = d.kpi4Title;
  document.getElementById('t-kpi-4-sub').textContent = d.kpi4Sub;
  document.getElementById('t-tbl-title').textContent = d.tblTitle;
  document.getElementById('t-tbl-sub').textContent = d.tblSub;
  document.getElementById('t-btn-export').textContent = d.btnExport;
  document.getElementById('th-name').textContent = d.thName;
  document.getElementById('th-email').textContent = d.thEmail;
  document.getElementById('th-plan').textContent = d.thPlan;
  document.getElementById('th-status').textContent = d.thStatus;
  document.getElementById('th-amount').textContent = d.thAmount;
  document.getElementById('th-actions').textContent = d.thActions;
  document.getElementById('t-modal-title').textContent = d.modalTitle;
  document.getElementById('lbl-name').textContent = d.lblName;
  document.getElementById('lbl-email').textContent = d.lblEmail;
  document.getElementById('lbl-plan').textContent = d.lblPlan;
  document.getElementById('lbl-amount').textContent = d.lblAmount;
  document.getElementById('btn-cancel').textContent = d.btnCancel;
  document.getElementById('btn-save').textContent = d.btnSave;
}

function showToast(msg) {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

document.addEventListener('DOMContentLoaded', initApp);
