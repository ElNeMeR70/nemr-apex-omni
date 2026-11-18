<?php
declare(strict_types=1);

/**
 * Nemr Apex Omni - Developer Platform & API Gateway
 * Subdomain: apex.elnemer-ai.com
 * PHP Version: 8.3 | Powered by LiteSpeed Enterprise
 */

// Simple autoloader for Nemr\Apex classes
spl_autoload_register(function ($class) {
    $prefix = 'Nemr\\Apex\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use Nemr\Apex\Database\QueryBuilder;
use Nemr\Apex\Http\Router;
use Nemr\Apex\Http\Middleware\JwtAuth;
use Nemr\Apex\Services\PaymobGateway;
use Nemr\Apex\Services\StripeGateway;

// API Route Handling
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput ?: '[]', true) ?: $_POST;

    // 1. Health check
    if ($requestUri === '/api/health') {
        echo json_encode([
            'status' => 'healthy',
            'server' => 'LiteSpeed / Hostinger',
            'php_version' => PHP_VERSION,
            'subdomain' => 'apex.elnemer-ai.com',
            'timestamp' => date('c'),
            'features' => ['QueryBuilder', 'Router', 'JwtAuth', 'PaymobGateway', 'StripeGateway']
        ]);
        exit;
    }

    // 2. Query Builder SQL Demo
    if ($requestUri === '/api/query-demo') {
        $table = $inputData['table'] ?? 'orders';
        $status = $inputData['status'] ?? 'paid';
        $minAmount = (float)($inputData['min_amount'] ?? 100.0);

        $qb = QueryBuilder::table($table)
            ->select('id', 'customer_name', 'amount', 'currency', 'status')
            ->where('status', '=', $status)
            ->where('amount', '>=', $minAmount)
            ->orderBy('id', 'DESC')
            ->limit(10);

        echo json_encode([
            'status' => 'success',
            'sql' => $qb->toSql(),
            'bindings' => $qb->getBindings(),
            'safe' => true,
            'injection_proof' => '100% Prepared Statements via PDO'
        ]);
        exit;
    }

    // 3. JWT Demo
    if ($requestUri === '/api/jwt-demo') {
        $secret = $inputData['secret'] ?? 'apex-secret-key-2026';
        $user = $inputData['user'] ?? ['id' => 101, 'email' => 'dev@elnemer-ai.com', 'role' => 'lead_engineer'];

        $token = JwtAuth::generate($user, $secret, 3600);
        $verified = JwtAuth::verify($token, $secret);

        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'verified_payload' => $verified,
            'algorithm' => 'HMAC-SHA256'
        ]);
        exit;
    }

    // 4. Paymob HMAC Simulator
    if ($requestUri === '/api/paymob-verify') {
        $secret = $inputData['hmac_secret'] ?? 'test_hmac_secret_apex';
        $sampleData = [
            'amount_cents' => '50000',
            'created_at' => date('Y-m-d H:i:s'),
            'currency' => 'EGP',
            'error_occured' => 'false',
            'has_parent_transaction' => 'false',
            'id' => '9845214',
            'integration_id' => '44123',
            'is_3d_secure' => 'true',
            'is_auth' => 'false',
            'is_capture' => 'false',
            'is_refunded' => 'false',
            'is_standalone_payment' => 'true',
            'is_voided' => 'false',
            'order' => ['id' => '12054'],
            'owner' => '8412',
            'pending' => 'false',
            'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
            'success' => 'true'
        ];

        $gateway = new PaymobGateway(hmacSecret: $secret);
        
        // Calculate simulated HMAC
        $keys = [
            'amount_cents','created_at','currency','error_occured','has_parent_transaction',
            'id','integration_id','is_3d_secure','is_auth','is_capture','is_refunded',
            'is_standalone_payment','is_voided','order.id','owner','pending','source_data.pan',
            'source_data.sub_type','source_data.type','success'
        ];
        $concat = '';
        foreach ($keys as $k) {
            $val = str_contains($k, '.') ? ($sampleData[explode('.', $k)[0]][explode('.', $k)[1]] ?? '') : ($sampleData[$k] ?? '');
            $concat .= (string)$val;
        }
        $hmac = hash_hmac('sha512', $concat, $secret);
        $valid = $gateway->verifyCallback($sampleData, $hmac);

        echo json_encode([
            'status' => 'success',
            'simulated_hmac' => $hmac,
            'is_valid' => $valid,
            'algorithm' => 'HMAC-SHA512 (Egyptian Payment Gateway Standard)'
        ]);
        exit;
    }

    echo json_encode(['error' => 'Not Found', 'endpoint' => $requestUri]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nemr Apex Omni | PHP 8.3 & AI Developer Engineering OS</title>
    <meta name="description" content="Enterprise PHP 8.3 architecture, zero-dependency QueryBuilder, REST Router, and Payment Handlers for modern developers.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            /* LUXURY LIGHT THEME (Default) */
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-alt: #f1f5f9;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --emerald: #10b981;
            --emerald-light: #ecfdf5;
            --amber: #f59e0b;
            --amber-light: #fffbeb;
            --accent: #2563eb;
            --code-bg: #0f172a;
            --code-text: #f8fafc;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        [data-theme="dark"] {
            --bg-page: #090d16;
            --bg-card: #111827;
            --bg-card-alt: #1f2937;
            --border: #1f2937;
            --border-hover: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --primary: #6366f1;
            --primary-hover: #818cf8;
            --primary-light: rgba(99, 102, 241, 0.15);
            --code-bg: #030712;
            --code-text: #f3f4f6;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* HEADER & NAVIGATION */
        header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }
        [data-theme="dark"] header {
            background: rgba(17, 24, 39, 0.85);
        }
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.25rem;
        }
        .brand-badge {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #ffffff;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.925rem;
            font-weight: 600;
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .theme-toggle-btn {
            background: var(--bg-card-alt);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.45rem 0.85rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* HERO SECTION */
        .hero {
            max-width: 1280px;
            margin: 0 auto;
            padding: 4rem 1.5rem 2.5rem;
            text-align: center;
        }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(79, 70, 229, 0.2);
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
        }
        .hero h1 span {
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.15rem;
            color: var(--text-secondary);
            max-width: 760px;
            margin: 0 auto 2rem;
        }
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
        }
        .stat-val {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        .stat-lbl {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* MAIN CONTAINER & GRID */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1.5rem 5rem;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
            .hero h1 { font-size: 2.25rem; }
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
        }
        .card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
        }
        .badge-indigo { background: var(--primary-light); color: var(--primary); }
        .badge-emerald { background: var(--emerald-light); color: var(--emerald); }

        /* CODE BLOCK & TABS */
        .tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .tab-btn {
            background: transparent;
            border: none;
            padding: 0.6rem 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .code-container {
            background: var(--code-bg);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--code-text);
            overflow-x: auto;
            position: relative;
            line-height: 1.5;
        }
        .copy-btn {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .copy-btn:hover { background: rgba(255, 255, 255, 0.25); }

        /* INTERACTIVE PLAYGROUND */
        .interactive-box {
            background: var(--bg-card-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-top: 1rem;
        }
        .btn {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.6rem 1.2rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn:hover { background: var(--primary-hover); }

        /* EXACT FLOATING ANTIGRAVITY USAGE WIDGET */
        #usage-pill {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            border-radius: 9999px;
            padding: 0.55rem 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            cursor: pointer;
            z-index: 100;
            font-weight: 700;
            font-size: 0.85rem;
            color: #0f172a;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease;
        }
        #usage-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -5px rgba(0,0,0,0.15);
        }
        .pulse-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        /* THE EXACT ANTIGRAVITY USAGE MODAL (From Screenshot) */
        #usage-modal {
            position: fixed;
            bottom: 74px;
            right: 24px;
            width: 320px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
            z-index: 101;
            padding: 1.1rem 1.25rem 1.25rem;
            display: none;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
        }
        #usage-modal.active {
            display: block;
            animation: fadeIn 0.15s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 0.75rem;
        }
        .modal-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #111827;
        }
        .spinner-icon {
            width: 14px;
            height: 14px;
            border: 2px solid #e5e7eb;
            border-top-color: #6b7280;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .usage-section {
            margin-bottom: 0.85rem;
        }
        .usage-section:last-child {
            margin-bottom: 0;
        }
        .section-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.35rem;
        }
        .limit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #4b5563;
            margin-bottom: 0.2rem;
        }
        .limit-sub {
            font-size: 0.72rem;
            color: #6b7280;
            margin-bottom: 0.4rem;
        }
        .ring-indicator {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            color: #111827;
        }
        .ring-svg {
            width: 15px;
            height: 15px;
            transform: rotate(-90deg);
        }
        .ring-bg {
            fill: none;
            stroke: #e5e7eb;
            stroke-width: 2.5;
        }
        .ring-fill {
            fill: none;
            stroke-linecap: round;
            stroke-width: 2.5;
            transition: stroke-dasharray 0.5s ease;
        }
    </style>
