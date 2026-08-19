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

                $fullTag = "<img {$prefix}src=\"{$dataUri}\"{$suffix}>";

                // Se houver atributos width ou height definidos na tag HTML (ex: do TinyMCE),
                // injeta-os explicitamente no style="" para que o WeasyPrint aplique com prioridade máxima CSS.
                $widthMatch = [];
                $heightMatch = [];
                preg_match('/\bwidth=["\']?(\d+)(?:px)?["\']?/i', $fullTag, $widthMatch);
                preg_match('/\bheight=["\']?(\d+)(?:px)?["\']?/i', $fullTag, $heightMatch);

                $extraCss = '';
                if (!empty($widthMatch[1])) {
                    $extraCss .= "width: {$widthMatch[1]}px !important; max-width: {$widthMatch[1]}px !important; ";
                }
                if (!empty($heightMatch[1])) {
                    $extraCss .= "height: {$heightMatch[1]}px !important; max-height: {$heightMatch[1]}px !important; ";
                }

                if (!empty($extraCss)) {
                    if (preg_match('/style=["\'](.*?)["\']/i', $fullTag, $styleMatch)) {
                        $newStyle = rtrim(trim($styleMatch[1]), ';') . '; ' . $extraCss;
                        $fullTag = preg_replace('/style=["\'](.*?)["\']/i', "style=\"{$newStyle}\"", $fullTag);
                    } else {
                        $fullTag = str_replace('<img ', "<img style=\"{$extraCss}\" ", $fullTag);
                    }
                }

                return $fullTag;
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

        // 3. Injetar regras CSS padrão de PDF (controle de imagens, logos, assinaturas, paginação e utilitários)
        $pdfResetCss = '
        <style>
            @page {
                size: A4;
                margin: 10mm;
            }
            * {
                box-sizing: border-box;
            }
            body {
                font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                color: #1f2937;
                line-height: 1.5;
            }
            img {
                max-width: 100%;
                height: auto;
            }
            img[src*="logo"], img[src*="Logo"], img[alt*="logo"], img[alt*="Logo"], .logo-img {
                max-height: 75px !important;
                max-width: 240px !important;
                height: auto !important;
                object-fit: contain !important;
            }
            img[src*="assinatura"], img[alt*="assinatura"], img[alt*="Assinatura"] {
                max-height: 70px !important;
                max-width: 220px !important;
                height: auto !important;
                object-fit: contain !important;
            }

            /* Utilitários Flexbox, Grid e Layout */
            .flex { display: flex !important; }
            .flex-col { flex-direction: column !important; }
            .justify-between { justify-content: space-between !important; }
            .justify-center { justify-content: center !important; }
            .items-center { align-items: center !important; }
            .items-baseline { align-items: baseline !important; }
            .flex-1 { flex: 1 !important; }
            .grid { display: grid !important; }
            .grid-cols-2 { grid-template-columns: 1fr 1fr !important; }
            .gap-4 { gap: 16px !important; }
            .gap-2 { gap: 8px !important; }

            /* Tipografia */
            .text-xs { font-size: 11px !important; }
            .text-sm { font-size: 13px !important; }
            .text-base { font-size: 14px !important; }
            .text-lg { font-size: 17px !important; }
            .text-xl { font-size: 20px !important; }
            .text-2xl { font-size: 24px !important; }
            .text-3xl { font-size: 26px !important; }
            .font-bold { font-weight: bold !important; }
            .font-medium { font-weight: 500 !important; }
            .uppercase { text-transform: uppercase !important; }
            .tracking-widest { letter-spacing: 0.1em !important; }
            .tracking-tight { letter-spacing: -0.025em !important; }
            .tracking-wide { letter-spacing: 0.05em !important; }
            .leading-snug { line-height: 1.375 !important; }
            .italic { font-style: italic !important; }
            .text-right { text-align: right !important; }
            .text-center { text-align: center !important; }
            .block { display: block !important; }

            /* Cores e Fundos */
            .text-cyan-800 { color: #155e75 !important; }
            .text-cyan-900 { color: #164e63 !important; }
            .text-gray-800 { color: #1f2937 !important; }
            .text-gray-700 { color: #374151 !important; }
            .text-gray-600 { color: #4b5563 !important; }
            .text-gray-500 { color: #6b7280 !important; }
            .text-gray-400 { color: #9ca3af !important; }
            .bg-gray-50 { background-color: #f9fafb !important; }
            .bg-gray-100 { background-color: #f3f4f6 !important; }

            /* Bordas e Espaçamentos */
            .border { border: 1px solid #e5e7eb !important; }
            .border-b { border-bottom: 1px solid #e5e7eb !important; }
            .border-t { border-top: 1px solid #e5e7eb !important; }
            .border-b-2 { border-bottom: 2px solid !important; }
            .border-t-2 { border-top: 2px solid !important; }
            .border-l-4 { border-left: 4px solid !important; }
            .border-dashed { border-style: dashed !important; }
            .border-cyan-800 { border-color: #155e75 !important; }
            .border-cyan-200 { border-color: #a5f3fc !important; }
            .border-gray-200 { border-color: #e5e7eb !important; }
            .border-gray-300 { border-color: #d1d5db !important; }
            .rounded-lg { border-radius: 8px !important; }
            .rounded { border-radius: 4px !important; }
            .p-4 { padding: 16px !important; }
            .pb-4 { padding-bottom: 16px !important; }
            .pb-2 { padding-bottom: 8px !important; }
            .pb-1 { padding-bottom: 4px !important; }
            .pt-4 { padding-top: 16px !important; }
            .pt-8 { padding-top: 32px !important; }
            .pl-4 { padding-left: 16px !important; }
            .py-1 { padding-top: 4px !important; padding-bottom: 4px !important; }
            .px-2 { padding-left: 8px !important; padding-right: 8px !important; }
            .py-0.5 { padding-top: 2px !important; padding-bottom: 2px !important; }
            .mb-4 { margin-bottom: 16px !important; }
            .mb-2 { margin-bottom: 8px !important; }
            .mb-1 { margin-bottom: 4px !important; }
            .mb-0.5 { margin-bottom: 2px !important; }
            .mb-8 { margin-bottom: 32px !important; }
            .mt-12 { margin-top: 48px !important; }
            .mt-4 { margin-top: 16px !important; }
            .mt-2 { margin-top: 8px !important; }
            .mt-1 { margin-top: 4px !important; }
            .space-y-4 > * + * { margin-top: 16px !important; }
            .w-64 { width: 16rem !important; }
            .mx-auto { margin-left: auto !important; margin-right: auto !important; }
            .border-black { border-color: #000 !important; }
            .h-20 { height: 75px !important; max-height: 75px !important; }
            .h-16 { height: 60px !important; max-height: 60px !important; }
            .h-24 { height: 90px !important; max-height: 90px !important; }
            .object-contain { object-fit: contain !important; }
        </style>
        ';

        if (stripos($processedHtml, '</head>') !== false) {
            $processedHtml = str_ireplace('</head>', $pdfResetCss . '</head>', $processedHtml);
        } else {
            $processedHtml = $pdfResetCss . $processedHtml;
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
