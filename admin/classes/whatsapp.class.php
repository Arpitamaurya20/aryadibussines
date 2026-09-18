<?php
class Whatsapp extends Core
{
	private $conn;

	public function __construct()
	{
		$this->setTimeZone();
	}

	/**
	 * Legacy Interakt OTP (login_otp template).
	 */
	public function sendOTPWhatsAppMessage($data)
	{
		$curl = curl_init();
		$phonenumber = $data['phonenumber'];
		$otp = $data['otp'];
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
			"countryCode": "+91",
			"phoneNumber": "'.$phonenumber.'",
			"type": "Template",
			"template": {
			"name": "login_otp",
			"languageCode": "en",
			"bodyValues": [
			"'.$otp.'"
			]
			}
		  }',
		  CURLOPT_HTTPHEADER => array(
		    'Authorization: Basic TVZPai1Sb3VRbE9fN3ltYWxNOTlRTWxFd0kwckt2NmFBak4zSUNEQnRaczo=',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
	}

	/**
	 * Chatmybot OTP using approved template (body + optional dynamic URL button).
	 */
	public function sendOtpViaChatmybot(array $data, array $config = []): array
	{
		$phone = (string) ($data['phonenumber'] ?? '');
		$otp = (string) ($data['otp'] ?? '');

		$buttonParams = null;
		if (!empty($config['otp_include_button'])) {
			$buttonText = isset($config['otp_button_param']) && $config['otp_button_param'] !== ''
				? (string) $config['otp_button_param']
				: $otp;
			$buttonParams = [$buttonText];
		}

		return $this->sendChatmybotTemplate([
			'endpoint' => $config['endpoint'] ?? '',
			'access_token' => $config['access_token'] ?? '',
			'auth_scheme' => $config['auth_scheme'] ?? 'accessToken',
			'template_id' => $config['otp_template_id'] ?? '',
			'phone' => $phone,
			'body_params' => [$otp],
			'button_params' => $buttonParams,
			'button_index' => $config['button_index'] ?? '0',
		]);
	}

	/**
	 * Generic chatmybot.in WhatsApp Business batch send.
	 *
	 * @return array{ok:bool,http_code:int,response:mixed,message_ids:array,error:string}
	 */
	public function sendChatmybotTemplate(array $options): array
	{
		$endpoint = trim((string) ($options['endpoint'] ?? ''));
		$token = trim((string) ($options['access_token'] ?? ''));
		$authScheme = trim((string) ($options['auth_scheme'] ?? 'accessToken'));
		$templateId = trim((string) ($options['template_id'] ?? ''));
		$phone = $this->normaliseIndianPhone((string) ($options['phone'] ?? ''));
		$bodyParams = is_array($options['body_params'] ?? null) ? $options['body_params'] : [];
		$buttonParams = $options['button_params'] ?? null;
		$buttonIndex = (string) ($options['button_index'] ?? '0');

		if ($endpoint === '') {
			return $this->chatmybotFail('WhatsApp gateway endpoint not configured.');
		}
		if ($templateId === '') {
			return $this->chatmybotFail('WhatsApp template id is required.');
		}
		if ($token === '') {
			return $this->chatmybotFail('WhatsApp access token not configured.');
		}
		if ($phone === '') {
			return $this->chatmybotFail('Invalid phone number.');
		}

		$components = [];
		if ($bodyParams) {
			$components[] = [
				'type' => 'body',
				'parameters' => $this->buildChatmybotTextParams($bodyParams),
			];
		}
		if (is_array($buttonParams) && $buttonParams) {
			$components[] = [
				'type' => 'button',
				'sub_type' => 'url',
				'urlType' => 'dynamic',
				'index' => $buttonIndex,
				'parameters' => $this->buildChatmybotTextParams($buttonParams),
			];
		}

		$payload = [[
			'template' => [
				'id' => $templateId,
				'components' => $components,
			],
			'to' => $phone,
			'type' => 'template',
		]];

		$headers = ['Content-Type: application/json', 'Accept: application/json'];
		$schemeLower = strtolower($authScheme);
		if ($schemeLower === 'bearer' || $schemeLower === 'basic') {
			$headers[] = 'Authorization: ' . ucfirst($schemeLower) . ' ' . $token;
		} elseif ($schemeLower === 'token') {
			$headers[] = 'Authorization: ' . $token;
		} elseif (in_array($schemeLower, ['x-api-key', 'apikey', 'api-key'], true)) {
			$headers[] = 'x-api-key: ' . $token;
		} else {
			$headers[] = $authScheme . ': ' . $token;
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_TIMEOUT => 45,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
		]);

		$raw = curl_exec($ch);
		$err = curl_error($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($raw === false) {
			return [
				'ok' => false,
				'http_code' => $httpCode,
				'response' => null,
				'message_ids' => [],
				'error' => $err !== '' ? $err : 'cURL request failed',
			];
		}

		$decoded = json_decode($raw, true);
		$ok = ($httpCode >= 200 && $httpCode < 300);
		$gatewayMsg = '';
		$messageIds = [];

		if (is_array($decoded)) {
			$status = strtolower((string) ($decoded['status'] ?? $decoded['Status'] ?? ''));
			if ($ok && $status !== '' && !in_array($status, ['success', 'ok', 'accepted', 'queued', 'sent', 'submitted'], true)) {
				$ok = false;
			}
			$gatewayMsg = trim((string) (
				$decoded['message'] ?? $decoded['Message'] ?? $decoded['error'] ?? $decoded['Error'] ?? ''
			));
			if (!empty($decoded['ids']) && is_array($decoded['ids'])) {
				$messageIds = array_values(array_filter(array_map('strval', $decoded['ids'])));
			}
		}

		$errorText = '';
		if (!$ok) {
			$errorText = 'Gateway returned HTTP ' . $httpCode;
			if ($gatewayMsg !== '') {
				$errorText .= ' - ' . $gatewayMsg;
			} elseif (is_string($raw) && $raw !== '') {
				$errorText .= ' - ' . substr(trim($raw), 0, 240);
			}
		}

		return [
			'ok' => $ok,
			'http_code' => $httpCode,
			'response' => $decoded ?? $raw,
			'message_ids' => $messageIds,
			'error' => $errorText,
		];
	}

	private function normaliseIndianPhone(string $phone): string
	{
		$digits = preg_replace('/\D+/', '', $phone);
		if ($digits === '') {
			return '';
		}
		if (strlen($digits) > 10) {
			$digits = substr($digits, -10);
		}
		return strlen($digits) === 10 ? $digits : '';
	}

	private function cleanWaTemplateParam($value): string
	{
		$value = (string) $value;
		$value = preg_replace("/[\r\n\t]+/", ' ', $value);
		$value = preg_replace('/\s{4,}/', '   ', $value);
		return trim($value);
	}

	private function buildChatmybotTextParams(array $values): array
	{
		$params = [];
		foreach ($values as $value) {
			$params[] = ['type' => 'text', 'text' => $this->cleanWaTemplateParam($value)];
		}
		return $params;
	}

	private function chatmybotFail(string $message): array
	{
		return [
			'ok' => false,
			'http_code' => 0,
			'response' => null,
			'message_ids' => [],
			'error' => $message,
		];
	}
}
?>