</head>
<body>

    <!-- STICKY HEADER -->
    <header>
        <div class="nav-container">
            <a href="/" class="brand">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: #4f46e5;">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                <span>NEMR APEX OMNI</span>
                <span class="brand-badge">PHP 8.3 ARCHITECTURE</span>
            </a>
            <div class="nav-links">
                <a href="#querybuilder" class="nav-link">QueryBuilder</a>
                <a href="#router" class="nav-link">REST Router</a>
                <a href="#payments" class="nav-link">Paymob & Stripe</a>
                <a href="#playground" class="nav-link">Live API</a>
                <button class="theme-toggle-btn" id="theme-btn" onclick="toggleTheme()">
                    <span id="theme-icon">🌙</span> <span id="theme-text">Dark Mode</span>
                </button>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-pill">
            <span>⚡ Running natively on apex.elnemer-ai.com</span>
            <span>•</span>
            <span>LiteSpeed & PHP 8.3 Enterprise</span>
        </div>
        <h1>High-Performance PHP 8.3 Engine & <span>AI Developer OS</span></h1>
        <p>
            Zero-dependency PDO QueryBuilder with 100% SQL-injection immunity, sub-millisecond REST Router with middleware onion pipelines, and hardened Egyptian (Paymob) & International (Stripe) payment verification.
        </p>
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-val">0.00ms</div>
                <div class="stat-lbl">External Dependencies</div>
            </div>
            <div class="stat-item">
                <div class="stat-val">100%</div>
                <div class="stat-lbl">PDO Parameterized</div>
            </div>
            <div class="stat-item">
                <div class="stat-val">PHP 8.3</div>
                <div class="stat-lbl">Readonly & Typed</div>
            </div>
            <div class="stat-item">
                <div class="stat-val">Paymob + Stripe</div>
                <div class="stat-lbl">HMAC SHA512 Verified</div>
            </div>
        </div>
    </section>

    <!-- MAIN GRID ARCHITECTURE -->
    <main class="container">
        
        <!-- ROW 1: QUERY BUILDER & REST ROUTER -->
        <div class="grid-2">
            
            <!-- CARD 1: QUERY BUILDER -->
            <div class="card" id="querybuilder">
                <div class="card-header">
                    <div class="card-title">
                        <span>🛡️ Zero-Injection QueryBuilder</span>
                    </div>
                    <span class="badge badge-emerald">PHP 8.2 / 8.3</span>
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Fluent, chainable query builder that compiles strict SQL with automated PDO bindings. Protects against 100% of OWASP A03 Injection vectors.
                </p>
                <div class="code-container">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
