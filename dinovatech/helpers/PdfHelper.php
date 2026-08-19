<?php

class PdfHelper
{
    /**
     * Retorna a lista de URLs candidatas do serviço WeasyPrint para tentativa/fallback.
     */
    public static function getCandidateUrls()
    {
        $urls = [];

        if (defined('WEASYPRINT_URL') && !empty(WEASYPRINT_URL)) {
            $urls[] = WEASYPRINT_URL;
        }

        $envUrl = getenv('WEASYPRINT_URL');
        if (!empty($envUrl) && !in_array($envUrl, $urls)) {
            $urls[] = $envUrl;
        }

        // Candidatos padrão em ambiente Docker
        $defaults = [
            'http://weasyprint:9080/convert/html',       // Mesmo network bridge (DNS por nome de container)
            'http://172.17.0.1:9080/convert/html',      // Gateway padrão Docker host no Linux
            'http://172.18.0.1:9080/convert/html',      // Gateway secundário Docker host
            'http://host.docker.internal:9080/convert/html',
            'http://localhost:9080/convert/html'
        ];

        foreach ($defaults as $d) {
            if (!in_array($d, $urls)) {
                $urls[] = $d;
            }
        }

        return $urls;
    }

    /**
     * Converte tags de imagens locais (caminhos relativos/absolutos do disco) para Data URI Base64.
     * Isso garante que o WeasyPrint renderize logos e assinaturas sem precisar de acesso à rede/DNS interno.
     */
    public static function embedLocalImagesAsBase64($html, $documentBasePath = null)
    {
        if (!$documentBasePath) {
            $documentBasePath = dirname(__DIR__, 2); // Raiz do projeto (e:\DEV\dinovatech)
        }

        $pattern = '/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i';

        return preg_replace_callback($pattern, function ($matches) use ($documentBasePath) {
            $prefix = $matches[1];
            $src = $matches[2];
            $suffix = $matches[3];

            // Se já for data URI ou URL remota com protocolo http/https
            if (strpos($src, 'data:') === 0) {
                return $matches[0];
            }

            // Normaliza caminho local
            $filePath = null;

            if (file_exists($src)) {
                $filePath = $src;
            } else {
                // Tenta resolver caminhos relativos como ../../uploads ou uploads/
                $cleanSrc = preg_replace('/^(\.\.\/)+/', '', $src);
                $cleanSrc = ltrim($cleanSrc, '/');

                $candidate1 = $documentBasePath . '/' . $cleanSrc;
                $candidate2 = dirname(__DIR__) . '/' . $cleanSrc; // dinovatech/uploads

                if (file_exists($candidate1)) {
                    $filePath = $candidate1;
                } elseif (file_exists($candidate2)) {
                    $filePath = $candidate2;
                }
            }

            if ($filePath && is_file($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $mimeMap = [
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp'
                ];
                $mime = $mimeMap[$ext] ?? 'image/jpeg';
                $imageData = base64_encode(file_get_contents($filePath));
                $dataUri = "data:{$mime};base64,{$imageData}";

                return "<img {$prefix}src=\"{$dataUri}\"{$suffix}>";
            }

            return $matches[0];
        }, $html);
    }

    /**
     * Envia o HTML ao microserviço WeasyPrint e retorna o binário do PDF.
     * 
     * @param string $html Conteúdo HTML completo com CSS embutido.
     * @param string|null $documentBasePath Caminho base para resolver imagens locais.
     * @return string|false Binário do PDF ou false em caso de falha.
     */
    public static function generatePdf($html, $documentBasePath = null)
    {
        // 1. Incorporar imagens locais em Base64
        $processedHtml = self::embedLocalImagesAsBase64($html, $documentBasePath);

        // 2. Garantir metadados e charset UTF-8 se não estiverem presentes
        if (stripos($processedHtml, '<meta charset') === false && stripos($processedHtml, '<head>') !== false) {
            $processedHtml = str_ireplace('<head>', '<head><meta charset="UTF-8">', $processedHtml);
        }

        $candidateUrls = self::getCandidateUrls();
        $lastError = '';

        foreach ($candidateUrls as $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $processedHtml);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: text/html; charset=utf-8',
                'Accept: application/pdf'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

            $pdfBinary = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($pdfBinary !== false && $httpCode === 200 && strlen($pdfBinary) > 100) {
                return $pdfBinary;
            }

            $lastError = "URL '{$url}' (HTTP {$httpCode}): {$curlError}";
        }

        error_log("Erro ao gerar PDF via WeasyPrint em todos os endpoints testados. Ultimo erro: " . $lastError);
        return false;
    }

    /**
     * Entrega o PDF diretamente ao navegador para visualização (inline) ou download (attachment).
     * 
     * @param string $html
     * @param string $filename Nome sugerido para o arquivo .pdf
     * @param bool $inline Se true, abre no visualizador do navegador; se false, força download.
     * @param string|null $documentBasePath
     */
    public static function streamPdf($html, $filename = 'documento.pdf', $inline = true, $documentBasePath = null)
    {
        $pdf = self::generatePdf($html, $documentBasePath);

        if ($pdf === false) {
            header('Content-Type: text/html; charset=utf-8');
            http_response_code(502);
            echo "<h3>Erro ao processar o documento em PDF</h3>";
            echo "<p>O serviço WeasyPrint não respondeu ou não está ativo no servidor. Por favor, contate o administrador.</p>";
            exit();
        }

        $disposition = $inline ? 'inline' : 'attachment';
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        // Limpa qualquer buffer de saída anterior
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header("Content-Disposition: {$disposition}; filename=\"{$cleanFilename}\"");
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdf;
        exit();
    }
}
