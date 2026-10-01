<?php
if (!function_exists('signatureMediaUrl')) {
	/**
	 * Public URL for a file in admin/media/signature/, on the host that served the request
	 * (localhost / LAN IP locally, the live domain in production).
	 */
	function signatureMediaUrl($fileName)
	{
		$fileName = (string) $fileName;
		if ($fileName === '' || preg_match('#^https?://#i', $fileName)) {
			return $fileName;
		}
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		if ($host === '') {
			return 'https://techxpertindia.in/admin/media/signature/' . $fileName;
		}
		$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
		$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
		$root = preg_replace('#/(api|admin)(/.*)?$#', '', $scriptName);
		return ($https ? 'https' : 'http') . '://' . $host . rtrim($root, '/') . '/admin/media/signature/' . rawurlencode($fileName);
	}
}
?>