<pre><code>use Nemr\Apex\Database\QueryBuilder;

$orders = QueryBuilder::table('orders', $pdo)
    ->select('id', 'customer_name', 'amount', 'status')
    ->where('status', '=', 'completed')
    ->where('amount', '>=', 500.0)
    ->whereIn('currency', ['EGP', 'USD'])
    ->orderBy('id', 'DESC')
    ->limit(20)
    ->get();</code></pre>
                </div>
                <div class="interactive-box">
                    <strong style="font-size: 0.85rem; color: var(--text-primary); display: block; margin-bottom: 0.4rem;">
                        Interactive SQL Compiler Test:
                    </strong>
                    <button class="btn" onclick="testQueryBuilder()">Run Live SQL Generation</button>
                    <pre id="qb-result" style="margin-top: 0.6rem; font-size: 0.78rem; font-family: monospace; color: var(--text-secondary); white-space: pre-wrap;"></pre>
                </div>
            </div>

            <!-- CARD 2: REST ROUTER -->
            <div class="card" id="router">
                <div class="card-header">
                    <div class="card-title">
                        <span>🚀 Fast Router & JWT Pipeline</span>
                    </div>
                    <span class="badge badge-indigo">Onion Middleware</span>
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Lightweight RESTful router with route parameters <code>{id}</code>, route groups with prefix inheritance, and layered HMAC-SHA256 JWT validation.
                </p>
                <div class="code-container">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
<pre><code>use Nemr\Apex\Http\Router;
use Nemr\Apex\Http\Middleware\JwtAuth;

