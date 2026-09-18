<?php 
/**
 * DB Connect 
 */
class Encryption
{ 
	private $key = "ShreeKrishna";
	
	function __construct()
	{
		
	}

	public function encrypt_message($message)
	{

		// Generate a random IV (Initialization Vector)
		// $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

		// // Encrypt the data using AES-256-CBC
		// $encryptedData = openssl_encrypt($message, 'aes-256-cbc', $this->key, 0, $iv);

		// // Combine IV and encrypted data
		// $encryptedMessage = base64_encode($iv . $encryptedData);

		// return $encryptedMessage;
		$encryptedMessage = base64_encode($message);
    	return $encryptedMessage;
	}

	public function decrypt_message($message)
	{

		// Decode the base64-encoded message
		// $encryptedMessage = base64_decode($message);

		// // Extract IV from the encrypted message
		// $iv = substr($encryptedMessage, 0, openssl_cipher_iv_length('aes-256-cbc'));

		// // Extract encrypted data from the message
		// $encryptedData = substr($encryptedMessage, openssl_cipher_iv_length('aes-256-cbc'));

		// // Decrypt the data using AES-256-CBC
		// $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $this->key, 0, $iv);

		// return $decryptedData;

		$decryptedData = base64_decode($message);
    	return $decryptedData;
	}
}