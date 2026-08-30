<?php
session_start();

const APP_NAME = 'Linkiraq';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'linkiraq';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(): void { if (!user()) { header('Location: auth.php'); exit; } }
function require_admin(): void { if (!user() || (user()['role'] ?? '') !== 'admin') { http_response_code(403); exit('Forbidden'); } }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid CSRF token'); }
}

function lang(): string {
    $allowed = ['ar','ku','en'];
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) $_SESSION['lang'] = $_GET['lang'];
    return $_SESSION['lang'] ?? 'ar';
}

$translations = [
'ar'=>['home'=>'الرئيسية','login'=>'دخول','register'=>'إنشاء حساب','dashboard'=>'لوحة التحكم','pricing'=>'الخطط','headline'=>'كل روابطك في مكان واحد، بهوية تشبهك','sub'=>'أنشئ صفحتك الشخصية، شارك روابطك، تابع الأداء، وطور حضورك الرقمي بسهولة.','start'=>'ابدأ مجاناً','links'=>'روابطي','analytics'=>'الإحصائيات','appearance'=>'المظهر','billing'=>'الاشتراك','admin'=>'الإدارة','logout'=>'تسجيل الخروج'],
'ku'=>['home'=>'سەرەکی','login'=>'چوونەژوورەوە','register'=>'هەژمار دروست بکە','dashboard'=>'داشبۆرد','pricing'=>'پلانەکان','headline'=>'هەموو بەستەرەکانت لە یەک شوێن','sub'=>'پەڕەی تایبەتی خۆت دروست بکە، بەستەرەکان بڵاو بکەوە و ئەنجامەکان ببینە.','start'=>'بەخۆڕایی دەست پێبکە','links'=>'بەستەرەکانم','analytics'=>'ئامار','appearance'=>'ڕووکار','billing'=>'بەشداری','admin'=>'بەڕێوەبردن','logout'=>'دەرچوون'],
'en'=>['home'=>'Home','login'=>'Login','register'=>'Sign up','dashboard'=>'Dashboard','pricing'=>'Pricing','headline'=>'All your links in one place, with a profile that feels like you','sub'=>'Create your page, share links, track performance, and grow your digital presence.','start'=>'Start free','links'=>'My links','analytics'=>'Analytics','appearance'=>'Appearance','billing'=>'Billing','admin'=>'Admin','logout'=>'Logout']
];
function t(string $key): string { global $translations; return $translations[lang()][$key] ?? $key; }
function dir_attr(): string { return lang() === 'en' ? 'ltr' : 'rtl'; }
