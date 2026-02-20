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

        // --- SIGNATURE UPLOAD ---
        if (isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileTmp = $_FILES['assinatura']['tmp_name'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fileTmp);

            if (in_array($mimeType, $allowedTypes)) {
                $ext = pathinfo($_FILES['assinatura']['name'], PATHINFO_EXTENSION);
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
                    $conteudoArquivo = file_get_contents($fileTmp);

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
            } else {
                $erro = "Tipo de arquivo não permitido para assinatura. Use PNG, JPG ou WebP.";
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

                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Assinatura Digital (Foto/PNG)</label>
                            <?php if (!empty($vet['url_assinatura'])): ?>
                                <div class="mb-2 p-2 border rounded-lg bg-gray-50 flex items-center justify-center">
                                    <img src="<?= $vet['url_assinatura'] ?>" alt="Assinatura Atual" class="max-h-24 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="assinatura" accept="image/*"
                                class="w-full border-gray-300 rounded-lg p-2 border text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
                            <p class="text-xs text-gray-500 mt-1">Envie uma imagem (PNG, JPG) com fundo transparente ou branco para melhor resultado.</p>
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
</body>

</html>