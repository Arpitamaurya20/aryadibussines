<?php
/**
 * WhatsApp gateway connectivity diagnostic.
 *
 * Run this from the production server (browser hit:
 *   https://<your-domain>/hrms/hrms-salary/action/wa-diagnose.php
 * ) to find out which network stage is blocking the chatmybot.in call.
 *
 * It does NOT send any real WhatsApp message. It only probes:
 *   1. DNS resolution of the gateway host
 *   2. TCP connect on port 443
 *   3. TLS handshake
 *   4. HTTP HEAD against the gateway URL
 *
 * Output is plain text (easy to copy/paste back if needed).
 *
 * IMPORTANT: delete this file (or restrict it behind admin auth) once you've
 * finished debugging - it exposes outbound network information.
 */

@session_start();
header('Content-Type: text/plain; charset=UTF-8');

require_once('../../include/autoloader.inc.php');

$conf = new Conf();
$wa = is_array($conf->_WhatsApp) ? $conf->_WhatsApp : [];
$endpoint = trim((string) ($wa['_BatchEndpoint'] ?? ''));
$token    = trim((string) ($wa['_ApiToken']      ?? ''));
$scheme   = trim((string) ($wa['_AuthScheme']    ?? ''));

echo "=== WhatsApp gateway diagnostic ===\n";
echo "Time:      " . date('Y-m-d H:i:s') . "\n";
echo "PHP:       " . PHP_VERSION . "\n";
echo "cURL:     "  . (function_exists('curl_version') ? (curl_version()['version'] ?? '?') : 'NOT INSTALLED') . "\n";
echo "OpenSSL:   " . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : '?') . "\n";
echo "Endpoint:  " . ($endpoint !== '' ? $endpoint : '<not set>') . "\n";
echo "Scheme:    " . ($scheme   !== '' ? $scheme   : '<not set>') . "\n";
echo "Token set: " . ($token    !== '' ? 'YES (' . strlen($token) . ' chars)' : 'NO') . "\n";
echo "\n";

if ($endpoint === '') {
	echo "FATAL: whatsapp._BatchEndpoint missing in config.json\n";
	exit;
}

$parts = parse_url($endpoint);
$host  = $parts['host']   ?? '';
$port  = intval($parts['port'] ?? 443);
$path  = $parts['path']   ?? '/';
$scheme = $parts['scheme'] ?? 'https';

echo "Host:      $host\n";
echo "Port:      $port\n";
echo "Path:      $path\n";
echo "\n";

echo "--- Step 1: DNS resolution ---\n";
$t0 = microtime(true);
$ips = @gethostbynamel($host);
$dt = round((microtime(true) - $t0) * 1000, 1);
if (!$ips) {
	echo "FAILED in {$dt} ms - server cannot resolve $host\n";
	echo "Likely cause: DNS broken on this server, or /etc/resolv.conf misconfigured.\n";
} else {
	echo "OK ({$dt} ms) - resolved to: " . implode(', ', $ips) . "\n";
}
echo "\n";

echo "--- Step 2: TCP connect on port $port ---\n";
$t0 = microtime(true);
$errno = 0; $errstr = '';
$fp = @fsockopen(($scheme === 'https' ? 'tcp://' : '') . $host, $port, $errno, $errstr, 10);
$dt = round((microtime(true) - $t0) * 1000, 1);
if (!$fp) {
	echo "FAILED in {$dt} ms - errno=$errno, error=$errstr\n";
	echo "Likely cause: outbound firewall blocks port $port to $host,\n";
	echo "             OR chatmybot has not whitelisted this server's outbound IP.\n";
} else {
	echo "OK ({$dt} ms) - TCP socket opened\n";
	fclose($fp);
}
echo "\n";

echo "--- Step 3: TLS handshake (via stream_socket_client) ---\n";
$t0 = microtime(true);
$ctx = stream_context_create([
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'SNI_enabled' => true,
		'peer_name' => $host,
	],
]);
$fp = @stream_socket_client(
	"ssl://{$host}:{$port}",
	$errno, $errstr, 15,
	STREAM_CLIENT_CONNECT, $ctx
);
$dt = round((microtime(true) - $t0) * 1000, 1);
if (!$fp) {
	echo "FAILED in {$dt} ms - errno=$errno, error=$errstr\n";
	echo "Likely cause: outdated OpenSSL on this server, missing CA bundle,\n";
	echo "             or chatmybot serving a cipher this PHP build does not support.\n";
} else {
	echo "OK ({$dt} ms) - TLS handshake completed\n";
	fclose($fp);
}
echo "\n";

echo "--- Step 4: HEAD request via cURL ---\n";
$ch = curl_init();
curl_setopt_array($ch, [
	CURLOPT_URL => $endpoint,
	CURLOPT_NOBODY => true,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_CONNECTTIMEOUT => 15,
	CURLOPT_SSL_VERIFYHOST => 0,
	CURLOPT_SSL_VERIFYPEER => 0,
	CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
	CURLOPT_HEADER => true,
]);
$raw = curl_exec($ch);
$err = curl_error($ch);
$errno = curl_errno($ch);
$info = curl_getinfo($ch);
curl_close($ch);
if ($raw === false) {
	echo "FAILED - errno=$errno, error=$err\n";
	echo "DNS:     " . round(($info['namelookup_time']  ?? 0) * 1000, 1) . " ms\n";
	echo "Connect: " . round(($info['connect_time']     ?? 0) * 1000, 1) . " ms\n";
	echo "TLS:     " . round(($info['appconnect_time']  ?? 0) * 1000, 1) . " ms\n";
	echo "Total:   " . round(($info['total_time']       ?? 0) * 1000, 1) . " ms\n";
} else {
	echo "OK - HTTP " . intval($info['http_code'] ?? 0) . "\n";
	echo "DNS:     " . round(($info['namelookup_time']  ?? 0) * 1000, 1) . " ms\n";
	echo "Connect: " . round(($info['connect_time']     ?? 0) * 1000, 1) . " ms\n";
	echo "TLS:     " . round(($info['appconnect_time']  ?? 0) * 1000, 1) . " ms\n";
	echo "Total:   " . round(($info['total_time']       ?? 0) * 1000, 1) . " ms\n";
}
echo "\n";

echo "--- Step 5: server's outbound public IP (whitelist this at chatmybot) ---\n";
$ipServices = [
	'https://api.ipify.org',
	'https://ifconfig.me/ip',
	'https://checkip.amazonaws.com',
];
foreach ($ipServices as $svc) {
	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => $svc,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 8,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
	]);
	$out = curl_exec($ch);
	$ok = curl_errno($ch) === 0;
	curl_close($ch);
	if ($ok && $out !== false) {
		echo "Outbound IP (per $svc): " . trim($out) . "\n";
		break;
	}
}
echo "\n=== End ===\n";
echo "If step 2 or 3 failed: contact chatmybot support, give them the outbound IP shown above, and ask them to whitelist it.\n";
echo "Delete this file (wa-diagnose.php) once you're done.\n";