$router = new Router();

$router->group('/api/v1', function (Router $api) {
    $api->get('/users/{id}', [UserController::class, 'show']);
    $api->post('/orders', [OrderController::class, 'create'], [
        JwtAuth::middleware('apex_secret_key')
    ]);
});

$response = $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);</code></pre>
                </div>
                <div class="interactive-box">
                    <strong style="font-size: 0.85rem; color: var(--text-primary); display: block; margin-bottom: 0.4rem;">
                        Live JWT Generator & Validator:
                    </strong>
                    <button class="btn" onclick="testJwtAuth()">Simulate JWT Flow</button>
                    <pre id="jwt-result" style="margin-top: 0.6rem; font-size: 0.78rem; font-family: monospace; color: var(--text-secondary); white-space: pre-wrap;"></pre>
                </div>
            </div>

        </div>

        <!-- ROW 2: EGYPTIAN & GLOBAL PAYMENTS -->
        <div class="grid-2">
            
            <!-- CARD 3: PAYMOB ACCEPT -->
            <div class="card" id="payments">
                <div class="card-header">
                    <div class="card-title">
                        <span>🇪🇬 Paymob Accept Egyptian Gateway</span>
                    </div>
                    <span class="badge badge-emerald">Vodafone Cash / InstaPay</span>
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Comprehensive integration for Egyptian cards, Mobile Wallets (Vodafone Cash, Orange, Etisalat, WE), and InstaPay with strict HMAC-SHA512 validation.
                </p>
                <div class="code-container">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
<pre><code>use Nemr\Apex\Services\PaymobGateway;

$gateway = new PaymobGateway(hmacSecret: getenv('PAYMOB_HMAC'));

// Verify transaction callback from Paymob
$isValid = $gateway->verifyCallback(
    data: $_POST,
    receivedHmac: $_GET['hmac'] ?? ''
);

if ($isValid && $_POST['success'] === 'true') {
    // Fulfill order securely
}</code></pre>
                </div>
                <div class="interactive-box">
                    <button class="btn" onclick="testPaymobVerify()">Test Paymob HMAC-SHA512</button>
                    <pre id="paymob-result" style="margin-top: 0.6rem; font-size: 0.78rem; font-family: monospace; color: var(--text-secondary); white-space: pre-wrap;"></pre>
                </div>
            </div>

            <!-- CARD 4: STRIPE GATEWAY -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>💳 Stripe International Checkout</span>
                    </div>
                    <span class="badge badge-indigo">Webhook Signature</span>
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Zero-SDK Stripe Webhook verifier with timestamp drift protection and constant-time HMAC-SHA256 signature comparison.
                </p>
                <div class="code-container">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
<pre><code>use Nemr\Apex\Services\StripeGateway;

