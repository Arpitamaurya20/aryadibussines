<?php 
class Conf
{
	public $_ProductName;
	public $_ProductLogo;
	public $_ProductIcon;
	public $_MailName;
	public $_WebsiteLink;
	public $_MailSignature;
	public $_DomainName;
	public $_LeadName;
	public $_Telecaller_LeadName;
	public $_MailLogo;

	/** WhatsApp gateway settings (decoded from config.json -> whatsapp). */
	public $_WhatsApp = [];

	public function __construct()
	{
		// Resolve config.json reliably regardless of the entry-point's CWD.
		$candidates = [
			__DIR__ . '/../config/config.json',
			'../config/config.json',
			dirname(__DIR__) . '/config/config.json',
		];
		$jsonString = false;
		foreach ($candidates as $path) {
			if (is_file($path)) {
				$jsonString = file_get_contents($path);
				if ($jsonString !== false) {
					break;
				}
			}
		}
		$configData = $jsonString !== false ? json_decode($jsonString, true) : [];
		if (!is_array($configData)) {
			$configData = [];
		}

		$product = isset($configData['product']) && is_array($configData['product']) ? $configData['product'] : [];
		$this->_ProductName         = $product['_ProductName']         ?? '';
		$this->_ProductLogo         = $product['_ProductLogo']         ?? '';
		$this->_ProductIcon         = $product['_ProductIcon']         ?? '';
		$this->_MailName            = $product['_MailName']            ?? '';
		$this->_WebsiteLink         = $product['_WebsiteLink']         ?? '';
		$this->_MailSignature       = $product['_MailSignature']       ?? '';
		$this->_DomainName          = $product['_DomainName']          ?? '';
		$this->_LeadName            = $product['_LeadName']            ?? '';
		$this->_Telecaller_LeadName = $product['_Telecaller_LeadName'] ?? '';
		$this->_MailLogo            = $product['_MailLogo']            ?? '';

		$this->_WhatsApp = isset($configData['whatsapp']) && is_array($configData['whatsapp'])
			? $configData['whatsapp']
			: [];
	}

	/**
	 * Look up an approved WhatsApp template ID by its config key.
	 * Returns '' when the key is not configured.
	 */
	public function getWhatsAppTemplateId($key)
	{
		$templates = isset($this->_WhatsApp['_Templates']) && is_array($this->_WhatsApp['_Templates'])
			? $this->_WhatsApp['_Templates']
			: [];
		return isset($templates[$key]) ? trim((string) $templates[$key]) : '';
	}
}