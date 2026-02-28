<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
// Vet Mode restriction removed
include "../../../database.php";

$link = DBConnect();
if (!$link)
    die("Erro de conexão");

$id_vet = $_GET['id'] ?? null;
$vet = null;
$is_edit = false;
$erro = "";

if ($id_vet) {
    $id_safe = mysqli_real_escape_string($link, $id_vet);
    $q = "SELECT * FROM Veterinarios WHERE id_vet = '$id_safe'";
    $r = DBExecute($link, $q);
    if ($r && mysqli_num_rows($r) > 0) {
        $vet = mysqli_fetch_assoc($r);
        $is_edit = true;
    } else {
        echo "<!-- Debug: Nenhum veterinário encontrado com ID: $id_safe -->";
    }
}

// Fetch Google Service Account Email for Hint
$googleEmailHint = '';
require_once __DIR__ . '/../../helpers/EncryptionHelper.php';
$qConf = "SELECT google_service_account_json FROM ConfiguracoesEmissor LIMIT 1";
$rConf = DBExecute($link, $qConf);
if ($rConf && mysqli_num_rows($rConf) > 0) {
    $conf = mysqli_fetch_assoc($rConf);
    if (!empty($conf['google_service_account_json'])) {
        try {
            $jsonDecrypted = EncryptionHelper::decrypt($conf['google_service_account_json']);
            $data = json_decode($jsonDecrypted, true);
            if ($data && isset($data['client_email'])) {
                $googleEmailHint = $data['client_email'];
            }
        } catch (Exception $e) {
        }
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $crmv = $_POST['crmv'] ?? '';
    $uf_crmv = $_POST['uf_crmv'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $google_calendar_id = $_POST['google_calendar_id'] ?? '';

    // Auto-fill CRMV/UF if not Vet Mode (Modularity)
    if (!AppHelper::isVetMode()) {
        $crmv = $crmv ?: '-';
        $uf_crmv = $uf_crmv ?: 'XX';
    }

    if (empty($nome) || empty($crmv) || empty($uf_crmv)) {
        $erro = "Nome, CRMV e UF são obrigatórios.";
    } else {
        $nome = mysqli_real_escape_string($link, $nome);
        $crmv = mysqli_real_escape_string($link, $crmv);
        $uf_crmv = mysqli_real_escape_string($link, $uf_crmv);
        $telefone = mysqli_real_escape_string($link, $telefone);
        $email = mysqli_real_escape_string($link, $email);
        $google_calendar_id = mysqli_real_escape_string($link, $google_calendar_id);

        $url_assinatura = $vet['url_assinatura'] ?? '';
        $assinatura_base64 = $_POST['assinatura_base64'] ?? '';

        $conteudoArquivo = null;
        $mimeType = '';
        $ext = '';

        // Priorize a assinatura desenhada (base64)
        if (!empty($assinatura_base64)) {
            $parts = explode(',', $assinatura_base64);
            if (count($parts) === 2) {
                $decoded_data = base64_decode($parts[1]);
                if ($decoded_data !== false) {
                    $conteudoArquivo = $decoded_data;
                    $mimeType = 'image/png';
                    $ext = 'png';
                } else {
                    $erro = "Erro ao processar assinatura desenhada.";
                }
            }
        }
        // Se não houver desenho, processa o upload normal
        elseif (isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileTmp = $_FILES['assinatura']['tmp_name'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fileTmp);

            if (in_array($mimeType, $allowedTypes)) {
                $ext = pathinfo($_FILES['assinatura']['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    if ($mimeType === 'image/jpeg')
                        $ext = 'jpg';
                    elseif ($mimeType === 'image/png')
                        $ext = 'png';
                    elseif ($mimeType === 'image/webp')
                        $ext = 'webp';
                }
                $conteudoArquivo = file_get_contents($fileTmp);
            } else {
                $erro = "Tipo de arquivo não permitido para assinatura. Use PNG, JPG ou WebP.";
            }
        }

        // Se conseguiu algum arquivo (desenho ou upload), envia pro Oracle
        if ($conteudoArquivo !== null && empty($erro)) {
            $nomeBucket = 'assinaturas/' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;

            // Load Oracle Config
            $qConf = "SELECT api_oracle_url FROM ConfiguracoesEmissor LIMIT 1";
            $resConf = DBExecute($link, $qConf);
            $rowConf = mysqli_fetch_assoc($resConf);
            $urlBucketPreauth = $rowConf['api_oracle_url'] ?? '';

            if (!empty($urlBucketPreauth)) {
                if (substr($urlBucketPreauth, -1) !== '/') {
                    $urlBucketPreauth .= '/';
                }
                $urlUpload = $urlBucketPreauth . $nomeBucket;

                $ch = curl_init($urlUpload);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $conteudoArquivo);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: ' . $mimeType,
                    'Content-Length: ' . strlen($conteudoArquivo)
                ]);

                $resultCurl = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    $url_assinatura = $urlUpload;
                } else {
                    $erro = "Erro ao enviar assinatura para a nuvem. Código HTTP: $httpCode";
                }
            } else {
                $erro = "URL do bucket Oracle não configurada.";
            }
        }

        if (empty($erro)) {
            $url_assinatura_safe = mysqli_real_escape_string($link, $url_assinatura);
            if ($is_edit) {
                $query = "UPDATE Veterinarios SET nome='$nome', crmv='$crmv', uf_crmv='$uf_crmv', telefone='$telefone', email='$email', google_calendar_id='$google_calendar_id', url_assinatura='$url_assinatura_safe' WHERE id_vet = " . (int) $id_vet;
            } else {
                $query = "INSERT INTO Veterinarios (nome, crmv, uf_crmv, telefone, email, google_calendar_id, url_assinatura) VALUES ('$nome', '$crmv', '$uf_crmv', '$telefone', '$email', '$google_calendar_id', '$url_assinatura_safe')";
            }

            if (DBExecute($link, $query)) {
                header("Location: veterinarios.php");
                exit();
            } else {
                $erro = "Erro ao salvar: " . mysqli_error($link);
            }
        }
    }
}
DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? "Editar" : "Novo" ?>
        <?= AppHelper::isVetMode() ? "Veterinário" : "Colaborador" ?> - DinoVet
    </title>
    <?php include '../../components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">
            <div class="max-w-xl mx-auto">
                <a href="veterinarios.php" class="text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center">
                    <span class="material-icons mr-1">arrow_back</span> Voltar
                </a>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <?= $is_edit ? "Editar" : "Novo" ?>
                            <?= AppHelper::isVetMode() ? "Veterinário" : "Colaborador" ?>
                        </h2>
                    </div>

                    <?php if ($erro): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-6">
                            <?= $erro ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Nome Completo *</label>
                            <input type="text" name="nome" value="<?= htmlspecialchars($vet['nome'] ?? '') ?>" required
                                class="w-full border-gray-300 rounded-lg p-3 border">
                        </div>

                        <div class="border mt-4 mb-4 p-5 rounded-xl bg-white shadow-sm border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">Assinatura Digital</h3>
                            <p class="text-sm text-gray-500 mb-4">A assinatura aparecerá nos atestados, receitas e
                                receituários.</p>

                            <?php if (!empty($vet['url_assinatura'])): ?>
                                <div
                                    class="mb-5 p-4 border border-cyan-100 rounded-lg bg-cyan-50/50 flex flex-col items-center justify-center transition-all hover:bg-cyan-50 shadow-sm">
                                    <span
                                        class="text-[10px] text-cyan-600 mb-2 font-bold uppercase tracking-widest bg-cyan-100 px-2 py-1 rounded-full">Assinatura
                                        Atual Salva</span>
                                    <img src="<?= $vet['url_assinatura'] ?>" alt="Assinatura Atual Atual"
                                        class="max-h-28 object-contain drop-shadow-md">
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-2 mb-5 border-b pb-4 border-gray-100">
                                <button type="button" onclick="showSignatureMode('upload')" id="btn-mode-upload"
                                    class="px-5 py-2.5 text-sm font-semibold border rounded-lg transition-all bg-cyan-100 text-cyan-800 border-cyan-300 shadow flex items-center">
                                    <span class="material-icons text-[18px] mr-2">upload_file</span> Enviar Imagem
                                </button>
                                <button type="button" onclick="showSignatureMode('draw')" id="btn-mode-draw"
                                    class="px-5 py-2.5 text-sm font-semibold border rounded-lg transition-all bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300 flex items-center shadow-sm">
                                    <span class="material-icons text-[18px] mr-2">draw</span> Desenhar na Tela
                                </button>
                            </div>

                            <!-- UPLOAD MODE -->
                            <div id="mode-upload-container" class="opacity-100 transition-opacity duration-300">
                                <label class="block text-gray-700 font-medium mb-2">Arquivo de Imagem (PNG, JPG)</label>
                                <input type="file" name="assinatura" id="input-assinatura-file" accept="image/*"
                                    class="w-full border-gray-300 rounded-lg p-3 border border-dashed hover:bg-cyan-50 text-sm text-gray-600 transition-colors cursor-pointer file:cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-600 file:text-white hover:file:bg-cyan-700"
                                    onchange="clearSignaturePad && typeof clearSignaturePad === 'function' && clearSignaturePad(); document.getElementById('assinatura_base64').value = '';">
                                <div
                                    class="mt-3 text-sm text-gray-500 bg-amber-50 p-3 rounded-lg border border-amber-100 flex items-start">
                                    <span class="material-icons text-[18px] text-amber-500 mr-2 mt-0.5">lightbulb</span>
                                    <span><strong>Dica:</strong> Se prefere não desenhar na tela, anexe uma imagem com
                                        <b>fundo transparente</b> para evitar caixas brancas sobre os textos dos
                                        documentos.</span>
                                </div>
                            </div>

                            <!-- DRAW MODE -->
                            <div id="mode-draw-container" class="hidden opacity-0 transition-opacity duration-300">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-gray-700 font-medium">Quadro de Assinatura</label>
                                    <span
                                        class="text-xs bg-cyan-100 text-cyan-800 px-2 py-0.5 rounded-full font-medium shadow-sm">Nova
                                        Assinatura</span>
                                </div>
                                <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white overflow-hidden relative shadow-inner cursor-crosshair group hover:border-cyan-400 transition-colors"
                                    style="touch-action: none; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px; min-height: 200px;">
                                    <canvas id="signature-pad"
                                        class="w-full h-56 sm:h-72 z-10 relative touch-none"></canvas>
                                    <div
                                        class="absolute bottom-4 left-0 right-0 text-center pointer-events-none opacity-30 group-hover:opacity-40 transition-opacity">
                                        <div class="border-b-2 border-gray-300 w-3/4 mx-auto mb-1"></div>
                                        <span
                                            class="text-[10px] uppercase font-bold tracking-widest text-gray-600 shadow-sm">Assine
                                            sobre a linha</span>
                                    </div>
                                </div>
                                <div
                                    class="flex justify-between items-center mt-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                    <button type="button" onclick="clearSignaturePad();"
                                        class="text-sm font-semibold text-red-500 hover:text-red-700 flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 rounded-lg transition-colors shadow-sm">
                                        <span class="material-icons text-[16px] mr-1">delete_outline</span> Limpar
                                    </button>
                                    <p class="text-xs font-bold px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-500 shadow-sm uppercase tracking-wide flex items-center gap-1 transition-colors"
                                        id="signature-status">
                                        <span
                                            class="w-2 h-2 rounded-full bg-gray-400 inline-block animate-pulse"></span>
                                        Aguardando...
                                    </p>
                                </div>
                                <input type="hidden" name="assinatura_base64" id="assinatura_base64">
                            </div>
                        </div>

                        <?php if (AppHelper::isVetMode()): ?>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-gray-700 font-medium mb-1">CRMV *</label>
                                    <input type="text" name="crmv" value="<?= htmlspecialchars($vet['crmv'] ?? '') ?>"
                                        required class="w-full border-gray-300 rounded-lg p-3 border">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1">UF *</label>
                                    <input type="text" name="uf_crmv"
                                        value="<?= htmlspecialchars($vet['uf_crmv'] ?? 'SP') ?>" maxlength="2" required
                                        class="w-full border-gray-300 rounded-lg p-3 border uppercase">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Telefone</label>
                                <input type="text" name="telefone"
                                    value="<?= htmlspecialchars($vet['telefone'] ?? '') ?>"
                                    class="w-full border-gray-300 rounded-lg p-3 border">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($vet['email'] ?? '') ?>"
                                    class="w-full border-gray-300 rounded-lg p-3 border">
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-1">ID Agenda Google (Opcional)</label>
                            <input type="text" name="google_calendar_id"
                                value="<?= htmlspecialchars($vet['google_calendar_id'] ?? '') ?>"
                                placeholder="ex: example@gmail.com ou ID da agenda"
                                class="w-full border-gray-300 rounded-lg p-3 border text-sm font-mono text-gray-600">
                            <p class="text-xs text-gray-500 mt-1">Compartilhe sua agenda com o e-mail da Service Account
                                antes de salvar.</p>
                            <?php if ($googleEmailHint): ?>
                                <div class="mt-4 bg-blue-50 text-blue-800 p-4 rounded-lg border border-blue-100 text-sm">
                                    <strong class="block mb-2 text-blue-900 border-b border-blue-200 pb-1">Passo a passo
                                        para integração:</strong>
                                    <ol class="list-decimal list-inside space-y-1 ml-1 text-blue-900/80">
                                        <li>No Google Calendar, crie uma nova agenda (ou use a principal).</li>
                                        <li>Vá em <strong>Configurações e compart.</strong> dessa agenda.</li>
                                        <li>Em "Compartilhar com pessoas...", adicione o e-mail abaixo:</li>
                                        <div class="py-2 pl-4">
                                            <code
                                                class="select-all bg-white px-2 py-1 rounded border border-blue-200 text-xs font-mono block w-fit"><?= $googleEmailHint ?></code>
                                        </div>
                                        <li>Marque a permissão: <strong>"Fazer alterações nos eventos"</strong>
                                            (Importante!).</li>
                                        <li>Role até "Integrar agenda" e copie o <strong>ID da agenda</strong>.</li>
                                        <li>Cole o ID no campo acima e salve aqui no sistema.</li>
                                    </ol>
                                </div>
                            <?php else: ?>
                                <div class="mt-2 text-xs bg-yellow-50 text-yellow-800 p-2 rounded border border-yellow-100">
                                    <strong>Atenção:</strong> Configure a integração com Google (JSON) em "Configurações"
                                    primeiro.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <a href="veterinarios.php"
                                class="bg-gray-100 text-gray-700 py-2 px-6 rounded-lg font-medium">Cancelar</a>
                            <button type="submit"
                                class="bg-cyan-600 text-white py-2 px-6 rounded-lg font-medium shadow hover:bg-cyan-700 transition">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Signature Pad JS -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
        let signaturePad = null;
        let isDrawingMode = false;

        function initSignaturePad() {
            if (signaturePad) return;

            const canvas = document.getElementById('signature-pad');

            function resizeCanvas() {
                if (!isDrawingMode) return; // Only resize when visible
                const ratio = Math.max(window.devicePixelRatio || 1, 1);

                // Get correct dimensions
                const width = canvas.offsetWidth;
                const height = canvas.offsetHeight;

                if (width === 0) return; // hidden

                canvas.width = width * ratio;
                canvas.height = height * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                if (signaturePad) {
                    signaturePad.clear();
                }
            }

            window.addEventListener("resize", resizeCanvas);

            signaturePad = new SignaturePad(canvas, {
                penColor: "#0f172a", // Dark slate
                backgroundColor: "rgba(0,0,0,0)", // Transparent!
                minWidth: 1.5,
                maxWidth: 3.5,
                dotSize: 2.5
            });

            signaturePad.addEventListener("endStroke", updateBase64);

            // Initial resize right away
            setTimeout(resizeCanvas, 100);
        }

        function clearSignaturePad() {
            if (signaturePad) {
                signaturePad.clear();
            }
            updateBase64();
        }

        function updateBase64() {
            const input = document.getElementById('assinatura_base64');
            const status = document.getElementById('signature-status');
            const fileInput = document.getElementById('input-assinatura-file');

            if (signaturePad && !signaturePad.isEmpty()) {
                input.value = signaturePad.toDataURL("image/png");

                status.innerHTML = '<span class="material-icons text-[14px]">check_circle</span> Pronto!';
                status.classList.remove('text-gray-500', 'border-gray-200');
                status.classList.add('text-green-700', 'bg-green-100', 'border-green-300');

                // Limpar file input quando usar o pad
                if (fileInput) fileInput.value = '';
            } else {
                input.value = "";
                status.innerHTML = '<span class="w-2 h-2 rounded-full bg-gray-400 inline-block animate-pulse"></span> Aguardando...';
                status.classList.add('text-gray-500', 'border-gray-200');
                status.classList.remove('text-green-700', 'bg-green-100', 'border-green-300');
            }
        }

        function showSignatureMode(mode) {
            const upContainer = document.getElementById('mode-upload-container');
            const dwContainer = document.getElementById('mode-draw-container');
            const btnUp = document.getElementById('btn-mode-upload');
            const btnDw = document.getElementById('btn-mode-draw');

            if (mode === 'upload') {
                isDrawingMode = false;
                upContainer.classList.remove('hidden');
                setTimeout(() => upContainer.classList.remove('opacity-0'), 10);

                dwContainer.classList.add('opacity-0');
                setTimeout(() => dwContainer.classList.add('hidden'), 300);

                // Styling buttons
                btnUp.className = "px-5 py-2.5 text-sm font-semibold border rounded-lg transition-all bg-cyan-100 text-cyan-800 border-cyan-300 shadow flex items-center";
                btnDw.className = "px-5 py-2.5 text-sm font-semibold border rounded-lg transition-all bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300 flex items-center shadow-sm";

                if (typeof clearSignaturePad === 'function') clearSignaturePad(); // clear canvas when going back to upload
            } else {
                isDrawingMode = true;
                dwContainer.classList.remove('hidden');
                setTimeout(() => dwContainer.classList.remove('opacity-0'), 10);

                upContainer.classList.add('opacity-0');
                setTimeout(() => upContainer.classList.add('hidden'), 300);

                // Styling buttons
                btnDw.className = "px-5 py-2.5 text-sm font-semibold border rounded-lg transition-all bg-cyan-100 text-cyan-800 border-cyan-300 shadow flex items-center";
                btnUp.className = "px-5 py-2.5 text-sm font-semibold border rounded-lg transition-all bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300 flex items-center shadow-sm";

                initSignaturePad();

                // limpa file import when pad selected
                const fileInput = document.getElementById('input-assinatura-file');
                if (fileInput) fileInput.value = '';
            }
        }
    </script>
</body>

</html>