$stripe = new StripeGateway(webhookSecret: getenv('STRIPE_WEBHOOK_SECRET'));

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if ($stripe->verifyWebhookSignature($payload, $sigHeader, tolerance: 300)) {
    $event = json_decode($payload, true);
    // Process checkout.session.completed
}</code></pre>
                </div>
                <div class="interactive-box">
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                        ⚡ Constant-time verification prevents timing attacks on webhook secrets.
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- FLOATING USAGE BUTTON -->
    <div id="usage-pill" onclick="toggleUsageModal()">
        <span class="pulse-dot"></span>
        <span>Usage 77% left</span>
    </div>

    <!-- EXACT ANTIGRAVITY USAGE MODAL (Matched to Image 1) -->
    <div id="usage-modal">
        <div class="modal-header">
            <span class="modal-title">Usage</span>
            <div class="spinner-icon"></div>
        </div>

        <!-- Gemini Models -->
        <div class="usage-section">
            <div class="section-name">Gemini Models</div>
            
            <div class="limit-row">
                <span>Weekly limit</span>
                <div class="ring-indicator">
                    <span>77% left</span>
                    <svg class="ring-svg" viewBox="0 0 20 20">
                        <circle class="ring-bg" cx="10" cy="10" r="7"></circle>
                        <circle class="ring-fill" cx="10" cy="10" r="7" stroke="#22c55e" stroke-dasharray="33.8 44"></circle>
                    </svg>
                </div>
            </div>
            <div class="limit-sub">Resets in 4d 3h</div>

            <div class="limit-row">
                <span>5h limit</span>
                <div class="ring-indicator">
                    <span>47% left</span>
                    <svg class="ring-svg" viewBox="0 0 20 20">
                        <circle class="ring-bg" cx="10" cy="10" r="7"></circle>
                        <circle class="ring-fill" cx="10" cy="10" r="7" stroke="#22c55e" stroke-dasharray="20.6 44"></circle>
                    </svg>
                </div>
            </div>
            <div class="limit-sub">Resets in 1h 24m</div>
        </div>

        <!-- Claude and GPT models -->
        <div class="usage-section">
            <div class="section-name">Claude and GPT models</div>
            
            <div class="limit-row">
                <span>Weekly limit</span>
                <div class="ring-indicator">
                    <span>19% left</span>
                    <svg class="ring-svg" viewBox="0 0 20 20">
                        <circle class="ring-bg" cx="10" cy="10" r="7"></circle>
                        <circle class="ring-fill" cx="10" cy="10" r="7" stroke="#f59e0b" stroke-dasharray="8.3 44"></circle>
                    </svg>
                </div>
            </div>
            <div class="limit-sub">Resets in 1d 20h</div>

            <div class="limit-row">
                <span>5h limit</span>
                <div class="ring-indicator">
                    <span>100% left</span>
                    <svg class="ring-svg" viewBox="0 0 20 20">
                        <circle class="ring-bg" cx="10" cy="10" r="7"></circle>
                        <circle class="ring-fill" cx="10" cy="10" r="7" stroke="#22c55e" stroke-dasharray="44 44"></circle>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Gemini 4 Argon -->
        <div class="usage-section">
            <div class="section-name">Gemini 4 Argon</div>
            
            <div class="limit-row">
                <span>Weekly limit</span>
                <div class="ring-indicator">
                    <span>89% left</span>
                    <svg class="ring-svg" viewBox="0 0 20 20">
                        <circle class="ring-bg" cx="10" cy="10" r="7"></circle>
                        <circle class="ring-fill" cx="10" cy="10" r="7" stroke="#22c55e" stroke-dasharray="39.1 44"></circle>
                    </svg>
                </div>
            </div>
            <div class="limit-sub">Weekly limit, resets in 6d 21h</div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            document.getElementById('theme-icon').textContent = target === 'dark' ? '☀️' : '🌙';
            document.getElementById('theme-text').textContent = target === 'dark' ? 'Light Mode' : 'Dark Mode';
        }

        function toggleUsageModal() {
            const modal = document.getElementById('usage-modal');
            modal.classList.toggle('active');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('usage-modal');
            const pill = document.getElementById('usage-pill');
            if (modal.classList.contains('active') && !modal.contains(e.target) && !pill.contains(e.target)) {
                modal.classList.remove('active');
            }
        });

        function copyCode(btn) {
            const pre = btn.nextElementSibling;
            navigator.clipboard.writeText(pre.innerText).then(() => {
                const orig = btn.innerText;
                btn.innerText = 'Copied!';
                setTimeout(() => btn.innerText = orig, 2000);
            });
        }

        async function testQueryBuilder() {
            const target = document.getElementById('qb-result');
            target.textContent = 'Executing query compiler...';
            try {
                const res = await fetch('/api/query-demo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table: 'orders', status: 'paid', min_amount: 250.0 })
                });
                const data = await res.json();
                target.textContent = JSON.stringify(data, null, 2);
            } catch (err) {
                target.textContent = 'Error: ' + err.message;
            }
        }

        async function testJwtAuth() {
            const target = document.getElementById('jwt-result');
            target.textContent = 'Generating & verifying token...';
            try {
                const res = await fetch('/api/jwt-demo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ secret: 'apex_demo_key_2026', user: { id: 701, name: 'Mahmoud ElNeMeR', role: 'admin' } })
                });
                const data = await res.json();
                target.textContent = JSON.stringify(data, null, 2);
            } catch (err) {
                target.textContent = 'Error: ' + err.message;
            }
        }

        async function testPaymobVerify() {
            const target = document.getElementById('paymob-result');
            target.textContent = 'Calculating Paymob HMAC-SHA512...';
            try {
                const res = await fetch('/api/paymob-verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ hmac_secret: 'demo_egypt_secret' })
                });
                const data = await res.json();
                target.textContent = JSON.stringify(data, null, 2);
            } catch (err) {
                target.textContent = 'Error: ' + err.message;
            }
        }
    </script>
</body>
</html>
