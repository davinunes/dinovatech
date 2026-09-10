<?php
session_set_cookie_params(0, '/');
session_start();

include "../database.php"; // Using backend database connection
require_once __DIR__ . '/../dinovatech/helpers/AppHelper.php';

$cliente_id = $_SESSION['cliente_id'] ?? null;
$id_fatura = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;
$fatura = null;
$error_msg = "";

if ($id_fatura) {
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_fatura);
    
    if (!empty($token)) {
        $token_safe = mysqli_real_escape_string($link, $token);
        // Acesso direto via token único de segurança da fatura
        $query = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj 
                  FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente 
                  WHERE F.id_fatura = '$id_safe' AND F.token_acesso = '$token_safe' AND F.token_acesso IS NOT NULL AND F.token_acesso != ''";
    } else {
        // Validação padrão por sessão ativa
        if (!$cliente_id) {
            header("Location: index.php");
            exit();
        }
        $query = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj 
                  FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente 
                  WHERE F.id_fatura = '$id_safe' AND F.id_cliente = '$cliente_id'";
    }

    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $fatura = mysqli_fetch_assoc($result);

        // Preenche a sessão para permitir navegação subsequente do cliente
        if (!empty($token)) {
            $_SESSION['cliente_id'] = $fatura['id_cliente'];
            $_SESSION['cliente_nome'] = $fatura['nome_cliente'];
        }

        $items = [];
        $query_items = "SELECT I.*, S.nome_servico FROM ItensFatura I JOIN Servicos S ON I.id_servico = S.id_servico WHERE I.id_fatura = '$id_safe'";
        $res_items = DBExecute($link, $query_items);
        while ($row = mysqli_fetch_assoc($res_items))
            $items[] = $row;

        $pagamentos = [];
        $query_pag = "SELECT * FROM Pagamentos WHERE id_fatura = '$id_safe' ORDER BY data_pagamento DESC";
        $res_pag = DBExecute($link, $query_pag);
        while ($row = mysqli_fetch_assoc($res_pag))
            $pagamentos[] = $row;

        $total_pago = 0;
        foreach ($pagamentos as $p) {
            if ($p['status_pagamento'] == 'Confirmado')
                $total_pago += $p['valor_pago'];
        }
        // Calculate Totals with Retention
        $calcTotals = AppHelper::calculateFaturaTotals($link, $id_fatura);
        $valorLiquidoFatura = $calcTotals['valor_liquido'];
        $saldo_devedor = $valorLiquidoFatura - $total_pago;

        // Fetch Company Config
        $config_emissor = [];
        $query_config = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
        $res_config = DBExecute($link, $query_config);
        if ($res_config && mysqli_num_rows($res_config) > 0) {
            $config_emissor = mysqli_fetch_assoc($res_config);
        }

        // Verifica vínculo com Contrato / Recorrência estritamente através dos itens da fatura
        $tem_recorrencia_elegivel = false;
        $id_recorrencia_fatura = null;
        $contrato_fatura = null;
        foreach ($items as $it) {
            if (!empty($it['id_recorrencia'])) {
                $id_recorrencia_fatura = (int)$it['id_recorrencia'];
                break;
            }
        }

        // Busca status do contrato e do Pix Automático
        $pixRecorrenciaAtiva = null;
        if ($id_recorrencia_fatura) {
            $qContratoFatura = "SELECT * FROM Recorrencias WHERE id_recorrencia = $id_recorrencia_fatura LIMIT 1";
            $rContratoFatura = DBExecute($link, $qContratoFatura);
            if ($rContratoFatura && mysqli_num_rows($rContratoFatura) > 0) {
                $contrato_fatura = mysqli_fetch_assoc($rContratoFatura);
                $hoje = date('Y-m-d');
                $isAtivo = !isset($contrato_fatura['ativo']) || (int)$contrato_fatura['ativo'] === 1;
                $isNaoExpirado = empty($contrato_fatura['data_fim_cobranca']) || $contrato_fatura['data_fim_cobranca'] >= $hoje;
                if ($isAtivo && $isNaoExpirado) {
                    $tem_recorrencia_elegivel = true;
                }
            }

            $qPixRec = "SELECT * FROM PixRecorrencias WHERE (id_recorrencia = $id_recorrencia_fatura OR id_fatura_inicial = $id_safe) ORDER BY FIELD(status, 'APROVADA', 'PENDENTE', 'CRIADA', 'REJEITADA', 'CANCELADA'), id_pix_recorrencia DESC LIMIT 1";
            $rPixRec = DBExecute($link, $qPixRec);
            if ($rPixRec && mysqli_num_rows($rPixRec) > 0) {
                $pixRecorrenciaAtiva = mysqli_fetch_assoc($rPixRec);
            }
        }

        $isInfinitePayAtivo = !empty($config_emissor['infinitepay_ativo']) && (int)$config_emissor['infinitepay_ativo'] === 1 && !empty($config_emissor['infinitepay_handle']);
        $isInterAtivo = AppHelper::isInterApiActive();

    } else {
        $error_msg = "Fatura não encontrada ou acesso negado.";
    }
    DBClose($link);
} else {
    $error_msg = "ID da fatura não fornecido.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Fatura #<?= $id_fatura ?> — <?= ($config_emissor['nome_fantasia'] ?? $config_emissor['razao_social'] ?? 'Portal do Cliente') ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php
    $is_vet_fatura = AppHelper::isVetMode();
    $empresa_nome_fatura = $config_emissor['nome_fantasia'] ?? $config_emissor['razao_social'] ?? '';
    $empresa_logo_fatura = $config_emissor['logo_url'] ?? '';
    if (!empty($empresa_logo_fatura) && !preg_match('~^(https?://|/)~i', $empresa_logo_fatura)) {
        $empresa_logo_fatura = '../dinovatech/' . $empresa_logo_fatura;
    }
    ?>
    <meta name="theme-color" content="<?= $is_vet_fatura ? '#065f46' : '#0c4a6e' ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Round|Material+Icons+Outlined" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/kjua@0.9.0/dist/kjua.min.js"></script>
    <style>
        :root {
            --font: 'Inter', sans-serif;
            <?php if ($is_vet_fatura): ?>
            --brand:      #059669;
            --brand-dark: #065f46;
            --brand-light:#d1fae5;
            --header-from:#064e3b;
            --header-to:  #065f46;
            --btn-primary:#059669;
            --btn-hover:  #047857;
            <?php else: ?>
            --brand:      #0284c7;
            --brand-dark: #0c4a6e;
            --brand-light:#e0f2fe;
            --header-from:#0c4a6e;
            --header-to:  #0369a1;
            --btn-primary:#0284c7;
            --btn-hover:  #0369a1;
            <?php endif; ?>
        }
        * { box-sizing: border-box; }
        body { font-family: var(--font); }

        #app-header { background: linear-gradient(135deg, var(--header-from), var(--header-to)); }

        .btn-brand { background: var(--btn-primary); color:#fff; transition: background .2s, transform .15s; }
        .btn-brand:hover { background: var(--btn-hover); transform: translateY(-1px); }

        /* Sticky bottom action bar no mobile */
        @media (max-width: 767px) {
            body { padding-bottom: 80px; }
            #stickyPayBar { display: flex !important; }
        }
        @media (min-width: 768px) {
            #stickyPayBar { display: none !important; }
        }

        /* Animação fade */
        @keyframes fadeInUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .animate-fadeInUp { animation: fadeInUp .4s ease both; }

        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left:0; top:0; width:100%; margin:0; padding:20px; }
            .no-print { display: none !important; }
            #app-header, #stickyPayBar { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- ====== HEADER UNIFICADO ====== -->
    <header id="app-header" class="sticky top-0 z-30 shadow-lg no-print">
        <div class="container mx-auto px-4 h-14 flex justify-between items-center gap-3">
            <a href="index.php" class="flex items-center gap-2.5 min-w-0">
                <?php if (!empty($empresa_logo_fatura)): ?>
                    <img src="<?= htmlspecialchars($empresa_logo_fatura) ?>" alt="Logo" class="h-8 w-auto object-contain shrink-0">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                        <span class="material-icons-round text-white text-xl"><?= $is_vet_fatura ? 'pets' : 'computer' ?></span>
                    </div>
                <?php endif; ?>
                <div class="min-w-0">
                    <p class="text-white/80 text-xs font-medium truncate leading-none"><?= $is_vet_fatura ? 'Portal do Tutor' : 'Área do Cliente' ?></p>
                    <?php if ($empresa_nome_fatura): ?>
                    <p class="text-white font-bold text-sm truncate leading-tight"><?= htmlspecialchars($empresa_nome_fatura) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <div class="flex items-center gap-2">
                <?php if (!$error_msg): ?>
                <button onclick="window.print()"
                    class="text-xs font-semibold text-white/90 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                    <span class="material-icons-round text-base">print</span>
                    <span class="hidden sm:inline">Imprimir</span>
                </button>
                <?php endif; ?>
                <a href="index.php"
                    class="text-xs font-semibold text-white/90 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                    <span class="material-icons-round text-base">arrow_back</span>
                    <span class="hidden sm:inline">Voltar</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-3 sm:px-4 max-w-4xl py-5 sm:py-7">

        <?php if ($error_msg): ?>
            <div class="bg-white border border-red-100 p-8 rounded-2xl shadow-sm text-center animate-fadeInUp">
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons-round text-red-500 text-3xl">error_outline</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Acesso Negado</h2>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars($error_msg) ?></p>
                <a href="index.php" class="inline-flex mt-5 items-center gap-2 text-white font-bold px-6 py-2.5 rounded-xl btn-brand text-sm">
                    <span class="material-icons-round text-base">arrow_back</span> Voltar ao Portal
                </a>
            </div>
        <?php else: ?>

            <?php
            $hoje = date('Y-m-d');
            $isVencida = ($fatura['status'] == 'Em Aberto' && $fatura['data_vencimento'] < $hoje);
            $statusLabel = $fatura['status'];
            $statusBgClass = 'bg-gray-100 text-gray-600';
            if ($fatura['status'] == 'Liquidada') { $statusBgClass = 'bg-emerald-100 text-emerald-700 border border-emerald-200'; }
            elseif ($isVencida) { $statusLabel = 'Atrasada'; $statusBgClass = 'bg-red-100 text-red-700 border border-red-200'; }
            else { $statusBgClass = 'bg-amber-100 text-amber-700 border border-amber-200'; }
            ?>

            <div id="printableArea" class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden relative animate-fadeInUp">

                <!-- Status Badge -->
                <div class="absolute top-0 right-0 p-5 no-print z-10">
                    <span class="px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-widest <?= $statusBgClass ?>">
                        <?= $statusLabel ?>
                    </span>
                </div>

                <div class="p-6 sm:p-8 md:p-10">
                    <!-- Watermark PAGO -->
                    <?php if ($fatura['status'] === 'Liquidada'): ?>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-none select-none z-0 whitespace-nowrap">
                            <span style="border: 6px solid #059669; color:#059669; font-weight:900; font-size:5rem; padding:.5rem 2rem; border-radius:1rem; opacity:.12; transform:rotate(-15deg); display:inline-block; letter-spacing:.05em; font-family:var(--font);">PAGO</span>
                        </div>
                    <?php endif; ?>

                    <!-- Invoice Header -->
                    <?php
                    $empresaNome = $config_emissor['nome_fantasia'] ?? $config_emissor['razao_social'] ?? 'Minha Empresa';
                    $empresaCnpj = $config_emissor['cnpj'] ?? '00.000.000/0000-00';
                    $empresaEndereco = ($config_emissor['endereco'] ?? '') . (($config_emissor['numero'] ?? '') ? ', '.$config_emissor['numero'] : '');
                    if (!empty($config_emissor['complemento'])) $empresaEndereco .= ' - '.$config_emissor['complemento'];
                    if (!empty($config_emissor['bairro'])) $empresaEndereco .= ' — '.$config_emissor['bairro'];
                    ?>
                    <div class="border-b border-gray-100 pb-6 mb-6 relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                        <div>
                            <?php if (!empty($empresa_logo_fatura)): ?>
                                <img src="<?= htmlspecialchars($empresa_logo_fatura) ?>" alt="Logo" class="h-10 object-contain mb-2">
                            <?php endif; ?>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-0.5"><?= htmlspecialchars($empresaNome) ?></h1>
                            <p class="text-gray-400 text-xs">CNPJ: <?= htmlspecialchars($empresaCnpj) ?></p>
                            <?php if ($empresaEndereco): ?><p class="text-gray-400 text-xs mt-0.5"><?= htmlspecialchars($empresaEndereco) ?></p><?php endif; ?>
                        </div>
                        <div class="text-left sm:text-right shrink-0">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Fatura</p>
                            <p class="text-3xl font-extrabold" style="color: var(--brand)">#<?= $id_fatura ?></p>
                        </div>
                    </div>

                    <!-- Client & Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Faturado Para</h3>
                            <p class="text-lg font-bold text-gray-800">
                                <?= htmlspecialchars($fatura['nome_cliente']) ?>
                            </p>
                            <p class="text-gray-600">
                                <?= htmlspecialchars($fatura['cpf_cnpj']) ?>
                            </p>
                        </div>
                        <div class="md:text-right">
                            <div class="mb-4">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Data de Vencimento
                                </h3>
                                <p class="text-xl font-bold <?= $isVencida ? 'text-red-600' : 'text-gray-800' ?>">
                                    <?= date('d/m/Y', strtotime($fatura['data_vencimento'])) ?>
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Data de Emissão
                                </h3>
                                <p class="text-gray-600">
                                    <?= date('d/m/Y', strtotime($fatura['data_emissao'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="mb-8">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Detalhes do
                            Serviço</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-sm text-gray-500">
                                    <tr>
                                        <th class="pb-4 font-semibold">Descrição</th>
                                        <th class="pb-4 font-semibold text-center">Qtd</th>
                                        <th class="pb-4 font-semibold text-right">Valor Unit.</th>
                                        <th class="pb-4 font-semibold text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <?php foreach ($items as $item): ?>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-4">
                                                <p class="font-bold text-gray-800">
                                                    <?= htmlspecialchars($item['nome_servico']) ?>
                                                </p>
                                                <?php if ($item['tag']): ?>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        <?= htmlspecialchars($item['tag']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-center">
                                                <?= $item['quantidade'] ?>
                                            </td>
                                            <td class="py-4 text-right">R$
                                                <?= number_format($item['valor_unitario'], 2, ',', '.') ?>
                                            </td>
                                            <td class="py-4 text-right font-bold">R$
                                                <?= number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="flex justify-end border-t border-gray-100 pt-8">
                        <div class="w-full md:w-1/2 lg:w-1/3">
                            <div class="flex justify-between mb-2 text-gray-600">
                                <span>Subtotal</span>
                                <span>R$
                                    <?= number_format($fatura['valor_total_fatura'], 2, ',', '.') ?>
                                </span>
                            </div>
                            <?php if ($calcTotals['iss_retido']): ?>
                                <div class="flex justify-between mb-2 text-red-500 text-sm">
                                    <span>(-) <?= $calcTotals['detalhes_retencao'] ?></span>
                                    <span>R$
                                        <?= number_format($calcTotals['valor_retencao'], 2, ',', '.') ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($calcTotals['desconto_aplicado']) && $calcTotals['desconto_aplicado'] > 0): ?>
                                <div class="flex justify-between mb-2 text-green-600 font-medium">
                                    <span>(-) Desconto</span>
                                    <span>R$ <?= number_format($calcTotals['desconto_aplicado'], 2, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($total_pago > 0): ?>
                                <div class="flex justify-between mb-2 text-green-600">
                                    <span>Valor Pago</span>
                                    <span>- R$
                                        <?= number_format($total_pago, 2, ',', '.') ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div
                                class="flex justify-between pt-4 border-t border-gray-200 text-2xl font-bold text-gray-900">
                                <span>Total a Pagar</span>
                                <span>R$
                                    <?= number_format($saldo_devedor, 2, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Attachments Section -->
                <div class="border-t border-gray-100 pt-8 mt-8">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Anexos da Fatura</h3>
                    <ul id="listaAnexosCliente" class="space-y-3">
                        <li class="text-sm text-gray-500">Carregando anexos...</li>
                    </ul>
                </div>

            </div>

            <!-- Desktop Payment Action Bar & Cards -->
            <?php if ($saldo_devedor > 0): ?>
                <div class="bg-gray-50/80 px-6 sm:px-8 py-6 border-t border-gray-100 no-print">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="material-icons-round text-cyan-600 text-base">payment</span>
                                Opções de Pagamento Online
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Selecione a forma de pagamento de sua preferência</p>
                        </div>
                        <?php if (($fatura['permitir_pagamento_parcial'] ?? 0) == 1): ?>
                            <button type="button" onclick="$('#modalPagamentoParcial').removeClass('hidden')"
                                class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold py-1.5 px-3 rounded-lg shadow-xs transition text-xs">
                                Pagar Outro Valor
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Opção 1: InfinitePay (PIX / Cartão) -->
                        <?php if ($isInfinitePayAtivo): ?>
                            <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col justify-between hover:border-emerald-300 hover:shadow-md transition">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <img src="https://cdn.prod.website-files.com/65c1399ac999a342139b5069/65c1399ac999a342139b5434_logo_brlc_preto.svg" 
                                                 alt="InfinitePay" class="h-4">
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            PIX / Cartão
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-4 leading-relaxed">
                                        Pagamento instantâneo via QR Code PIX ou parcelamento em até 12x no Cartão de Crédito.
                                    </p>
                                </div>
                                <button type="button" onclick="abrirModalInfinitePayCliente()"
                                    class="w-full bg-slate-900 hover:bg-slate-800 active:bg-black text-white font-bold py-3 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm">
                                    <span class="material-icons-round text-base text-emerald-400">credit_card</span>
                                    <span>Pagar com InfinitePay</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Opção 2: Banco Inter (Somente PIX) -->
                        <?php if ($isInterAtivo): ?>
                            <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col justify-between hover:border-orange-300 hover:shadow-md transition">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-5 w-auto" viewBox="0 0 120 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="32" height="32" rx="8" fill="#F27321"/>
                                                <path d="M10 9H16V23H10V9Z" fill="white"/>
                                                <path d="M19 14H24V23H19V14Z" fill="white"/>
                                                <text x="38" y="22" fill="#1E293B" font-family="sans-serif" font-weight="800" font-size="18" letter-spacing="-0.5">inter</text>
                                            </svg>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                            PIX Instantâneo
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-4 leading-relaxed">
                                        Pagamento direto via QR Code ou Chave Copia e Cola Banco Inter.
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <button type="button" id="btnPagarPix"
                                        class="w-full bg-slate-900 hover:bg-slate-800 active:bg-black text-white font-bold py-3 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm">
                                        <span class="material-icons-round text-base text-orange-400">qr_code_2</span>
                                        <span>Pagar PIX via Banco Inter</span>
                                    </button>
                                    <?php if ($tem_recorrencia_elegivel && (!$pixRecorrenciaAtiva || $pixRecorrenciaAtiva['status'] !== 'APROVADA')): ?>
                                        <button id="btnAtivarPixAutomatico" type="button"
                                            class="w-full bg-gradient-to-r from-purple-700 via-indigo-600 to-cyan-600 hover:opacity-90 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                                            <span class="material-icons-round text-sm text-yellow-300">bolt</span> Ativar Pix Automático
                                        </button>
                                    <?php elseif ($pixRecorrenciaAtiva && $pixRecorrenciaAtiva['status'] === 'APROVADA'): ?>
                                        <div class="text-center py-1.5 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-center gap-1">
                                            <span class="material-icons-round text-xs">bolt</span> Débito Automático Pix Ativo
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STICKY BOTTOM BAR (Mobile) -->
                <div id="stickyPayBar" class="fixed bottom-0 left-0 right-0 z-20 bg-white border-t border-gray-200 shadow-xl no-print p-3 flex flex-col gap-2 md:hidden" style="padding-bottom: env(safe-area-inset-bottom, 12px);">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 text-xs font-medium">Saldo a pagar</span>
                        <span class="font-extrabold text-lg text-gray-900">R$ <?= number_format($saldo_devedor, 2, ',', '.') ?></span>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($isInfinitePayAtivo): ?>
                            <button type="button" onclick="abrirModalInfinitePayCliente()"
                                class="flex-1 bg-slate-900 text-white font-bold py-3 rounded-xl shadow-md text-xs flex items-center justify-center gap-1.5">
                                <span class="material-icons-round text-sm text-emerald-400">credit_card</span> InfinitePay
                            </button>
                        <?php endif; ?>
                        <?php if ($isInterAtivo): ?>
                            <button id="btnPagarPixMobile" type="button"
                                class="flex-1 bg-slate-900 text-white font-bold py-3 rounded-xl shadow-md flex items-center justify-center gap-1.5 text-xs">
                                <span class="material-icons-round text-sm text-orange-400">qr_code_2</span> Inter PIX
                            </button>
                        <?php endif; ?>
                        <?php if (AppHelper::isInterApiActive() && $tem_recorrencia_elegivel && (!$pixRecorrenciaAtiva || $pixRecorrenciaAtiva['status'] !== 'APROVADA')): ?>
                            <button id="btnAtivarPixAutomaticoMobile" type="button"
                                class="bg-gradient-to-r from-purple-700 to-cyan-600 text-white font-bold py-3 px-3 rounded-xl shadow-md text-xs flex items-center justify-center gap-1">
                                <span class="material-icons-round text-sm text-yellow-300">bolt</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal PIX Automático (Jornada 4) -->
        <div id="modalPixAutomatico" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 hidden">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 sm:p-8 text-center relative overflow-hidden">
                <button onclick="fecharModalPixRec()"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
                    <span class="material-icons">close</span>
                </button>

                <!-- Etapa 1: Explicação e Benefícios -->
                <div id="pixRecInfoStep">
                    <div class="w-14 h-14 bg-gradient-to-tr from-purple-600 to-cyan-500 text-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md">
                        <span class="material-icons text-3xl text-yellow-300">bolt</span>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-1">Pix Automático Mensal</h2>
                    <p class="text-xs text-gray-500 mb-5">Pague esta fatura e autorize as próximas em débito automático sem burocracia.</p>

                    <div class="bg-gradient-to-br from-purple-50 via-indigo-50 to-cyan-50 border border-purple-100 rounded-xl p-4 text-left space-y-3 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="material-icons text-purple-600 text-xl shrink-0 mt-0.5">verified</span>
                            <div class="text-xs text-gray-700 leading-relaxed">
                                <strong class="text-purple-950 block text-sm font-bold mb-0.5">Pagamento 2 em 1</strong>
                                O mesmo QR Code quita esta fatura (R$ <?= number_format($saldo_devedor, 2, ',', '.') ?>) e cadastra o débito automático para os meses seguintes.
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-icons text-cyan-600 text-xl shrink-0 mt-0.5">event_repeat</span>
                            <div class="text-xs text-gray-700 leading-relaxed">
                                <strong class="text-cyan-950 block text-sm font-bold mb-0.5">Zero Preocupação com Atrasos</strong>
                                No dia do vencimento das próximas mensalidades, o débito ocorre direto na sua conta do banco participante sem taxas.
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-icons text-emerald-600 text-xl shrink-0 mt-0.5">lock_open</span>
                            <div class="text-xs text-gray-700 leading-relaxed">
                                <strong class="text-emerald-950 block text-sm font-bold mb-0.5">Controle Total e Cancelamento Fácil</strong>
                                Você pode cancelar ou pausar a qualquer momento direto no aplicativo do seu banco ou na Central do Cliente.
                            </div>
                        </div>
                    </div>

                    <!-- Alerta Passo Importante no Banco -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-left mb-6 flex items-start gap-2.5 shadow-sm">
                        <span class="material-icons text-amber-600 text-lg shrink-0 mt-0.5">touch_app</span>
                        <div class="text-xs text-amber-900 leading-relaxed">
                            <strong class="text-amber-950 block font-bold mb-0.5">⚠️ Passo Importante no App do seu Banco:</strong>
                            Após concluir o pagamento desta fatura, o próprio banco exibirá em seguida a tela do <strong>Assistente de Contratação do Pix Automático</strong>. Você <strong>deve continuar e confirmar no aplicativo do seu banco</strong> para finalizar a autorização das cobranças recorrentes!
                        </div>
                    </div>

                    <button id="btnConfirmarGerarPixRec" type="button"
                        class="w-full bg-gradient-to-r from-purple-700 via-indigo-600 to-cyan-600 hover:opacity-95 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                        <span class="material-icons text-base">qr_code_scanner</span>
                        Gerar QR Code Pix Automático
                    </button>
                </div>

                <!-- Etapa 2: Loading -->
                <div id="pixRecLoading" class="py-10 hidden">
                    <div class="animate-spin rounded-full h-14 w-14 border-4 border-purple-200 border-t-purple-600 mx-auto mb-4"></div>
                    <h3 class="text-lg font-bold text-gray-800">Gerando proposta no Banco Inter...</h3>
                    <p class="text-xs text-gray-500 mt-1">Criando cobrança combinada (Jornada 4)</p>
                </div>

                <!-- Etapa 3: Exibição do QR Code Jornada 4 -->
                <div id="pixRecContent" class="hidden">
                    <div class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-800 bg-purple-100 px-3 py-1 rounded-full mb-3 uppercase tracking-wider">
                        <span class="material-icons text-xs text-purple-600">bolt</span> Jornada 4 - Fatura + Recorrência
                    </div>

                    <h3 class="text-xl font-bold text-gray-800 mb-1">Escaneie o QR Code no seu Banco</h3>
                    <p class="text-xs text-gray-500 mb-3">Pague a fatura atual e confirme a recorrência no aplicativo do seu banco.</p>

                    <div id="qrcodeDisplayRec"
                        class="mx-auto inline-block p-3.5 border-2 border-purple-100 rounded-2xl mb-3 shadow-sm bg-white"></div>

                    <div class="mb-4 text-left">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Pix Copia e Cola Combinado</label>
                        <div class="flex">
                            <input type="text" id="pixCopiaColaRecInput" readonly
                                class="flex-1 p-2.5 bg-gray-50 border border-r-0 border-gray-300 rounded-l-lg text-xs text-gray-600 focus:outline-none select-all">
                            <button onclick="copiarPixRec()"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-r-lg font-bold text-xs transition">Copiar</button>
                        </div>
                        <p id="msgCopiaRec" class="text-green-600 text-xs mt-1 hidden font-bold">Código copiado com sucesso!</p>
                    </div>

                    <!-- Instrução de Continuidade no Banco -->
                    <div class="bg-purple-50/80 border border-purple-200 rounded-xl p-3.5 text-left mb-4 space-y-2">
                        <div class="flex items-center gap-1.5 text-purple-950 font-bold text-xs">
                            <span class="material-icons text-purple-600 text-sm">phonelink_setup</span>
                            <span>COMO CONCLUIR NO APLICATIVO DO SEU BANCO:</span>
                        </div>
                        <div class="text-xs text-purple-900 space-y-1.5 leading-snug">
                            <p class="flex items-start gap-1.5">
                                <span class="bg-purple-600 text-white rounded-full w-4 h-4 text-[10px] font-bold inline-flex items-center justify-center shrink-0 mt-0.5">1</span>
                                <span><strong>Pague a fatura atual:</strong> Conclua a transferência do valor da fatura de hoje no app do seu banco.</span>
                            </p>
                            <p class="flex items-start gap-1.5">
                                <span class="bg-purple-600 text-white rounded-full w-4 h-4 text-[10px] font-bold inline-flex items-center justify-center shrink-0 mt-0.5">2</span>
                                <span><strong>Avance no assistente do banco:</strong> Logo após o pagamento, o app do seu banco abrirá a tela de contratação. <strong>Avance e confirme a autorização no app do seu banco</strong> para ativar as cobranças futuras!</span>
                            </p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-100 text-purple-900 p-3.5 rounded-xl text-xs space-y-1 text-center">
                        <p class="font-bold flex items-center justify-center gap-1"><span class="material-icons text-sm animate-spin">sync</span> Aguardando confirmação do banco...</p>
                        <p class="text-[11px] text-purple-700">Assim que você concluir a autorização no app do seu banco, a fatura será baixada e o Pix Automático ativado automaticamente.</p>
                    </div>
                </div>

                <!-- Etapa 4: Sucesso -->
                <div id="pixRecSuccess" class="hidden py-8">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-emerald-100 mb-4">
                        <span class="material-icons text-emerald-600 text-3xl">check_circle</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-1">Pagamento e Pix Automático Confirmados!</h3>
                    <p class="text-sm text-gray-600 mb-6">Sua fatura foi liquidada com sucesso e o débito automático via Pix está ativo para os próximos meses.</p>
                    <button onclick="window.location.reload()"
                        class="bg-gray-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition shadow">Fechar</button>
                </div>

            </div>
        </div>

        <!-- Modal PIX Padrão -->
        <div id="modalPix" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-8 text-center relative">
                <button onclick="$('#modalPix').addClass('hidden')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>

                <h2 class="text-2xl font-bold text-gray-800 mb-2">Pagamento via PIX</h2>
                <div id="pixLoading" class="py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Gerando QR Code...</p>
                </div>

                <div id="pixContent" class="hidden">
                    <p class="text-sm text-gray-600 mb-6">Escaneie o QR Code ou use o código Copia e Cola.</p>

                    <div id="qrcodeDisplay"
                        class="mx-auto inline-block p-4 border border-gray-200 rounded-lg mb-6 shadow-sm"></div>

                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 text-left">Pix
                            Copia e Cola</label>
                        <div class="flex">
                            <input type="text" id="pixCopiaColaInput" readonly
                                class="flex-1 p-2 bg-gray-50 border border-r-0 border-gray-300 rounded-l-lg text-xs text-gray-600 focus:outline-none">
                            <button onclick="copiarPix()"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-r-lg font-medium text-xs transition-colors">Copiar</button>
                        </div>
                        <p id="msgCopia" class="text-green-600 text-xs mt-1 hidden font-bold">Copiado!</p>
                    </div>

                    <div class="bg-blue-50 text-blue-700 p-4 rounded-lg text-sm">
                        <p class="font-bold flex items-center justify-center mb-1"><span
                                class="material-icons text-sm mr-1">sync</span> Aguardando pagamento...</p>
                        <p class="text-xs">Após pagar, a fatura será baixada automaticamente em instantes.</p>
                        <p class="text-xs">Expira em: <span id="expiraEm"></span>.</p>
                    </div>
                </div>

                <div id="pixSuccess" class="hidden py-8">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <span class="material-icons text-green-600 text-3xl">check</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Pagamento Confirmado!</h3>
                    <p class="text-gray-600 mb-6">Obrigado. Sua fatura foi liquidada.</p>
                    <button onclick="window.location.reload()"
                        class="bg-gray-800 text-white px-6 py-2 rounded-lg font-medium hover:bg-gray-900">Fechar</button>
                </div>

            </div>
        </div>

        <!-- Modal Pagamento Parcial -->
        <div id="modalPagamentoParcial"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 relative">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Pagamento Parcial</h3>
                <p class="text-gray-600 text-sm mb-4">Informe o valor que deseja pagar agora:</p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$)</label>
                    <input type="number" id="valorParcialInput" step="0.01" max="<?= $saldo_devedor ?>"
                        class="w-full p-3 border border-gray-300 rounded-lg text-lg font-bold text-gray-800"
                        placeholder="0,00">
                    <p class="text-xs text-gray-500 mt-1">Saldo restante: R$
                        <?= number_format($saldo_devedor, 2, ',', '.') ?></p>
                </div>

                <div class="flex gap-2">
                    <button onclick="$('#modalPagamentoParcial').addClass('hidden')"
                        class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-lg font-medium">Cancelar</button>
                    <button onclick="iniciarPagamentoParcial()"
                        class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold shadow-md">Pagar</button>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                // Load Attachments
                carregarAnexos();

                let pollingInterval;
                let pollingRecInterval;

                $('#btnPagarPix').click(function () {
                    $('#modalPix').removeClass('hidden');
                    generatePix();
                });

                // Pix Automático Handlers
                $('#btnAtivarPixAutomatico').click(function () {
                    $('#modalPixAutomatico').removeClass('hidden');
                    $('#pixRecInfoStep').removeClass('hidden');
                    $('#pixRecLoading').addClass('hidden');
                    $('#pixRecContent').addClass('hidden');
                    $('#pixRecSuccess').addClass('hidden');
                });

                window.fecharModalPixRec = function () {
                    if (pollingRecInterval) clearInterval(pollingRecInterval);
                    $('#modalPixAutomatico').addClass('hidden');
                };

                $('#btnConfirmarGerarPixRec').click(function () {
                    $('#pixRecInfoStep').addClass('hidden');
                    $('#pixRecLoading').removeClass('hidden');

                    $.ajax({
                        url: '../inter/endpoint.php?action=obter_ou_criar_pix_jornada4',
                        type: 'POST',
                        data: JSON.stringify({ id_fatura: <?= $id_fatura ?> }),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                renderPixRec(response);
                            } else {
                                alert('Erro ao gerar Pix Automático: ' + (response.message || 'Falha na comunicação com o banco.'));
                                $('#pixRecLoading').addClass('hidden');
                                $('#pixRecInfoStep').removeClass('hidden');
                            }
                        },
                        error: function (xhr) {
                            let msg = 'Erro de comunicação ao gerar proposta.';
                            try {
                                const errObj = JSON.parse(xhr.responseText);
                                if (errObj && errObj.message) msg = errObj.message;
                            } catch(e){}
                            alert(msg);
                            $('#pixRecLoading').addClass('hidden');
                            $('#pixRecInfoStep').removeClass('hidden');
                        }
                    });
                });

                function renderPixRec(data) {
                    $('#pixRecLoading').addClass('hidden');
                    $('#pixRecContent').removeClass('hidden');

                    const pixCode = data.pixCopiaECola || '';
                    const el = kjua({ text: pixCode, size: 400, fill: '#000', back: '#fff', quiet: 1 });
                    $(el).css({ 'max-width': '100%', 'height': 'auto' });
                    $('#qrcodeDisplayRec').html('').append(el);
                    $('#pixCopiaColaRecInput').val(pixCode);

                    // Inicia Polling duplo: Fatura e Recorrência
                    startPollingRec(data.idRec, data.txid);
                }

                function startPollingRec(idRec, txid) {
                    if (pollingRecInterval) clearInterval(pollingRecInterval);
                    pollingRecInterval = setInterval(function () {
                        // 1. Checa pagamento da fatura
                        if (txid) {
                            $.getJSON(`../inter/endpoint.php?action=verificar_pagamento_pix&txid=${txid}`, function (res) {
                                if (res.success && res.data.status === 'CONCLUIDA') {
                                    clearInterval(pollingRecInterval);
                                    // Sincroniza recorrência
                                    if (idRec) {
                                        $.getJSON(`../inter/endpoint.php?action=consultar_status_recorrencia&idRec=${encodeURIComponent(idRec)}`);
                                    }
                                    $('#pixRecContent').addClass('hidden');
                                    $('#pixRecSuccess').removeClass('hidden');
                                }
                            });
                        }
                    }, 4000);
                }

                window.copiarPixRec = function () {
                    const copyText = document.getElementById("pixCopiaColaRecInput");
                    copyText.select();
                    document.execCommand("copy");
                    if (navigator.clipboard) navigator.clipboard.writeText(copyText.value);

                    $('#msgCopiaRec').removeClass('hidden');
                    setTimeout(() => $('#msgCopiaRec').addClass('hidden'), 2500);
                };

                window.iniciarPagamentoParcial = function () {
                    let valor = parseFloat($('#valorParcialInput').val());
                    if (!valor || valor <= 0) {
                        alert("Digite um valor válido.");
                        return;
                    }
                    $('#modalPagamentoParcial').addClass('hidden');
                    $('#modalPix').removeClass('hidden');
                    generatePix(valor);
                };

                function generatePix(valor = null) {
                    let payload = { id_fatura: <?= $id_fatura ?> };
                    if (valor) {
                        payload.valor_pagamento = valor;
                    }

                    $.ajax({
                        url: '../inter/endpoint.php?action=obter_ou_criar_pix_pagamento',
                        type: 'POST',
                        data: JSON.stringify(payload),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                renderPix(response.data);
                            } else {
                                alert('Erro ao gerar PIX: ' + response.message);
                                $('#modalPix').addClass('hidden');
                            }
                        },
                        error: function () {
                            alert('Erro de comunicação.');
                            $('#modalPix').addClass('hidden');
                        }
                    });
                }

                function renderPix(data) {
                    $('#pixLoading').addClass('hidden');
                    $('#pixContent').removeClass('hidden');

                    const el = kjua({ text: data.pixCopiaECola, size: 400, fill: '#000', back: '#fff', quiet: 1 });
                    $(el).css({ 'max-width': '100%', 'height': 'auto' });
                    $('#qrcodeDisplay').html('').append(el);
                    $('#pixCopiaColaInput').val(data.pixCopiaECola);
                    
                    let expirationDate;
                    if (data.expiraEm) {
                        expirationDate = new Date(data.expiraEm);
                    } else if (data.calendario && data.calendario.criacao) {
                        const criacao = new Date(data.calendario.criacao);
                        const seconds = data.calendario.expiracao || 0;
                        expirationDate = new Date(criacao.getTime() + (seconds * 1000));
                    }

                    if (expirationDate) {
                        $('#expiraEm').text(expirationDate.toLocaleString('pt-BR', {
                            timeZone: 'America/Sao_Paulo',
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        }));
                    } else {
                        $('#expiraEm').text('');
                    }

                    startPolling(data.txid);
                }

                function startPolling(txid) {
                    if (pollingInterval) clearInterval(pollingInterval);
                    pollingInterval = setInterval(function () {
                        $.getJSON(`../inter/endpoint.php?action=verificar_pagamento_pix&txid=${txid}`, function (res) {
                            if (res.success && res.data.status === 'CONCLUIDA') {
                                clearInterval(pollingInterval);
                                $('#pixContent').addClass('hidden');
                                $('#pixSuccess').removeClass('hidden');
                            }
                        });
                    }, 5000);
                }

                window.copiarPix = function () {
                    const copyText = document.getElementById("pixCopiaColaInput");
                    copyText.select();
                    document.execCommand("copy");
                    if (navigator.clipboard) navigator.clipboard.writeText(copyText.value);

                    $('#msgCopia').removeClass('hidden');
                    setTimeout(() => $('#msgCopia').addClass('hidden'), 2000);
                };
            });

            function carregarAnexos() {
                $.post('../dinovatech/app.php', { action: 'get_fatura_arquivos', id_fatura: <?= $id_fatura ?> }, function (res) {
                    if (res.success) {
                        let html = '';
                        if (res.data.length > 0) {
                            res.data.forEach(arq => {
                                let sizeStr = '';
                                if (arq.tamanho_bytes < 1024) sizeStr = arq.tamanho_bytes + ' B';
                                else if (arq.tamanho_bytes < 1024 * 1024) sizeStr = (arq.tamanho_bytes / 1024).toFixed(1) + ' KB';
                                else sizeStr = (arq.tamanho_bytes / (1024 * 1024)).toFixed(1) + ' MB';

                                html += `
                                        <li class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
                                            <div class="flex items-center overflow-hidden">
                                                <span class="material-icons text-red-500 mr-3 text-2xl">description</span>
                                                <div class="truncate">
                                                    <a href="${arq.url_publica}" target="_blank" class="text-sm font-semibold text-gray-700 hover:text-blue-600 block truncate" title="${arq.nome_original}">
                                                        ${arq.nome_original}
                                                    </a>
                                                    <span class="text-xs text-gray-400 font-medium">${sizeStr}</span>
                                                </div>
                                            </div>
                                            <a href="${arq.url_publica}" target="_blank" class="ml-4 text-cyan-600 hover:text-cyan-800 text-sm font-medium flex items-center">
                                                <span class="material-icons text-base mr-1">download</span> Baixar
                                            </a>
                                        </li>
                                    `;
                            });
                        } else {
                            html = '<li class="text-sm text-gray-400 italic">Nenhum arquivo anexado a esta fatura.</li>';
                        }
                        $('#listaAnexosCliente').html(html);
                    } else {
                        $('#listaAnexosCliente').html('<li class="text-sm text-red-500">Erro ao carregar anexos.</li>');
                    }
                }, 'json');
            }
        </script>


    <?php endif; ?>
    </div>

        <!-- Modal InfinitePay (Cliente) -->
        <div id="modalInfinitePayCliente" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 hidden p-4 no-print">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative overflow-hidden">
                <button type="button" onclick="$('#modalInfinitePayCliente').addClass('hidden')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition p-1 rounded-full hover:bg-gray-100">
                    <span class="material-icons-round text-xl">close</span>
                </button>

                <div class="text-center mb-6">
                    <img src="https://cdn.prod.website-files.com/65c1399ac999a342139b5069/65c1399ac999a342139b5434_logo_brlc_preto.svg" 
                         alt="InfinitePay" class="h-6 mx-auto mb-3">
                    <h3 class="text-lg font-bold text-gray-900">Pagamento via InfinitePay</h3>
                    <p class="text-xs text-gray-500 mt-1">Pague via PIX instantâneo ou Cartão de Crédito</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
                    <div class="flex justify-between items-center text-xs text-gray-600 mb-2">
                        <span>Fatura:</span>
                        <span class="font-bold text-gray-800">#<?= $id_fatura ?></span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-gray-600 mb-2">
                        <span>Cliente:</span>
                        <span class="font-bold text-gray-800 truncate max-w-[180px]"><?= htmlspecialchars($fatura['nome_cliente'] ?? '') ?></span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between items-center text-sm font-bold text-gray-900">
                        <span>Valor a Pagar:</span>
                        <span class="text-emerald-600">R$ <?= number_format($saldo_devedor, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div id="infinitePayStepInitial">
                    <button type="button" onclick="gerarCheckoutInfinitePayCliente(<?= $id_fatura ?>)" id="btnGerarInfinitePayCliente"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-md">
                        <span class="material-icons-round">open_in_new</span> Ir para Checkout InfinitePay
                    </button>
                </div>

                <div id="infinitePayStepLoading" class="hidden text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-emerald-500 border-t-transparent mb-3"></div>
                    <p class="text-xs font-semibold text-gray-600">Gerando checkout seguro...</p>
                </div>
            </div>
        </div>

    <script>
    function abrirModalInfinitePayCliente() {
        $('#modalInfinitePayCliente').removeClass('hidden');
    }

    function gerarCheckoutInfinitePayCliente(idFatura) {
        $('#infinitePayStepInitial').addClass('hidden');
        $('#infinitePayStepLoading').removeClass('hidden');

        $.ajax({
            url: '../dinovatech/app.php',
            type: 'POST',
            data: { action: 'gerar_checkout_infinitepay', id_fatura: idFatura },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.url) {
                    window.location.href = res.url;
                } else {
                    alert('Erro ao gerar checkout: ' + (res.message || 'Tente novamente.'));
                    $('#infinitePayStepLoading').addClass('hidden');
                    $('#infinitePayStepInitial').removeClass('hidden');
                }
            },
            error: function() {
                alert('Erro de comunicação ao gerar checkout.');
                $('#infinitePayStepLoading').addClass('hidden');
                $('#infinitePayStepInitial').removeClass('hidden');
            }
        });
    }

    // Conecta botões duplicados (mobile) aos mesmos handlers do desktop
    $(document).ready(function(){
        $('#btnPagarPixMobile').click(function(){
            $('#modalPix').removeClass('hidden');
            generatePix();
        });
        $('#btnAtivarPixAutomaticoMobile').click(function(){
            $('#btnAtivarPixAutomatico').trigger('click');
        });
    });
    </script>

</body>
</html>