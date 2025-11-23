<?php
// App en un solo archivo, 100% funcional
session_start();

// ----------------------
// 🚦 Router corregido
// ----------------------
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 🔥 Cualquier POST entra aquí → calculadora sin errores ni 404
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_calc_post();
    exit;
}

if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    show_home();
} elseif ($uri === '/info') {
    show_info();
} elseif ($uri === '/health') {
    show_health();
} else {
    show_404();
}

exit();

// ----------------------
// Helpers
// ----------------------
function envv($key, $default = '') {
    $v = getenv($key);
    return $v === false ? $default : $v;
}

// Eval matemático seguro
function safe_eval_math($expr, &$error = null) {
    $expr = trim($expr);
    $expr = str_replace(',', '.', $expr);
    $expr = preg_replace_callback('/([0-9\.]+)\s*%/', function($m){ return '(' . $m[1] . '/100)'; }, $expr);

    if (!preg_match('/^[0-9\.\+\-\*\/\(\)\s]+$/', $expr)) {
        $error = "Expresión no permitida.";
        return null;
    }

    $calc = '$r = ' . $expr . ';';
    try {
        $r = null;
        @eval($calc);
        $err = error_get_last();
        if ($err) { $error = $err['message']; return null; }
        if (!is_numeric($r)) { $error = "No numérico"; return null; }
        return is_float($r) ? round($r, 8) : $r;
    } catch (Throwable $t) {
        $error = $t->getMessage();
        return null;
    }
}

// Log
function app_log($msg) {
    error_log("webapp: " . $msg);
}

// ----------------------
// 🏠 Página principal
// ----------------------
function show_home() {
    $pod   = envv('POD_NAME', 'unknown-pod');
    $node  = envv('NODE_NAME', 'unknown-node');
    $banner= envv('BANNER', 'Bienvenido al ITM');

    header('Content-Type: text/html; charset=utf-8');

    echo "<!doctype html><html lang='es'><head><meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Calculadora</title>
    <style>
    body{background:#eef2f7;font-family:sans-serif;margin:0;}
    header{background:#0b5cff;padding:18px;color:white;}
    .wrap{max-width:900px;margin:auto;padding:16px;}
    .card{background:white;padding:16px;margin-bottom:14px;border-radius:8px;
          box-shadow:0 3px 10px rgba(0,0,0,.1);}
    input[type=text]{padding:10px;width:100%;font-size:16px;}
    button{padding:10px;background:#0b5cff;color:white;border:none;border-radius:6px;cursor:pointer;}
    table{width:100%;border-collapse:collapse;}
    td,th{border-bottom:1px solid #eee;padding:8px;}
    .small{color:#555;font-size:14px;}
    </style>
    </head><body>";

    echo "<header><h2>$banner</h2></header>";
    echo "<div class='wrap'>";

    echo "<div class='card'><strong>POD:</strong> $pod — <strong>NODE:</strong> $node</div>";

    echo "<div class='card'><h3>Calculadora</h3>
        <form method='POST' action='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>
            <input type='text' name='expr' placeholder='Ej: (3+2)*5 - 10%'>
            <br><br>
            <button type='submit'>Calcular</button>
        </form>";

    if (isset($_SESSION['last_result'])) {
        $lr = $_SESSION['last_result'];
        echo "<h4>Último resultado</h4>
              <pre>{$lr['expr']} = {$lr['result']}</pre>";
    }

    echo "</div>";

    // Historial
    echo "<div class='card'><h3>Historial</h3>";
    $history = $_SESSION['history'] ?? [];
    if (!$history) {
        echo "<p class='small'>No hay operaciones aún.</p>";
    } else {
        echo "<table><tr><th>Expr</th><th>Resultado</th><th>Pod</th><th>Fecha</th></tr>";
        foreach(array_reverse($history) as $h){
            echo "<tr>
                    <td>{$h['expr']}</td>
                    <td>{$h['result']}</td>
                    <td>{$h['pod']}</td>
                    <td>{$h['time']}</td>
                </tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='card'><h4>API:</h4>
            <pre>/info → JSON
/health → ok</pre></div>";

    echo "</div></body></html>";
}

// ----------------------
// 🔢 Procesar cálculo
// ----------------------
function handle_calc_post() {
    $expr = trim($_POST['expr'] ?? '');
    $pod  = envv('POD_NAME', 'unknown-pod');

    if ($expr === '') {
        $_SESSION['last_result'] = ['expr'=>$expr,'result'=>"Error: vacío",'time'=>date('c')];
        header("Location: /");
        return;
    }

    $error = null;
    $res = safe_eval_math($expr, $error);
    $display = ($error ? "Error: $error" : $res);

    if (!isset($_SESSION['history'])) { $_SESSION['history'] = []; }

    $_SESSION['history'][] = [
        'expr'=>$expr,
        'result'=>$display,
        'pod'=>$pod,
        'time'=>date('c')
    ];

    $_SESSION['last_result'] = [
        'expr'=>$expr,
        'result'=>$display,
        'time'=>date('c')
    ];

    header("Location: /");
    exit;
}

// ----------------------
// /info
// ----------------------
function show_info() {
    $out = [
        'pod'=>envv('POD_NAME','unknown'),
        'node'=>envv('NODE_NAME','unknown'),
        'banner'=>envv('BANNER','ITM'),
        'php'=>phpversion(),
        'time'=>date('c')
    ];
    header('Content-Type: application/json');
    echo json_encode($out, JSON_PRETTY_PRINT);
}

// ----------------------
// /health
// ----------------------
function show_health() {
    header('Content-Type:text/plain');
    echo "ok";
}

// ----------------------
// 404
// ----------------------
function show_404() {
    http_response_code(404);
    echo "<h1>404 - Página no encontrada</h1>";
}
