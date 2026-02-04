<?php

class EncryptionHelper
{
    private static $cipher = "aes-256-gcm";

    private static function getKey()
    {
        // Garante que a chave mestra esteja definida
        if (!defined('APP_MASTER_KEY') || empty(APP_MASTER_KEY)) {
            throw new Exception("APP_MASTER_KEY não definida. Configure a variável de ambiente.");
        }

        // Decodifica a chave se estiver em base64 (comum para chaves binárias em env vars)
        // Se a chave for uma string simples de 32 chars, usa como está.
        // Recomendado: chaves de 32 bytes (256 bits) codificadas em base64.
        $key = APP_MASTER_KEY;

        // Se a chave parecer ser base64 e decodificar para 32 bytes, usa a decodificada.
        // Caso contrário, faz hash SHA-256 para garantir 32 bytes.
        if (strlen($key) === 44 && preg_match('/^[a-zA-Z0-9\/\+]+={0,2}$/', $key)) {
            $decoded = base64_decode($key, true);
            if ($decoded && strlen($decoded) === 32) {
                return $decoded;
            }
        }

        // Fallback robusto: Hash SHA-256 da string da chave
        return hash('sha256', $key, true);
    }

    /**
     * Criptografa dados usando AES-256-GCM
     */
    public static function encrypt($data)
    {
        if (empty($data))
            return null;

        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $tag = "";

        // GCM mode requires PHP 7.1+
        $encrypted = openssl_encrypt($data, self::$cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($encrypted === false) {
            throw new Exception("Falha na criptografia: " . openssl_error_string());
        }

        // Retorna: IV + TAG + CIFFERTEXT (tudo em base64 para armazenamento seguro em texto)
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Descriptografa dados
     */
    public static function decrypt($encryptedData)
    {
        if (empty($encryptedData))
            return null;

        $binary = base64_decode($encryptedData);
        if ($binary === false)
            return null;

        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length(self::$cipher);
        $tagLen = 16; // GCM tag length is usually 16 bytes

        if (strlen($binary) < ($ivLen + $tagLen)) {
            return null; // Dados inválidos ou corrompidos
        }

        $iv = substr($binary, 0, $ivLen);
        $tag = substr($binary, $ivLen, $tagLen);
        $ciphertext = substr($binary, $ivLen + $tagLen);

        $decrypted = openssl_decrypt($ciphertext, self::$cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted !== false ? $decrypted : null;
    }
}
