<?php
set_time_limit(180);
// Auto-deploy webhook — called by GitHub Actions on every push to main.
// Lives in the same directory it deploys into, so no server paths need hardcoding.

$key_file = __DIR__ . '/.deploy_key';
if (!file_exists($key_file)) { http_response_code(500); die('Deploy key not configured'); }
$expected = trim(file_get_contents($key_file));
$provided = $_GET['token'] ?? '';
if (!$provided || !hash_equals($expected, $provided)) { http_response_code(403); die('Forbidden'); }

$dest = __DIR__;
$tmp  = sys_get_temp_dir();
$zip  = "$tmp/nobantees_deploy.zip";
$dir  = "$tmp/nobantees_extract";

// Only needed if the repo is ever made private — public repos download anonymously
$ghToken = '';
$ghTokenFile = __DIR__ . '/.github_token';
if (file_exists($ghTokenFile)) {
    $ghToken = trim(file_get_contents($ghTokenFile));
}

// Use the exact commit SHA from GitHub Actions if provided, otherwise fall back to main
$sha    = preg_replace('/[^a-f0-9]/i', '', $_GET['sha'] ?? '');
$ref    = $sha ?: 'main';
$zipUrl = "https://api.github.com/repos/vistecshare-Bert/nobantees/zipball/$ref";

$curlHeaders = ['User-Agent: NobanteesDeploy/1.0'];
if ($ghToken) $curlHeaders[] = "Authorization: token $ghToken";

// GitHub Actions fires the instant you push, but codeload's zip archive for a
// brand-new commit can lag a few seconds behind — retry before giving up.
$maxAttempts = 5;
$zipContent  = false;
$curlErr     = '';
$curlInfo    = [];
for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $ch = curl_init($zipUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => $curlHeaders,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $zipContent = curl_exec($ch);
    $curlErr    = curl_error($ch);
    $curlInfo   = curl_getinfo($ch);
    curl_close($ch);

    if ($zipContent && strlen($zipContent) >= 1000) break;
    if ($attempt < $maxAttempts) sleep(3);
}

if (!$zipContent || strlen($zipContent) < 1000) {
    http_response_code(500);
    die(json_encode([
        'status'     => 'error',
        'msg'        => 'ZIP download failed after ' . $maxAttempts . ' attempts',
        'curl_error' => $curlErr,
        'curl_info'  => $curlInfo,
        'body'       => substr($zipContent ?: '', 0, 500),
    ], JSON_PRETTY_PRINT));
}
file_put_contents($zip, $zipContent);

// Extract ZIP
exec("rm -rf " . escapeshellarg($dir) . " && mkdir -p " . escapeshellarg($dir));
exec("unzip -q " . escapeshellarg($zip) . " -d " . escapeshellarg($dir) . " 2>&1", $unzipOut, $unzipCode);
if ($unzipCode !== 0) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'msg' => 'Unzip failed', 'detail' => implode("\n", $unzipOut)]));
}

// GitHub ZIP extracts into a single subdir named "owner-repo-HASH/"
$subdirs = glob($dir . '/*/');
if (empty($subdirs)) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'msg' => 'No extracted directory found']));
}
$src = rtrim($subdirs[0], '/') . '/';
$ref = basename(rtrim($subdirs[0], '/'));

// Files that live only on the server — never overwrite from GitHub.
// Keep this in sync with .gitignore.
$excludes = [
    'config.php', '.deploy_key', '.github_token',
    'data/products.json', 'js/products.js',
    'data/decorated_products.json', 'data/decorated_categories.json',
    'data/users.json', 'users.json', 'orders.json',
    'pending_orders/', 'quotes/', 'contacts/',
    'images/hoodies/', 'images/shirts/', 'images/pants/',
];
$excludeFlags = implode(' ', array_map(fn($e) => '--exclude=' . escapeshellarg($e), $excludes));
$cmd = "/usr/bin/rsync -a --delete --chmod=D755,F644 $excludeFlags " . escapeshellarg($src) . ' ' . escapeshellarg($dest . '/') . ' 2>&1';
exec($cmd, $syncOut, $syncCode);

// Cleanup
exec("rm -f " . escapeshellarg($zip));
exec("rm -rf " . escapeshellarg($dir));

http_response_code(200);
echo json_encode([
    'status' => $syncCode === 0 ? 'ok' : 'sync_error',
    'ref'    => $ref,
    'size'   => strlen($zipContent),
    'sync'   => $syncCode === 0 ? 'success' : implode("\n", $syncOut),
]);
