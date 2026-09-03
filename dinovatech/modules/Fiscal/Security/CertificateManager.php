<?php
namespace Dinovatech\Modules\Fiscal\Security;

use Exception;
use EncryptionHelper;

class CertificateManager
{
    private array $certs = [];
    private ?string $tempCertPath = null;
    private ?string $tempKeyPath = null;

    public function __construct(string $pfxContentOrBase64, string $encryptedPassword)
    {
        // 1. Decriptografa a senha
        $password = $encryptedPassword;
        if (class_exists('EncryptionHelper')) {
            try {
                $dec = EncryptionHelper::decrypt($encryptedPassword);
                if ($dec) {
                    $password = $dec;
                }
            } catch (Exception $e) {
            }
        }

        // 2. Decodifica se for base64
        $pfxBinary = $pfxContentOrBase64;
        if (base64_encode(base64_decode($pfxContentOrBase64, true) ?: '') === $pfxContentOrBase64) {
            $pfxBinary = base64_decode($pfxContentOrBase64);
        }

        // 3. Lê o PKCS#12
        $certs = [];
        if (!openssl_pkcs12_read($pfxBinary, $certs, $password)) {
            throw new Exception("Falha ao abrir certificado PFX. Verifique a senha ou a integridade do arquivo.");
        }

        $this->certs = $certs;
    }

    public function getPrivateKey(): string
    {
        return $this->certs['pkey'] ?? '';
    }

    public function getCertificate(): string
    {
        return $this->certs['cert'] ?? '';
    }

    public function getCleanCertificate(): string
    {
        $cert = $this->getCertificate();
        return str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '], '', $cert);
    }

    /**
     * Cria arquivos temporários para autenticação mTLS no cURL e retorna seus caminhos.
     * Retorna ['cert' => string, 'key' => string]
     */
    public function getTlsFiles(): array
    {
        if ($this->tempCertPath && file_exists($this->tempCertPath) &&
            $this->tempKeyPath && file_exists($this->tempKeyPath)) {
            return ['cert' => $this->tempCertPath, 'key' => $this->tempKeyPath];
        }

        $this->tempCertPath = tempnam(sys_get_temp_dir(), 'nfs_cert_');
        $this->tempKeyPath = tempnam(sys_get_temp_dir(), 'nfs_key_');

        file_put_contents($this->tempCertPath, $this->getCertificate());
        file_put_contents($this->tempKeyPath, $this->getPrivateKey());

        return ['cert' => $this->tempCertPath, 'key' => $this->tempKeyPath];
    }

    public function cleanup(): void
    {
        if ($this->tempCertPath && file_exists($this->tempCertPath)) {
            @unlink($this->tempCertPath);
            $this->tempCertPath = null;
        }
        if ($this->tempKeyPath && file_exists($this->tempKeyPath)) {
            @unlink($this->tempKeyPath);
            $this->tempKeyPath = null;
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
