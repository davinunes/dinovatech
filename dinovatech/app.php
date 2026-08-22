<?php
// app.php
session_set_cookie_params(0, '/');
session_start();

include "../database.php"; // Seu arquivo com DBConnect, DBExecute, etc.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
require_once __DIR__ . '/helpers/EncryptionHelper.php';
require_once __DIR__ . '/helpers/ContaDevHelper.php';

// ACTION GET: Toggle Status Cliente (Direct Link)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status_cliente') {
    $link = DBConnect();
    $id = mysqli_real_escape_string($link, $_GET['id'] ?? '');
    $status = (int) ($_GET['status'] ?? 1);

    if ($id) {
        $query = "UPDATE Clientes SET ativo = $status WHERE id_cliente = '$id'";
        DBExecute($link, $query);
    }
    DBClose($link);

    header("Location: clientes.php");
    exit();
}

// ACTION GET/POST: Download Backup BD
if ((isset($_GET['action']) && $_GET['action'] === 'download_backup') || (isset($_POST['action']) && $_POST['action'] === 'download_backup')) {
    $file = null;
    $filename = null;
    $contentType = 'application/octet-stream';

    if (file_exists('../backup_bd.zip')) {
        $file = '../backup_bd.zip';
        $filename = 'backup_bd_' . date('Y-m-d_H-i-s') . '.zip';
        $contentType = 'application/zip';
    } elseif (file_exists('../backup_bd.sql.gz')) {
        $file = '../backup_bd.sql.gz';
        $filename = 'backup_bd_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $contentType = 'application/x-gzip';
    } elseif (file_exists('../backup_bd.sql')) {
        $file = '../backup_bd.sql';
        $filename = 'backup_bd_' . date('Y-m-d_H-i-s') . '.sql';
        $contentType = 'application/sql';
    } elseif (file_exists('../estrutura.sql') && file_exists('../dados.sql')) {
        $file = '../estrutura.sql';
        $filename = 'backup_bd_' . date('Y-m-d_H-i-s') . '.sql';
        $contentType = 'application/sql';
    }

    if ($file && file_exists($file)) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    } else {
        header('Content-Type: text/plain');
        echo "Arquivo de backup nao encontrado.";
        exit();
    }
}

header('Content-Type: application/json'); // Sempre retorna JSON

$link = DBConnect(); // Abre a conexão UMA VEZ para toda a requisição AJAX

// Verifica se a conexão falhou
if (!$link) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
    exit(); // Sai do script se não houver conexão
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

    switch ($action) {
        // ACTIONS: ContaDev Integration
        case 'contadev_login':
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $res = ContaDevHelper::login($link, $email, $password);
            $response = array_merge($response, $res);
            break;

        case 'contadev_status':
            $res = ContaDevHelper::getAccountStatus($link);
            $response['success'] = true;
            $response['data'] = $res;
            break;

        case 'contadev_disconnect':
            $res = ContaDevHelper::disconnect($link);
            $response = array_merge($response, $res);
            break;

        case 'contadev_import_fatura':
            $idFatura = $_POST['id_fatura'] ?? null;
            $force = !empty($_POST['force']);
            if (!$idFatura) {
                $response['message'] = "ID da fatura não fornecido.";
                break;
            }
            $res = ContaDevHelper::importInvoice($link, $idFatura, $force);
            $response = array_merge($response, $res);
            break;

        case 'contadev_check_fatura':
            $idFatura = $_POST['id_fatura'] ?? $_GET['id_fatura'] ?? null;
            if (!$idFatura) {
                $response['message'] = "ID da fatura não fornecido.";
                break;
            }
            $statusAcc = ContaDevHelper::getAccountStatus($link);
            if (!$statusAcc['active']) {
                $response['success'] = true;
                $response['data'] = ['already_imported' => false, 'active' => false];
                break;
            }
            $config = ContaDevHelper::getConfig($link);
            $token = EncryptionHelper::decrypt($config['contadev_token'] ?? '');
            $cnpjId = $config['contadev_cnpj_id'] ?? '';
            $res = ContaDevHelper::checkInvoiceAlreadyImported($link, $idFatura, $token, $cnpjId);
            $response['success'] = true;
            $response['data'] = $res;
            break;

        // NOVA ACTION: Registrar Vacina
        case 'register_vaccine':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_pet = $_POST['id_pet'] ?? '';
            $id_vacina = $_POST['id_vacina'] ?? '';
            $data_aplicacao = $_POST['data_aplicacao'] ?? date('Y-m-d');
            $data_proxima = $_POST['data_proxima'] ?? NULL;
            $lote = $_POST['lote'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';

            if (empty($id_pet) || empty($id_vacina) || empty($data_aplicacao)) {
                $response['message'] = "Pet, Vacina e Data de Aplicação são obrigatórios.";
            } else {
                $id_pet = (int) $id_pet;
                $id_vacina = (int) $id_vacina;
                $data_aplicacao = mysqli_real_escape_string($link, $data_aplicacao);
                $data_proxima_val = $data_proxima ? "'" . mysqli_real_escape_string($link, $data_proxima) . "'" : "NULL";
                $lote = mysqli_real_escape_string($link, $lote);
                $observacoes = mysqli_real_escape_string($link, $observacoes);

                $query = "INSERT INTO CarteiraVacinas (id_pet, id_vacina, data_aplicacao, data_vencimento, lote, observacao) 
                          VALUES ($id_pet, $id_vacina, '$data_aplicacao', $data_proxima_val, '$lote', '$observacoes')";

                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Vacina registrada com sucesso!";
                } else {
                    $response['message'] = "Erro ao registrar vacina: " . mysqli_error($link);
                }
            }
            break;

        case 'edit_vaccine':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_carteira = $_POST['id_carteira'] ?? '';
            $id_vacina = $_POST['id_vacina'] ?? '';
            $data_aplicacao = $_POST['data_aplicacao'] ?? '';
            $data_proxima = $_POST['data_proxima'] ?? NULL;
            $lote = $_POST['lote'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';

            if (empty($id_carteira) || empty($id_vacina) || empty($data_aplicacao)) {
                $response['message'] = "ID da aplicação, Vacina e Data de Aplicação são obrigatórios.";
            } else {
                $id_carteira = (int) $id_carteira;
                $id_vacina = (int) $id_vacina;
                $data_aplicacao = mysqli_real_escape_string($link, $data_aplicacao);
                $data_proxima_val = $data_proxima ? "'" . mysqli_real_escape_string($link, $data_proxima) . "'" : "NULL";
                $lote = mysqli_real_escape_string($link, $lote);
                $observacoes = mysqli_real_escape_string($link, $observacoes);

                $query = "UPDATE CarteiraVacinas SET 
                            id_vacina = $id_vacina, 
                            data_aplicacao = '$data_aplicacao', 
                            data_vencimento = $data_proxima_val, 
                            lote = '$lote', 
                            observacao = '$observacoes' 
                          WHERE id_carteira = $id_carteira";

                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Vacina atualizada com sucesso!";
                } else {
                    $response['message'] = "Erro ao atualizar vacina: " . mysqli_error($link);
                }
            }
            break;

        case 'delete_vaccine':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_carteira = $_POST['id_carteira'] ?? '';

            if (empty($id_carteira)) {
                $response['message'] = "ID da aplicação é obrigatório.";
            } else {
                $id_carteira = (int) $id_carteira;
                $query = "DELETE FROM CarteiraVacinas WHERE id_carteira = $id_carteira";

                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Vacina removida com sucesso!";
                } else {
                    $response['message'] = "Erro ao remover vacina: " . mysqli_error($link);
                }
            }
            break;

        // ACTIONS: Módulo de Internação Veterinária
        case 'save_internacao':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_internacao = $_POST['id_internacao'] ?? '';
            $id_pet = $_POST['id_pet'] ?? '';
            $id_vet = $_POST['id_vet'] ?? NULL;
            $data_internacao = $_POST['data_internacao'] ?? date('Y-m-d H:i:s');
            $data_alta = !empty($_POST['data_alta']) ? $_POST['data_alta'] : NULL;
            $suspeita_clinica = $_POST['suspeita_clinica'] ?? '';
            $status = $_POST['status'] ?? 'internado';
            $observacoes = $_POST['observacoes'] ?? '';

            if (empty($id_pet) || empty($data_internacao)) {
                $response['message'] = "Pet e Data da Internação são obrigatórios.";
            } else {
                $id_pet = (int) $id_pet;
                $id_vet_val = !empty($id_vet) ? (int)$id_vet : "NULL";
                $data_internacao_val = mysqli_real_escape_string($link, $data_internacao);
                $data_alta_val = $data_alta ? "'" . mysqli_real_escape_string($link, $data_alta) . "'" : "NULL";
                $suspeita_val = mysqli_real_escape_string($link, $suspeita_clinica);
                $status_val = mysqli_real_escape_string($link, $status);
                $obs_val = mysqli_real_escape_string($link, $observacoes);

                if (!empty($id_internacao)) {
                    $id_int = (int) $id_internacao;
                    $query = "UPDATE Internacoes SET 
                                id_vet = $id_vet_val, 
                                data_internacao = '$data_internacao_val', 
                                data_alta = $data_alta_val, 
                                suspeita_clinica = '$suspeita_val', 
                                status = '$status_val', 
                                observacoes = '$obs_val' 
                              WHERE id_internacao = $id_int";
                    if (DBExecute($link, $query)) {
                        $response['success'] = true;
                        $response['message'] = "Internação atualizada com sucesso!";
                        $response['id_internacao'] = $id_int;
                    } else {
                        $response['message'] = "Erro ao atualizar internação: " . mysqli_error($link);
                    }
                } else {
                    $query = "INSERT INTO Internacoes (id_pet, id_vet, data_internacao, data_alta, suspeita_clinica, status, observacoes) 
                              VALUES ($id_pet, $id_vet_val, '$data_internacao_val', $data_alta_val, '$suspeita_val', '$status_val', '$obs_val')";
                    if (DBExecute($link, $query)) {
                        $new_id = mysqli_insert_id($link);
                        // Auto-criar Dia 1 se não houver
                        $data_hoje = date('Y-m-d', strtotime($data_internacao));
                        DBExecute($link, "INSERT INTO InternacaoDias (id_internacao, data_dia) VALUES ($new_id, '$data_hoje')");
                        
                        $response['success'] = true;
                        $response['message'] = "Internação cadastrada com sucesso!";
                        $response['id_internacao'] = $new_id;
                    } else {
                        $response['message'] = "Erro ao cadastrar internação: " . mysqli_error($link);
                    }
                }
            }
            break;

        case 'delete_internacao':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_internacao = $_POST['id_internacao'] ?? '';
            if (empty($id_internacao)) {
                $response['message'] = "ID da internação é obrigatório.";
            } else {
                $id_int = (int) $id_internacao;
                if (DBExecute($link, "DELETE FROM Internacoes WHERE id_internacao = $id_int")) {
                    $response['success'] = true;
                    $response['message'] = "Internação removida com sucesso!";
                } else {
                    $response['message'] = "Erro ao remover internação: " . mysqli_error($link);
                }
            }
            break;

        case 'save_internacao_dia':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_dia = $_POST['id_dia'] ?? '';
            $id_internacao = $_POST['id_internacao'] ?? '';
            $data_dia = $_POST['data_dia'] ?? date('Y-m-d');
            $soro = $_POST['soro'] ?? '';
            $volume = $_POST['volume'] ?? '';
            $frequencia = $_POST['frequencia'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';

            if (empty($id_internacao) || empty($data_dia)) {
                $response['message'] = "Internação e Data do Dia são obrigatórios.";
            } else {
                $id_int = (int) $id_internacao;
                $data_dia_val = mysqli_real_escape_string($link, $data_dia);
                $soro_val = mysqli_real_escape_string($link, $soro);
                $volume_val = mysqli_real_escape_string($link, $volume);
                $freq_val = mysqli_real_escape_string($link, $frequencia);
                $obs_val = mysqli_real_escape_string($link, $observacoes);

                if (!empty($id_dia)) {
                    $id_d = (int) $id_dia;
                    $query = "UPDATE InternacaoDias SET 
                                data_dia = '$data_dia_val', 
                                soro = '$soro_val', 
                                volume = '$volume_val', 
                                frequencia = '$freq_val', 
                                observacoes = '$obs_val' 
                              WHERE id_dia = $id_d";
                    if (DBExecute($link, $query)) {
                        $response['success'] = true;
                        $response['message'] = "Ficha do dia atualizada!";
                        $response['id_dia'] = $id_d;
                    } else {
                        $response['message'] = "Erro ao atualizar dia: " . mysqli_error($link);
                    }
                } else {
                    $query = "INSERT INTO InternacaoDias (id_internacao, data_dia, soro, volume, frequencia, observacoes) 
                              VALUES ($id_int, '$data_dia_val', '$soro_val', '$volume_val', '$freq_val', '$obs_val')";
                    if (DBExecute($link, $query)) {
                        $response['success'] = true;
                        $response['message'] = "Dia de internação adicionado!";
                        $response['id_dia'] = mysqli_insert_id($link);
                    } else {
                        $response['message'] = "Erro ao adicionar dia: " . mysqli_error($link);
                    }
                }
            }
            break;

        case 'delete_internacao_dia':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_dia = $_POST['id_dia'] ?? '';
            if (empty($id_dia)) {
                $response['message'] = "ID do dia é obrigatório.";
            } else {
                $id_d = (int) $id_dia;
                if (DBExecute($link, "DELETE FROM InternacaoDias WHERE id_dia = $id_d")) {
                    $response['success'] = true;
                    $response['message'] = "Dia removido com sucesso!";
                } else {
                    $response['message'] = "Erro ao remover dia: " . mysqli_error($link);
                }
            }
            break;

        case 'save_internacao_medicacao':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_medicacao = $_POST['id_medicacao'] ?? '';
            $id_dia = $_POST['id_dia'] ?? '';
            $medicacao = trim($_POST['medicacao'] ?? '');
            $dose = trim($_POST['dose'] ?? '');
            $via = trim($_POST['via'] ?? '');
            $horarios_json = $_POST['horarios'] ?? '[]';

            if (empty($id_dia) || empty($medicacao)) {
                $response['message'] = "Dia da Ficha e Nome da Medicação são obrigatórios.";
            } else {
                $id_d = (int) $id_dia;
                $med_val = mysqli_real_escape_string($link, $medicacao);
                $dose_val = mysqli_real_escape_string($link, $dose);
                $via_val = mysqli_real_escape_string($link, $via);

                if (is_array($horarios_json)) {
                    $horarios_json = json_encode($horarios_json);
                }
                $horarios_val = mysqli_real_escape_string($link, $horarios_json);

                if (!empty($id_medicacao)) {
                    $id_m = (int) $id_medicacao;
                    $query = "UPDATE InternacaoMedicacoes SET 
                                medicacao = '$med_val', 
                                dose = '$dose_val', 
                                via = '$via_val', 
                                horarios = '$horarios_val' 
                              WHERE id_medicacao = $id_m";
                    if (DBExecute($link, $query)) {
                        $response['success'] = true;
                        $response['message'] = "Medicação atualizada!";
                        $response['id_medicacao'] = $id_m;
                    } else {
                        $response['message'] = "Erro ao atualizar medicação: " . mysqli_error($link);
                    }
                } else {
                    $query = "INSERT INTO InternacaoMedicacoes (id_dia, medicacao, dose, via, horarios) 
                              VALUES ($id_d, '$med_val', '$dose_val', '$via_val', '$horarios_val')";
                    if (DBExecute($link, $query)) {
                        $response['success'] = true;
                        $response['message'] = "Medicação adicionada!";
                        $response['id_medicacao'] = mysqli_insert_id($link);
                    } else {
                        $response['message'] = "Erro ao adicionar medicação: " . mysqli_error($link);
                    }
                }
            }
            break;

        case 'delete_internacao_medicacao':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_medicacao = $_POST['id_medicacao'] ?? '';
            if (empty($id_medicacao)) {
                $response['message'] = "ID da medicação é obrigatório.";
            } else {
                $id_m = (int) $id_medicacao;
                if (DBExecute($link, "DELETE FROM InternacaoMedicacoes WHERE id_medicacao = $id_m")) {
                    $response['success'] = true;
                    $response['message'] = "Medicação removida!";
                } else {
                    $response['message'] = "Erro ao remover medicação: " . mysqli_error($link);
                }
            }
            break;

        case 'get_internacao_details':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Modo Veterinário desativado.";
                break;
            }
            $id_internacao = (int)($_POST['id_internacao'] ?? 0);
            $q_int = "SELECT i.*, v.nome as vet_nome, v.crmv as vet_crmv 
                      FROM Internacoes i 
                      LEFT JOIN Veterinarios v ON i.id_vet = v.id_vet 
                      WHERE i.id_internacao = $id_internacao";
            $r_int = DBExecute($link, $q_int);
            if ($r_int && mysqli_num_rows($r_int) > 0) {
                $int_data = mysqli_fetch_assoc($r_int);
                $dias_data = [];
                $q_d = "SELECT * FROM InternacaoDias WHERE id_internacao = $id_internacao ORDER BY data_dia ASC";
                $r_d = DBExecute($link, $q_d);
                if ($r_d) {
                    while ($d = mysqli_fetch_assoc($r_d)) {
                        $id_d = $d['id_dia'];
                        $meds = [];
                        $q_m = "SELECT * FROM InternacaoMedicacoes WHERE id_dia = $id_d ORDER BY ordem ASC, id_medicacao ASC";
                        $r_m = DBExecute($link, $q_m);
                        if ($r_m) {
                            while ($m = mysqli_fetch_assoc($r_m)) {
                                $meds[] = $m;
                            }
                        }
                        $d['medicacoes'] = $meds;
                        $dias_data[] = $d;
                    }
                }
                $response['success'] = true;
                $response['internacao'] = $int_data;
                $response['dias'] = $dias_data;
            } else {
                $response['message'] = "Internação não encontrada.";
            }
            break;

        // NOVA ACTION: Configuração Fiscal
        case 'save_config_fiscal':
            // Recebe dados do POST
            $id_config = $_POST['id_config'] ?? '';
            $razao_social = $_POST['razao_social'] ?? '';
            $nome_fantasia = $_POST['nome_fantasia'] ?? '';
            $cnpj = $_POST['cnpj'] ?? '';
            $inscricao_municipal = $_POST['inscricao_municipal'] ?? '';
            $codigo_municipio = $_POST['codigo_municipio'] ?? '';
            $regime_tributario = $_POST['regime_tributario'] ?? 'simples';
            $optante_simples = isset($_POST['optante_simples']) ? 1 : 0;
            $modulo_fiscal_ativo = isset($_POST['modulo_fiscal_ativo']) ? 1 : 0;
            $permitir_cadastro_sem_cpf = isset($_POST['permitir_cadastro_sem_cpf']) ? 1 : 0;
            $ambiente_padrao = $_POST['ambiente_padrao'] ?? 'homologacao';
            $serie_rps = $_POST['serie_rps'] ?? '8';
            $ultimo_rps_homologacao = $_POST['ultimo_rps_homologacao'] ?? 0;
            $ultimo_rps_producao = $_POST['ultimo_rps_producao'] ?? 0;
            $caminho_certificado = $_POST['caminho_certificado'] ?? '';
            $senha_certificado = $_POST['senha_certificado'] ?? '';
            $landing_page_theme = $_POST['landing_page_theme'] ?? 'default';
            $landing_page_path = $_POST['landing_page_path'] ?? '';

            if (empty($razao_social) || empty($cnpj) || ($modulo_fiscal_ativo == 1 && empty($inscricao_municipal))) {
                $response['message'] = "Razão Social, CNPJ e Inscrição Municipal são obrigatórios para emissão fiscal.";
            } else {
                $razao_social = mysqli_real_escape_string($link, $razao_social);
                $nome_fantasia = mysqli_real_escape_string($link, $nome_fantasia);
                $cnpj = mysqli_real_escape_string($link, $cnpj);
                $inscricao_municipal = mysqli_real_escape_string($link, $_POST['inscricao_municipal'] ?? '');
                $inscricao_estadual = mysqli_real_escape_string($link, $_POST['inscricao_estadual'] ?? '');
                $codigo_municipio = mysqli_real_escape_string($link, $_POST['codigo_municipio'] ?? '');
                $regime_tributario = mysqli_real_escape_string($link, $regime_tributario);
                $ambiente_padrao = mysqli_real_escape_string($link, $ambiente_padrao);
                $serie_rps = mysqli_real_escape_string($link, $serie_rps);
                $ultimo_rps_homologacao = (int) $ultimo_rps_homologacao;
                $ultimo_rps_producao = (int) $ultimo_rps_producao;
                $telefone = mysqli_real_escape_string($link, $_POST['telefone'] ?? '');
                $endereco = mysqli_real_escape_string($link, $_POST['endereco'] ?? '');
                $numero = mysqli_real_escape_string($link, $_POST['numero'] ?? '');
                $complemento = mysqli_real_escape_string($link, $_POST['complemento'] ?? '');
                $bairro = mysqli_real_escape_string($link, $_POST['bairro'] ?? '');
                $cep = mysqli_real_escape_string($link, $_POST['cep'] ?? '');
                $uf = mysqli_real_escape_string($link, $_POST['uf'] ?? '');
                $landing_page_theme = mysqli_real_escape_string($link, $landing_page_theme);
                $landing_page_path = mysqli_real_escape_string($link, $landing_page_path);

                // --- LOGO UPLOAD ---
                $logo_url_update = "";
                if (isset($_FILES['arquivo_logo']) && $_FILES['arquivo_logo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['arquivo_logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $uploadLogoDir = __DIR__ . '/assets/';
                        if (!is_dir($uploadLogoDir))
                            mkdir($uploadLogoDir, 0755, true);

                        $newLogoName = 'logo_empresa.png'; // Sempre salvar como PNG ou manter original? 
                        // User requested overwrite. Let's fix name.
                        // Convert to preferred ext or keep orig? Using orig ext allow overwrite but user said "overwrite same file".
                        // If user uploads png then jpg, we might have 2 files if we use extension.
                        // Let's force a name 'logo_empresa' and extension.
                        // Better: 'logo_empresa_TIMESTAMP.ext' and store in DB? 
                        // User said: "sobrescrever o mesmo arquivo ... pra não ficar sobrando nada".
                        // So 'assets/logo_empresa.png' (if we convert) or just 'assets/logo_empresa.ext'.
                        // Let's use 'assets/logo_empresa.png' and just support that or strictly use uploaded ext?
                        // Simple: 'logo_empresa.png' (assuming usually png/jpg). 

                        // Actually, let's stick to 'logo_empresa.png' for simplicity in print templates.
                        // If uploaded is JPG, it's fine to rename to .png? No, bad for mime.
                        // Let's try to detect valid image. 
                        // "logo_empresa" + ext. But then we have multiple.
                        // Cleanup: remove any glob('assets/logo_empresa.*')?

                        // Let's try simple overwrite: 'assets/logo_empresa.' . $ext
                        // And clean others? 
                        // User: "sobrescrever o mesmo arquivo" -> implies one file.
                        // I will clean old variations.
                        $baseLogo = $uploadLogoDir . 'logo_empresa';
                        array_map('unlink', glob($baseLogo . '.*'));

                        $destLogo = $baseLogo . '.' . $ext;
                        if (move_uploaded_file($_FILES['arquivo_logo']['tmp_name'], $destLogo)) {
                            $logo_url_update = 'assets/logo_empresa.' . $ext;
                        }
                    }
                }

                // --- FILE UPLOAD (PFX - BASE64) ---
                $pfxContentForValidation = null;
                $certificado_pfx_base64 = '';
                $pfx_sql_part = "";

                if (isset($_FILES['arquivo_pfx']) && $_FILES['arquivo_pfx']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['arquivo_pfx']['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) !== 'pfx') {
                        $response['message'] = "Erro: Apenas arquivos .pfx são permitidos.";
                        break;
                    }

                    $pfxContent = file_get_contents($_FILES['arquivo_pfx']['tmp_name']);
                    if ($pfxContent === false || strlen($pfxContent) === 0) {
                        $response['message'] = "Erro: O arquivo .pfx enviado está vazio.";
                        break;
                    }

                    $certificado_pfx_base64 = base64_encode($pfxContent);
                    $pfxContentForValidation = $pfxContent;
                    $pfxBase64Safe = mysqli_real_escape_string($link, $certificado_pfx_base64);
                    $pfx_sql_part = ", certificado_pfx_base64 = '$pfxBase64Safe'";
                } elseif (!empty($id_config) && !empty($senha_certificado)) {
                    // Se não houve upload novo mas foi enviada nova senha, busca o PFX existente do banco para validar
                    $qPfx = "SELECT certificado_pfx_base64, caminho_certificado FROM ConfiguracoesEmissor WHERE id_config = '$id_config' LIMIT 1";
                    $rPfx = DBExecute($link, $qPfx);
                    if ($rPfx && $rowPfx = mysqli_fetch_assoc($rPfx)) {
                        if (!empty($rowPfx['certificado_pfx_base64'])) {
                            $pfxContentForValidation = base64_decode($rowPfx['certificado_pfx_base64']);
                        } elseif (!empty($rowPfx['caminho_certificado'])) {
                            $pfxPath = dirname(__DIR__) . '/' . $rowPfx['caminho_certificado'];
                            if (file_exists($pfxPath)) {
                                $pfxContentForValidation = file_get_contents($pfxPath);
                            }
                        }
                    }
                }

                // --- VALIDATE CERTIFICATE ---
                if ($pfxContentForValidation && !empty($senha_certificado)) {
                    $certs = [];
                    if (!openssl_pkcs12_read($pfxContentForValidation, $certs, $senha_certificado)) {
                        $response['message'] = "Erro de validação do Certificado: Senha incorreta ou arquivo inválido.";
                        break;
                    }
                }

                $caminho_certificado = mysqli_real_escape_string($link, $caminho_certificado);

                // --- INTER CERTIFICATES UPLOAD (BASE64) ---
                $api_inter_cert_base64 = '';
                $api_inter_key_base64 = '';
                $api_inter_ca_base64 = '';

                // Handle CRT upload
                if (isset($_FILES['arquivo_inter_crt']) && $_FILES['arquivo_inter_crt']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['arquivo_inter_crt']['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) !== 'crt') {
                        $response['message'] = "Erro: Arquivo do certificado Inter deve ser .crt";
                        break;
                    }
                    $content = file_get_contents($_FILES['arquivo_inter_crt']['tmp_name']);
                    if ($content === false || strlen($content) === 0) {
                        $response['message'] = "Erro: Arquivo .crt do Inter está vazio.";
                        break;
                    }
                    $api_inter_cert_base64 = base64_encode($content);
                }

                // Handle KEY upload
                if (isset($_FILES['arquivo_inter_key']) && $_FILES['arquivo_inter_key']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['arquivo_inter_key']['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) !== 'key') {
                        $response['message'] = "Erro: Arquivo de chave Inter deve ser .key";
                        break;
                    }
                    $content = file_get_contents($_FILES['arquivo_inter_key']['tmp_name']);
                    if ($content === false || strlen($content) === 0) {
                        $response['message'] = "Erro: Arquivo .key do Inter está vazio.";
                        break;
                    }
                    $api_inter_key_base64 = base64_encode($content);
                }

                // Handle CA upload
                if (isset($_FILES['arquivo_inter_ca']) && $_FILES['arquivo_inter_ca']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['arquivo_inter_ca']['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) !== 'crt') {
                        $response['message'] = "Erro: Arquivo CA do Inter deve ser .crt";
                        break;
                    }
                    $content = file_get_contents($_FILES['arquivo_inter_ca']['tmp_name']);
                    if ($content === false || strlen($content) === 0) {
                        $response['message'] = "Erro: Arquivo CA do Inter está vazio.";
                        break;
                    }
                    $api_inter_ca_base64 = base64_encode($content);
                }

                // Prepared DB updates for Inter Files
                $inter_files_sql_part = "";
                if (!empty($api_inter_cert_base64)) {
                    $api_inter_cert_base64_safe = mysqli_real_escape_string($link, $api_inter_cert_base64);
                    $inter_files_sql_part .= ", api_inter_cert_base64 = '$api_inter_cert_base64_safe'";
                }
                if (!empty($api_inter_key_base64)) {
                    $api_inter_key_base64_safe = mysqli_real_escape_string($link, $api_inter_key_base64);
                    $inter_files_sql_part .= ", api_inter_key_base64 = '$api_inter_key_base64_safe'";
                }
                if (!empty($api_inter_ca_base64)) {
                    $api_inter_ca_base64_safe = mysqli_real_escape_string($link, $api_inter_ca_base64);
                    $inter_files_sql_part .= ", api_inter_ca_base64 = '$api_inter_ca_base64_safe'";
                }

                // Novos Campos de Integração
                $api_inter_client_id = mysqli_real_escape_string($link, $_POST['api_inter_client_id'] ?? '');
                $api_inter_chave_pix = mysqli_real_escape_string($link, $_POST['api_inter_chave_pix'] ?? '');
                $api_inter_conta_corrente = mysqli_real_escape_string($link, $_POST['api_inter_conta_corrente'] ?? '');

                $api_oracle_user = mysqli_real_escape_string($link, $_POST['api_oracle_user'] ?? '');
                $api_oracle_url = mysqli_real_escape_string($link, $_POST['api_oracle_url'] ?? '');
                $api_oracle_password_raw = $_POST['api_oracle_password'] ?? '';

                $google_oauth_client_id = mysqli_real_escape_string($link, $_POST['google_oauth_client_id'] ?? '');
                $google_oauth_client_secret_raw = $_POST['google_oauth_client_secret'] ?? '';

                $email_fatura_template_id = !empty($_POST['email_fatura_template_id']) ? (int)$_POST['email_fatura_template_id'] : 'NULL';
                $email_fatura_template_id_val = ($email_fatura_template_id === 'NULL') ? 'NULL' : $email_fatura_template_id;

                // Encrypt Passwords if provided
                $senha_sql_part = "";
                if (!empty($senha_certificado)) {
                    $enc = EncryptionHelper::encrypt($senha_certificado);
                    $senha_sql_part = ", senha_certificado = '$enc'";
                }

                $inter_secret_sql_part = "";
                if (!empty($api_inter_client_secret_raw)) {
                    $enc = EncryptionHelper::encrypt($api_inter_client_secret_raw);
                    $inter_secret_sql_part = ", api_inter_client_secret = '$enc'";
                }

                $oracle_pass_sql_part = "";
                if (!empty($api_oracle_password_raw)) {
                    $enc = EncryptionHelper::encrypt($api_oracle_password_raw);
                    $oracle_pass_sql_part = ", api_oracle_password = '$enc'";
                }

                $google_secret_sql_part = "";
                if (!empty($google_oauth_client_secret_raw)) {
                    $enc = EncryptionHelper::encrypt($google_oauth_client_secret_raw);
                    $google_secret_sql_part = ", google_oauth_client_secret = '$enc'";
                }

                // Handle Google JSON Upload
                $google_json_sql_part = "";
                if (isset($_FILES['arquivo_google_json']) && $_FILES['arquivo_google_json']['error'] === UPLOAD_ERR_OK) {
                    $jsonContent = file_get_contents($_FILES['arquivo_google_json']['tmp_name']);
                    if (json_decode($jsonContent)) {
                        $enc = EncryptionHelper::encrypt($jsonContent);
                        $google_json_sql_part = ", google_service_account_json = '$enc'";
                    } else {
                        $response['message'] = "Erro: Arquivo Google Service Account inválido (não é um JSON).";
                        break;
                    }
                }

                // Logo URL SQL (Update only if present)
                $logo_sql_part = "";
                if (!empty($logo_url_update)) {
                    $logo_url_update_safe = mysqli_real_escape_string($link, $logo_url_update);
                    $logo_sql_part = ", logo_url = '$logo_url_update_safe'";
                }

                $banho_checkin_foto_ativo = isset($_POST['banho_checkin_foto_ativo']) ? 1 : 0;
                $banho_capacidade_simultanea = isset($_POST['banho_capacidade_simultanea']) ? max(1, (int)$_POST['banho_capacidade_simultanea']) : 2;

                if (!empty($id_config)) {
                    // Update
                    $query = "UPDATE ConfiguracoesEmissor SET 
                                razao_social='$razao_social', nome_fantasia='$nome_fantasia', cnpj='$cnpj', 
                                inscricao_municipal='$inscricao_municipal', inscricao_estadual='$inscricao_estadual', codigo_municipio='$codigo_municipio',
                                regime_tributario='$regime_tributario', optante_simples='$optante_simples',
                                modulo_fiscal_ativo='$modulo_fiscal_ativo',
                                permitir_cadastro_sem_cpf='$permitir_cadastro_sem_cpf',
                                ambiente_padrao='$ambiente_padrao', serie_rps='$serie_rps', 
                                ultimo_rps_homologacao='$ultimo_rps_homologacao', ultimo_rps_producao='$ultimo_rps_producao',
                                caminho_certificado='$caminho_certificado',
                                endereco='$endereco', numero='$numero', complemento='$complemento',
                                bairro='$bairro', cep='$cep', uf='$uf', telefone='$telefone',
                                landing_page_theme='$landing_page_theme',
                                landing_page_path='$landing_page_path',
                                banho_checkin_foto_ativo='$banho_checkin_foto_ativo',
                                banho_capacidade_simultanea='$banho_capacidade_simultanea',
                                api_inter_client_id='$api_inter_client_id', 
                                api_inter_chave_pix='$api_inter_chave_pix',
                                api_inter_conta_corrente='$api_inter_conta_corrente',
                                api_oracle_user='$api_oracle_user',
                                api_oracle_url='$api_oracle_url',
                                google_oauth_client_id='$google_oauth_client_id',
                                email_fatura_template_id=$email_fatura_template_id_val
                                $senha_sql_part
                                $pfx_sql_part
                                $inter_secret_sql_part
                                $oracle_pass_sql_part
                                $google_json_sql_part
                                $google_secret_sql_part
                                $inter_files_sql_part
                                $logo_sql_part
                              WHERE id_config='$id_config'";
                } else {
                    // Insert
                    $senha_val = empty($senha_certificado) ? "NULL" : "'" . EncryptionHelper::encrypt($senha_certificado) . "'";
                    $pfx_base64_val = !empty($certificado_pfx_base64) ? "'" . mysqli_real_escape_string($link, $certificado_pfx_base64) . "'" : "NULL";
                    $inter_secret_val = empty($api_inter_client_secret_raw) ? "NULL" : "'" . EncryptionHelper::encrypt($api_inter_client_secret_raw) . "'";
                    $oracle_pass_val = empty($api_oracle_password_raw) ? "NULL" : "'" . EncryptionHelper::encrypt($api_oracle_password_raw) . "'";
                    $google_oauth_secret_val = empty($google_oauth_client_secret_raw) ? "NULL" : "'" . EncryptionHelper::encrypt($google_oauth_client_secret_raw) . "'";

                    // JSON Google
                    $google_json_val = "NULL";
                    if (isset($_FILES['arquivo_google_json']) && $_FILES['arquivo_google_json']['error'] === UPLOAD_ERR_OK) {
                        $jsonContent = file_get_contents($_FILES['arquivo_google_json']['tmp_name']);
                        if (json_decode($jsonContent)) {
                            $google_json_val = "'" . EncryptionHelper::encrypt($jsonContent) . "'";
                        }
                    }

                    // For Insert, we use the variables directly (checked empty above)
                    $api_inter_cert_val = !empty($api_inter_cert_base64) ? "'" . mysqli_real_escape_string($link, $api_inter_cert_base64) . "'" : "NULL";
                    $api_inter_key_val = !empty($api_inter_key_base64) ? "'" . mysqli_real_escape_string($link, $api_inter_key_base64) . "'" : "NULL";
                    $api_inter_ca_val = !empty($api_inter_ca_base64) ? "'" . mysqli_real_escape_string($link, $api_inter_ca_base64) . "'" : "NULL";
                    $logo_val = !empty($logo_url_update) ? "'" . mysqli_real_escape_string($link, $logo_url_update) . "'" : "NULL";

                    $query = "INSERT INTO ConfiguracoesEmissor 
                              (razao_social, nome_fantasia, cnpj, inscricao_municipal, inscricao_estadual, codigo_municipio, 
                               regime_tributario, optante_simples, modulo_fiscal_ativo, permitir_cadastro_sem_cpf, ambiente_padrao, serie_rps, 
                               ultimo_rps_homologacao, ultimo_rps_producao, 
                               caminho_certificado, certificado_pfx_base64, senha_certificado,
                               endereco, numero, complemento, bairro, cep, uf, telefone, logo_url,
                               landing_page_theme, landing_page_path, banho_checkin_foto_ativo, banho_capacidade_simultanea,
                               api_inter_client_id, api_inter_client_secret, 
                               api_inter_chave_pix, api_inter_conta_corrente,
                               api_inter_cert_base64, api_inter_key_base64, api_inter_ca_base64,
                               api_oracle_user, api_oracle_password, api_oracle_url, google_service_account_json,
                               google_oauth_client_id, google_oauth_client_secret, email_fatura_template_id)
                              VALUES 
                              ('$razao_social', '$nome_fantasia', '$cnpj', '$inscricao_municipal', '$inscricao_estadual', '$codigo_municipio',
                               '$regime_tributario', '$optante_simples', '$modulo_fiscal_ativo', '$permitir_cadastro_sem_cpf', '$ambiente_padrao', '$serie_rps', 
                               '$ultimo_rps_homologacao', '$ultimo_rps_producao', 
                               '$caminho_certificado', $pfx_base64_val, $senha_val,
                               '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$uf', '$telefone', $logo_val,
                               '$landing_page_theme', '$landing_page_path', '$banho_checkin_foto_ativo', '$banho_capacidade_simultanea',
                               '$api_inter_client_id', $inter_secret_val,
                               '$api_inter_chave_pix', '$api_inter_conta_corrente',
                               $api_inter_cert_val, $api_inter_key_val, $api_inter_ca_val,
                               '$api_oracle_user', $oracle_pass_val, '$api_oracle_url', $google_json_val,
                               '$google_oauth_client_id', $google_oauth_secret_val, $email_fatura_template_id_val)";
                }

                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Configuração Fiscal salva com sucesso!";
                } else {
                    $response['message'] = "Erro ao salvar config: " . mysqli_error($link);
                }
            }
            break;

        case 'get_config_fiscal':
            // Pega a primeira configuração encontrada
            $query = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
            $result = DBExecute($link, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                // --- VALIDATE CERTIFICATE ON LOAD ---
                $certStatus = ['valid' => false, 'message' => 'Nenhum certificado configurado.', 'days_remaining' => 0];

                $hasPfx = !empty($row['certificado_pfx_base64']) || !empty($row['caminho_certificado']);
                if ($hasPfx && !empty($row['senha_certificado'])) {
                    $pfxContent = null;
                    if (!empty($row['certificado_pfx_base64'])) {
                        $pfxContent = base64_decode($row['certificado_pfx_base64']);
                    } elseif (!empty($row['caminho_certificado'])) {
                        $pfxPath = dirname(__DIR__) . '/' . $row['caminho_certificado'];
                        if (file_exists($pfxPath)) {
                            $pfxContent = file_get_contents($pfxPath);
                        }
                    }

                    if ($pfxContent) {
                        try {
                            $certPass = EncryptionHelper::decrypt($row['senha_certificado']);

                            if ($certPass) {
                                $certs = [];
                                if (openssl_pkcs12_read($pfxContent, $certs, $certPass)) {
                                    $data = openssl_x509_parse($certs['cert']);
                                    $validTo = $data['validTo_time_t'];
                                    $daysRemaining = floor(($validTo - time()) / (60 * 60 * 24));

                                    $certStatus['valid'] = ($daysRemaining >= 0);
                                    $certStatus['days_remaining'] = $daysRemaining;
                                    $certStatus['message'] = $certStatus['valid'] ? "Válido (Vence em " . date('d/m/Y', $validTo) . ")" : "Expirado em " . date('d/m/Y', $validTo);
                                    $certStatus['expiration_date'] = date('d/m/Y', $validTo);
                                } else {
                                    $certStatus['message'] = "Senha incorreta ou arquivo corrompido.";
                                }
                            } else {
                                $certStatus['message'] = "Erro ao descriptografar senha.";
                            }
                        } catch (Exception $e) {
                            $certStatus['message'] = "Erro interno na validação.";
                        }
                    } else {
                        $certStatus['message'] = "Arquivo não encontrado.";
                    }
                }
                $row['cert_validation'] = $certStatus;
                $row['has_certificado_pfx'] = $hasPfx;

                // Flags de presença dos certificados Inter
                $row['has_inter_crt'] = !empty($row['api_inter_cert_base64']) || (!empty($row['api_inter_cert_path']) && file_exists(dirname(__DIR__) . '/' . $row['api_inter_cert_path']));
                $row['has_inter_key'] = !empty($row['api_inter_key_base64']) || (!empty($row['api_inter_key_path']) && file_exists(dirname(__DIR__) . '/' . $row['api_inter_key_path']));
                $row['has_inter_ca'] = !empty($row['api_inter_ca_base64']) || (!empty($row['api_inter_ca_path']) && file_exists(dirname(__DIR__) . '/' . $row['api_inter_ca_path']));

                // Não retorna senhas nem chaves privadas/base64 por segurança
                unset($row['senha_certificado']);
                unset($row['certificado_pfx_base64']);
                unset($row['api_inter_client_secret']);
                unset($row['api_inter_cert_base64']);
                unset($row['api_inter_key_base64']);
                unset($row['api_inter_ca_base64']);
                unset($row['api_oracle_password']);
                unset($row['google_oauth_client_secret']);
                unset($row['google_oauth_refresh_token']);

                $row['google_json_configured'] = !empty($row['google_service_account_json']);
                $row['google_email'] = '';

                if ($row['google_json_configured']) {
                    try {
                        $jsonDecrypted = EncryptionHelper::decrypt($row['google_service_account_json']);
                        $data = json_decode($jsonDecrypted, true);
                        if ($data && isset($data['client_email'])) {
                            $row['google_email'] = $data['client_email'];
                        }
                    } catch (Exception $e) {
                    }
                }

                // Busca templates de documentos para vincular ao e-mail de fatura
                $templates = [];
                $qTemplates = "SELECT id_modelo, titulo FROM ModelosDocumentos WHERE ativo = 1 ORDER BY titulo ASC";
                $resTemplates = DBExecute($link, $qTemplates);
                if ($resTemplates) {
                    while ($t = mysqli_fetch_assoc($resTemplates)) {
                        $templates[] = $t;
                    }
                }
                $row['templates_list'] = $templates;

                unset($row['google_service_account_json']);

                $response['success'] = true;
                $response['data'] = $row;
            } else {
                $response['success'] = true;
                $response['data'] = null; // Vazio, formulário em branco
            }
            break;

        case 'fazer_backup':
            // 1. Configurações
            $pathEstrutura = '../estrutura.sql';
            $pathDados = '../dados.sql';
            $pathZip = '../backup_bd.zip';
            $pathGz = '../backup_bd.sql.gz';
            $pathSql = '../backup_bd.sql';

            // 2. Apaga arquivos anteriores se existirem (opcional, mas bom pra garantir limpeza)
            if (file_exists($pathEstrutura))
                unlink($pathEstrutura);
            if (file_exists($pathDados))
                unlink($pathDados);
            if (file_exists($pathZip))
                unlink($pathZip);
            if (file_exists($pathGz))
                unlink($pathGz);
            if (file_exists($pathSql))
                unlink($pathSql);

            $estruturaContent = "";
            $dadosContent = "";

            // 3. Obtém todas as tabelas
            $tables = [];
            $result = DBExecute($link, "SHOW TABLES");
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                // --- ESTRUTURA ---
                $resultCreate = DBExecute($link, "SHOW CREATE TABLE $table");
                $rowCreate = mysqli_fetch_row($resultCreate);
                $estruturaContent .= "\n\n" . $rowCreate[1] . ";\n\n";

                // --- DADOS ---
                $resultSelect = DBExecute($link, "SELECT * FROM $table");
                $numFields = mysqli_num_fields($resultSelect);

                $dadosContent .= "\n\n-- Dumping data for table `$table` --\n";

                while ($row = mysqli_fetch_row($resultSelect)) {
                    $dadosContent .= "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $numFields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $dadosContent .= '"' . $row[$j] . '"';
                        } else {
                            $dadosContent .= '""';
                        }
                        if ($j < ($numFields - 1)) {
                            $dadosContent .= ',';
                        }
                    }
                    $dadosContent .= ");\n";
                }
            }

            // 4. Salva os arquivos no servidor
            $headerTime = "-- Backup do Banco de Dados - Gerado em " . date('Y-m-d H:i:s') . "\n";
            $resEstrutura = file_put_contents($pathEstrutura, $headerTime . $estruturaContent);
            $resDados = file_put_contents($pathDados, $headerTime . $dadosContent);

            // 5. Compactação do arquivo para download (ZIP / GZ / SQL)
            if ($resEstrutura !== false && $resDados !== false) {
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($pathZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                        $zip->addFile($pathEstrutura, 'estrutura.sql');
                        $zip->addFile($pathDados, 'dados.sql');
                        $zip->close();
                    }
                } else {
                    $fullContent = $headerTime . "\n-- ESTRUTURA DO BANCO DE DADOS --\n" . $estruturaContent . "\n\n-- DADOS DO BANCO DE DADOS --\n" . $dadosContent;
                    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
                    if (function_exists('gzencode') && strpos($acceptEncoding, 'gzip') !== false) {
                        $gzData = gzencode($fullContent, 9);
                        if ($gzData !== false) {
                            file_put_contents($pathGz, $gzData);
                        } else {
                            file_put_contents($pathSql, $fullContent);
                        }
                    } else {
                        file_put_contents($pathSql, $fullContent);
                    }
                }

                $response['success'] = true;
                $response['message'] = "Backup realizado com sucesso! Baixando arquivo...";
                $response['download_url'] = 'app.php?action=download_backup';
            } else {
                $response['message'] = "Erro ao gravar arquivos de backup no disco. Verifique permissões.";
            }
            break;

        case 'criar_cliente':
            $nome = $_POST['nome'] ?? '';
            $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
            $telefone = $_POST['telefone'] ?? '';

            // Verificação de Configuração
            $configQuery = "SELECT permitir_cadastro_sem_cpf FROM ConfiguracoesEmissor LIMIT 1";
            $configResult = DBExecute($link, $configQuery);
            $permitirSemCpf = 0;
            if ($configResult && $rowConfig = mysqli_fetch_assoc($configResult)) {
                $permitirSemCpf = $rowConfig['permitir_cadastro_sem_cpf'];
            }
            $email = $_POST['email'] ?? '';

            if (empty($nome) || ($permitirSemCpf == 0 && empty($cpf_cnpj))) {
                $response['message'] = "Nome e CPF/CNPJ são obrigatórios.";
            } else {
                $nome = mysqli_real_escape_string($link, $nome);
                $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
                $cpf_cnpj_val = empty($cpf_cnpj) ? "NULL" : "'$cpf_cnpj'";
                $telefone = mysqli_real_escape_string($link, $telefone);
                $email_val = empty($email) ? "NULL" : "'" . mysqli_real_escape_string($link, $email) . "'";
                $data_nascimento = $_POST['data_nascimento'] ?? '';
                $data_nascimento_val = empty($data_nascimento) ? "NULL" : "'" . mysqli_real_escape_string($link, $data_nascimento) . "'";

                // Address - Cliente
                $endereco = mysqli_real_escape_string($link, $_POST['endereco'] ?? '');
                $numero = mysqli_real_escape_string($link, $_POST['numero'] ?? '');
                $complemento = mysqli_real_escape_string($link, $_POST['complemento'] ?? '');
                $bairro = mysqli_real_escape_string($link, $_POST['bairro'] ?? '');
                $cep = mysqli_real_escape_string($link, $_POST['cep'] ?? '');
                $uf = mysqli_real_escape_string($link, $_POST['uf'] ?? '');
                $codigo_municipio = mysqli_real_escape_string($link, $_POST['codigo_municipio'] ?? '');
                $inscricao_municipal = mysqli_real_escape_string($link, $_POST['inscricao_municipal'] ?? '');
                $inscricao_estadual = mysqli_real_escape_string($link, $_POST['inscricao_estadual'] ?? '');

                $query = "INSERT INTO Clientes (nome, cpf_cnpj, telefone, email, endereco, numero, complemento, bairro, cep, uf, codigo_municipio, inscricao_municipal, inscricao_estadual, data_nascimento) 
                          VALUES ('$nome', $cpf_cnpj_val, '$telefone', $email_val, '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$uf', '$codigo_municipio', '$inscricao_municipal', '$inscricao_estadual', $data_nascimento_val)";

                $result = mysqli_query($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Cliente cadastrado com sucesso!";
                    $response['id_cliente'] = mysqli_insert_id($link);
                } else {
                    $mysql_error_code = mysqli_errno($link);

                    if ($mysql_error_code == 1062) {
                        if (strpos(mysqli_error($link), 'cpf_cnpj') !== false) {
                            $response['message'] = "Erro: CPF/CNPJ já cadastrado para outro cliente.";
                        } elseif (strpos(mysqli_error($link), 'email') !== false) {
                            $response['message'] = "Erro: E-mail já cadastrado para outro cliente.";
                        } else {
                            $response['message'] = "Erro de duplicidade: " . mysqli_error($link);
                        }
                    } else {
                        $response['message'] = "Erro ao cadastrar cliente: " . mysqli_error($link);
                    }
                }
            }
            break;

        case 'get_cliente_details': // Para carregar dados completos do cliente para edição
            $id_cliente = $_POST['id_cliente'] ?? '';
            if (empty($id_cliente)) {
                $response['message'] = "ID do cliente é obrigatório.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                // Select * to get address fields
                $query = "SELECT * FROM Clientes WHERE id_cliente = '$id_cliente'";
                $result = DBExecute($link, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $response['success'] = true;
                    $response['data'] = mysqli_fetch_assoc($result);
                } else {
                    $response['message'] = "Cliente não encontrado.";
                }
            }
            break;

        case 'editar_cliente': // Para editar dados do cliente
            $id_cliente = $_POST['id_cliente'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';

            // Verificação de Configuração
            $configQuery = "SELECT permitir_cadastro_sem_cpf FROM ConfiguracoesEmissor LIMIT 1";
            $configResult = DBExecute($link, $configQuery);
            $permitirSemCpf = 0;
            if ($configResult && $rowConfig = mysqli_fetch_assoc($configResult)) {
                $permitirSemCpf = $rowConfig['permitir_cadastro_sem_cpf'];
            }
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';

            if (empty($id_cliente) || empty($nome) || ($permitirSemCpf == 0 && empty($cpf_cnpj))) {
                $response['message'] = "ID do cliente, Nome e CPF/CNPJ são obrigatórios para edição.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $nome = mysqli_real_escape_string($link, $nome);
                $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
                $cpf_cnpj_val = empty($cpf_cnpj) ? "NULL" : "'$cpf_cnpj'";
                $telefone = mysqli_real_escape_string($link, $telefone);
                $email_val = empty($email) ? "NULL" : "'" . mysqli_real_escape_string($link, $email) . "'";
                $data_nascimento = $_POST['data_nascimento'] ?? '';
                $data_nascimento_val = empty($data_nascimento) ? "NULL" : "'" . mysqli_real_escape_string($link, $data_nascimento) . "'";

                // Address - Cliente Edit
                $endereco = mysqli_real_escape_string($link, $_POST['endereco'] ?? '');
                $numero = mysqli_real_escape_string($link, $_POST['numero'] ?? '');
                $complemento = mysqli_real_escape_string($link, $_POST['complemento'] ?? '');
                $bairro = mysqli_real_escape_string($link, $_POST['bairro'] ?? '');
                $cep = mysqli_real_escape_string($link, $_POST['cep'] ?? '');
                $uf = mysqli_real_escape_string($link, $_POST['uf'] ?? '');
                $codigo_municipio = mysqli_real_escape_string($link, $_POST['codigo_municipio'] ?? '');
                $inscricao_municipal = mysqli_real_escape_string($link, $_POST['inscricao_municipal'] ?? '');
                $inscricao_estadual = mysqli_real_escape_string($link, $_POST['inscricao_estadual'] ?? '');

                $query = "UPDATE Clientes SET nome='$nome', cpf_cnpj=$cpf_cnpj_val, telefone='$telefone', email=$email_val,
                          endereco='$endereco', numero='$numero', complemento='$complemento', bairro='$bairro', cep='$cep', uf='$uf', codigo_municipio='$codigo_municipio',
                          inscricao_municipal='$inscricao_municipal', inscricao_estadual='$inscricao_estadual', data_nascimento=$data_nascimento_val
                          WHERE id_cliente='$id_cliente'";
                $result = DBExecute($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Cliente atualizado com sucesso!";
                } else {
                    $mysql_error_code = mysqli_errno($link);
                    if ($mysql_error_code == 1062) {
                        if (strpos(mysqli_error($link), 'cpf_cnpj') !== false) {
                            $response['message'] = "Erro: CPF/CNPJ já cadastrado para outro cliente.";
                        } elseif (strpos(mysqli_error($link), 'email') !== false) {
                            $response['message'] = "Erro: E-mail já cadastrado para outro cliente.";
                        } else {
                            $response['message'] = "Erro de duplicidade ao atualizar cliente: " . mysqli_error($link);
                        }
                    } else {
                        $response['message'] = "Erro ao atualizar cliente: " . mysqli_error($link);
                    }
                }
            }
            break;

        case 'criar_servico':
            $nome_servico = $_POST['nome_servico'] ?? '';
            $valor_sugerido = $_POST['valor_sugerido'] ?? '';

            if (empty($nome_servico) || !is_numeric($valor_sugerido) || $valor_sugerido <= 0) {
                $response['message'] = "Nome do serviço e valor sugerido válidos são obrigatórios.";
            } else {
                $nome_servico = mysqli_real_escape_string($link, $nome_servico);
                $valor_sugerido = mysqli_real_escape_string($link, $valor_sugerido);

                // Novos Campos Fiscais e Módulos
                $item_lista_servico = mysqli_real_escape_string($link, $_POST['item_lista_servico'] ?? '');
                $codigo_cnae = mysqli_real_escape_string($link, $_POST['codigo_cnae'] ?? '');
                $codigo_tributacao_municipio = mysqli_real_escape_string($link, $_POST['codigo_tributacao_municipio'] ?? '');
                $codigo_nbs = mysqli_real_escape_string($link, $_POST['codigo_nbs'] ?? '');
                $aliquota_iss = mysqli_real_escape_string($link, $_POST['aliquota_iss'] ?? '0.00');
                $iss_retido = isset($_POST['iss_retido']) ? 1 : 0;
                $descricao_nfse_padrao = mysqli_real_escape_string($link, $_POST['descricao_nfse_padrao'] ?? '');
                $descricao_fiscal = mysqli_real_escape_string($link, $_POST['descricao_fiscal'] ?? '');
                
                // Módulos e Banho e Tosa
                $disponivel_clinica = (AppHelper::isVetMode() && isset($_POST['disponivel_clinica'])) ? 1 : 0;
                $disponivel_banho = (AppHelper::isVetMode() && isset($_POST['disponivel_banho'])) ? 1 : 0;
                $duracao_minutos = (int) ($_POST['duracao_minutos'] ?? 30);
                if ($duracao_minutos <= 0) $duracao_minutos = 30;
                $defaultIconServ = AppHelper::isVetMode() ? 'pets' : 'build';
                $icone_servico = mysqli_real_escape_string($link, !empty($_POST['icone_servico']) ? $_POST['icone_servico'] : $defaultIconServ);
                $imagem_url = mysqli_real_escape_string($link, $_POST['imagem_url'] ?? '');

                $query = "INSERT INTO Servicos 
                          (nome_servico, valor_sugerido, item_lista_servico, codigo_cnae, codigo_tributacao_municipio, codigo_nbs, aliquota_iss, iss_retido, descricao_nfse_padrao, descricao_fiscal, disponivel_clinica, disponivel_banho, duracao_minutos, icone_servico, imagem_url) 
                          VALUES 
                          ('$nome_servico', '$valor_sugerido', '$item_lista_servico', '$codigo_cnae', '$codigo_tributacao_municipio', '$codigo_nbs', '$aliquota_iss', '$iss_retido', '$descricao_nfse_padrao', '$descricao_fiscal', '$disponivel_clinica', '$disponivel_banho', '$duracao_minutos', '$icone_servico', '$imagem_url')";

                $result = DBExecute($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Serviço cadastrado com sucesso!";
                    $response['id_servico'] = mysqli_insert_id($link);
                } else {
                    $response['message'] = "Erro ao cadastrar serviço: " . mysqli_error($link);
                }
            }
            break;

        case 'get_servico_details': // Para carregar dados completos do serviço para edição
            $id_servico = $_POST['id_servico'] ?? '';
            if (empty($id_servico)) {
                $response['message'] = "ID do serviço é obrigatório.";
            } else {
                $id_servico = mysqli_real_escape_string($link, $id_servico);
                $query = "SELECT * FROM Servicos WHERE id_servico = '$id_servico'";
                $result = DBExecute($link, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $response['success'] = true;
                    $response['data'] = mysqli_fetch_assoc($result);
                } else {
                    $response['message'] = "Serviço não encontrado.";
                }
            }
            break;

        case 'editar_servico': // Para editar dados do serviço
            $id_servico = $_POST['id_servico'] ?? '';
            $nome_servico = $_POST['nome_servico'] ?? '';
            $valor_sugerido = $_POST['valor_sugerido'] ?? '';

            if (empty($id_servico) || empty($nome_servico) || !is_numeric($valor_sugerido) || $valor_sugerido <= 0) {
                $response['message'] = "Dados inválidos para edição do serviço.";
            } else {
                $id_servico = mysqli_real_escape_string($link, $id_servico);
                $nome_servico = mysqli_real_escape_string($link, $nome_servico);
                $valor_sugerido = mysqli_real_escape_string($link, $valor_sugerido);

                // Novos Campos Fiscais
                $item_lista_servico = mysqli_real_escape_string($link, $_POST['item_lista_servico'] ?? '');
                $codigo_cnae = mysqli_real_escape_string($link, $_POST['codigo_cnae'] ?? '');
                $codigo_tributacao_municipio = mysqli_real_escape_string($link, $_POST['codigo_tributacao_municipio'] ?? '');
                $codigo_nbs = mysqli_real_escape_string($link, $_POST['codigo_nbs'] ?? '');
                $aliquota_iss = mysqli_real_escape_string($link, $_POST['aliquota_iss'] ?? '0.00');
                $iss_retido = isset($_POST['iss_retido']) ? 1 : 0;
                $descricao_nfse_padrao = mysqli_real_escape_string($link, $_POST['descricao_nfse_padrao'] ?? '');
                $descricao_fiscal = mysqli_real_escape_string($link, $_POST['descricao_fiscal'] ?? '');

                // Módulos e Banho e Tosa
                $disponivel_clinica = (AppHelper::isVetMode() && isset($_POST['disponivel_clinica'])) ? 1 : 0;
                $disponivel_banho = (AppHelper::isVetMode() && isset($_POST['disponivel_banho'])) ? 1 : 0;
                $duracao_minutos = (int) ($_POST['duracao_minutos'] ?? 30);
                if ($duracao_minutos <= 0) $duracao_minutos = 30;
                $defaultIconServ = AppHelper::isVetMode() ? 'pets' : 'build';
                $icone_servico = mysqli_real_escape_string($link, !empty($_POST['icone_servico']) ? $_POST['icone_servico'] : $defaultIconServ);
                $imagem_url = mysqli_real_escape_string($link, $_POST['imagem_url'] ?? '');

                $query = "UPDATE Servicos SET 
                            nome_servico = '$nome_servico', 
                            valor_sugerido = '$valor_sugerido',
                            item_lista_servico = '$item_lista_servico',
                            codigo_cnae = '$codigo_cnae',
                            codigo_tributacao_municipio = '$codigo_tributacao_municipio',
                            codigo_nbs = '$codigo_nbs',
                            aliquota_iss = '$aliquota_iss',
                            iss_retido = '$iss_retido',
                            descricao_nfse_padrao = '$descricao_nfse_padrao',
                            descricao_fiscal = '$descricao_fiscal',
                            disponivel_clinica = '$disponivel_clinica',
                            disponivel_banho = '$disponivel_banho',
                            duracao_minutos = '$duracao_minutos',
                            icone_servico = '$icone_servico',
                            imagem_url = '$imagem_url'
                          WHERE id_servico = '$id_servico'";
                $result = DBExecute($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Serviço atualizado com sucesso!";
                } else {
                    $response['message'] = "Erro ao atualizar serviço: " . mysqli_error($link);
                }
            }
            break;

        case 'criar_fatura':
            $id_cliente = $_POST['id_cliente'] ?? '';
            $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
            $data_vencimento = $_POST['data_vencimento'] ?? '';

            if (empty($id_cliente) || empty($data_vencimento)) {
                $response['message'] = "Selecione um cliente e uma data de vencimento.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $data_emissao = mysqli_real_escape_string($link, $data_emissao);
                $data_vencimento = mysqli_real_escape_string($link, $data_vencimento);

                // Novos Campos
                $desconto_valor = mysqli_real_escape_string($link, $_POST['desconto_valor'] ?? '0.00');
                $desconto_tipo = mysqli_real_escape_string($link, $_POST['desconto_tipo'] ?? 'percentual');
                $permitir_pagamento_parcial = isset($_POST['permitir_pagamento_parcial']) ? '1' : '0';

                $query = "INSERT INTO Faturas (id_cliente, data_emissao, data_vencimento, status, desconto_valor, desconto_tipo, permitir_pagamento_parcial) 
                          VALUES ('$id_cliente', '$data_emissao', '$data_vencimento', 'Em Aberto', '$desconto_valor', '$desconto_tipo', '$permitir_pagamento_parcial')";

                $result = DBExecute($link, $query);

                if ($result) {
                    $new_fatura_id = mysqli_insert_id($link);

                    $response['success'] = true;
                    $response['message'] = "Fatura criada com sucesso!";
                    $response['id_fatura'] = $new_fatura_id;
                } else {
                    $response['message'] = "Erro ao criar fatura: " . mysqli_error($link);
                }
            }
            break;

        case 'adicionar_item_fatura':
            $id_fatura = $_POST['id_fatura'] ?? '';
            $id_servico = $_POST['id_servico'] ?? ''; // Agora vem do campo hidden do autocomplete
            $quantidade = $_POST['quantidade'] ?? '';
            $valor_unitario = $_POST['valor_unitario'] ?? '';
            $tag = $_POST['tag'] ?? NULL; // Novo campo tag

            if (empty($id_fatura) || empty($id_servico) || !is_numeric($quantidade) || $quantidade <= 0 || !is_numeric($valor_unitario) || $valor_unitario <= 0) {
                $response['message'] = "Preencha todos os campos do item corretamente.";
            } else {
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);
                $id_servico = mysqli_real_escape_string($link, $id_servico);
                $quantidade = mysqli_real_escape_string($link, $quantidade);
                $valor_unitario = mysqli_real_escape_string($link, $valor_unitario);
                $tag = $tag ? mysqli_real_escape_string($link, $tag) : NULL; // Escapa a tag ou mantém NULL

                $query_insert_item = "INSERT INTO ItensFatura (id_fatura, id_servico, quantidade, valor_unitario, tag) 
                                      VALUES ('$id_fatura', '$id_servico', '$quantidade', '$valor_unitario', " . ($tag ? "'$tag'" : "NULL") . ")";

                $result_insert_item = DBExecute($link, $query_insert_item);

                if ($result_insert_item) {
                    // Atualiza o valor total da fatura (recalculando)
                    $query_update_total = "UPDATE Faturas
                                           SET valor_total_fatura = (SELECT COALESCE(SUM(quantidade * valor_unitario), 0) FROM ItensFatura WHERE id_fatura = '$id_fatura')
                                           WHERE id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_total);

                    $response['success'] = true;
                    $response['message'] = "Item adicionado com sucesso e fatura atualizada!";
                } else {
                    $response['message'] = "Erro ao adicionar item à fatura: " . mysqli_error($link);
                }
            }
            break;

        case 'editar_item_fatura':
            $id_item_fatura = $_POST['id_item_fatura'] ?? '';
            $id_fatura = $_POST['id_fatura'] ?? '';
            $quantidade = $_POST['quantidade'] ?? '';
            $valor_unitario = $_POST['valor_unitario'] ?? '';
            $tag = $_POST['tag'] ?? NULL; // Novo campo tag

            if (empty($id_item_fatura) || empty($id_fatura) || !is_numeric($quantidade) || $quantidade <= 0 || !is_numeric($valor_unitario) || $valor_unitario <= 0) {
                $response['message'] = "Dados inválidos para edição do item.";
            } else {
                $id_item_fatura = mysqli_real_escape_string($link, $id_item_fatura);
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);
                $quantidade = mysqli_real_escape_string($link, $quantidade);
                $valor_unitario = mysqli_real_escape_string($link, $valor_unitario);
                $tag = $tag ? mysqli_real_escape_string($link, $tag) : NULL;

                $query_update_item = "UPDATE ItensFatura
                                      SET quantidade = '$quantidade', valor_unitario = '$valor_unitario', tag = " . ($tag ? "'$tag'" : "NULL") . "
                                      WHERE id_item_fatura = '$id_item_fatura'";

                $result_update_item = DBExecute($link, $query_update_item);

                if ($result_update_item) {
                    // Recalcula o valor total da fatura após a edição do item
                    $query_update_total = "UPDATE Faturas
                                           SET valor_total_fatura = (SELECT COALESCE(SUM(quantidade * valor_unitario), 0) FROM ItensFatura WHERE id_fatura = '$id_fatura')
                                           WHERE id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_total);

                    $response['success'] = true;
                    $response['message'] = "Item da fatura atualizado com sucesso!";
                } else {
                    $response['message'] = "Erro ao atualizar item da fatura: " . mysqli_error($link);
                }
            }
            break;

        case 'remover_item_fatura':
            $id_item_fatura = $_POST['id_item_fatura'] ?? '';
            $id_fatura = $_POST['id_fatura'] ?? '';

            if (empty($id_item_fatura) || empty($id_fatura)) {
                $response['message'] = "ID do item da fatura ou ID da fatura inválido para remoção.";
            } else {
                $id_item_fatura = mysqli_real_escape_string($link, $id_item_fatura);
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);

                // OBTÉM id_recorrencia DO ITEM ANTES DE DELETAR
                $query_get_recorrencia_id = "SELECT id_recorrencia FROM ItensFatura WHERE id_item_fatura = '$id_item_fatura'";
                $result_get_recorrencia_id = DBExecute($link, $query_get_recorrencia_id);
                $recorrencia_id_do_item = null;
                if ($result_get_recorrencia_id && mysqli_num_rows($result_get_recorrencia_id) > 0) {
                    $row = mysqli_fetch_assoc($result_get_recorrencia_id);
                    $recorrencia_id_do_item = $row['id_recorrencia'];
                }

                $query_delete_item = "DELETE FROM ItensFatura WHERE id_item_fatura = '$id_item_fatura'";

                $result_delete_item = DBExecute($link, $query_delete_item);

                if ($result_delete_item) {
                    // Recalcula o valor total da fatura após a remoção do item
                    $query_update_total = "UPDATE Faturas
                                           SET valor_total_fatura = (SELECT COALESCE(SUM(quantidade * valor_unitario), 0)
                                                                    FROM ItensFatura
                                                                    WHERE id_fatura = '$id_fatura')
                                           WHERE id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_total);

                    // SE O ITEM REMOVIDO ERA DE UMA RECORRÊNCIA, ZERA ultima_fatura_gerada_mes_ano
                    if ($recorrencia_id_do_item) {
                        $query_reset_recorrencia_flag = "UPDATE Recorrencias SET ultima_fatura_gerada_mes_ano = NULL WHERE id_recorrencia = '$recorrencia_id_do_item'";
                        DBExecute($link, $query_reset_recorrencia_flag);
                    }


                    $response['success'] = true;
                    $response['message'] = "Item da fatura removido com sucesso!";
                } else {
                    $response['message'] = "Erro ao remover item da fatura: " . mysqli_error($link);
                }
            }
            break;

        case 'buscar_clientes':
            $termo = $_POST['termo'] ?? '';
            $clientes = [];
            $termo = mysqli_real_escape_string($link, $termo);

            $query = "SELECT id_cliente, nome, cpf_cnpj FROM Clientes WHERE nome LIKE '%$termo%' OR cpf_cnpj LIKE '%$termo%' LIMIT 10";
            $result = DBExecute($link, $query);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $clientes[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $clientes;
            } else {
                $response['message'] = "Erro ao buscar clientes: " . mysqli_error($link);
            }
            break;

        case 'buscar_servicos': // Para autocomplete de serviços
            $termo = $_POST['termo'] ?? '';
            $servicos = [];
            $termo = mysqli_real_escape_string($link, $termo);

            $query = "SELECT id_servico, nome_servico, valor_sugerido FROM Servicos WHERE nome_servico LIKE '%$termo%' LIMIT 10";
            $result = DBExecute($link, $query);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $servicos[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $servicos;
            } else {
                $response['message'] = "Erro ao buscar serviços: " . mysqli_error($link);
            }
            break;

        case 'buscar_faturas_cliente':
            $id_cliente = $_POST['id_cliente'] ?? '';
            $faturas = [];

            if (empty($id_cliente)) {
                $response['message'] = "ID do cliente é obrigatório para buscar faturas.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);

                // Inclui o total pago para cada fatura
                $query = "SELECT F.id_fatura, F.data_emissao, F.data_vencimento, F.valor_total_fatura, F.status,
                                 COALESCE(SUM(P.valor_pago), 0) AS total_pago_fatura
                          FROM Faturas F
                          LEFT JOIN Pagamentos P ON F.id_fatura = P.id_fatura AND P.status_pagamento = 'Confirmado'
                          WHERE F.id_cliente = '$id_cliente'
                          GROUP BY F.id_fatura
                          ORDER BY F.data_emissao DESC";
                $result = DBExecute($link, $query);

                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $faturas[] = $row;
                    }
                    $response['success'] = true;
                    $response['data'] = $faturas;
                } else {
                    $response['message'] = "Erro ao buscar faturas do cliente: " . mysqli_error($link);
                }
            }
            break;

        case 'get_servicos': // Este é o get_servicos original, que retorna TODOS os serviços
            $servicos = [];
            $query = "SELECT id_servico, nome_servico, valor_sugerido FROM Servicos ORDER BY nome_servico ASC";
            $result = DBExecute($link, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $servicos[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $servicos;
            } else {
                $response['message'] = "Erro ao carregar serviços: " . mysqli_error($link);
            }
            break;

        case 'get_fatura_detalhes':
            $id_fatura = $_POST['id_fatura'] ?? '';
            // Novo: verifica se a requisição vem da área do cliente
            $is_client_view = isset($_POST['visao_cliente']) && $_POST['visao_cliente'] === 'true';

            $fatura_detalhes = null;
            $itens_fatura = [];
            $pagamentos_fatura = [];

            if (empty($id_fatura)) {
                $response['message'] = "ID da fatura é obrigatório.";
            } else {
                $id_fatura_escaped = mysqli_real_escape_string($link, $id_fatura);

                // A query da fatura principal ADICIONADA com os novos campos
                $query_fatura = "SELECT F.id_fatura, C.nome AS nome_cliente, F.data_emissao, F.data_vencimento, F.valor_total_fatura, F.status, F.desconto_valor, F.desconto_tipo, F.permitir_pagamento_parcial
									 FROM Faturas F
									 JOIN Clientes C ON F.id_cliente = C.id_cliente
									 WHERE F.id_fatura = '$id_fatura_escaped'";
                $result_fatura = DBExecute($link, $query_fatura);

                if ($result_fatura && mysqli_num_rows($result_fatura) > 0) {
                    $fatura_detalhes = mysqli_fetch_assoc($result_fatura);

                    // A query dos itens não muda
                    $query_itens = "SELECT IFI.id_item_fatura, S.nome_servico, IFI.quantidade, IFI.valor_unitario, IFI.tag, IFI.id_recorrencia
										FROM ItensFatura IFI
										JOIN Servicos S ON IFI.id_servico = S.id_servico
										WHERE IFI.id_fatura = '$id_fatura_escaped'";
                    $result_itens = DBExecute($link, $query_itens);
                    if ($result_itens) {
                        while ($row_item = mysqli_fetch_assoc($result_itens)) {
                            $itens_fatura[] = $row_item;
                        }
                    }

                    // ** LÓGICA CONDICIONAL PARA PAGAMENTOS **
                    // Monta a query base
                    $query_pagamentos = "SELECT id_pagamento, valor_pago, data_pagamento, status_pagamento, observacao
											 FROM Pagamentos
											 WHERE id_fatura = '$id_fatura_escaped'";

                    // Se for a visão do cliente, adiciona o filtro de status
                    if ($is_client_view) {
                        $query_pagamentos .= " AND status_pagamento = 'Confirmado'";
                    }

                    $query_pagamentos .= " ORDER BY data_pagamento DESC";

                    $result_pagamentos = DBExecute($link, $query_pagamentos);
                    if ($result_pagamentos) {
                        while ($row_pagamento = mysqli_fetch_assoc($result_pagamentos)) {
                            $pagamentos_fatura[] = $row_pagamento;
                        }
                    }

                    $response['success'] = true;
                    $response['data'] = [
                        'fatura' => $fatura_detalhes,
                        'itens' => $itens_fatura,
                        'pagamentos' => $pagamentos_fatura
                    ];
                } else {
                    $response['message'] = "Fatura não encontrada: " . mysqli_error($link);
                }
            }
            break;


        case 'validar_cpf_cnpj_DELETED':
            $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';

            if (empty($cpf_cnpj)) {
                $response['message'] = "CPF/CNPJ é obrigatório.";
            } else {
                $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
                $query = "SELECT id_cliente, nome FROM Clientes WHERE cpf_cnpj = '$cpf_cnpj'";
                $result = DBExecute($link, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    $cliente_info = mysqli_fetch_assoc($result);
                    $response['success'] = true;
                    $response['message'] = "Login bem-sucedido!";
                    $response['data'] = [
                        'id_cliente' => $cliente_info['id_cliente'],
                        'nome_cliente' => $cliente_info['nome']
                    ];
                } else {
                    $response['message'] = "CPF/CNPJ não encontrado ou inválido.";
                }
            }
            break;

        case 'vincular_recorrencia': // Adicionar uma nova recorrência
            $id_cliente = $_POST['id_cliente'] ?? '';
            $id_servico = $_POST['id_servico'] ?? '';
            $quantidade = $_POST['quantidade'] ?? '';
            $valor_sugerido_recorrencia = $_POST['valor_sugerido_recorrencia'] ?? '';
            $tipo_periodo = $_POST['tipo_periodo'] ?? '';
            $intervalo = $_POST['intervalo'] ?? '';
            $data_inicio_cobranca = $_POST['data_inicio_cobranca'] ?? '';
            $data_fim_cobranca = $_POST['data_fim_cobranca'] ?? NULL;

            if (empty($id_cliente) || empty($id_servico) || !is_numeric($quantidade) || $quantidade <= 0 || !is_numeric($valor_sugerido_recorrencia) || $valor_sugerido_recorrencia <= 0 || empty($tipo_periodo) || !is_numeric($intervalo) || $intervalo <= 0 || empty($data_inicio_cobranca)) {
                $response['message'] = "Preencha todos os campos obrigatórios da recorrência corretamente.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $id_servico = mysqli_real_escape_string($link, $id_servico);
                $quantidade = mysqli_real_escape_string($link, $quantidade);
                $valor_sugerido_recorrencia = mysqli_real_escape_string($link, $valor_sugerido_recorrencia);
                $tipo_periodo = mysqli_real_escape_string($link, $tipo_periodo);
                $intervalo = mysqli_real_escape_string($link, $intervalo);
                $data_inicio_cobranca = mysqli_real_escape_string($link, $data_inicio_cobranca);
                $data_fim_cobranca = $data_fim_cobranca ? "'" . mysqli_real_escape_string($link, $data_fim_cobranca) . "'" : "NULL";

                // Novos Campos Fiscais (Overrides)
                $item_lista_servico = mysqli_real_escape_string($link, $_POST['item_lista_servico'] ?? '');
                $aliquota_iss = !empty($_POST['aliquota_iss']) ? "'" . mysqli_real_escape_string($link, $_POST['aliquota_iss']) . "'" : "NULL";

                // ISS Retido: Checkbox/Select logic. Se vazio (NULL/Default), envia NULL. Se 0 ou 1, envia valor.
                $iss_retido_input = $_POST['iss_retido'] ?? '';
                $iss_retido = ($iss_retido_input === '1' || $iss_retido_input === '0') ? "'$iss_retido_input'" : "NULL";

                $descricao_personalizada = mysqli_real_escape_string($link, $_POST['descricao_personalizada'] ?? '');
                $descricao_fiscal = mysqli_real_escape_string($link, $_POST['descricao_fiscal'] ?? '');

                $dia_vencimento_input = $_POST['dia_vencimento'] ?? '';
                $dia_vencimento = (!empty($dia_vencimento_input) && (int) $dia_vencimento_input >= 1 && (int) $dia_vencimento_input <= 31) ? (int) $dia_vencimento_input : "NULL";

                // V2 Refinements
                $codigo_cnae = mysqli_real_escape_string($link, $_POST['codigo_cnae'] ?? '');
                $codigo_nbs = mysqli_real_escape_string($link, $_POST['codigo_nbs'] ?? '');
                $codigo_tributacao_municipio = mysqli_real_escape_string($link, $_POST['codigo_tributacao_municipio'] ?? '');

                $query = "INSERT INTO Recorrencias (id_cliente, id_servico, quantidade, valor_sugerido_recorrencia, tipo_periodo, intervalo, dia_vencimento, data_inicio_cobranca, data_fim_cobranca, item_lista_servico, aliquota_iss, iss_retido, descricao_personalizada, descricao_fiscal, codigo_cnae, codigo_nbs, codigo_tributacao_municipio)
                          VALUES ('$id_cliente', '$id_servico', '$quantidade', '$valor_sugerido_recorrencia', '$tipo_periodo', '$intervalo', $dia_vencimento, '$data_inicio_cobranca', $data_fim_cobranca, '$item_lista_servico', $aliquota_iss, $iss_retido, '$descricao_personalizada', '$descricao_fiscal', '$codigo_cnae', '$codigo_nbs', '$codigo_tributacao_municipio')";

                $result = DBExecute($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Recorrência vinculada com sucesso!";
                } else {
                    $mysql_error_code = mysqli_errno($link);
                    if ($mysql_error_code == 1062) { // Duplicate entry for UNIQUE constraint
                        $response['message'] = "Erro: Esta regra de recorrência já existe para este cliente e serviço.";
                    } else {
                        $response['message'] = "Erro ao vincular recorrência: " . mysqli_error($link);
                    }
                }
            }
            break;

        case 'get_cliente_recorrencias': // Obter recorrências de um cliente
            $id_cliente = $_POST['id_cliente'] ?? '';
            $recorrencias = [];

            if (empty($id_cliente)) {
                $response['message'] = "ID do cliente é obrigatório.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $query = "SELECT R.*, S.nome_servico FROM Recorrencias R JOIN Servicos S ON R.id_servico = S.id_servico WHERE R.id_cliente = '$id_cliente' ORDER BY R.data_inicio_cobranca DESC";
                $result = DBExecute($link, $query);

                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $recorrencias[] = $row;
                    }
                    $response['success'] = true;
                    $response['data'] = $recorrencias;
                } else {
                    $response['message'] = "Erro ao buscar recorrências: " . mysqli_error($link);
                }
            }
            break;

        case 'remover_recorrencia': // Remover uma recorrência
            $id_recorrencia = $_POST['id_recorrencia'] ?? '';

            if (empty($id_recorrencia)) {
                $response['message'] = "ID da recorrência é obrigatório para remoção.";
            } else {
                $id_recorrencia = mysqli_real_escape_string($link, $id_recorrencia);
                $query = "DELETE FROM Recorrencias WHERE id_recorrencia = '$id_recorrencia'";
                $result = DBExecute($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Recorrência removida com sucesso!";
                } else {
                    $response['message'] = "Erro ao remover recorrência: " . mysqli_error($link);
                }
            }
            break;

        case 'editar_recorrencia':
            $id_recorrencia = $_POST['id_recorrencia'] ?? '';
            $id_cliente = $_POST['id_cliente'] ?? '';
            $id_servico = $_POST['id_servico'] ?? '';
            $quantidade = $_POST['quantidade'] ?? '';
            $valor_sugerido_recorrencia = $_POST['valor_sugerido_recorrencia'] ?? '';
            $tipo_periodo = $_POST['tipo_periodo'] ?? '';
            $intervalo = $_POST['intervalo'] ?? '';
            $data_inicio_cobranca = $_POST['data_inicio_cobranca'] ?? '';
            $data_fim_cobranca = $_POST['data_fim_cobranca'] ?? NULL;

            if (empty($id_recorrencia) || empty($id_cliente) || empty($id_servico) || !is_numeric($quantidade) || $quantidade <= 0 || !is_numeric($valor_sugerido_recorrencia) || $valor_sugerido_recorrencia <= 0 || empty($tipo_periodo) || !is_numeric($intervalo) || $intervalo <= 0 || empty($data_inicio_cobranca)) {
                $response['message'] = "Preencha todos os campos obrigatórios da recorrência corretamente para edição.";
            } else {
                $id_recorrencia = mysqli_real_escape_string($link, $id_recorrencia);
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $id_servico = mysqli_real_escape_string($link, $id_servico);
                $quantidade = mysqli_real_escape_string($link, $quantidade);
                $valor_sugerido_recorrencia = mysqli_real_escape_string($link, $valor_sugerido_recorrencia);
                $tipo_periodo = mysqli_real_escape_string($link, $tipo_periodo);
                $intervalo = mysqli_real_escape_string($link, $intervalo);
                $data_inicio_cobranca = mysqli_real_escape_string($link, $data_inicio_cobranca);
                $data_fim_cobranca = $data_fim_cobranca ? "'" . mysqli_real_escape_string($link, $data_fim_cobranca) . "'" : "NULL";

                $dia_vencimento_input = $_POST['dia_vencimento'] ?? '';
                $dia_vencimento = (!empty($dia_vencimento_input) && (int) $dia_vencimento_input >= 1 && (int) $dia_vencimento_input <= 31) ? (int) $dia_vencimento_input : "NULL";

                // Novos Campos Fiscais (Overrides) - Edição
                $item_lista_servico = mysqli_real_escape_string($link, $_POST['item_lista_servico'] ?? '');
                $aliquota_iss = !empty($_POST['aliquota_iss']) ? "'" . mysqli_real_escape_string($link, $_POST['aliquota_iss']) . "'" : "NULL";
                $iss_retido_input = $_POST['iss_retido'] ?? '';
                $iss_retido = ($iss_retido_input === '1' || $iss_retido_input === '0') ? "'$iss_retido_input'" : "NULL";
                $descricao_personalizada = mysqli_real_escape_string($link, $_POST['descricao_personalizada'] ?? '');
                $descricao_fiscal = mysqli_real_escape_string($link, $_POST['descricao_fiscal'] ?? '');

                // V2 Refinements - Edição
                $codigo_cnae = mysqli_real_escape_string($link, $_POST['codigo_cnae'] ?? '');
                $codigo_nbs = mysqli_real_escape_string($link, $_POST['codigo_nbs'] ?? '');
                $codigo_tributacao_municipio = mysqli_real_escape_string($link, $_POST['codigo_tributacao_municipio'] ?? '');

                $query = "UPDATE Recorrencias 
                          SET id_cliente='$id_cliente', id_servico='$id_servico', quantidade='$quantidade', valor_sugerido_recorrencia='$valor_sugerido_recorrencia', 
                              tipo_periodo='$tipo_periodo', intervalo='$intervalo', dia_vencimento=$dia_vencimento, data_inicio_cobranca='$data_inicio_cobranca', data_fim_cobranca=$data_fim_cobranca,
                              item_lista_servico='$item_lista_servico', aliquota_iss=$aliquota_iss, iss_retido=$iss_retido, descricao_personalizada='$descricao_personalizada', descricao_fiscal='$descricao_fiscal',
                              codigo_cnae='$codigo_cnae', codigo_nbs='$codigo_nbs', codigo_tributacao_municipio='$codigo_tributacao_municipio'
                          WHERE id_recorrencia='$id_recorrencia'";

                $result = DBExecute($link, $query);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Recorrência atualizada com sucesso!";
                } else {
                    $response['message'] = "Erro ao atualizar recorrência: " . mysqli_error($link);
                }
            }
            break;

        case 'incorporar_recorrencias_na_fatura': // Incorporar recorrências em uma fatura
            $id_fatura = $_POST['id_fatura'] ?? '';
            $id_cliente = $_POST['id_cliente'] ?? '';
            $mes_ano_fatura = $_POST['mes_ano_fatura'] ?? ''; // Formato YYYY-MM

            if (empty($id_fatura) || empty($id_cliente) || empty($mes_ano_fatura)) {
                $response['message'] = "Dados insuficientes para incorporar recorrências.";
                break;
            }

            // Validação do formato do mês/ano
            if (!preg_match('/^\d{4}-\d{2}$/', $mes_ano_fatura)) {
                $response['message'] = "Formato de mês/ano inválido. Use YYYY-MM.";
                break;
            }

            // Função para obter o último dia válido do mês
            function getLastValidDayOfMonth($yearMonth)
            {
                $date = new DateTime($yearMonth . '-01');
                return $date->format('t'); // 't' retorna o número de dias no mês
            }

            // Obtém o último dia válido do mês
            $lastValidDay = getLastValidDayOfMonth($mes_ano_fatura);
            $dataInicioMes = $mes_ano_fatura . '-01';
            $dataFimMes = $mes_ano_fatura . '-' . $lastValidDay;

            $id_fatura = mysqli_real_escape_string($link, $id_fatura);
            $id_cliente = mysqli_real_escape_string($link, $id_cliente);
            $dataInicioMes = mysqli_real_escape_string($link, $dataInicioMes);
            $dataFimMes = mysqli_real_escape_string($link, $dataFimMes);

            // 1. Buscar recorrências ativas para o cliente e para o mês/ano da fatura
            $query_recorrencias = "SELECT R.id_recorrencia, R.id_servico, R.quantidade, R.valor_sugerido_recorrencia, S.nome_servico
									   FROM Recorrencias R
									   JOIN Servicos S ON R.id_servico = S.id_servico
									   WHERE R.id_cliente = '$id_cliente'
										 AND R.data_inicio_cobranca <= '$dataFimMes' 
										 AND (R.data_fim_cobranca IS NULL OR R.data_fim_cobranca >= '$dataInicioMes')
										 AND NOT EXISTS (SELECT 1 FROM ItensFatura WHERE id_fatura = '$id_fatura' AND id_recorrencia = R.id_recorrencia)";

            $result_recorrencias = DBExecute($link, $query_recorrencias);
            $itens_incorporados = 0;

            if ($result_recorrencias) {
                while ($rec = mysqli_fetch_assoc($result_recorrencias)) {
                    $servico_id = mysqli_real_escape_string($link, $rec['id_servico']);
                    $quantidade = mysqli_real_escape_string($link, $rec['quantidade']);
                    $valor_unitario = mysqli_real_escape_string($link, $rec['valor_sugerido_recorrencia']);
                    $tag = mysqli_real_escape_string($link, "Mensalidade - " . $rec['nome_servico'] . " (" . $mes_ano_fatura . ")");
                    $id_recorrencia_original = mysqli_real_escape_string($link, $rec['id_recorrencia']);

                    // 2. Inserir o item na fatura
                    $query_insert_item = "INSERT INTO ItensFatura (id_fatura, id_servico, quantidade, valor_unitario, tag, id_recorrencia)
											  VALUES ('$id_fatura', '$servico_id', '$quantidade', '$valor_unitario', '$tag', '$id_recorrencia_original')";

                    $item_inserted = DBExecute($link, $query_insert_item);

                    if ($item_inserted) {
                        $itens_incorporados++;
                        // 3. Atualizar a recorrência com a última fatura gerada
                        $query_update_recorrencia = "UPDATE Recorrencias SET ultima_fatura_gerada_mes_ano = '$mes_ano_fatura' WHERE id_recorrencia = '$id_recorrencia_original'";
                        DBExecute($link, $query_update_recorrencia);
                    } else {
                        error_log("Erro ao incorporar item recorrente (Fatura ID: $id_fatura, Recorrência ID: $id_recorrencia_original): " . mysqli_error($link));
                    }
                }

                // 4. Recalcular o total da fatura após todas as inserções
                if ($itens_incorporados > 0) {
                    $query_update_total = "UPDATE Faturas
											   SET valor_total_fatura = (SELECT COALESCE(SUM(quantidade * valor_unitario), 0) FROM ItensFatura WHERE id_fatura = '$id_fatura')
											   WHERE id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_total);
                }

                $response['success'] = true;
                $response['message'] = "Incorporados $itens_incorporados serviços recorrentes na fatura!";
            } else {
                $response['message'] = "Erro ao buscar recorrências para incorporação: " . mysqli_error($link);
            }
            break;

        case 'executar_cron_recorrencias_manual':
            require_once __DIR__ . '/helpers/CronRecorrenciasHelper.php';
            $competencia = $_POST['competencia'] ?? date('m/Y');
            $resCron = CronRecorrenciasHelper::processarRecorrencias($competencia, 'manual');
            $response = $resCron;
            break;

        case 'registrar_pagamento':
            $id_fatura = $_POST['id_fatura'] ?? '';
            $valor_pago = $_POST['valor_pago'] ?? '';
            $data_pagamento = $_POST['data_pagamento'] ?? '';
            $observacao = $_POST['observacao'] ?? ''; // Pode ser vazio
            $itens_pagos_json = $_POST['itens_pagos_json'] ?? '[]';

            if (empty($id_fatura) || empty($valor_pago) || empty($data_pagamento)) {
                $response['message'] = "ID da Fatura, Valor e Data são obrigatórios.";
            } else {
                // ** CORREÇÃO: Garante que todos os valores de texto sejam escapados e colocados entre aspas **
                // Isso evita erros de sintaxe SQL se um campo como 'observacao' estiver vazio.
                $id_fatura_safe = mysqli_real_escape_string($link, $id_fatura);
                $valor_pago_safe = mysqli_real_escape_string($link, $valor_pago);
                $data_pagamento_safe = mysqli_real_escape_string($link, $data_pagamento);
                $observacao_safe = mysqli_real_escape_string($link, $observacao);
                $itens_pagos_json_safe = mysqli_real_escape_string($link, $itens_pagos_json);

                $query = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento, observacao, itens_pagos_json) 
						  VALUES ('{$id_fatura_safe}', '{$valor_pago_safe}', '{$data_pagamento_safe}', 'Confirmado', '{$observacao_safe}', '{$itens_pagos_json_safe}')";

                if (DBExecute($link, $query)) {
                    // Após registrar o pagamento, verifica se a fatura deve ser liquidada
                    $query_total_pago = "SELECT SUM(valor_pago) as total_pago FROM Pagamentos WHERE id_fatura = '{$id_fatura_safe}' AND status_pagamento = 'Confirmado'";
                    $result_total_pago = DBExecute($link, $query_total_pago);
                    $total_pago_data = mysqli_fetch_assoc($result_total_pago);
                    $total_pago = (float) $total_pago_data['total_pago'];

                    // Use Helper to get Net Total (Less Retentions/Discounts)
                    $totals = AppHelper::calculateFaturaTotals($link, $id_fatura_safe);
                    $valor_para_liquidar = (float) $totals['valor_liquido'];

                    if ($total_pago >= $valor_para_liquidar - 0.01) { // 1 cent tolerance
                        $query_update_fatura = "UPDATE Faturas SET status = 'Liquidada' WHERE id_fatura = '{$id_fatura_safe}'";
                        DBExecute($link, $query_update_fatura);
                    }

                    $response['success'] = true;
                    $response['message'] = "Pagamento registrado com sucesso!";
                } else {
                    $response['message'] = "Erro ao registrar pagamento: " . mysqli_error($link);
                }
            }
            break;


        case 'estornar_pagamento': // Estornar um pagamento
            $id_pagamento = $_POST['id_pagamento'] ?? '';
            $id_fatura = $_POST['id_fatura'] ?? ''; // Necessário para recalcular status da fatura

            if (empty($id_pagamento) || empty($id_fatura)) {
                $response['message'] = "ID do pagamento ou ID da fatura é obrigatório para estorno.";
            } else {
                $id_pagamento = mysqli_real_escape_string($link, $id_pagamento);
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);

                // 1. Mudar o status do pagamento para 'Cancelado'
                $query_estornar_pagamento = "UPDATE Pagamentos SET status_pagamento = 'Cancelado' WHERE id_pagamento = '$id_pagamento'";
                $result_estornar = DBExecute($link, $query_estornar_pagamento);

                if ($result_estornar) {
                    // 2. Recalcular o total pago da fatura e atualizar o status
                    $query_total_pago = "SELECT COALESCE(SUM(valor_pago), 0) as total_pago FROM Pagamentos WHERE id_fatura = '$id_fatura' AND status_pagamento = 'Confirmado'";
                    $res_total = DBExecute($link, $query_total_pago);
                    $total_pago = (float) mysqli_fetch_assoc($res_total)['total_pago'];

                    $totals = AppHelper::calculateFaturaTotals($link, $id_fatura);
                    $valor_liquido = (float) $totals['valor_liquido'];

                    $novo_status = ($total_pago >= $valor_liquido - 0.01) ? 'Liquidada' : 'Em Aberto';

                    $query_update_fatura_status = "UPDATE Faturas SET status = '$novo_status' WHERE id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_fatura_status);

                    $response['success'] = true;
                    $response['message'] = "Pagamento estornado com sucesso!";
                } else {
                    $response['message'] = "Erro ao estornar pagamento: " . mysqli_error($link);
                }
            }
            break;

        case 'editar_fatura_dados':
            $id_fatura = $_POST['id_fatura'] ?? '';
            $data_vencimento = $_POST['data_vencimento'] ?? '';
            $desconto_valor = $_POST['desconto_valor'] ?? '0.00';
            $desconto_tipo = $_POST['desconto_tipo'] ?? 'percentual';
            $permitir_pagamento_parcial = isset($_POST['permitir_pagamento_parcial']) ? '1' : '0';

            if (empty($id_fatura)) {
                $response['message'] = "ID da fatura obrigatório.";
            } else {
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);
                $desconto_valor = mysqli_real_escape_string($link, $desconto_valor);
                $desconto_tipo = mysqli_real_escape_string($link, $desconto_tipo);

                $set_clause = "desconto_valor = '$desconto_valor',
                               desconto_tipo = '$desconto_tipo',
                               permitir_pagamento_parcial = '$permitir_pagamento_parcial'";

                if (!empty($data_vencimento)) {
                    $data_vencimento = mysqli_real_escape_string($link, $data_vencimento);
                    $set_clause .= ", data_vencimento = '$data_vencimento'";
                }

                $query = "UPDATE Faturas SET $set_clause WHERE id_fatura = '$id_fatura'";

                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Dados da fatura atualizados!";
                } else {
                    $response['message'] = "Erro ao atualizar fatura: " . mysqli_error($link);
                }
            }
            break;

        case 'validar_cpf_cnpj':
            $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';

            if (empty($cpf_cnpj)) {
                $response['message'] = "CPF/CNPJ é obrigatório.";
            } else {
                $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
                $query = "SELECT id_cliente, nome FROM Clientes WHERE cpf_cnpj = '$cpf_cnpj'";
                $result = DBExecute($link, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    $cliente_info = mysqli_fetch_assoc($result);

                    // Set Session for Client
                    $_SESSION['cliente_id'] = $cliente_info['id_cliente'];
                    $_SESSION['cliente_nome'] = $cliente_info['nome'];

                    $response['success'] = true;
                    $response['message'] = "Login bem-sucedido!";
                    $response['data'] = [
                        'id_cliente' => $cliente_info['id_cliente'],
                        'nome_cliente' => $cliente_info['nome']
                    ];
                } else {
                    $response['message'] = "CPF/CNPJ não encontrado ou inválido.";
                }
            }
            break;

        case 'get_dashboard_stats':
            // Filtros Dashboard
            $mes = $_POST['mes'] ?? '';
            $id_cliente = $_POST['id_cliente'] ?? '';
            $id_servico = $_POST['id_servico'] ?? '';

            // Cláusulas WHERE comuns
            $where_fatura_base = "1=1";
            if (!empty($id_cliente)) {
                $id_cliente_safe = mysqli_real_escape_string($link, $id_cliente);
                $where_fatura_base .= " AND f.id_cliente = '$id_cliente_safe'";
            }

            // Se filtra serviço, precisamos garantir que as faturas contenham o serviço
            // (Isso já filtra a lista de faturas relevantes)
            if (!empty($id_servico)) {
                $id_servico_safe = mysqli_real_escape_string($link, $id_servico);
                $where_fatura_base .= " AND f.id_fatura IN (SELECT id_fatura FROM ItensFatura WHERE id_servico = '$id_servico_safe')";
            }

            // Cláusula de Mês (Opcional)
            $where_mes_pagamento = "1=1";
            $where_mes_vencimento = "1=1";
            $titulo_faturado = "Total Recebido (Geral)";
            $titulo_aberto = "A Receber (Geral)";

            if (!empty($mes)) {
                $mes_safe = mysqli_real_escape_string($link, $mes);
                $where_mes_pagamento = "DATE_FORMAT(p.data_pagamento, '%Y-%m') = '$mes_safe'";
                $where_mes_vencimento = "DATE_FORMAT(f.data_vencimento, '%Y-%m') = '$mes_safe'";
                $titulo_faturado = "Total Recebido (Mês)";
                $titulo_aberto = "A Receber (Mês)";
            }

            // --- LÓGICA DE CÁLCULO ---
            // Se tiver filtro de serviço, fazemos cálculo proporcional.
            // Se NÃO tiver, usamos o valor total da fatura/pagamento.

            if (!empty($id_servico)) {
                $id_servico_safe = mysqli_real_escape_string($link, $id_servico);

                // Subquery para obter o valor total deste serviço específico dentro de uma fatura
                $sql_servico_valor = "(SELECT COALESCE(SUM(it.quantidade * it.valor_unitario), 0) 
                                       FROM ItensFatura it 
                                       WHERE it.id_fatura = f.id_fatura AND it.id_servico = '$id_servico_safe')";

                // Fórmula da Proporção: (ValorServico / ValorTotalFatura)
                // Usamos GREATEST(..., 1) para evitar divisão por zero se fatura estiver zerada (improvável, mas seguro)
                $sql_proporcao = "($sql_servico_valor / GREATEST(f.valor_total_fatura, 1))";


                // 1. Total Faturado (Proporcional ao serviço)
                $query_faturado = "SELECT SUM(p.valor_pago * $sql_proporcao) as total 
                                   FROM Pagamentos p 
                                   JOIN Faturas f ON p.id_fatura = f.id_fatura 
                                   WHERE p.status_pagamento = 'Confirmado' 
                                   AND $where_mes_pagamento
                                   AND $where_fatura_base";

                // 2. Total a Receber (Em Aberto - Proporcional ao saldo devedor)
                // SaldoDevedor = (FaturaTotal - TotalPago)
                // ValorServicoReceber = SaldoDevedor * Proporcao
                $query_aberto = "SELECT SUM(
                                    (f.valor_total_fatura - (SELECT COALESCE(SUM(p2.valor_pago),0) FROM Pagamentos p2 WHERE p2.id_fatura = f.id_fatura AND p2.status_pagamento = 'Confirmado')) 
                                    * $sql_proporcao
                                 ) as total 
                                 FROM Faturas f 
                                 WHERE f.status = 'Em Aberto' 
                                 AND $where_mes_vencimento
                                 AND $where_fatura_base";

                // 3. Total Atrasado (Proporcional)
                $hoje = date('Y-m-d');
                $query_atrasado = "SELECT SUM(
                                    (f.valor_total_fatura - (SELECT COALESCE(SUM(p2.valor_pago),0) FROM Pagamentos p2 WHERE p2.id_fatura = f.id_fatura AND p2.status_pagamento = 'Confirmado')) 
                                    * $sql_proporcao
                                   ) as total 
                                   FROM Faturas f 
                                   WHERE f.status = 'Em Aberto' 
                                   AND f.data_vencimento < '$hoje'
                                   AND $where_fatura_base";

                // 5. Gráfico (Proporcional)
                $query_grafico = "SELECT DATE_FORMAT(p.data_pagamento, '%Y-%m') as mes, 
                                         SUM(p.valor_pago * $sql_proporcao) as total
                                  FROM Pagamentos p 
                                  JOIN Faturas f ON p.id_fatura = f.id_fatura
                                  WHERE p.status_pagamento = 'Confirmado' 
                                  AND p.data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                  AND $where_fatura_base
                                  GROUP BY mes 
                                  ORDER BY mes ASC";

            } else {
                // --- LÓGICA PADRÃO (SEM FILTRO DE SERVIÇO) ---

                // 1. Total Faturado
                $query_faturado = "SELECT SUM(p.valor_pago) as total 
                                   FROM Pagamentos p 
                                   JOIN Faturas f ON p.id_fatura = f.id_fatura 
                                   WHERE p.status_pagamento = 'Confirmado' 
                                   AND $where_mes_pagamento
                                   AND $where_fatura_base";

                // 2. Total a Receber (Em Aberto)
                $query_aberto = "SELECT SUM(f.valor_total_fatura - (SELECT COALESCE(SUM(p2.valor_pago),0) FROM Pagamentos p2 WHERE p2.id_fatura = f.id_fatura AND p2.status_pagamento = 'Confirmado')) as total 
                                 FROM Faturas f 
                                 WHERE f.status = 'Em Aberto' 
                                 AND $where_mes_vencimento
                                 AND $where_fatura_base";

                // 3. Total Atrasado
                $hoje = date('Y-m-d');
                $query_atrasado = "SELECT SUM(f.valor_total_fatura - (SELECT COALESCE(SUM(p2.valor_pago),0) FROM Pagamentos p2 WHERE p2.id_fatura = f.id_fatura AND p2.status_pagamento = 'Confirmado')) as total 
                                   FROM Faturas f 
                                   WHERE f.status = 'Em Aberto' 
                                   AND f.data_vencimento < '$hoje'
                                   AND $where_fatura_base";

                // 5. Gráfico
                $query_grafico = "SELECT DATE_FORMAT(p.data_pagamento, '%Y-%m') as mes, SUM(p.valor_pago) as total
                                  FROM Pagamentos p 
                                  JOIN Faturas f ON p.id_fatura = f.id_fatura
                                  WHERE p.status_pagamento = 'Confirmado' 
                                  AND p.data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                  AND $where_fatura_base
                                  GROUP BY mes 
                                  ORDER BY mes ASC";
            }

            // Executa as queries calculadas acima
            $result_faturado = DBExecute($link, $query_faturado);
            $total_faturado = mysqli_fetch_assoc($result_faturado)['total'] ?? 0;

            $result_aberto = DBExecute($link, $query_aberto);
            $total_aberto = mysqli_fetch_assoc($result_aberto)['total'] ?? 0;

            $result_atrasado = DBExecute($link, $query_atrasado);
            $total_atrasado = mysqli_fetch_assoc($result_atrasado)['total'] ?? 0;

            // 4. Faturas Recentes (Prioriza faturas Em Aberto e Atrasadas em primeiro lugar)
            $query_recentes = "SELECT f.id_fatura, c.nome, f.valor_total_fatura, f.status, f.data_vencimento 
                               FROM Faturas f 
                               JOIN Clientes c ON f.id_cliente = c.id_cliente 
                               WHERE $where_fatura_base
                               ORDER BY (CASE WHEN f.status = 'Em Aberto' THEN 0 WHEN f.status = 'Atrasada' THEN 1 ELSE 2 END) ASC, f.id_fatura DESC LIMIT 5";
            $result_recentes = DBExecute($link, $query_recentes);
            $recentes = [];
            while ($row = mysqli_fetch_assoc($result_recentes)) {
                $recentes[] = $row;
            }

            // Executa Gráfico
            $result_grafico = DBExecute($link, $query_grafico);
            $grafico_data = [];
            $labels = [];
            $values = [];

            while ($row = mysqli_fetch_assoc($result_grafico)) {
                $labels[] = date('m/Y', strtotime($row['mes'] . '-01'));
                $values[] = (float) $row['total'];
            }

            $response['success'] = true;
            $response['data'] = [
                'total_faturado' => $total_faturado,
                'total_aberto' => $total_aberto,
                'total_atrasado' => $total_atrasado,
                'faturas_recentes' => $recentes,
                'grafico' => [
                    'labels' => $labels,
                    'values' => $values
                ],
                'titulo_faturado' => $titulo_faturado,
                'titulo_aberto' => $titulo_aberto
            ];
            break;

        case 'get_atendimentos_recentes':
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            if ($page < 1) $page = 1;
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
            if ($limit < 1) $limit = 10;
            $offset = ($page - 1) * $limit;

            // Total count
            $qCount = "SELECT COUNT(*) as total FROM Atendimentos";
            $rCount = DBExecute($link, $qCount);
            $total = 0;
            if ($rCount && $rowC = mysqli_fetch_assoc($rCount)) {
                $total = (int)$rowC['total'];
            }
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            // Fetch items
            $qAtend = "SELECT 
                        a.id_atendimento,
                        a.id_pet,
                        a.data_atendimento,
                        a.queixa_principal,
                        a.diagnostico,
                        p.nome as pet_nome,
                        p.especie as pet_especie,
                        c.nome as tutor_nome,
                        v.nome as vet_nome
                      FROM Atendimentos a
                      LEFT JOIN Pets p ON a.id_pet = p.id_pet
                      LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente
                      LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
                      ORDER BY a.data_atendimento DESC, a.id_atendimento DESC
                      LIMIT $offset, $limit";
            $rAtend = DBExecute($link, $qAtend);
            $items = [];
            if ($rAtend) {
                while ($row = mysqli_fetch_assoc($rAtend)) {
                    $items[] = $row;
                }
            }

            $response['success'] = true;
            $response['data'] = [
                'items' => $items,
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ];
            break;

        case 'get_cliente_dashboard_data':
            $id_cliente = $_SESSION['cliente_id'] ?? ($_POST['id_cliente'] ?? '');
            if (empty($id_cliente)) {
                $response['message'] = "Sessão de cliente expirada ou ID não fornecido.";
                break;
            }

            $id_cliente_safe = mysqli_real_escape_string($link, $id_cliente);

            // 1. Dados Cadastrais do Cliente
            $qCliente = "SELECT id_cliente, nome, cpf_cnpj, email, telefone, endereco, numero, complemento, bairro, cep, uf, codigo_municipio, google_calendar_id FROM Clientes WHERE id_cliente = '$id_cliente_safe'";
            $rCliente = DBExecute($link, $qCliente);
            $clienteData = mysqli_fetch_assoc($rCliente);

            // 2. Faturas (Estatísticas e Próximo Vencimento)
            $qFaturas = "SELECT F.id_fatura, F.data_emissao, F.data_vencimento, F.valor_total_fatura, F.status,
                                COALESCE(SUM(P.valor_pago), 0) AS total_pago_fatura
                         FROM Faturas F
                         LEFT JOIN Pagamentos P ON F.id_fatura = P.id_fatura AND P.status_pagamento = 'Confirmado'
                         WHERE F.id_cliente = '$id_cliente_safe'
                         GROUP BY F.id_fatura
                         ORDER BY F.data_vencimento ASC";
            $rFaturas = DBExecute($link, $qFaturas);
            $faturasList = [];
            $totalAbertoVal = 0.0;
            $countAberto = 0;
            $totalPagoVal = 0.0;
            $countPago = 0;
            $proximaFaturaPendente = null;

            if ($rFaturas) {
                while ($f = mysqli_fetch_assoc($rFaturas)) {
                    $faturasList[] = $f;
                    $isLiq = ($f['status'] === 'Liquidada');
                    if ($isLiq) {
                        $totalPagoVal += (float)$f['valor_total_fatura'];
                        $countPago++;
                    } else {
                        $totalAbertoVal += (float)$f['valor_total_fatura'];
                        $countAberto++;
                        if (!$proximaFaturaPendente) {
                            $proximaFaturaPendente = $f;
                        }
                    }
                }
            }

            // 3. Agendamentos do Cliente
            $qAgend = "SELECT a.id_agendamento, a.titulo, a.data_inicio, a.data_fim, a.status,
                              p.nome as pet_nome, v.nome as vet_nome
                       FROM Agendamentos a
                       LEFT JOIN Pets p ON a.id_pet = p.id_pet
                       LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
                       WHERE a.id_cliente = '$id_cliente_safe'
                       ORDER BY a.data_inicio DESC
                       LIMIT 10";
            $rAgend = DBExecute($link, $qAgend);
            $agendamentos = [];
            if ($rAgend) {
                while ($ag = mysqli_fetch_assoc($rAgend)) {
                    $agendamentos[] = $ag;
                }
            }

            // 4. Se Modo Vet (Pets, Atendimentos e Vacinas)
            $pets = [];
            $atendimentosRecentes = [];
            $carteiraVacinas = [];

            if (AppHelper::isVetMode()) {
                // Pets com histórico de peso e estatísticas
                $qPets = "SELECT * FROM Pets WHERE id_cliente = '$id_cliente_safe' ORDER BY nome ASC";
                $rPets = DBExecute($link, $qPets);
                if ($rPets) {
                    while ($pt = mysqli_fetch_assoc($rPets)) {
                        $idPet = $pt['id_pet'];

                        // 1. Estatísticas de Atendimentos do Pet
                        $qStatAtend = "SELECT COUNT(*) as total_atendimentos, 
                                             MAX(data_atendimento) as ultimo_atendimento
                                      FROM Atendimentos WHERE id_pet = '$idPet'";
                        $rStatAtend = DBExecute($link, $qStatAtend);
                        $statAtend = mysqli_fetch_assoc($rStatAtend);
                        $pt['total_atendimentos'] = (int)($statAtend['total_atendimentos'] ?? 0);
                        $pt['ultimo_atendimento'] = $statAtend['ultimo_atendimento'] ?? null;

                        // Queixa do último atendimento
                        $pt['ultimo_atendimento_queixa'] = null;
                        if (!empty($pt['ultimo_atendimento'])) {
                            $qUltQueixa = "SELECT queixa_principal FROM Atendimentos WHERE id_pet = '$idPet' ORDER BY data_atendimento DESC LIMIT 1";
                            $rUltQueixa = DBExecute($link, $qUltQueixa);
                            if ($rUltQueixa && $rowQ = mysqli_fetch_assoc($rUltQueixa)) {
                                $pt['ultimo_atendimento_queixa'] = $rowQ['queixa_principal'];
                            }
                        }

                        // 2. Histórico de Pesagem (Atendimentos com peso > 0)
                        $qPesoHist = "SELECT DATE_FORMAT(data_atendimento, '%Y-%m-%d') as data, peso 
                                      FROM Atendimentos 
                                      WHERE id_pet = '$idPet' AND peso IS NOT NULL AND peso > 0 
                                      ORDER BY data_atendimento ASC";
                        $rPesoHist = DBExecute($link, $qPesoHist);
                        $pesosHist = [];
                        if ($rPesoHist) {
                            while ($pRow = mysqli_fetch_assoc($rPesoHist)) {
                                $pesosHist[] = [
                                    'data' => $pRow['data'],
                                    'peso' => (float)$pRow['peso']
                                ];
                            }
                        }

                        // Se não houver histórico em Atendimentos mas houver no cadastro do Pet, inclui como Ponto Atual
                        if (empty($pesosHist) && !empty($pt['peso']) && (float)$pt['peso'] > 0) {
                            $pesosHist[] = [
                                'data' => date('Y-m-d'),
                                'peso' => (float)$pt['peso']
                            ];
                        }

                        $pt['historico_peso'] = $pesosHist;

                        $pets[] = $pt;
                    }
                }

                // Atendimentos Recentes
                $qAtendRec = "SELECT a.id_atendimento, a.id_pet, a.data_atendimento, a.queixa_principal, a.diagnostico,
                                     p.nome as pet_nome, v.nome as vet_nome
                              FROM Atendimentos a
                              JOIN Pets p ON a.id_pet = p.id_pet
                              LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
                              WHERE p.id_cliente = '$id_cliente_safe'
                              ORDER BY a.data_atendimento DESC, a.id_atendimento DESC
                              LIMIT 10";
                $rAtendRec = DBExecute($link, $qAtendRec);
                if ($rAtendRec) {
                    while ($at = mysqli_fetch_assoc($rAtendRec)) {
                        $atendimentosRecentes[] = $at;
                    }
                }

                // Vacinas
                $qVac = "SELECT cv.*, v.nome as vacina_nome, p.nome as pet_nome
                         FROM CarteiraVacinas cv
                         JOIN Vacinas v ON cv.id_vacina = v.id_vacina
                         JOIN Pets p ON cv.id_pet = p.id_pet
                         WHERE p.id_cliente = '$id_cliente_safe'
                         ORDER BY cv.data_vencimento ASC";
                $rVac = DBExecute($link, $qVac);
                if ($rVac) {
                    while ($vc = mysqli_fetch_assoc($rVac)) {
                        $carteiraVacinas[] = $vc;
                    }
                }
            }

            // 4.5. Contratos / Recorrências do Cliente + Documentos Emitidos
            $recorrencias = [];
            $qRec = "SELECT R.*, S.nome_servico 
                     FROM Recorrencias R 
                     JOIN Servicos S ON R.id_servico = S.id_servico 
                     WHERE R.id_cliente = '$id_cliente_safe' 
                     ORDER BY R.data_inicio_cobranca DESC";
            $rRec = DBExecute($link, $qRec);
            if ($rRec) {
                while ($rec = mysqli_fetch_assoc($rRec)) {
                    $idRec = $rec['id_recorrencia'];
                    
                    // Fetch linked documents
                    $qDocs = "SELECT d.id_documento_emitido, d.titulo, d.tipo, d.data_emissao 
                              FROM DocumentosEmitidos d 
                              WHERE d.id_recorrencia = '$idRec' 
                              ORDER BY d.data_emissao DESC";
                    $rDocs = DBExecute($link, $qDocs);
                    $docs = [];
                    if ($rDocs) {
                        while ($docRow = mysqli_fetch_assoc($rDocs)) {
                            $docs[] = $docRow;
                        }
                    }
                    $rec['documentos'] = $docs;
                    $recorrencias[] = $rec;
                }
            }

            // 4.6. Pacotes e Saldos de Banho & Tosa do Cliente
            $clientePacotes = [];
            $qPacotes = "SELECT cp.*, p.nome_pacote, p.valor_total, p.is_recorrente, p.icone, p.imagem_url,
                                pt.nome as nome_pet_vinculado, cp.id_pet as pet_vinculado
                         FROM ClientePacotes cp 
                         JOIN Pacotes p ON cp.id_pacote = p.id_pacote 
                         LEFT JOIN Pets pt ON cp.id_pet = pt.id_pet
                         WHERE cp.id_cliente = '$id_cliente_safe' AND cp.status = 'ativo' 
                         ORDER BY cp.data_aquisicao DESC";
            $rPacotes = DBExecute($link, $qPacotes);
            if ($rPacotes) {
                while ($cp = mysqli_fetch_assoc($rPacotes)) {
                    $idCP = (int)$cp['id_cliente_pacote'];
                    $qS = "SELECT cps.*, s.nome_servico, s.duracao_minutos, (cps.qtd_total - cps.qtd_utilizada) as saldo_restante 
                           FROM ClientePacoteSaldos cps 
                           JOIN Servicos s ON cps.id_servico = s.id_servico 
                           WHERE cps.id_cliente_pacote = $idCP";
                    $rS = DBExecute($link, $qS);
                    $saldos = [];
                    if ($rS) {
                        while ($sRow = mysqli_fetch_assoc($rS)) {
                            $saldos[] = $sRow;
                        }
                    }
                    $cp['saldos'] = $saldos;
                    $clientePacotes[] = $cp;
                }
            }

            // 4.7. Status de Banho ao Vivo na Esteira para os Pets do Cliente
            $banhoFilaAoVivo = [];
            $qFilaAoVivo = "SELECT f.*, p.nome as pet_nome, p.porte, p.tipo_pelagem,
                            DATE_FORMAT(f.horario_entrada, '%H:%i') as horario_entrada_fmt
                            FROM BanhoProducaoFila f 
                            JOIN Pets p ON f.id_pet = p.id_pet 
                            WHERE p.id_cliente = '$id_cliente_safe' AND f.etapa != 'finalizado' 
                            ORDER BY f.horario_entrada DESC";
            $rFilaAoVivo = DBExecute($link, $qFilaAoVivo);
            if ($rFilaAoVivo) {
                while ($fRow = mysqli_fetch_assoc($rFilaAoVivo)) {
                    $banhoFilaAoVivo[] = $fRow;
                }
            }

            // 4.8. Serviços disponíveis para agendamento online de Banho & Tosa
            $servicosBanho = [];
            $qServBanho = "SELECT id_servico, nome_servico, duracao_minutos, valor_sugerido, icone_servico 
                           FROM Servicos 
                           WHERE disponivel_banho = 1 OR (disponivel_clinica = 0 AND disponivel_banho = 0) 
                           ORDER BY nome_servico ASC";
            $rServBanho = DBExecute($link, $qServBanho);
            if ($rServBanho) {
                while ($sb = mysqli_fetch_assoc($rServBanho)) {
                    $servicosBanho[] = $sb;
                }
            }

            // 5. System Google Integration Hint
            $googleServiceEmailHint = '';
            $qConf = "SELECT google_service_account_json FROM ConfiguracoesEmissor LIMIT 1";
            $rConf = DBExecute($link, $qConf);
            if ($rConf && $conf = mysqli_fetch_assoc($rConf)) {
                if (!empty($conf['google_service_account_json'])) {
                    $jsonDecrypted = EncryptionHelper::decrypt($conf['google_service_account_json']);
                    if ($jsonDecrypted) {
                        $dataJson = json_decode($jsonDecrypted, true);
                        if (isset($dataJson['client_email'])) {
                            $googleServiceEmailHint = $dataJson['client_email'];
                        }
                    }
                }
            }

            $response['success'] = true;
            $response['data'] = [
                'cliente' => $clienteData,
                'faturas_summary' => [
                    'total_aberto' => $totalAbertoVal,
                    'count_aberto' => $countAberto,
                    'total_pago' => $totalPagoVal,
                    'count_pago' => $countPago,
                    'proxima_pendente' => $proximaFaturaPendente
                ],
                'faturas' => $faturasList,
                'agendamentos' => $agendamentos,
                'pets' => $pets,
                'atendimentos' => $atendimentosRecentes,
                'vacinas' => $carteiraVacinas,
                'recorrencias' => $recorrencias,
                'pacotes' => $clientePacotes,
                'banho_ao_vivo' => $banhoFilaAoVivo,
                'servicos_banho' => $servicosBanho,
                'google_service_email_hint' => $googleServiceEmailHint,
                'is_vet_mode' => AppHelper::isVetMode()
            ];
            break;

        case 'atualizar_dados_cliente':
            $id_cliente = $_SESSION['cliente_id'] ?? '';
            if (empty($id_cliente)) {
                $response['message'] = "Sessão de cliente inválida.";
                break;
            }

            $id_cliente_safe = mysqli_real_escape_string($link, $id_cliente);
            $email = mysqli_real_escape_string($link, $_POST['email'] ?? '');
            $telefone = mysqli_real_escape_string($link, $_POST['telefone'] ?? '');
            $endereco = mysqli_real_escape_string($link, $_POST['endereco'] ?? '');
            $numero = mysqli_real_escape_string($link, $_POST['numero'] ?? '');
            $complemento = mysqli_real_escape_string($link, $_POST['complemento'] ?? '');
            $bairro = mysqli_real_escape_string($link, $_POST['bairro'] ?? '');
            $cep = mysqli_real_escape_string($link, $_POST['cep'] ?? '');
            $uf = mysqli_real_escape_string($link, $_POST['uf'] ?? '');
            $codigo_municipio = mysqli_real_escape_string($link, $_POST['codigo_municipio'] ?? '');
            $google_calendar_id = mysqli_real_escape_string($link, $_POST['google_calendar_id'] ?? '');

            $qUpdate = "UPDATE Clientes SET 
                        email = '$email',
                        telefone = '$telefone',
                        endereco = '$endereco',
                        numero = '$numero',
                        complemento = '$complemento',
                        bairro = '$bairro',
                        cep = '$cep',
                        uf = '$uf',
                        codigo_municipio = '$codigo_municipio',
                        google_calendar_id = '$google_calendar_id'
                        WHERE id_cliente = '$id_cliente_safe'";

            if (DBExecute($link, $qUpdate)) {
                $response['success'] = true;
                $response['message'] = "Dados atualizados com sucesso!";
            } else {
                $response['message'] = "Erro ao atualizar dados: " . mysqli_error($link);
            }
            break;

        case 'cliente_agendar_banho':
            $id_cliente = $_SESSION['cliente_id'] ?? '';
            if (empty($id_cliente)) {
                $response['message'] = "Sessão expirada. Faça login novamente.";
                break;
            }

            $id_cliente_safe = (int)$id_cliente;
            $id_pet = (int)($_POST['id_pet'] ?? 0);
            $id_servico = (int)($_POST['id_servico'] ?? 0);
            $data_inicio = $_POST['data_inicio'] ?? '';
            $obsRaw = trim($_POST['observacoes'] ?? ($_POST['observacoes_estetica'] ?? ''));
            $observacoes = mysqli_real_escape_string($link, $obsRaw);
            $usar_saldo = isset($_POST['usar_saldo_pacote']) && $_POST['usar_saldo_pacote'] == 1;

            if ($id_pet <= 0 || $id_servico <= 0 || empty($data_inicio)) {
                $response['message'] = "Preencha todos os campos obrigatórios (Pet, Serviço e Horário).";
                break;
            }

            // Verify Pet belongs to Client
            $resPet = DBExecute($link, "SELECT p.*, s.duracao_minutos, s.nome_servico 
                                        FROM Pets p 
                                        LEFT JOIN Servicos s ON s.id_servico = $id_servico 
                                        WHERE p.id_pet = $id_pet AND p.id_cliente = $id_cliente_safe");
            if (!$resPet || mysqli_num_rows($resPet) == 0) {
                $response['message'] = "Pet não localizado ou não pertence ao seu cadastro.";
                break;
            }

            $pInfo = mysqli_fetch_assoc($resPet);
            $duracaoBase = (int)($pInfo['duracao_minutos'] ?: 30);
            $porte = $pInfo['porte'] ?: 'P';
            $pelagem = $pInfo['tipo_pelagem'] ?: 'Curto';

            // Multiplier
            $mult = 1.0;
            if ($porte === 'M') $mult = 1.2;
            if ($porte === 'G') $mult = 1.5;
            if ($porte === 'GG') $mult = 2.0;

            $duracaoFinal = (int) round($duracaoBase * $mult);
            if ($pelagem === 'Longo' || $pelagem === 'Dupla Pelagem') {
                $duracaoFinal += 15;
            }

            $dtInicio = new DateTime($data_inicio, new DateTimeZone('America/Sao_Paulo'));
            $dtFim = clone $dtInicio;
            $dtFim->modify("+{$duracaoFinal} minutes");

            $startStr = $dtInicio->format('Y-m-d H:i:s');
            $endStr = $dtFim->format('Y-m-d H:i:s');

            $titulo = mysqli_real_escape_string($link, "Banho/Tosa: " . $pInfo['nome'] . " (" . $pInfo['nome_servico'] . ")");

            // Check if using package balance
            $id_cliente_pacote_val = "NULL";
            if ($usar_saldo) {
                $qPac = "SELECT cp.id_cliente_pacote, cps.id_saldo, cps.qtd_total, cps.qtd_utilizada 
                         FROM ClientePacotes cp 
                         JOIN ClientePacoteSaldos cps ON cp.id_cliente_pacote = cps.id_cliente_pacote 
                         WHERE cp.id_cliente = $id_cliente_safe 
                           AND cp.status = 'ativo' 
                           AND cps.id_servico = $id_servico 
                           AND (cps.qtd_total - cps.qtd_utilizada) > 0 
                         ORDER BY cp.data_aquisicao ASC LIMIT 1";
                $rPac = DBExecute($link, $qPac);
                if ($rPac && $pacRow = mysqli_fetch_assoc($rPac)) {
                    $id_cliente_pacote_val = (int)$pacRow['id_cliente_pacote'];
                    $newUtil = $pacRow['qtd_utilizada'] + 1;
                    $idSaldo = (int)$pacRow['id_saldo'];
                    DBExecute($link, "UPDATE ClientePacoteSaldos SET qtd_utilizada = $newUtil WHERE id_saldo = $idSaldo");
                }
            }

            $statusAgend = 'Agendado';

            $query = "INSERT INTO Agendamentos (id_cliente, id_pet, id_servico, id_cliente_pacote, tipo_agenda, titulo, descricao, data_inicio, data_fim, status) 
                      VALUES ($id_cliente_safe, $id_pet, $id_servico, $id_cliente_pacote_val, 'banho_tosa', '$titulo', '$observacoes', '$startStr', '$endStr', '$statusAgend')";
            if (DBExecute($link, $query)) {
                $newAgendId = mysqli_insert_id($link);

                // Auto enqueue to BanhoProducaoFila
                DBExecute($link, "INSERT INTO BanhoProducaoFila (id_agendamento, id_pet, etapa, horario_entrada, observacoes_estetica) 
                                  VALUES ($newAgendId, $id_pet, 'aguardando', '$startStr', '$observacoes')");

                // Log consumo if package was used
                if ($id_cliente_pacote_val !== "NULL") {
                    DBExecute($link, "INSERT INTO ClientePacoteConsumo (id_cliente_pacote, id_servico, id_pet, id_agendamento, observacao) 
                                      VALUES ($id_cliente_pacote_val, $id_servico, $id_pet, $newAgendId, 'Agendamento Online pelo Tutor')");
                }

                $response['success'] = true;
                $response['message'] = "Agendamento de Banho & Tosa realizado com sucesso!";
            } else {
                $response['message'] = "Erro ao agendar: " . mysqli_error($link);
            }
            break;

        case 'upload_imagem_oracle':
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = "Nenhum arquivo enviado ou erro no upload.";
                break;
            }

            $origName = $_FILES['foto']['name'];
            $tmpPath = $_FILES['foto']['tmp_name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $response['message'] = "Apenas imagens (JPG, PNG, WEBP, GIF) são permitidas.";
                break;
            }

            // Check Oracle Preauth URL
            $qConf = "SELECT api_oracle_url FROM ConfiguracoesEmissor LIMIT 1";
            $resConf = DBExecute($link, $qConf);
            $urlOracle = '';
            if ($resConf && $rC = mysqli_fetch_assoc($resConf)) {
                $urlOracle = $rC['api_oracle_url'] ?? '';
            }

            if (!empty($urlOracle)) {
                if (substr($urlOracle, -1) !== '/') $urlOracle .= '/';
                $bucketFileName = 'imagens/' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
                $urlUpload = $urlOracle . $bucketFileName;

                $content = file_get_contents($tmpPath);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpPath);

                $ch = curl_init($urlUpload);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: ' . $mimeType,
                    'Content-Length: ' . strlen($content)
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    $response['success'] = true;
                    $response['url'] = $urlUpload;
                    $response['message'] = "Imagem enviada para o Oracle Cloud com sucesso!";
                    break;
                }
            }

            // Fallback Local Storage
            $uploadDir = __DIR__ . '/uploads/imagens/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $localName = 'img_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
            if (move_uploaded_file($tmpPath, $uploadDir . $localName)) {
                $response['success'] = true;
                $response['url'] = 'uploads/imagens/' . $localName;
                $response['message'] = "Imagem salva com sucesso!";
            } else {
                $response['message'] = "Erro ao salvar imagem no servidor.";
            }
            break;

        case 'get_atendimento_detalhes_cliente':
            $id_cliente = $_SESSION['cliente_id'] ?? '';
            $id_atendimento = $_POST['id_atendimento'] ?? '';

            if (empty($id_cliente) || empty($id_atendimento)) {
                $response['message'] = "Parâmetros inválidos ou sessão expirada.";
                break;
            }

            $id_cliente_safe = mysqli_real_escape_string($link, $id_cliente);
            $id_atendimento_safe = mysqli_real_escape_string($link, $id_atendimento);

            // 1. Fetch Atendimento and check ownership via Pet -> Cliente
            $qAtend = "SELECT a.*, 
                        p.nome as pet_nome, p.especie as pet_especie, p.raca as pet_raca, p.sexo as pet_sexo, p.peso as pet_peso, p.data_nascimento as pet_nascimento,
                        v.nome as vet_nome, v.crmv as vet_crmv, v.uf_crmv as vet_uf_crmv
                       FROM Atendimentos a
                       JOIN Pets p ON a.id_pet = p.id_pet
                       LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
                       WHERE a.id_atendimento = '$id_atendimento_safe' AND p.id_cliente = '$id_cliente_safe'";
            $rAtend = DBExecute($link, $qAtend);
            if (!$rAtend || mysqli_num_rows($rAtend) === 0) {
                $response['message'] = "Atendimento não encontrado ou acesso não permitido.";
                break;
            }

            $atendimentoData = mysqli_fetch_assoc($rAtend);

            // 2. Fetch Attached Files
            $qArq = "SELECT arq.id_arquivo, arq.nome_original, arq.url_publica, arq.tamanho_bytes, arq.data_upload
                     FROM Arquivos arq
                     JOIN AtendimentoArquivos aa ON arq.id_arquivo = aa.id_arquivo
                     WHERE aa.id_atendimento = '$id_atendimento_safe'
                     ORDER BY arq.data_upload DESC";
            $rArq = DBExecute($link, $qArq);
            $arquivos = [];
            if ($rArq) {
                while ($ar = mysqli_fetch_assoc($rArq)) {
                    $arquivos[] = $ar;
                }
            }

            // 3. Fetch Prescriptions (Receitas) & Items
            $qRec = "SELECT * FROM Receitas WHERE id_atendimento = '$id_atendimento_safe' ORDER BY data_receita DESC";
            $rRec = DBExecute($link, $qRec);
            $receitas = [];
            if ($rRec) {
                while ($rec = mysqli_fetch_assoc($rRec)) {
                    $idRec = $rec['id_receita'];
                    $qItens = "SELECT * FROM ItensReceita WHERE id_receita = '$idRec' ORDER BY id_item ASC";
                    $rItens = DBExecute($link, $qItens);
                    $itens = [];
                    if ($rItens) {
                        while ($it = mysqli_fetch_assoc($rItens)) {
                            $itens[] = $it;
                        }
                    }
                    $rec['itens'] = $itens;
                    $receitas[] = $rec;
                }
            }

            $response['success'] = true;
            $response['data'] = [
                'atendimento' => $atendimentoData,
                'arquivos' => $arquivos,
                'receitas' => $receitas
            ];
            break;

        default:
            $response['message'] = "Ação inválida.";
            break;

        // --- GESTÃO DE ARQUIVOS (OCI S3) ---

        case 'upload_arquivo_fatura':
            $id_fatura = $_POST['id_fatura'] ?? '';

            // Verifica o upload
            if (empty($id_fatura) || !isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = "Fatura ou arquivo inválido.";
                break;
            }

            // Validar tamanho (Max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB em bytes
            if ($_FILES['arquivo']['size'] > $maxSize) {
                $response['message'] = "O arquivo excede o tamanho máximo permitido de 10MB.";
                break;
            }

            // Validar tipo de arquivo seguro (opcional, mas recomendado)
            // Aqui permitimos PDF, Imagens, XML, ZIP
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/xml', 'text/xml', 'application/zip', 'application/x-zip-compressed'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['arquivo']['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                // $response['message'] = "Tipo de arquivo não permitido ($mimeType).";
                // break; 
                // (Opcional: Descomentar para restringir tipos. O usuário pediu PDF/XML, mas pode querer outros.)
            }

            // Preparar para enviar ao OCI S3
            $nomeOriginal = $_FILES['arquivo']['name'];
            $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
            // Nome único no bucket: timestamp_idFatura_hash.ext
            $nomeArquivoBucket = time() . '_' . $id_fatura . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extensao;

            // Carregar URL pré-autenticada
            // Carregar URL pré-autenticada do Banco de Dados
            $queryConfig = "SELECT api_oracle_url FROM ConfiguracoesEmissor LIMIT 1";
            $resConfig = DBExecute($link, $queryConfig);
            $rowConfig = mysqli_fetch_assoc($resConfig);

            $urlBucketPreauth = $rowConfig['api_oracle_url'] ?? '';

            if (empty($urlBucketPreauth)) {
                $response['message'] = "URL do bucket Oracle não configurada nas Configurações (Fiscal).";
                break;
            }

            // Garante barra no final
            if (substr($urlBucketPreauth, -1) !== '/') {
                $urlBucketPreauth .= '/';
            }

            // Nome único no bucket com pasta 'arquivos/': arquivos/timestamp_idFatura_hash.ext
            $nomeArquivoBucket = 'arquivos/' . time() . '_' . $id_fatura . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extensao;
            $urlUpload = $urlBucketPreauth . $nomeArquivoBucket;
            $caminhoTemp = $_FILES['arquivo']['tmp_name'];
            $tamanhoBytes = $_FILES['arquivo']['size']; // tamanho correto

            // Ler conteúdo do arquivo para enviar no corpo do PUT
            $conteudoArquivo = file_get_contents($caminhoTemp);

            // Iniciar CURL para PUT
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
            $curlError = curl_error($ch);
            curl_close($ch);

            // Código 200 ou 201 (Created) geralmente indicam sucesso no PUT
            if ($httpCode >= 200 && $httpCode < 300) {

                // Sucesso no upload, salvar no BD
                $link = DBConnect(); // Assegurar conexão (já deve estar aberta, mas por segurança)
                if (!$link)
                    $link = DBConnect();

                $nomeOriginalSafe = mysqli_real_escape_string($link, $nomeOriginal);
                $urlPublicaSafe = mysqli_real_escape_string($link, $urlUpload);
                $mimeTypeSafe = mysqli_real_escape_string($link, $mimeType);

                // 1. Inserir em Arquivos
                $queryArquivo = "INSERT INTO Arquivos (nome_original, url_publica, tamanho_bytes, tipo_mime) 
                                 VALUES ('$nomeOriginalSafe', '$urlPublicaSafe', '$tamanhoBytes', '$mimeTypeSafe')";

                if (DBExecute($link, $queryArquivo)) {
                    $idArquivo = mysqli_insert_id($link);

                    // 2. Vincular na Fatura
                    $idFaturaSafe = mysqli_real_escape_string($link, $id_fatura);
                    $queryVinculo = "INSERT INTO FaturaArquivos (id_fatura, id_arquivo) VALUES ('$idFaturaSafe', '$idArquivo')";

                    if (DBExecute($link, $queryVinculo)) {
                        $response['success'] = true;
                        $response['message'] = "Arquivo anexado com sucesso!";
                    } else {
                        $response['message'] = "Arquivo enviado, mas erro ao vincular na fatura: " . mysqli_error($link);
                    }
                } else {
                    $response['message'] = "Arquivo enviado, mas erro ao salvar metadados: " . mysqli_error($link);
                }

            } else {
                $response['message'] = "Erro ao enviar arquivo para nuvem. HTTP Code: $httpCode. Curl Error: $curlError";
            }
            break;

        case 'get_fatura_arquivos':
            $id_fatura = $_POST['id_fatura'] ?? '';
            if (empty($id_fatura)) {
                $response['message'] = "ID da fatura obrigatório.";
            } else {
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);
                $query = "SELECT A.id_arquivo, A.nome_original, A.url_publica, A.tamanho_bytes, A.data_upload 
                          FROM Arquivos A
                          JOIN FaturaArquivos FA ON A.id_arquivo = FA.id_arquivo
                          WHERE FA.id_fatura = '$id_fatura'
                          ORDER BY A.data_upload DESC";

                $result = DBExecute($link, $query);
                $arquivos = [];
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $arquivos[] = $row;
                    }
                    $response['success'] = true;
                    $response['data'] = $arquivos;
                } else {
                    $response['message'] = "Erro ao buscar arquivos: " . mysqli_error($link);
                }
            }
            break;

        case 'preview_nfse_data':
            $id_fatura = $_POST['id_fatura'] ?? '';
            $calcData = AppHelper::calculateNfseData($link, $id_fatura);

            if ($calcData['success']) {
                $response['success'] = true;
                $response['data'] = [
                    'tomador' => $calcData['tomador'],
                    'discriminacao' => $calcData['discriminacao'],
                    'tax_settings' => $calcData['tax_settings'],
                    'total_servicos' => $calcData['total_servicos'],
                    'validation_errors' => $calcData['validation_errors'],
                    'ambiente' => $calcData['ambiente']
                ];
            } else {
                $response['message'] = $calcData['message'];
            }
            break;

        case 'gerar_nfse':
            require_once '../nfse_test/api.php';

            $id_fatura = $_POST['id_fatura'] ?? '';
            if (empty($id_fatura)) {
                $response['message'] = "ID Fatura obrigatório";
                break;
            }

            // 1. Calculate Data via Helper
            $calcData = AppHelper::calculateNfseData($link, $id_fatura);
            if (!$calcData['success']) {
                $response['message'] = $calcData['message'];
                break;
            }

            $fatura = $calcData['fatura'];
            $config = $calcData['config'];
            $taxSettings = $calcData['tax_settings'];
            $discriminacaoFinal = $calcData['discriminacao'];
            $tomadorData = $calcData['tomador'];
            $totalServicos = $calcData['total_servicos'];
            $ambiente = $calcData['ambiente'];

            // Check Validations
            if (!empty($calcData['validation_errors'])) {
                $response['success'] = false;
                $response['message'] = "Erro de Validação: Complete o cadastro do cliente.";
                $response['details'] = "Campos obrigatórios faltando: " . implode(", ", $calcData['validation_errors']);
                break;
            }

            $hasCert = !empty($config['certificado_pfx_base64']) || !empty($config['caminho_certificado']);
            if (!$hasCert) {
                $response['message'] = "Certificado não configurado";
                break;
            }

            // Decrypt Certificate Password
            if (!empty($config['senha_certificado'])) {
                try {
                    $decrypted = EncryptionHelper::decrypt($config['senha_certificado']);
                    if ($decrypted) {
                        $config['senha_certificado'] = $decrypted;
                    }
                } catch (Exception $e) {
                }
            }

            // RPS Number
            $nextRps = ($ambiente == 'producao') ? $config['ultimo_rps_producao'] + 1 : $config['ultimo_rps_homologacao'] + 1;

            $inputApi = [
                'cnpj' => $config['cnpj'],
                'im' => $config['inscricao_municipal'],
                'ie' => $config['inscricao_estadual'] ?? '',
                'numero_rps' => $nextRps,
                'serie_rps' => $config['serie_rps'],
                'tipo_rps' => '1',
                'valor' => number_format($totalServicos, 2, '.', ''),
                'iss_retido' => $taxSettings['iss_retido'] ? '1' : '2',
                'aliquota' => $taxSettings['aliquota_iss'],
                'discriminacao' => $discriminacaoFinal,
                'codigo_cnae' => $taxSettings['codigo_cnae'],
                'codigo_nbs' => $taxSettings['codigo_nbs'],
                'item_lista' => $taxSettings['item_lista_servico'],
                'codigo_tributacao' => $taxSettings['codigo_tributacao_municipio'],
                'regime_tributario' => $config['regime_tributario'],
                'optante_simples' => ($config['optante_simples'] == '1') ? '1' : '2',
                'tomador' => $tomadorData
            ];

            // 5. Build XML
            if (!function_exists('buildGerarNfseXml')) {
                $response['message'] = "Erro interno: Biblioteca NFSe não carregada.";
                break;
            }
            $xmlData = buildGerarNfseXml($inputApi);

            // 6. Load Cert
            $pfxContent = null;
            if (!empty($config['certificado_pfx_base64'])) {
                $pfxContent = base64_decode($config['certificado_pfx_base64']);
            } elseif (!empty($config['caminho_certificado'])) {
                $pfxPath = $config['caminho_certificado'];
                $finalPfxPath = null;
                if (file_exists($pfxPath)) {
                    $finalPfxPath = $pfxPath;
                } elseif (file_exists(__DIR__ . '/' . $pfxPath)) {
                    $finalPfxPath = __DIR__ . '/' . $pfxPath;
                } elseif (file_exists(__DIR__ . '/../' . $pfxPath)) {
                    $finalPfxPath = __DIR__ . '/../' . $pfxPath;
                }
                if ($finalPfxPath) {
                    $pfxContent = file_get_contents($finalPfxPath);
                }
            }

            if (!$pfxContent) {
                $response['message'] = "Arquivo do Certificado PFX não encontrado.";
                break;
            }

            $certs = [];
            if (!openssl_pkcs12_read($pfxContent, $certs, $config['senha_certificado'])) {
                $response['message'] = "Senha do certificado incorreta ou PFX inválido.";
                break;
            }

            // 7. Sign
            $xmlSigned = assinarRoot($xmlData['root'], $certs, "", 'support_combo');

            // 8. Send
            $endpoint = ($ambiente == 'producao')
                ? 'https://df.issnetonline.com.br/webservicenfse204/nfse.asmx'
                : 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';

            $resultSoap = sendSoap($xmlSigned, $endpoint, $certs, 'support_combo', 'gerar', true);
            $responseSoap = $resultSoap['response_body'] ?? '';

            // 9. Process Response
            $status = 'Erro';
            if (strpos($responseSoap, '<Numero>') !== false && strpos($responseSoap, '<CompNfse>') !== false) {
                $status = 'concluido';
            } elseif (strpos($responseSoap, '<ListaMensagemRetorno>') === false && strpos($responseSoap, '<Fault>') === false && !empty($responseSoap)) {
                // Potential raw success
            }

            $xml_envio_esc = mysqli_real_escape_string($link, $xmlSigned);
            $xml_retorno_esc = mysqli_real_escape_string($link, $responseSoap);

            // Prepare snapshot data
            $valor_servico = number_format($totalServicos, 2, '.', '');
            $aliquota = $taxSettings['aliquota_iss'] ?: '0.00';
            $iss_retido_val = ($taxSettings['iss_retido'] == '1') ? 1 : 0;

            $item_lista = $taxSettings['item_lista_servico'];
            $discriminacao_esc = mysqli_real_escape_string($link, $discriminacaoFinal);

            $queryLog = "INSERT INTO NfseEmissoes (
                id_fatura, numero_rps, serie_rps, ambiente, 
                valor_servico, aliquota_iss, iss_retido, item_lista_servico, discriminacao,
                xml_envio, xml_retorno, status, data_emissao
            ) VALUES (
                '$id_fatura', '$nextRps', '{$config['serie_rps']}', '$ambiente', 
                '$valor_servico', '$aliquota', '$iss_retido_val', '$item_lista', '$discriminacao_esc',
                '$xml_envio_esc', '$xml_retorno_esc', '$status', NOW()
            )";
            $insertResult = DBExecute($link, $queryLog);

            if (!$insertResult) {
                $response['success'] = false;
                $response['message'] = "Erro CRÍTICO no Banco de Dados: " . mysqli_error($link);
                break;
            }

            if ($status == 'concluido') {
                if ($ambiente == 'producao') {
                    DBExecute($link, "UPDATE ConfiguracoesEmissor SET ultimo_rps_producao=$nextRps WHERE id_config={$config['id_config']}");
                } else {
                    DBExecute($link, "UPDATE ConfiguracoesEmissor SET ultimo_rps_homologacao=$nextRps WHERE id_config={$config['id_config']}");
                }

                // Atualiza data_emissao_nfse e possui_nfse na Fatura (com fallback resiliente)
                $resFaturaNfse = @DBExecute($link, "UPDATE Faturas SET data_emissao_nfse = NOW(), possui_nfse = 1 WHERE id_fatura = '$id_fatura'");
                if (!$resFaturaNfse) {
                    @DBExecute($link, "UPDATE Faturas SET possui_nfse = 1 WHERE id_fatura = '$id_fatura'");
                }

                $response['success'] = true;
                $response['message'] = "NFS-e Gerada com Sucesso! RPS $nextRps";
            } else {
                $response['success'] = false;
                $response['message'] = "Erro ao gerar NFS-e / Recusada.";
                $details = "";
                if (preg_match_all('/<Mensagem>(.*?)<\/Mensagem>/', $responseSoap, $matches)) {
                    $details = implode("\n", $matches[1]);
                } elseif (preg_match('/<Fault>(.*?)<\/Fault>/s', $responseSoap, $matches)) {
                    $details = strip_tags($matches[1]);
                }
                if (empty($details)) {
                    $details = "Arquivo em desacordo com o XML Schema.";
                }
                $response['details'] = $details;
                $response['debug_xml'] = $xmlSigned;
                $response['debug_input'] = $inputApi;
            }
            break;

        case 'excluir_arquivo_fatura':
            $id_arquivo = $_POST['id_arquivo'] ?? '';
            $id_fatura = $_POST['id_fatura'] ?? ''; // Para validação extra de segurança, se desejar

            if (empty($id_arquivo)) {
                $response['message'] = "ID do arquivo obrigatório.";
            } else {
                $id_arquivo = mysqli_real_escape_string($link, $id_arquivo);

                // Excluir apenas o vínculo ou o arquivo todo?
                // Como um arquivo pode (teoricamente) ser usado em outros lugares no futuro, 
                // mas aqui é 1:1 na prática, vamos remover o arquivo da tabela Arquivos também.
                // O ON DELETE CASCADE na FK cuidará do vínculo.

                // Nota: Não estamos deletando do Bucket S3 pq a URL pré-autenticada pode não ter permissão DELETE
                // e não temos SDK configurado, apenas URL mágica.

                $query = "DELETE FROM Arquivos WHERE id_arquivo = '$id_arquivo'";

                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Arquivo desvinculado com sucesso!";
                } else {
                    $response['message'] = "Erro ao excluir arquivo: " . mysqli_error($link);
                }
            }
            break;

        case 'excluir_fatura':
            $id_fatura = $_POST['id_fatura'] ?? '';
            if (empty($id_fatura)) {
                $response['message'] = "ID da fatura é obrigatório.";
                break;
            }

            $id_safe = mysqli_real_escape_string($link, $id_fatura);

            // 1. Busca a fatura para verificar existência e status
            $qFatura = "SELECT status, id_cliente FROM Faturas WHERE id_fatura = '$id_safe'";
            $rFatura = DBExecute($link, $qFatura);
            if (!$rFatura || mysqli_num_rows($rFatura) === 0) {
                $response['message'] = "Fatura não encontrada.";
                break;
            }

            $faturaData = mysqli_fetch_assoc($rFatura);
            if ($faturaData['status'] === 'Liquidada') {
                $response['message'] = "Faturas já liquidadas não podem ser excluídas.";
                break;
            }

            $id_cliente = $faturaData['id_cliente'];

            // 2. Limpeza de Arquivos vinculados (busca id_arquivo em FaturaArquivos)
            $qArquivos = "SELECT id_arquivo FROM FaturaArquivos WHERE id_fatura = '$id_safe'";
            $rArquivos = DBExecute($link, $qArquivos);
            $arquivosIds = [];
            if ($rArquivos) {
                while ($rowArq = mysqli_fetch_assoc($rArquivos)) {
                    $arquivosIds[] = "'" . mysqli_real_escape_string($link, $rowArq['id_arquivo']) . "'";
                }
            }

            // Exclui vínculos e arquivos órfãos
            DBExecute($link, "DELETE FROM FaturaArquivos WHERE id_fatura = '$id_safe'");
            if (!empty($arquivosIds)) {
                $idsList = implode(',', $arquivosIds);
                DBExecute($link, "DELETE FROM Arquivos WHERE id_arquivo IN ($idsList) AND id_arquivo NOT IN (SELECT id_arquivo FROM AtendimentoArquivos)");
            }

            // 3. Exclui registros das demais tabelas relacionais
            DBExecute($link, "DELETE FROM ItensFatura WHERE id_fatura = '$id_safe'");
            DBExecute($link, "DELETE FROM Pagamentos WHERE id_fatura = '$id_safe'");
            DBExecute($link, "DELETE FROM NfseEmissoes WHERE id_fatura = '$id_safe'");
            DBExecute($link, "DELETE FROM nf_contadev_sync WHERE id_fatura = '$id_safe'");
            DBExecute($link, "DELETE FROM contadev_logs WHERE id_fatura = '$id_safe'");

            // 4. Exclui a fatura principal
            if (DBExecute($link, "DELETE FROM Faturas WHERE id_fatura = '$id_safe'")) {
                $response['success'] = true;
                $response['message'] = "Fatura excluída com sucesso!";
                $response['id_cliente'] = $id_cliente;
            } else {
                $response['message'] = "Erro ao excluir fatura: " . mysqli_error($link);
            }
            break;





        case 'consultar_url_nfse':
            $id_emissao = $_POST['id_emissao'];
            $res = DBExecute($link, "SELECT * FROM NfseEmissoes WHERE id_emissao = '$id_emissao'");
            $emissao = mysqli_fetch_assoc($res);
            if (!$emissao) {
                $response['success'] = false;
                $response['message'] = 'Emissão não encontrada';
                break;
            }

            $resConf = DBExecute($link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
            $config = mysqli_fetch_assoc($resConf);

            $pfxContent = null;
            if (!empty($config['certificado_pfx_base64'])) {
                $pfxContent = base64_decode($config['certificado_pfx_base64']);
            } elseif (!empty($config['caminho_certificado'])) {
                $pfxPath = $config['caminho_certificado'];
                $finalPfxPath = null;
                if (file_exists($pfxPath)) {
                    $finalPfxPath = $pfxPath;
                } elseif (file_exists(__DIR__ . '/' . $pfxPath)) {
                    $finalPfxPath = __DIR__ . '/' . $pfxPath;
                } elseif (file_exists(__DIR__ . '/../' . $pfxPath)) {
                    $finalPfxPath = __DIR__ . '/../' . $pfxPath;
                }
                if ($finalPfxPath) {
                    $pfxContent = file_get_contents($finalPfxPath);
                }
            }

            if (!$pfxContent) {
                $response['message'] = "Certificado não encontrado.";
                break;
            }

            $certs = [];

            // Decrypt Password
            $senhaDecrypted = null;
            try {
                $senhaDecrypted = \EncryptionHelper::decrypt($config['senha_certificado']);
            } catch (Exception $e) {
                // Ignore error, try raw
            }

            // Attempt 1: Decrypted
            $status = false;
            if ($senhaDecrypted) {
                $status = openssl_pkcs12_read($pfxContent, $certs, $senhaDecrypted);
            }

            // Attempt 2: Raw (Fallback)
            if (!$status) {
                $status = openssl_pkcs12_read($pfxContent, $certs, $config['senha_certificado']);
            }

            if (!$status) {
                $response['message'] = "Senha do certificado incorreta.";
                break;
            }

            // Define function to perform request
            $performConsultarUrl = function ($useRpsOnly) use ($config, $emissao, $certs) {
                // Input
                $inputApi = [
                    'cnpj' => $config['cnpj'],
                    'im' => $config['inscricao_municipal'],
                    // If useRpsOnly is true, we force numero_nota to be empty/0 so it falls back to RPS
                    'numero_nota' => $useRpsOnly ? '0' : ($emissao['numero_nota'] ?: '0'),
                    'numero_rps' => $emissao['numero_rps'],
                    'serie_rps' => $emissao['serie_rps'] ?? '8',
                    'tipo_rps' => $emissao['tipo_rps'] ?? '1'
                ];

                if (!function_exists('buildConsultarUrlNfseXml')) {
                    require_once '../nfse_test/api.php';
                }

                $xmlComponents = buildConsultarUrlNfseXml($inputApi);
                $rootXml = $xmlComponents['root'];
                $rootId = $xmlComponents['id'];

                $variation = 'support_combo';
                $uriRef = "#" . $rootId;

                if ($variation === 'support_combo') {
                    $uriRef = "";
                    if (!empty($rootId)) {
                        $rootXml = str_replace(' Id="' . $rootId . '"', '', $rootXml);
                    }
                }

                $finalXml = assinarRoot($rootXml, $certs, $uriRef, $variation);

                $endpoint = ($emissao['ambiente'] == 'producao')
                    ? 'https://df.issnetonline.com.br/webservicenfse204/nfse.asmx'
                    : 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';

                return sendSoap($finalXml, $endpoint, $certs, $variation, 'consultar_url', true);
            };

            // Attempt 1: Default (Prioritize Note Number)
            $resultSoap = $performConsultarUrl(false);
            $respXml = $resultSoap['response_body'] ?? '';

            // Retry Logic: If Not Found (E212) and we tried Note Number, try RPS
            // Only retry if we actually have an RPS number
            if ((strpos($respXml, 'E212') !== false || strpos($respXml, 'não encontrada') !== false) && !empty($emissao['numero_rps'])) {
                $resultSoap = $performConsultarUrl(true); // Force RPS
                $respXml = $resultSoap['response_body'] ?? '';
            }

            // Parse Final Response
            if (strpos($respXml, '<Fault>') !== false) {
                $response['success'] = false;
                $response['message'] = 'Erro na API Prefeitura (Fault).';
                $response['debug_xml'] = $respXml;
            } elseif (strpos($respXml, 'ConsultarUrlNfseResposta') !== false) {
                $dom = new DOMDocument;
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                @$dom->loadXML($respXml);
                $formattedXml = $dom->saveXML();

                $url = '';
                if (preg_match('/<UrlVisualizacaoNfse>(.*?)<\/UrlVisualizacaoNfse>/', $respXml, $m)) {
                    $url = $m[1];
                } elseif (preg_match('/<Url>(.*?)<\/Url>/', $respXml, $m)) {
                    $url = $m[1];
                } elseif (preg_match('/<UrlNfse>(.*?)<\/UrlNfse>/', $respXml, $m)) {
                    $url = $m[1];
                }

                if ($url) {
                    $url = htmlspecialchars_decode($url); // Decode entities
                    $url_esc = mysqli_real_escape_string($link, $url);
                    DBExecute($link, "UPDATE NfseEmissoes SET url_pdf = '$url_esc' WHERE id_emissao = '$id_emissao'");
                    $response['success'] = true;
                    $response['message'] = "URL encontrada: $url";
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Retorno recebido, mas URL não identificada.';
                    $response['debug_xml'] = $formattedXml;
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Erro no Retorno da Consulta.';
                $response['debug_xml'] = $respXml;
            }
            break;

        // --- MÓDULO VET: ARQUIVOS E RECEITAS ---

        case 'upload_arquivo_atendimento':
            $id_atendimento = $_POST['id_atendimento'] ?? '';

            if (empty($id_atendimento) || !isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = "Atendimento ou arquivo inválido.";
                break;
            }

            $maxSize = 10 * 1024 * 1024;
            if ($_FILES['arquivo']['size'] > $maxSize) {
                $response['message'] = "Arquivo excede 10MB.";
                break;
            }

            $nomeOriginal = $_FILES['arquivo']['name'];
            $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
            $nomeArquivoBucket = 'arquivos_vet/' . time() . '_' . $id_atendimento . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extensao;

            $pathConfig = __DIR__ . '/../oci-s3.php';
            if (file_exists($pathConfig)) {
                include $pathConfig;
            } else {
                $response['message'] = "Erro na conf de storage.";
                break;
            }

            if (!isset($urlBucketPreauth)) {
                $response['message'] = "URL Bucket não definida.";
                break;
            }

            $urlUpload = $urlBucketPreauth . $nomeArquivoBucket;
            $caminhoTemp = $_FILES['arquivo']['tmp_name'];
            $tamanhoBytes = $_FILES['arquivo']['size'];
            $mimeType = mime_content_type($caminhoTemp);
            $conteudoArquivo = file_get_contents($caminhoTemp);

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
                $link = DBConnect();
                if (!$link)
                    $link = DBConnect();

                $nomeOriginalSafe = mysqli_real_escape_string($link, $nomeOriginal);
                $urlPublicaSafe = mysqli_real_escape_string($link, $urlUpload);
                $mimeTypeSafe = mysqli_real_escape_string($link, $mimeType);
                $descricao = $_POST['descricao'] ?? '';
                $descricaoSafe = mysqli_real_escape_string($link, $descricao);

                $queryArquivo = "INSERT INTO Arquivos (nome_original, url_publica, tamanho_bytes, tipo_mime, descricao) 
                                 VALUES ('$nomeOriginalSafe', '$urlPublicaSafe', '$tamanhoBytes', '$mimeTypeSafe', '$descricaoSafe')";

                if (DBExecute($link, $queryArquivo)) {
                    $idArquivo = mysqli_insert_id($link);
                    $idAtendimentoSafe = mysqli_real_escape_string($link, $id_atendimento);
                    $queryVinculo = "INSERT INTO AtendimentoArquivos (id_atendimento, id_arquivo) VALUES ('$idAtendimentoSafe', '$idArquivo')";

                    if (DBExecute($link, $queryVinculo)) {
                        $response['success'] = true;
                        $response['message'] = "Arquivo anexado ao atendimento!";
                        // Retornar dados para atualizar a lista via JS
                        $response['arquivo'] = [
                            'id_arquivo' => $idArquivo,
                            'nome_original' => $nomeOriginal,
                            'url_publica' => $urlUpload,
                            'tamanho_bytes' => $tamanhoBytes
                        ];
                    } else {
                        $response['message'] = "Erro ao vincular no atendimento: " . mysqli_error($link);
                    }
                } else {
                    $response['message'] = "Erro db arquivo: " . mysqli_error($link);
                }
            } else {
                $response['message'] = "Upload Cloud falhou. HTTP $httpCode";
            }
            break;

        case 'get_atendimento_arquivos':
            $id_atendimento = $_POST['id_atendimento'] ?? '';
            if (empty($id_atendimento)) {
                $response['message'] = "ID Atendimento invalido";
            } else {
                $id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
                $query = "SELECT A.id_arquivo, A.nome_original, A.url_publica, A.tamanho_bytes, A.data_upload, A.descricao 
                          FROM Arquivos A
                          JOIN AtendimentoArquivos AA ON A.id_arquivo = AA.id_arquivo
                          WHERE AA.id_atendimento = '$id_atendimento'
                          ORDER BY A.data_upload DESC";
                $result = DBExecute($link, $query);
                $arquivos = [];
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $arquivos[] = $row;
                    }
                    $response['success'] = true;
                    $response['data'] = $arquivos;
                }
            }
            break;

        case 'excluir_arquivo_atendimento':
            $id_arquivo = $_POST['id_arquivo'] ?? '';
            if (empty($id_arquivo)) {
                $response['message'] = "ID necessário";
            } else {
                $id_arquivo = mysqli_real_escape_string($link, $id_arquivo);
                // Remove de Arquivos. A FK Cascade limpa AtendimentoArquivos.
                $query = "DELETE FROM Arquivos WHERE id_arquivo = '$id_arquivo'";
                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Arquivo removido.";
                } else {
                    $response['message'] = "Erro ao excluir: " . mysqli_error($link);
                }
            }
            break;

        case 'salvar_receita':
            $id_atendimento = $_POST['id_atendimento'] ?? '';
            $id_receita = $_POST['id_receita'] ?? ''; // New param for Edit
            $itens = json_decode($_POST['itens'] ?? '[]', true);
            $observacoes = $_POST['observacoes'] ?? '';

            if (empty($id_atendimento)) {
                $response['message'] = "Atendimento obrigatório.";
                break;
            }
            if (empty($itens) || !is_array($itens)) {
                $response['message'] = "Nenhum item na receita.";
                break;
            }

            $id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
            $observacoes = mysqli_real_escape_string($link, $observacoes);

            if (!empty($id_receita)) {
                // UPDATE MODE
                $id_receita = mysqli_real_escape_string($link, $id_receita);
                $qUpdate = "UPDATE Receitas SET observacoes = '$observacoes' WHERE id_receita = '$id_receita' AND id_atendimento = '$id_atendimento'";
                if (!DBExecute($link, $qUpdate)) {
                    $response['message'] = "Erro ao atualizar receita: " . mysqli_error($link);
                    break;
                }
                // Clear old items to replace with new ones
                DBExecute($link, "DELETE FROM ItensReceita WHERE id_receita = '$id_receita'");
                $idReceita = $id_receita; // Use existing ID
                $actionMsg = "atualizada";
            } else {
                // INSERT MODE
                $queryReceita = "INSERT INTO Receitas (id_atendimento, observacoes, data_receita) VALUES ('$id_atendimento', '$observacoes', NOW())";
                if (DBExecute($link, $queryReceita)) {
                    $idReceita = mysqli_insert_id($link);
                    $actionMsg = "criada";
                } else {
                    $response['message'] = "Erro ao criar receita: " . mysqli_error($link);
                    break;
                }
            }

            // Insert Items (Common for both)
            foreach ($itens as $item) {
                $nome = mysqli_real_escape_string($link, $item['nome_medicamento'] ?? '');
                $qtd = mysqli_real_escape_string($link, $item['quantidade'] ?? '');
                $uso = mysqli_real_escape_string($link, $item['uso'] ?? '');
                $cat = mysqli_real_escape_string($link, $item['categoria'] ?? 'Veterinaria');
                $pos = mysqli_real_escape_string($link, $item['posologia'] ?? '');

                $queryItem = "INSERT INTO ItensReceita (id_receita, nome_medicamento, quantidade, uso, categoria, posologia)
                              VALUES ('$idReceita', '$nome', '$qtd', '$uso', '$cat', '$pos')";
                DBExecute($link, $queryItem);
            }
            $response['success'] = true;
            $response['message'] = "Receita $actionMsg com sucesso!";
            break;

        case 'get_receitas_atendimento':
            $id_atendimento = $_POST['id_atendimento'] ?? '';
            if (empty($id_atendimento)) {
                $response['message'] = "ID necessário";
                break;
            }
            $id_atendimento = mysqli_real_escape_string($link, $id_atendimento);

            // Buscar receitas
            $qReceitas = "SELECT * FROM Receitas WHERE id_atendimento = '$id_atendimento' ORDER BY data_receita DESC";
            $resReceitas = DBExecute($link, $qReceitas);
            $receitas = [];
            while ($r = mysqli_fetch_assoc($resReceitas)) {
                $r['itens'] = [];
                // Buscar itens desta receita
                $idR = $r['id_receita'];
                $qItens = "SELECT * FROM ItensReceita WHERE id_receita = '$idR'";
                $resItens = DBExecute($link, $qItens);
                while ($i = mysqli_fetch_assoc($resItens)) {
                    $r['itens'][] = $i;
                }
                $receitas[] = $r;
            }
            $response['success'] = true;
            $response['data'] = $receitas;
            break;

        case 'excluir_receita':
            $id_receita = $_POST['id_receita'] ?? '';
            if (empty($id_receita)) {
                $response['message'] = "ID inválido";
                break;
            }
            $id_receita = mysqli_real_escape_string($link, $id_receita);
            $query = "DELETE FROM Receitas WHERE id_receita = '$id_receita'";
            if (DBExecute($link, $query)) {
                $response['success'] = true;
                $response['message'] = "Receita excluída.";
            } else {
                $response['message'] = "Erro ao excluir: " . mysqli_error($link);
            }
            break;

        case 'check_migrations_status':
            $migrationsDir = __DIR__ . '/../database/migrations/';
            if (!is_dir($migrationsDir)) {
                if (is_dir(__DIR__ . '/database/migrations/')) {
                    $migrationsDir = __DIR__ . '/database/migrations/';
                } else {
                    mkdir($migrationsDir, 0755, true);
                }
            }

            // Ensure table exists
            $link->query("CREATE TABLE IF NOT EXISTS migrations_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $files = glob($migrationsDir . '*.sql');
            $executed = [];
            $res = $link->query("SELECT migration_name FROM migrations_history");
            while ($row = $res->fetch_assoc())
                $executed[] = $row['migration_name'];

            $pending = 0;
            if ($files) {
                foreach ($files as $file) {
                    if (!in_array(basename($file), $executed))
                        $pending++;
                }
            }

            $response['success'] = true;
            $response['pending_count'] = $pending;
            break;

        case 'run_migrations':
            $migrationsDir = __DIR__ . '/../database/migrations/';
            if (!is_dir($migrationsDir)) {
                if (is_dir(__DIR__ . '/database/migrations/')) {
                    $migrationsDir = __DIR__ . '/database/migrations/';
                } else {
                    mkdir($migrationsDir, 0755, true);
                }
            }

            // Ensure table exists
            $link->query("CREATE TABLE IF NOT EXISTS migrations_history (
                 id INT AUTO_INCREMENT PRIMARY KEY,
                 migration_name VARCHAR(255) NOT NULL UNIQUE,
                 executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
             )");

            $files = glob($migrationsDir . '*.sql');
            if ($files) {
                sort($files);
            } else {
                $files = [];
            }

            $executed = [];
            $res = $link->query("SELECT migration_name FROM migrations_history");
            while ($row = $res->fetch_assoc())
                $executed[] = $row['migration_name'];

            $logs = [];
            $pendingCount = 0;
            $errorOccurred = false;

            foreach ($files as $file) {
                $filename = basename($file);
                if (in_array($filename, $executed))
                    continue;

                $pendingCount++;
                $logs[] = "Migrating: $filename...";

                $content = file_get_contents($file);
                if (empty(trim($content))) {
                    $logs[] = "SKIPPED (Empty file).";
                    continue;
                }

                if (mysqli_multi_query($link, $content)) {
                    do {
                        if ($result = mysqli_store_result($link))
                            mysqli_free_result($result);
                    } while (mysqli_more_results($link) && mysqli_next_result($link));

                    if (mysqli_errno($link)) {
                        $logs[] = "ERROR: " . mysqli_error($link);
                        $errorOccurred = true;
                        break;
                    }

                    $filenameEscaped = mysqli_real_escape_string($link, $filename);
                    $link->query("INSERT INTO migrations_history (migration_name) VALUES ('$filenameEscaped')");
                    $logs[] = "DONE.";
                } else {
                    $logs[] = "ERROR executing query: " . mysqli_error($link);
                    $errorOccurred = true;
                    break;
                }
            }

            if ($pendingCount == 0)
                $logs[] = "Nenhuma migração pendente.";
            else
                $logs[] = "Migrações finalizadas.";

            $response['success'] = !$errorOccurred;
            $response['logs'] = $logs;
            break;

        // --- MODELOS DE DOCUMENTOS ---

        case 'get_modelos_documentos':
            $query = "SELECT * FROM ModelosDocumentos WHERE ativo = 1 ORDER BY titulo ASC";
            $result = DBExecute($link, $query);
            $modelos = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $modelos[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $modelos;
            break;

        case 'get_modelo_detalhes':
            $id = $_POST['id'] ?? 0;
            $id = mysqli_real_escape_string($link, $id);
            $query = "SELECT * FROM ModelosDocumentos WHERE id_modelo = '$id'";
            $result = DBExecute($link, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $response['success'] = true;
                $response['data'] = mysqli_fetch_assoc($result);
            } else {
                $response['message'] = "Modelo não encontrado.";
            }
            break;

        case 'get_modelo_vars_preview':
            $id_modelo = $_POST['id_modelo'] ?? 0;
            $id_atendimento = $_POST['id_atendimento'] ?? 0;
            $id_recorrencia = $_POST['id_recorrencia'] ?? 0;

            if (!$id_modelo || (!$id_atendimento && !$id_recorrencia)) {
                $response['message'] = "ID do modelo e (atendimento ou recorrência) necessários.";
                break;
            }

            // 1. Fetch Model Content
            $qm = "SELECT conteudo FROM ModelosDocumentos WHERE id_modelo = '$id_modelo'";
            $rm = DBExecute($link, $qm);
            $modelo = mysqli_fetch_assoc($rm);
            $texto = $modelo['conteudo'];

            // 2. Fetch Data
            $dados = [];
            if ($id_atendimento) {
                $qa = "SELECT a.*, 
                        p.nome as nome_pet, p.especie, p.raca, p.sexo, p.peso as peso_pet, p.data_nascimento as nascimento,
                        c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor, c.email as email_tutor, c.telefone as telefone_tutor,
                        v.nome as nome_vet, v.crmv as crmv_vet
                       FROM Atendimentos a
                       LEFT JOIN Pets p ON a.id_pet = p.id_pet
                       LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente
                       LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
                       WHERE a.id_atendimento = '$id_atendimento'";
                $ra = DBExecute($link, $qa);
                $dados = mysqli_fetch_assoc($ra);
            } elseif ($id_recorrencia) {
                $q = "SELECT r.*, 
                        c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor, c.email as email_tutor, c.telefone as telefone_tutor,
                        s.nome_servico
                        FROM Recorrencias r
                        LEFT JOIN Clientes c ON r.id_cliente = c.id_cliente
                        LEFT JOIN Servicos s ON r.id_servico = s.id_servico
                        WHERE r.id_recorrencia = '$id_recorrencia'";
                $r = DBExecute($link, $q);
                $dados = mysqli_fetch_assoc($r);

                // Defaults
                $dados['nome_pet'] = 'N/A';
                $dados['especie'] = 'N/A';
                $dados['raca'] = 'N/A';
                $dados['sexo'] = 'N/A';
                $dados['nome_vet'] = 'N/A';
                $dados['crmv_vet'] = 'N/A';
                $dados['nascimento'] = null;
            }

            // 2b. Company
            $qc = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
            $rc = DBExecute($link, $qc);
            $empresa = mysqli_fetch_assoc($rc);

            // Calculate Age
            $idade = 'N/I';
            $data_nascimento = '';
            if (!empty($dados['nascimento'])) {
                $nasc = new DateTime($dados['nascimento']);
                $hoje = new DateTime();
                $diff = $hoje->diff($nasc);
                $idade = $diff->y . ' anos';
                if ($diff->y < 1)
                    $idade = $diff->m . ' meses';
                $data_nascimento = date('d/m/Y', strtotime($dados['nascimento']));
            }

            // Calculate City
            $nomeCidade = 'São Paulo';
            if (!empty($empresa['codigo_municipio'])) {
                // Try helper
                $ibgeCidade = AppHelper::getCidadePorCodigo($empresa['codigo_municipio']);
                if ($ibgeCidade)
                    $nomeCidade = $ibgeCidade;
            }

            // Helper for CPF
            if (!function_exists('formatCpfCnpj_Preview')) {
                function formatCpfCnpj_Preview($pCpfCnpj)
                {
                    $cnpj_cpf = preg_replace("/\D/", '', $pCpfCnpj);
                    if (strlen($cnpj_cpf) === 11)
                        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
                    if (strlen($cnpj_cpf) === 14)
                        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
                    return $pCpfCnpj;
                }
            }

            // Map ALL available variables
            $vars = [
                '{{NOME_TUTOR}}' => $dados['nome_tutor'],
                '{{NOME_CLIENTE}}' => $dados['nome_tutor'],
                '{{CPF_TUTOR}}' => formatCpfCnpj_Preview($dados['cpf_tutor'] ?? ''),
                '{{CPF_CNPJ_CLIENTE}}' => formatCpfCnpj_Preview($dados['cpf_tutor'] ?? ''),
                '{{ENDERECO_TUTOR}}' => $dados['endereco_tutor'] ?? '',
                '{{ENDERECO_CLIENTE}}' => $dados['endereco_tutor'] ?? '',
                '{{EMAIL_CLIENTE}}' => $dados['email_tutor'] ?? '',
                '{{TELEFONE_CLIENTE}}' => $dados['telefone_tutor'] ?? '',
                '{{NOME_PET}}' => $dados['nome_pet'],
                '{{ESPECIE_PET}}' => $dados['especie'],
                '{{RACA_PET}}' => $dados['raca'],
                '{{NASCIMENTO_PET}}' => $data_nascimento,
                '{{IDADE_PET}}' => $idade,
                '{{PESO_PET}}' => $dados['peso'] ?? $dados['peso_pet'] ?? '',
                '{{SEXO_PET}}' => $dados['sexo'],
                '{{NOME_VET}}' => $dados['nome_vet'],
                '{{CRMV_VET}}' => $dados['crmv_vet'],
                '{{SERVICO_NOME}}' => $dados['nome_servico'] ?? '',
                '{{VALOR_CONTRATO}}' => isset($dados['valor_sugerido_recorrencia']) ? 'R$ ' . number_format($dados['valor_sugerido_recorrencia'], 2, ',', '.') : '',
                '{{DATA_INICIO}}' => isset($dados['data_inicio_cobranca']) ? date('d/m/Y', strtotime($dados['data_inicio_cobranca'])) : '',
                '{{DIA_VENCIMENTO}}' => isset($dados['data_inicio_cobranca']) ? date('d', strtotime($dados['data_inicio_cobranca'])) : '',
                '{{DESCRICAO_FISCAL}}' => $dados['descricao_fiscal'] ?? $dados['descricao_personalizada'] ?? '',
                '{{ISS_RETIDO}}' => (isset($dados['iss_retido']) && $dados['iss_retido'] == '1') ? 'Sim' : 'Não',
                '{{DATA_ATUAL}}' => date('d/m/Y'),
                '{{CIDADE_DATA}}' => $nomeCidade . ', ' . date('d/m/Y'),
                '{{TEXTO_PERSONALIZADO}}' => '',

                // Company / Emissor
                '{{EMPRESA_NOME}}' => $empresa['razao_social'] ?? '',
                '{{RAZAO_SOCIAL}}' => $empresa['razao_social'] ?? '', // Alias
                '{{NOME_FANTASIA}}' => $empresa['nome_fantasia'] ?? '',
                '{{EMPRESA_CNPJ}}' => formatCpfCnpj_Preview($empresa['cnpj'] ?? ''),
                '{{CNPJ_EMISSOR}}' => formatCpfCnpj_Preview($empresa['cnpj'] ?? ''), // Alias
                '{{EMPRESA_ENDERECO}}' => ($empresa['endereco'] ?? '') . ', ' . ($empresa['numero'] ?? '') . ' - ' . ($empresa['bairro'] ?? ''),
                '{{EMPRESA_CIDADE}}' => $nomeCidade,
                '{{EMPRESA_UF}}' => $empresa['uf'] ?? '',
                '{{EMPRESA_TELEFONE}}' => $empresa['telefone'] ?? '',
                '{{EMPRESA_EMAIL}}' => '',
                '{{EMPRESA_IE}}' => $empresa['inscricao_estadual'] ?? '',
                '{{EMPRESA_IM}}' => $empresa['inscricao_municipal'] ?? '',
            ];

            // Filter only used vars? Or return all?
            // Let's filter to only show relevant ones, by checking strpos
            $used_vars = [];
            foreach ($vars as $key => $val) {
                if (strpos($texto, $key) !== false) {
                    $used_vars[] = ['key' => $key, 'label' => str_replace(['{{', '}}', '_'], ['', '', ' '], $key), 'value' => $val];
                }
            }

            $response['success'] = true;
            $response['data'] = $used_vars;
            break;

        case 'salvar_modelo_documento':
            $id = $_POST['id'] ?? '';
            $titulo = mysqli_real_escape_string($link, $_POST['titulo'] ?? '');
            $conteudo = mysqli_real_escape_string($link, $_POST['conteudo'] ?? '');
            $tipo = mysqli_real_escape_string($link, $_POST['tipo'] ?? 'Geral');

            if (empty($titulo)) {
                $response['message'] = "Título é obrigatório.";
                break;
            }

            if (!empty($id)) {
                $id = mysqli_real_escape_string($link, $id);
                $query = "UPDATE ModelosDocumentos SET titulo='$titulo', conteudo='$conteudo', tipo='$tipo' WHERE id_modelo='$id'";
            } else {
                $query = "INSERT INTO ModelosDocumentos (titulo, conteudo, tipo) VALUES ('$titulo', '$conteudo', '$tipo')";
            }

            if (DBExecute($link, $query)) {
                $response['success'] = true;
                $response['message'] = "Modelo salvo com sucesso!";
            } else {
                $response['message'] = "Erro ao salvar modelo: " . mysqli_error($link);
            }
            break;


        case 'excluir_modelo_documento':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                $response['message'] = "ID obrigatório.";
                break;
            }
            $id = mysqli_real_escape_string($link, $id);
            // Soft delete
            $query = "UPDATE ModelosDocumentos SET ativo = 0 WHERE id_modelo = '$id'";
            if (DBExecute($link, $query)) {
                $response['success'] = true;
                $response['message'] = "Modelo excluído com sucesso!";
            } else {
                $response['message'] = "Erro ao excluir: " . mysqli_error($link);
            }
            break;

        case 'get_documentos_emitidos':
            $id_atendimento = $_POST['id_atendimento'] ?? 0;
            $id_recorrencia = $_POST['id_recorrencia'] ?? 0;

            if (!$id_atendimento && !$id_recorrencia) {
                $response['message'] = "ID Atendimento ou Recorrência obrigatório.";
            } else {
                $id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
                $id_recorrencia = mysqli_real_escape_string($link, $id_recorrencia);

                $whereClause = "";
                if ($id_atendimento) {
                    $whereClause = "d.id_atendimento = '$id_atendimento'";
                } elseif ($id_recorrencia) {
                    $whereClause = "d.id_recorrencia = '$id_recorrencia'";
                }

                $query = "SELECT d.*, u.nome as nome_emissor 
                          FROM DocumentosEmitidos d
                          LEFT JOIN Usuarios u ON d.usuario_emissor = u.id_usuario
                          WHERE $whereClause
                          ORDER BY d.data_emissao DESC";
                $result = DBExecute($link, $query);
                $docs = [];
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $docs[] = $row;
                    }
                    $response['success'] = true;
                    $response['data'] = $docs;
                } else {
                    $response['message'] = "Erro ao buscar documentos: " . mysqli_error($link);
                }
            }
            break;

        case 'save_document_emitted':
            // Logic duplicated/adapted from documento_print.php to support AJAX saving without opening new tab
            $id_atendimento = $_POST['id_atendimento'] ?? 0;
            $id_recorrencia = $_POST['id_recorrencia'] ?? 0;
            $id_modelo = $_POST['id_modelo'] ?? 0;
            $titulo_custom = $_POST['titulo_custom'] ?? '';
            $overrides = $_POST['overrides'] ?? [];

            if ((!$id_atendimento && !$id_recorrencia) || !$id_modelo) {
                $response['message'] = "Parâmetros inválidos.";
                break;
            }

            $id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
            $id_recorrencia = mysqli_real_escape_string($link, $id_recorrencia);
            $id_modelo = mysqli_real_escape_string($link, $id_modelo);

            // 1. Fetch Data (Similar to documento_print.php)
            $dados = [];
            if ($id_atendimento) {
                $q = "SELECT a.*, p.id_cliente as pet_id_cliente, 
                        p.nome as nome_pet, p.especie, p.raca, p.sexo, p.peso as peso_pet, p.data_nascimento as nascimento,
                        c.id_cliente as client_id_final, c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor, c.email as email_tutor, c.telefone as telefone_tutor,
                        v.nome as nome_vet, v.crmv as crmv_vet
                        FROM Atendimentos a
                        LEFT JOIN Pets p ON a.id_pet = p.id_pet
                        LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente
                        LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
                        WHERE a.id_atendimento = '$id_atendimento'";
                $r = DBExecute($link, $q);
                $dados = mysqli_fetch_assoc($r);
                $dados['id_cliente'] = $dados['pet_id_cliente'] ?? $dados['client_id_final'];
            } elseif ($id_recorrencia) {
                $q = "SELECT r.*, r.id_cliente as rec_id_cliente,
                        c.id_cliente as client_id_final, c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor, c.email as email_tutor, c.telefone as telefone_tutor,
                        s.nome_servico
                        FROM Recorrencias r
                        LEFT JOIN Clientes c ON r.id_cliente = c.id_cliente
                        LEFT JOIN Servicos s ON r.id_servico = s.id_servico
                        WHERE r.id_recorrencia = '$id_recorrencia'";
                $r = DBExecute($link, $q);
                $dados = mysqli_fetch_assoc($r);
                $dados['id_cliente'] = $dados['rec_id_cliente'] ?? $dados['client_id_final'];

                // Defaults
                $dados['nome_pet'] = 'N/A';
                $dados['especie'] = 'N/A';
                $dados['raca'] = 'N/A';
                $dados['sexo'] = 'N/A';
                $dados['nome_vet'] = 'N/A';
                $dados['crmv_vet'] = 'N/A';
                $dados['nascimento'] = null;
            }

            // 2. Fetch Model
            $q_mod = "SELECT * FROM ModelosDocumentos WHERE id_modelo = '$id_modelo'";
            $r_mod = DBExecute($link, $q_mod);
            $modelo = mysqli_fetch_assoc($r_mod);

            // 3. Variables
            // We need to construct $vars array.
            // Simplified version or full? Full version logic from documento_print.php is best but lengthy.
            // Since this is for SAVING the content, we must render it fully.

            // Helper for Date/Age
            $idade = 'N/I';
            $data_nascimento = '';
            if (!empty($dados['nascimento'])) {
                $nasc = new DateTime($dados['nascimento']);
                $hoje = new DateTime();
                $diff = $hoje->diff($nasc);
                $idade = $diff->y . ' anos';
                if ($diff->y < 1)
                    $idade = $diff->m . ' meses';
                $data_nascimento = date('d/m/Y', strtotime($dados['nascimento']));
            }

            // Helper for CPF
            function formatCpfCnpj_App($pCpfCnpj)
            {
                $cnpj_cpf = preg_replace("/\D/", '', $pCpfCnpj);
                if (strlen($cnpj_cpf) === 11)
                    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
                if (strlen($cnpj_cpf) === 14)
                    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
                return $pCpfCnpj;
            }

            // Config for Logo/City
            $q_conf = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
            $r_conf = DBExecute($link, $q_conf);
            $empresa = mysqli_fetch_assoc($r_conf);

            $nomeCidade = 'São Paulo';
            if (!empty($empresa['codigo_municipio'])) {
                $ibgeCidade = AppHelper::getCidadePorCodigo($empresa['codigo_municipio']);
                if ($ibgeCidade)
                    $nomeCidade = $ibgeCidade;
            }

            // Logo URL logic (simplified for saving, usually consistent)
            $basePath = '../../'; // Relative to modules/Vet/
            // Actually, here we are in app.php (root). We don't need relative path magic as much, but stored content might expect it if used in print.
            // documento_print uses relative path because it's in modules/Vet/.
            // The stored content is HTML. `documento_print.php` renders it.
            // If we store with `../../`, it works if printed from `modules/Vet/`.
            // But if printed from elsewhere, it breaks.
            // Best to store absolute path or relate to root?
            // `documento_print.php` sets `{{LOGO_URL}}` dynamically.
            // Wait! `documento_print.php` REPLACES variables at runtime (render time).
            // DOES IT?
            // `documento_print.php` lines 195-202: It replaces keys in `$modelo['conteudo']` with values.
            // AND THEN SAVES THE RESULT ($conteudo_final) to database!
            // So yes, we ARE saving the RENDERED content (snapshot).
            // So we MUST replace variables here too.

            $logo_url = '';
            if (!empty($empresa['logo_url'])) {
                $logo_url = '../../' . $empresa['logo_url']; // Keep relative to modules/Vet/ for compatibility with print view if it shares base
            } else {
                $logo_url = '../../assets/img/logo_dino.png';
            }

            $vars = [
                '{{DATA_ATUAL}}' => date('d/m/Y'),
                '{{HORA_ATUAL}}' => date('H:i'),
                '{{CIDADE_DATA}}' => $nomeCidade . ', ' . date('d/m/Y'),
                '{{LOGO_URL}}' => $logo_url,

                // Company / Emissor
                '{{EMPRESA_NOME}}' => $empresa['razao_social'] ?? '',
                '{{RAZAO_SOCIAL}}' => $empresa['razao_social'] ?? '', // Alias
                '{{NOME_FANTASIA}}' => $empresa['nome_fantasia'] ?? '',
                '{{EMPRESA_CNPJ}}' => formatCpfCnpj_App($empresa['cnpj'] ?? ''),
                '{{CNPJ_EMISSOR}}' => formatCpfCnpj_App($empresa['cnpj'] ?? ''), // Alias
                '{{EMPRESA_ENDERECO}}' => ($empresa['endereco'] ?? '') . ', ' . ($empresa['numero'] ?? '') . ' - ' . ($empresa['bairro'] ?? ''),
                '{{EMPRESA_CIDADE}}' => $nomeCidade,
                '{{EMPRESA_UF}}' => $empresa['uf'] ?? '',
                '{{EMPRESA_TELEFONE}}' => $empresa['telefone'] ?? '',
                '{{EMPRESA_EMAIL}}' => '',
                '{{EMPRESA_IE}}' => $empresa['inscricao_estadual'] ?? '',
                '{{EMPRESA_IM}}' => $empresa['inscricao_municipal'] ?? '',

                '{{NOME_TUTOR}}' => $dados['nome_tutor'],
                '{{NOME_CLIENTE}}' => $dados['nome_tutor'],
                '{{CPF_TUTOR}}' => formatCpfCnpj_App($dados['cpf_tutor'] ?? ''),
                '{{CPF_CNPJ_CLIENTE}}' => formatCpfCnpj_App($dados['cpf_tutor'] ?? ''),
                '{{ENDERECO_TUTOR}}' => $dados['endereco_tutor'] ?? '',
                '{{ENDERECO_CLIENTE}}' => $dados['endereco_tutor'] ?? '',
                '{{EMAIL_CLIENTE}}' => $dados['email_tutor'] ?? '',
                '{{TELEFONE_CLIENTE}}' => $dados['telefone_tutor'] ?? '',
                '{{NOME_PET}}' => $dados['nome_pet'],
                '{{ESPECIE_PET}}' => $dados['especie'],
                '{{RACA_PET}}' => $dados['raca'],
                '{{NASCIMENTO_PET}}' => $data_nascimento,
                '{{IDADE_PET}}' => $idade,
                '{{PESO_PET}}' => $dados['peso'] ?? $dados['peso_pet'] ?? '',
                '{{SEXO_PET}}' => $dados['sexo'],
                '{{NOME_VET}}' => $dados['nome_vet'],
                '{{CRMV_VET}}' => $dados['crmv_vet'],
                '{{SERVICO_NOME}}' => $dados['nome_servico'] ?? '',
                '{{VALOR_CONTRATO}}' => isset($dados['valor_sugerido_recorrencia']) ? 'R$ ' . number_format($dados['valor_sugerido_recorrencia'], 2, ',', '.') : '',
                '{{DATA_INICIO}}' => isset($dados['data_inicio_cobranca']) ? date('d/m/Y', strtotime($dados['data_inicio_cobranca'])) : '',
                '{{DIA_VENCIMENTO}}' => isset($dados['data_inicio_cobranca']) ? date('d', strtotime($dados['data_inicio_cobranca'])) : '',
                '{{DESCRICAO_FISCAL}}' => $dados['descricao_fiscal'] ?? $dados['descricao_personalizada'] ?? '',
                '{{ISS_RETIDO}}' => (isset($dados['iss_retido']) && $dados['iss_retido'] == '1') ? 'Sim' : 'Não',
                '{{TEXTO_PERSONALIZADO}}' => '',
            ];

            // Overrides
            if (is_array($overrides)) {
                foreach ($overrides as $key => $val) {
                    if (array_key_exists($key, $vars)) {
                        $vars[$key] = $val;
                    }
                }
            }

            // Replace
            $conteudo_final = $modelo['conteudo'];
            foreach ($vars as $key => $val) {
                $conteudo_final = str_replace($key, $val, $conteudo_final);
            }

            // Title
            $titulo_final = !empty($titulo_custom) ? $titulo_custom : $modelo['titulo'];

            // Save
            $id_cliente_val = $dados['id_cliente'] ?? 'NULL';
            $id_pet_val = isset($dados['id_pet']) ? $dados['id_pet'] : 'NULL';
            $id_atend_val = $id_atendimento ? $id_atendimento : 'NULL';
            $id_rec_val = $id_recorrencia ? $id_recorrencia : 'NULL';
            $usuario_id = $_SESSION['usuario_id'] ?? 'NULL';
            $tipo = mysqli_real_escape_string($link, $modelo['tipo']);
            $conteudo_html_safe = mysqli_real_escape_string($link, $conteudo_final);
            $texto_personalizado_safe = mysqli_real_escape_string($link, $vars['{{TEXTO_PERSONALIZADO}}'] ?? '');
            $titulo_final_safe = mysqli_real_escape_string($link, $titulo_final);

            $qSave = "INSERT INTO DocumentosEmitidos (id_cliente, id_pet, id_atendimento, id_recorrencia, titulo, tipo, conteudo_html, texto_personalizado, data_emissao, usuario_emissor)
                      VALUES ('$id_cliente_val', $id_pet_val, $id_atend_val, $id_rec_val, '$titulo_final_safe', '$tipo', '$conteudo_html_safe', '$texto_personalizado_safe', NOW(), $usuario_id)";

            if (DBExecute($link, $qSave)) {
                $response['success'] = true;
                $response['message'] = "Documento salvo no histórico.";
            } else {
                $response['message'] = "Erro ao salvar: " . mysqli_error($link);
            }
            break;

        case 'excluir_documento_emitido':
            $id = $_POST['id_documento'] ?? 0;
            if (!$id) {
                $response['message'] = "ID obrigatório.";
            } else {
                $id = mysqli_real_escape_string($link, $id);
                $query = "DELETE FROM DocumentosEmitidos WHERE id_documento_emitido = '$id'";
                if (DBExecute($link, $query)) {
                    $response['success'] = true;
                    $response['message'] = "Documento excluído com sucesso!";
                } else {
                    $response['message'] = "Erro ao excluir: " . mysqli_error($link);
                }
            }
            break;

        // --- GESTÃO DE USUÁRIOS ---
        case 'get_usuarios':
            $query = "SELECT id_usuario, nome, email, nivel_acesso FROM Usuarios ORDER BY nome ASC";
            $result = DBExecute($link, $query);
            $usuarios = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $usuarios[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $usuarios;
            } else {
                $response['message'] = "Erro ao buscar usuários: " . mysqli_error($link);
            }
            break;

        case 'save_usuario':
            $id_usuario = $_POST['id_usuario'] ?? '';
            $nome = mysqli_real_escape_string($link, $_POST['nome'] ?? '');
            $email = mysqli_real_escape_string($link, $_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $nivel_acesso = mysqli_real_escape_string($link, $_POST['nivel_acesso'] ?? 'admin');

            if (empty($nome) || empty($email)) {
                $response['message'] = "Nome e Email são obrigatórios.";
                break;
            }

            if (!empty($id_usuario)) {
                // Update
                $id_usuario = mysqli_real_escape_string($link, $id_usuario);
                $query = "UPDATE Usuarios SET nome='$nome', email='$email', nivel_acesso='$nivel_acesso'";

                if (!empty($senha)) {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $query .= ", senha='$senhaHash'";
                }

                $query .= " WHERE id_usuario='$id_usuario'";
            } else {
                // Insert
                if (empty($senha)) {
                    $response['message'] = "Senha é obrigatória para novos usuários.";
                    break;
                }
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $query = "INSERT INTO Usuarios (nome, email, senha, nivel_acesso) VALUES ('$nome', '$email', '$senhaHash', '$nivel_acesso')";
            }

            if (DBExecute($link, $query)) {
                $response['success'] = true;
                $response['message'] = "Usuário salvo com sucesso!";
            } else {
                // Check for duplicate email error
                if (mysqli_errno($link) == 1062) {
                    $response['message'] = "Erro: Este email já está em uso.";
                } else {
                    $response['message'] = "Erro ao salvar usuário: " . mysqli_error($link);
                }
            }
            break;

        case 'excluir_usuario':
            $id_usuario = $_POST['id_usuario'] ?? '';
            if (empty($id_usuario)) {
                $response['message'] = "ID do usuário é obrigatório.";
                break;
            }
            $id_usuario = mysqli_real_escape_string($link, $id_usuario);

            // Prevent deleting self? (Optional but good practice)
            // But session handling is simpler here. 
            // Just delete.
            $query = "DELETE FROM Usuarios WHERE id_usuario='$id_usuario'";

            if (DBExecute($link, $query)) {
                $response['success'] = true;
                $response['message'] = "Usuário excluído com sucesso!";
            } else {
                $response['message'] = "Erro ao excluir usuário: " . mysqli_error($link);
            }
            break;

        case 'desvincular_gmail':
            $query = "UPDATE ConfiguracoesEmissor SET google_oauth_email = NULL, google_oauth_refresh_token = NULL";
            if (DBExecute($link, $query)) {
                $response['success'] = true;
                $response['message'] = "E-mail desvinculado com sucesso!";
            } else {
                $response['message'] = "Erro ao desvincular e-mail: " . mysqli_error($link);
            }
            break;

        case 'enviar_fatura_email':
            $id_fatura = $_POST['id_fatura'] ?? null;
            if (empty($id_fatura)) {
                $response['message'] = "ID da fatura é obrigatório.";
                break;
            }

            $idFatura_safe = mysqli_real_escape_string($link, $id_fatura);

            // 1. Busca fatura e e-mail do cliente
            $query = "SELECT F.*, C.nome AS nome_cliente, C.email AS email_cliente 
                      FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente 
                      WHERE F.id_fatura = '$idFatura_safe'";
            $result = DBExecute($link, $query);
            $fatura = mysqli_fetch_assoc($result);

            if (!$fatura) {
                $response['message'] = "Fatura não encontrada.";
                break;
            }

            if (empty($fatura['email_cliente'])) {
                $response['message'] = "Erro: O cliente " . htmlspecialchars($fatura['nome_cliente']) . " não possui e-mail cadastrado.";
                break;
            }

            // 2. Busca configurações da empresa
            $query_config = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
            $res_config = DBExecute($link, $query_config);
            $config_emissor = mysqli_fetch_assoc($res_config);

            if (!$config_emissor || empty($config_emissor['google_oauth_email'])) {
                $response['message'] = "Erro: Integração de e-mail de envio (Gmail) não configurada ou inativa.";
                break;
            }

            // 3. Gera token_acesso se estiver em branco
            $token = $fatura['token_acesso'] ?? '';
            if (empty($token)) {
                $token = bin2hex(random_bytes(16));
                $token_safe = mysqli_real_escape_string($link, $token);
                $query_update_token = "UPDATE Faturas SET token_acesso = '$token_safe' WHERE id_fatura = '$idFatura_safe'";
                DBExecute($link, $query_update_token);
            }

            // 4. Busca itens da fatura
            $items = [];
            $query_items = "SELECT I.*, S.nome_servico FROM ItensFatura I JOIN Servicos S ON I.id_servico = S.id_servico WHERE I.id_fatura = '$idFatura_safe'";
            $res_items = DBExecute($link, $query_items);
            while ($row = mysqli_fetch_assoc($res_items)) {
                $items[] = $row;
            }

            // Calcula total líquido
            $calcTotals = AppHelper::calculateFaturaTotals($link, $id_fatura);
            $valorLiquidoFatura = $calcTotals['valor_liquido'];

            // 5. Verifica se existe NFS-e emitida com sucesso
            $nfsePdfLink = "";
            $nfseXmlLink = "";
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];

            $queryNfse = "SELECT url_pdf, id_emissao FROM NfseEmissoes WHERE id_fatura = '$idFatura_safe' AND status = 'concluido' ORDER BY id_emissao DESC LIMIT 1";
            $resNfse = DBExecute($link, $queryNfse);
            if ($resNfse && mysqli_num_rows($resNfse) > 0) {
                $nfse = mysqli_fetch_assoc($resNfse);
                $nfsePdfLink = $nfse['url_pdf'] ?? "";
                $nfseXmlLink = "$protocol://$host/dinovatech/ver_nfse_xml.php?id=" . $nfse['id_emissao'];
            }

            // 6. Busca anexos vinculados (FaturaArquivos -> Arquivos)
            $attachments = [];
            $query_arquivos = "SELECT A.nome_original, A.url_publica, A.tipo_mime 
                               FROM Arquivos A 
                               JOIN FaturaArquivos FA ON A.id_arquivo = FA.id_arquivo 
                               WHERE FA.id_fatura = '$idFatura_safe'";
            $res_arquivos = DBExecute($link, $query_arquivos);
            while ($arq = mysqli_fetch_assoc($res_arquivos)) {
                $fileContent = @file_get_contents($arq['url_publica']);
                if ($fileContent !== false) {
                    $attachments[] = [
                        'name' => $arq['nome_original'],
                        'data' => $fileContent,
                        'mime' => $arq['tipo_mime']
                    ];
                }
            }

            // 7. Assunto e Corpo do E-mail
            $subject = "Fatura #" . $fatura['id_fatura'] . " - " . ($config_emissor['nome_fantasia'] ?? 'Dinovatech');
            $htmlBody = "";

            if (!empty($config_emissor['email_fatura_template_id'])) {
                $tempId = (int)$config_emissor['email_fatura_template_id'];
                $qTemp = "SELECT titulo, conteudo FROM ModelosDocumentos WHERE id_modelo = $tempId";
                $rTemp = DBExecute($link, $qTemp);
                if ($rTemp && mysqli_num_rows($rTemp) > 0) {
                    $templateData = mysqli_fetch_assoc($rTemp);
                    $subject = $templateData['titulo'] . " #" . $fatura['id_fatura'];
                    $htmlBody = $templateData['conteudo'];
                }
            }

            // Fallback premium template if not set
            if (empty($htmlBody)) {
                $htmlBody = '
                <div style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #ffffff; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="text-align: center; border-bottom: 2px solid #0891b2; padding-bottom: 20px; margin-bottom: 25px;">
                        <h2 style="color: #0891b2; font-weight: bold; margin: 0; font-size: 24px; text-transform: uppercase;">{{EMPRESA_NOME}}</h2>
                        <span style="color: #6b7280; font-size: 14px;">Documento Auxiliar de Cobrança</span>
                    </div>
                    
                    <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">Olá, <strong>{{NOME_CLIENTE}}</strong>!</p>
                    <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">Sua fatura foi emitida e já está disponível para visualização e pagamento. Veja os detalhes abaixo:</p>
                    
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                        <table style="width: 100%; font-size: 14px; border-collapse: collapse; color: #334155;">
                            <tr>
                                <td style="padding: 6px 0; font-weight: 600;">Código da Fatura:</td>
                                <td style="padding: 6px 0; text-align: right; font-family: monospace;">#{{FATURA_ID}}</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; font-weight: 600;">Data de Emissão:</td>
                                <td style="padding: 6px 0; text-align: right;">' . date('d/m/Y', strtotime($fatura['data_emissao'])) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; font-weight: 600;">Vencimento:</td>
                                <td style="padding: 6px 0; text-align: right; color: #b91c1c; font-weight: bold;">{{DATA_VENCIMENTO}}</td>
                            </tr>
                            <tr style="border-top: 1px solid #e2e8f0;">
                                <td style="padding: 12px 0 0 0; font-size: 16px; font-weight: 700; color: #0f172a;">Valor Total:</td>
                                <td style="padding: 12px 0 0 0; text-align: right; font-size: 18px; font-weight: 700; color: #0891b2;">R$ {{VALOR_FATURA}}</td>
                            </tr>
                        </table>
                    </div>

                    <div style="text-align: center; margin-bottom: 30px;">
                        <a href="{{LINK_PAGAMENTO}}" style="background-color: #0891b2; color: #ffffff; padding: 14px 30px; text-decoration: none; font-weight: bold; border-radius: 8px; display: inline-block; box-shadow: 0 4px 6px rgba(8,145,178,0.25); transition: background-color 0.2s;">Acessar Área de Pagamento (PIX / Código QR)</a>
                    </div>

                    <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #4b5563;">
                        <p style="margin: 0 0 10px 0; font-weight: 600;">Serviços Prestados:</p>
                        {{ITENS_FATURA}}
                    </div>
                    
                    {{BLOCO_NFSE}}
                    
                    <p style="color: #94a3b8; font-size: 12px; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;">Este é um e-mail automático enviado pelo sistema. Por favor, não responda diretamente a este endereço.</p>
                </div>';
            }

            // 8. Substitui placeholders no template carregado ou no fallback
            $itemsHtml = '<ul style="margin: 0; padding-left: 20px; line-height: 1.5;">';
            foreach ($items as $item) {
                $itemsHtml .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($item['nome_servico']) . ' (x' . $item['quantidade'] . ') - R$ ' . number_format($item['valor_unitario'] * $item['quantidade'], 2, ',', '.') . '</li>';
            }
            $itemsHtml .= '</ul>';

            $directPaymentLink = "$protocol://$host/cliente/fatura.php?id=" . $fatura['id_fatura'] . "&token=" . $token;
            $valorFormatado = number_format($valorLiquidoFatura, 2, ',', '.');
            $vencimentoFormatado = date('d/m/Y', strtotime($fatura['data_vencimento']));
            $empresaNome = $config_emissor['nome_fantasia'] ?? $config_emissor['razao_social'] ?? 'Dinovatech';

            $blocoNfse = "";
            if (!empty($nfsePdfLink)) {
                $blocoNfse = '
                <div style="border-top: 1px dashed #e2e8f0; margin-top: 25px; padding-top: 20px;">
                    <p style="color: #334155; font-size: 14px; font-weight: 600; margin: 0 0 10px 0;">Sua Nota Fiscal de Serviços Eletrônica (NFS-e) já foi emitida:</p>
                    <p style="margin: 5px 0; font-size: 14px;">
                        <a href="' . $nfsePdfLink . '" target="_blank" style="color: #dc2626; font-weight: 700; text-decoration: underline; margin-right: 20px;">Visualizar PDF da NFS-e</a>
                        <a href="' . $nfseXmlLink . '" target="_blank" style="color: #2563eb; font-weight: 700; text-decoration: underline;">Baixar XML</a>
                    </p>
                </div>';
            }

            $placeholders = [
                '{{NOME_CLIENTE}}' => htmlspecialchars($fatura['nome_cliente']),
                '{{CLIENTE_NOME}}' => htmlspecialchars($fatura['nome_cliente']),
                '{{VALOR_FATURA}}' => $valorFormatado,
                '{{DATA_VENCIMENTO}}' => $vencimentoFormatado,
                '{{LINK_PAGAMENTO}}' => $directPaymentLink,
                '{{LINK_NFSE_PDF}}' => $nfsePdfLink,
                '{{LINK_NFSE_XML}}' => $nfseXmlLink,
                '{{BLOCO_NFSE}}' => $blocoNfse,
                '{{ITENS_FATURA}}' => $itemsHtml,
                '{{EMPRESA_NOME}}' => htmlspecialchars($empresaNome),
                '{{FATURA_ID}}' => $fatura['id_fatura']
            ];

            $htmlBody = str_replace(array_keys($placeholders), array_values($placeholders), $htmlBody);

            // 9. Dispara o e-mail pelo helper do Gmail
            require_once __DIR__ . '/helpers/GmailHelper.php';
            try {
                GmailHelper::sendEmail($fatura['email_cliente'], $subject, $htmlBody, $attachments);
                $response['success'] = true;
                $response['message'] = "Fatura enviada por e-mail com sucesso para " . htmlspecialchars($fatura['email_cliente']) . "!";
            } catch (Exception $e) {
                $response['message'] = "Falha ao enviar e-mail: " . $e->getMessage();
            }
            break;

        // ==========================================
        // MÓDULO BANHO E TOSA: PACOTES & CONSUMO
        // ==========================================

        case 'save_pacote':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            $id_pacote = $_POST['id_pacote'] ?? null;
            $nome_pacote = trim($_POST['nome_pacote'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $valor_total = (float) ($_POST['valor_total'] ?? 0);
            $is_recorrente = isset($_POST['is_recorrente']) ? 1 : 0;
            $intervalo_dias = (int) ($_POST['intervalo_dias_recorrencia'] ?? 30);
            if ($intervalo_dias <= 0) $intervalo_dias = 30;
            $icone = mysqli_real_escape_string($link, !empty($_POST['icone']) ? $_POST['icone'] : 'card_giftcard');
            $imagem_url = mysqli_real_escape_string($link, $_POST['imagem_url'] ?? '');

            $itens_servico = $_POST['itens_servico'] ?? [];
            $itens_quantidade = $_POST['itens_quantidade'] ?? [];

            if (empty($nome_pacote) || $valor_total <= 0) {
                $response['message'] = "Nome do pacote e valor total válido são obrigatórios.";
                break;
            }

            if (empty($itens_servico) || !is_array($itens_servico)) {
                $response['message'] = "Adicione ao menos um serviço ao pacote.";
                break;
            }

            $nome_safe = mysqli_real_escape_string($link, $nome_pacote);
            $desc_safe = mysqli_real_escape_string($link, $descricao);

            if ($id_pacote) {
                $id_pacote_safe = (int)$id_pacote;
                $query = "UPDATE Pacotes SET 
                            nome_pacote = '$nome_safe',
                            descricao = '$desc_safe',
                            valor_total = $valor_total,
                            is_recorrente = $is_recorrente,
                            intervalo_dias_recorrencia = $intervalo_dias,
                            icone = '$icone',
                            imagem_url = '$imagem_url'
                          WHERE id_pacote = $id_pacote_safe";
                $res = DBExecute($link, $query);
            } else {
                $query = "INSERT INTO Pacotes (nome_pacote, descricao, valor_total, is_recorrente, intervalo_dias_recorrencia, icone, imagem_url, ativo) 
                          VALUES ('$nome_safe', '$desc_safe', $valor_total, $is_recorrente, $intervalo_dias, '$icone', '$imagem_url', 1)";
                $res = DBExecute($link, $query);
                $id_pacote_safe = mysqli_insert_id($link);
            }

            if ($res && $id_pacote_safe) {
                // Delete old items and insert updated ones
                DBExecute($link, "DELETE FROM PacoteItens WHERE id_pacote = $id_pacote_safe");

                for ($i = 0; $i < count($itens_servico); $i++) {
                    $id_srv = (int) $itens_servico[$i];
                    $qtd = (int) ($itens_quantidade[$i] ?? 1);
                    if ($id_srv > 0 && $qtd > 0) {
                        DBExecute($link, "INSERT INTO PacoteItens (id_pacote, id_servico, quantidade) VALUES ($id_pacote_safe, $id_srv, $qtd)");
                    }
                }

                $response['success'] = true;
                $response['message'] = $id_pacote ? "Pacote atualizado com sucesso!" : "Pacote cadastrado com sucesso!";
            } else {
                $response['message'] = "Erro ao salvar pacote: " . mysqli_error($link);
            }
            break;

        case 'delete_pacote':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }
            $id_pacote = (int) ($_POST['id_pacote'] ?? 0);
            if ($id_pacote > 0) {
                DBExecute($link, "UPDATE Pacotes SET ativo = 0 WHERE id_pacote = $id_pacote");
                $response['success'] = true;
                $response['message'] = "Pacote inativado com sucesso!";
            } else {
                $response['message'] = "ID do pacote inválido.";
            }
            break;

        case 'vincular_cliente_pacote':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }
            $id_cliente = (int) ($_POST['id_cliente'] ?? 0);
            $id_pacote = (int) ($_POST['id_pacote'] ?? 0);
            $id_pet = !empty($_POST['id_pet']) ? (int)$_POST['id_pet'] : null;
            $id_pet_val = $id_pet ? $id_pet : "NULL";

            if ($id_cliente <= 0 || $id_pacote <= 0) {
                $response['message'] = "Selecione o cliente e o pacote.";
                break;
            }

            // Get Pacote
            $resP = DBExecute($link, "SELECT * FROM Pacotes WHERE id_pacote = $id_pacote AND ativo = 1");
            if (!$resP || mysqli_num_rows($resP) == 0) {
                $response['message'] = "Pacote não encontrado ou inativo.";
                break;
            }
            $pacote = mysqli_fetch_assoc($resP);

            // Fetch Items
            $resIt = DBExecute($link, "SELECT * FROM PacoteItens WHERE id_pacote = $id_pacote");
            $items = [];
            while ($it = mysqli_fetch_assoc($resIt)) {
                $items[] = $it;
            }

            if (empty($items)) {
                $response['message'] = "Este pacote não possui serviços configurados.";
                break;
            }

            // 1. If recorrente, create in Recorrencias table
            $id_recorrencia_val = "NULL";
            if ($pacote['is_recorrente'] == 1) {
                $primeiro_servico_id = (int)$items[0]['id_servico'];
                $valor_rec = (float)$pacote['valor_total'];
                $intervalo = (int)$pacote['intervalo_dias_recorrencia'];
                $data_inicio = date('Y-m-d');
                $obs_rec = mysqli_real_escape_string($link, "Pacote: " . $pacote['nome_pacote']);

                $qRec = "INSERT INTO Recorrencias (id_cliente, id_servico, quantidade, valor_sugerido_recorrencia, tipo_periodo, intervalo, data_inicio_cobranca, observacoes) 
                         VALUES ($id_cliente, $primeiro_servico_id, 1, $valor_rec, 'dias', $intervalo, '$data_inicio', '$obs_rec')";
                if (DBExecute($link, $qRec)) {
                    $id_recorrencia_val = mysqli_insert_id($link);
                }
            }

            // 2. Insert into ClientePacotes com id_pet opcional
            $qCP = "INSERT INTO ClientePacotes (id_cliente, id_pacote, id_pet, id_recorrencia, status) 
                    VALUES ($id_cliente, $id_pacote, $id_pet_val, $id_recorrencia_val, 'ativo')";
            if (DBExecute($link, $qCP)) {
                $id_cliente_pacote = mysqli_insert_id($link);

                // 3. Populate ClientePacoteSaldos
                foreach ($items as $it) {
                    $id_srv = (int)$it['id_servico'];
                    $qtd = (int)$it['quantidade'];
                    DBExecute($link, "INSERT INTO ClientePacoteSaldos (id_cliente_pacote, id_servico, qtd_total, qtd_utilizada) 
                                      VALUES ($id_cliente_pacote, $id_srv, $qtd, 0)");
                }

                $response['success'] = true;
                $response['message'] = "Pacote vinculado ao cliente com sucesso! Saldos de serviços liberados.";
            } else {
                $response['message'] = "Erro ao vincular pacote: " . mysqli_error($link);
            }
            break;

        case 'get_pets_by_cliente':
            $id_cliente = (int) ($_REQUEST['id_cliente'] ?? 0);
            if ($id_cliente <= 0) {
                $response['message'] = "Cliente não informado.";
                break;
            }

            $resPets = DBExecute($link, "SELECT id_pet, nome, porte, tipo_pelagem FROM Pets WHERE id_cliente = $id_cliente ORDER BY nome ASC");
            $pets = [];
            if ($resPets) {
                while ($p = mysqli_fetch_assoc($resPets)) {
                    $pets[] = $p;
                }
            }

            $response['success'] = true;
            $response['pets'] = $pets;
            break;

        case 'get_cliente_pacotes_saldo':
            $id_cliente = (int) ($_REQUEST['id_cliente'] ?? 0);
            $id_servico = (int) ($_REQUEST['id_servico'] ?? 0);
            $id_pet = (int) ($_REQUEST['id_pet'] ?? 0);

            if ($id_cliente <= 0) {
                $response['message'] = "Cliente não informado.";
                break;
            }

            $whereServ = $id_servico > 0 ? "AND cps.id_servico = $id_servico" : "";
            $wherePet = $id_pet > 0 ? "AND (cp.id_pet IS NULL OR cp.id_pet = $id_pet)" : "";

            $query = "SELECT cps.*, cp.id_pacote, cp.id_pet as pet_vinculado, p.nome_pacote, s.nome_servico, s.duracao_minutos,
                      (cps.qtd_total - cps.qtd_utilizada) as saldo_restante
                      FROM ClientePacoteSaldos cps
                      JOIN ClientePacotes cp ON cps.id_cliente_pacote = cp.id_cliente_pacote
                      JOIN Pacotes p ON cp.id_pacote = p.id_pacote
                      JOIN Servicos s ON cps.id_servico = s.id_servico
                      WHERE cp.id_cliente = $id_cliente 
                        AND cp.status = 'ativo'
                        AND (cps.qtd_total - cps.qtd_utilizada) > 0
                        $whereServ
                        $wherePet
                      ORDER BY cp.data_aquisicao ASC";

            $res = DBExecute($link, $query);
            $saldos = [];
            if ($res) {
                while ($r = mysqli_fetch_assoc($res)) {
                    $saldos[] = $r;
                }
            }

            $response['success'] = true;
            $response['saldos'] = $saldos;
            break;

        case 'get_extrato_pacote':
            $id_cliente_pacote = (int) ($_REQUEST['id_cliente_pacote'] ?? 0);
            if ($id_cliente_pacote <= 0) {
                $response['message'] = "ID do contrato de pacote não informado.";
                break;
            }

            // Pacote info
            $qP = "SELECT cp.*, p.nome_pacote, p.valor_total, p.is_recorrente, p.intervalo_dias_recorrencia, p.icone,
                          c.nome as nome_tutor, c.telefone as telefone_tutor, c.email as email_tutor,
                          pt.nome as nome_pet_exclusivo,
                          DATE_FORMAT(cp.data_aquisicao, '%d/%m/%Y %H:%i') as data_aquisicao_fmt
                   FROM ClientePacotes cp
                   JOIN Pacotes p ON cp.id_pacote = p.id_pacote
                   JOIN Clientes c ON cp.id_cliente = c.id_cliente
                   LEFT JOIN Pets pt ON cp.id_pet = pt.id_pet
                   WHERE cp.id_cliente_pacote = $id_cliente_pacote";
            $resP = DBExecute($link, $qP);
            if (!$resP || mysqli_num_rows($resP) == 0) {
                $response['message'] = "Contrato de pacote não localizado.";
                break;
            }
            $pacote = mysqli_fetch_assoc($resP);

            // Saldos
            $qSaldos = "SELECT cps.*, s.nome_servico, s.duracao_minutos, s.icone_servico,
                               (cps.qtd_total - cps.qtd_utilizada) as saldo_restante
                        FROM ClientePacoteSaldos cps
                        JOIN Servicos s ON cps.id_servico = s.id_servico
                        WHERE cps.id_cliente_pacote = $id_cliente_pacote";
            $resS = DBExecute($link, $qSaldos);
            $saldos = [];
            if ($resS) {
                while ($s = mysqli_fetch_assoc($resS)) {
                    $saldos[] = $s;
                }
            }

            // Histórico de Utilizações
            $qCons = "SELECT cpc.*, s.nome_servico, pt.nome as nome_pet,
                             DATE_FORMAT(cpc.data_consumo, '%d/%m/%Y %H:%i') as data_consumo_fmt
                      FROM ClientePacoteConsumo cpc
                      JOIN Servicos s ON cpc.id_servico = s.id_servico
                      JOIN Pets pt ON cpc.id_pet = pt.id_pet
                      WHERE cpc.id_cliente_pacote = $id_cliente_pacote
                      ORDER BY cpc.data_consumo DESC";
            $resC = DBExecute($link, $qCons);
            $consumos = [];
            if ($resC) {
                while ($c = mysqli_fetch_assoc($resC)) {
                    $consumos[] = $c;
                }
            }

            $response['success'] = true;
            $response['pacote'] = $pacote;
            $response['saldos'] = $saldos;
            $response['consumos'] = $consumos;
            break;

        case 'consumir_pacote_servico':
            $id_cliente_pacote = (int) ($_POST['id_cliente_pacote'] ?? 0);
            $id_servico = (int) ($_POST['id_servico'] ?? 0);
            $id_pet = (int) ($_POST['id_pet'] ?? 0);
            $id_agendamento = !empty($_POST['id_agendamento']) ? (int)$_POST['id_agendamento'] : "NULL";
            $observacao = mysqli_real_escape_string($link, $_POST['observacao'] ?? 'Consumo pelo Banho e Tosa');

            if ($id_cliente_pacote <= 0 || $id_servico <= 0 || $id_pet <= 0) {
                $response['message'] = "Dados de consumo incompletos.";
                break;
            }

            // Check saldo
            $qCheck = "SELECT id_saldo, qtd_total, qtd_utilizada 
                       FROM ClientePacoteSaldos 
                       WHERE id_cliente_pacote = $id_cliente_pacote AND id_servico = $id_servico";
            $resCheck = DBExecute($link, $qCheck);
            if ($resCheck && $saldo = mysqli_fetch_assoc($resCheck)) {
                if ($saldo['qtd_utilizada'] >= $saldo['qtd_total']) {
                    $response['message'] = "Saldo deste serviço no pacote já está esgotado.";
                    break;
                }

                // Increment qtd_utilizada
                $newUtil = $saldo['qtd_utilizada'] + 1;
                $id_saldo = (int)$saldo['id_saldo'];
                DBExecute($link, "UPDATE ClientePacoteSaldos SET qtd_utilizada = $newUtil WHERE id_saldo = $id_saldo");

                // Log consumo
                DBExecute($link, "INSERT INTO ClientePacoteConsumo (id_cliente_pacote, id_servico, id_pet, id_agendamento, observacao) 
                                  VALUES ($id_cliente_pacote, $id_servico, $id_pet, $id_agendamento, '$observacao')");

                // Check if all items in package are finished
                $qAll = "SELECT COUNT(*) as total_itens, 
                         SUM(CASE WHEN qtd_utilizada >= qtd_total THEN 1 ELSE 0 END) as itens_esgotados
                         FROM ClientePacoteSaldos WHERE id_cliente_pacote = $id_cliente_pacote";
                $resAll = DBExecute($link, $qAll);
                if ($resAll && $rAll = mysqli_fetch_assoc($resAll)) {
                    if ($rAll['total_itens'] == $rAll['itens_esgotados']) {
                        DBExecute($link, "UPDATE ClientePacotes SET status = 'esgotado' WHERE id_cliente_pacote = $id_cliente_pacote");
                    }
                }

                $response['success'] = true;
                $response['message'] = "Crédito do pacote consumido com sucesso!";
            } else {
                $response['message'] = "Saldo não localizado para este pacote e serviço.";
            }
            break;

        case 'get_banho_producao_fila':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            // 0. Verificar se Gmail está configurado para envio de e-mails
            $resCfgE = DBExecute($link, "SELECT google_oauth_token, google_oauth_email FROM ConfiguracoesEmissor LIMIT 1");
            $gmailConfigurado = false;
            if ($resCfgE && $rowCfgE = mysqli_fetch_assoc($resCfgE)) {
                $gmailConfigurado = !empty($rowCfgE['google_oauth_token']) || !empty($rowCfgE['google_oauth_email']);
            }

            // 1. AUTO-SYNC: Apenas banhos agendados para HOJE entram automaticamente na coluna de espera da esteira
            $qAutoSync = "INSERT INTO BanhoProducaoFila (id_agendamento, id_pet, id_colaborador, etapa, horario_entrada, observacoes_estetica)
                          SELECT a.id_agendamento, a.id_pet, a.id_vet, 'aguardando', a.data_inicio, a.descricao
                          FROM Agendamentos a
                          WHERE a.tipo_agenda = 'banho_tosa'
                            AND a.status NOT IN ('Cancelado', 'Concluído')
                            AND DATE(a.data_inicio) = CURDATE()
                            AND a.id_pet IS NOT NULL
                            AND NOT EXISTS (
                              SELECT 1 FROM BanhoProducaoFila f WHERE f.id_agendamento = a.id_agendamento
                            )";
            DBExecute($link, $qAutoSync);

            // 2. Buscar esteira: exibe apenas os do dia em "aguardando" (ou qualquer pet que já tenha iniciado o atendimento)
            $query = "SELECT f.*, 
                             COALESCE(NULLIF(f.observacoes_estetica, ''), a.descricao, '') as observacoes_estetica,
                             COALESCE(NULLIF(a.descricao, ''), f.observacoes_estetica, '') as observacoes_agendamento,
                             p.nome as nome_pet, p.porte, p.tipo_pelagem, p.preferencias_banho,
                             c.id_cliente, c.nome as nome_tutor, c.telefone as telefone_tutor, c.email as email_tutor,
                             v.nome as nome_colaborador,
                             s.nome_servico, s.duracao_minutos,
                             pac.nome_pacote,
                             a.status as status_agendamento,
                             CASE 
                               WHEN a.id_agendamento IS NULL OR (a.data_inicio IS NOT NULL AND a.titulo LIKE 'Banho/Tosa: %') THEN 1 
                               ELSE 0 
                             END as is_avulso,
                             DATE_FORMAT(f.horario_entrada, '%H:%i') as horario_entrada_fmt,
                             (SELECT COUNT(*) FROM BanhoCheckinFotos bcf WHERE bcf.id_fila = f.id_fila) as total_fotos
                      FROM BanhoProducaoFila f
                      JOIN Pets p ON f.id_pet = p.id_pet
                      JOIN Clientes c ON p.id_cliente = c.id_cliente
                      LEFT JOIN Agendamentos a ON f.id_agendamento = a.id_agendamento
                      LEFT JOIN Servicos s ON a.id_servico = s.id_servico
                      LEFT JOIN ClientePacotes cp ON a.id_cliente_pacote = cp.id_cliente_pacote
                      LEFT JOIN Pacotes pac ON cp.id_pacote = pac.id_pacote
                      LEFT JOIN Veterinarios v ON f.id_colaborador = v.id_vet
                      WHERE f.etapa != 'finalizado'
                        AND (
                          f.etapa != 'aguardando'
                          OR DATE(f.horario_entrada) = CURDATE()
                          OR (a.data_inicio IS NOT NULL AND DATE(a.data_inicio) = CURDATE())
                        )
                      ORDER BY f.horario_entrada ASC";

            $res = DBExecute($link, $query);
            $fila = [];
            if ($res) {
                while ($r = mysqli_fetch_assoc($res)) {
                    $fila[] = $r;
                }
            }

            $response['success'] = true;
            $response['gmail_configurado'] = $gmailConfigurado;
            $response['fila'] = $fila;
            break;

        case 'criar_checkin_banho':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            $id_pet = (int) ($_POST['id_pet'] ?? 0);
            $id_servico = (int) ($_POST['id_servico'] ?? 0);
            $id_colaborador = !empty($_POST['id_colaborador']) ? (int)$_POST['id_colaborador'] : null;
            $id_colaborador_sql = $id_colaborador ? $id_colaborador : "NULL";
            $obsRaw = trim($_POST['observacoes_estetica'] ?? ($_POST['observacoes'] ?? ($_POST['descricao'] ?? '')));
            $observacoes = mysqli_real_escape_string($link, $obsRaw);
            $usar_saldo = isset($_POST['usar_saldo_pacote']) && $_POST['usar_saldo_pacote'] == 1;

            if ($id_pet <= 0) {
                $response['message'] = "Selecione o pet para dar entrada.";
                break;
            }

            // Buscar dados do pet e tutor
            $qPetInfo = "SELECT p.*, c.id_cliente, c.nome as nome_tutor, c.telefone as telefone_tutor, c.email as email_tutor 
                         FROM Pets p 
                         JOIN Clientes c ON p.id_cliente = c.id_cliente 
                         WHERE p.id_pet = $id_pet";
            $resPetInfo = DBExecute($link, $qPetInfo);
            if (!$resPetInfo || mysqli_num_rows($resPetInfo) == 0) {
                $response['message'] = "Pet não encontrado.";
                break;
            }
            $petInfo = mysqli_fetch_assoc($resPetInfo);
            $id_cliente = (int)$petInfo['id_cliente'];

            // Obter serviço padrão se não informado
            if ($id_servico <= 0) {
                $qDefServ = "SELECT id_servico, nome_servico, duracao_minutos FROM Servicos WHERE disponivel_banho = 1 ORDER BY id_servico ASC LIMIT 1";
                $rDefServ = DBExecute($link, $qDefServ);
                if ($rDefServ && $rowDef = mysqli_fetch_assoc($rDefServ)) {
                    $id_servico = (int)$rowDef['id_servico'];
                    $nomeServico = $rowDef['nome_servico'];
                    $duracaoBase = (int)$rowDef['duracao_minutos'];
                } else {
                    $nomeServico = 'Banho & Tosa';
                    $duracaoBase = 30;
                }
            } else {
                $rServ = DBExecute($link, "SELECT nome_servico, duracao_minutos FROM Servicos WHERE id_servico = $id_servico");
                $rowS = mysqli_fetch_assoc($rServ);
                $nomeServico = $rowS['nome_servico'] ?? 'Banho & Tosa';
                $duracaoBase = (int)($rowS['duracao_minutos'] ?? 30);
            }

            // Cálculo da duração inteligente
            $porte = $petInfo['porte'] ?: 'P';
            $pelagem = $petInfo['tipo_pelagem'] ?: 'Curto';
            $mult = 1.0;
            if ($porte === 'M') $mult = 1.2;
            if ($porte === 'G') $mult = 1.5;
            if ($porte === 'GG') $mult = 2.0;

            $duracaoFinal = (int) round($duracaoBase * $mult);
            if ($pelagem === 'Longo' || $pelagem === 'Dupla Pelagem') {
                $duracaoFinal += 15;
            }

            $dtInicioStr = $_POST['data_inicio'] ?? '';
            if (!empty($dtInicioStr)) {
                $dtInicio = new DateTime($dtInicioStr, new DateTimeZone('America/Sao_Paulo'));
            } else {
                $dtInicio = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
            }
            $dtFim = clone $dtInicio;
            $dtFim->modify("+{$duracaoFinal} minutes");
            $startStr = $dtInicio->format('Y-m-d H:i:s');
            $endStr = $dtFim->format('Y-m-d H:i:s');
            $statusAgend = 'Agendado';

            // 1. Verificar e abater saldo de pacote do tutor
            $id_cliente_pacote_val = "NULL";
            if ($usar_saldo && $id_servico > 0) {
                $qPac = "SELECT cp.id_cliente_pacote, cps.id_saldo, cps.qtd_total, cps.qtd_utilizada 
                         FROM ClientePacotes cp 
                         JOIN ClientePacoteSaldos cps ON cp.id_cliente_pacote = cps.id_cliente_pacote 
                         WHERE cp.id_cliente = $id_cliente 
                           AND cp.status = 'ativo' 
                           AND cps.id_servico = $id_servico 
                           AND (cps.qtd_total - cps.qtd_utilizada) > 0 
                         ORDER BY cp.data_aquisicao ASC LIMIT 1";
                $rPac = DBExecute($link, $qPac);
                if ($rPac && $pacRow = mysqli_fetch_assoc($rPac)) {
                    $id_cliente_pacote_val = (int)$pacRow['id_cliente_pacote'];
                    $newUtil = $pacRow['qtd_utilizada'] + 1;
                    $idSaldo = (int)$pacRow['id_saldo'];
                    DBExecute($link, "UPDATE ClientePacoteSaldos SET qtd_utilizada = $newUtil WHERE id_saldo = $idSaldo");
                }
            }

            // 2. CRIAR O ITEM AUTOMATICAMENTE NA AGENDA
            $titulo = mysqli_real_escape_string($link, "Banho/Tosa: " . $petInfo['nome'] . " (" . $nomeServico . ")");
            $queryAgend = "INSERT INTO Agendamentos (id_cliente, id_pet, id_vet, id_servico, id_cliente_pacote, tipo_agenda, titulo, descricao, data_inicio, data_fim, status) 
                           VALUES ($id_cliente, $id_pet, $id_colaborador_sql, $id_servico, $id_cliente_pacote_val, 'banho_tosa', '$titulo', '$observacoes', '$startStr', '$endStr', '$statusAgend')";
            
            $id_agendamento_val = "NULL";
            if (DBExecute($link, $queryAgend)) {
                $id_agendamento_val = mysqli_insert_id($link);

                // Log de consumo se utilizou pacote
                if ($id_cliente_pacote_val !== "NULL") {
                    DBExecute($link, "INSERT INTO ClientePacoteConsumo (id_cliente_pacote, id_servico, id_pet, id_agendamento, observacao) 
                                      VALUES ($id_cliente_pacote_val, $id_servico, $id_pet, $id_agendamento_val, 'Check-in na Esteira')");
                }
            }

            // 3. INSERIR NA ESTEIRA DE PRODUÇÃO VINCULADO AO AGENDAMENTO
            $queryFila = "INSERT INTO BanhoProducaoFila (id_agendamento, id_pet, id_colaborador, etapa, horario_entrada, observacoes_estetica) 
                          VALUES ($id_agendamento_val, $id_pet, $id_colaborador_sql, 'aguardando', '$startStr', '$observacoes')";

            if (DBExecute($link, $queryFila)) {
                $id_fila = mysqli_insert_id($link);

                // Upload das fotos de check-in
                if (isset($_FILES['fotos_checkin']) && !empty($_FILES['fotos_checkin']['name'][0])) {
                    $uploadDir = __DIR__ . '/uploads/banho_fotos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $totalFiles = count($_FILES['fotos_checkin']['name']);
                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($_FILES['fotos_checkin']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmpName = $_FILES['fotos_checkin']['tmp_name'][$i];
                            $origName = $_FILES['fotos_checkin']['name'][$i];
                            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $newFileName = 'checkin_' . $id_fila . '_' . time() . '_' . $i . '.' . $ext;
                                $destPath = $uploadDir . $newFileName;
                                if (move_uploaded_file($tmpName, $destPath)) {
                                    $relPath = 'uploads/banho_fotos/' . $newFileName;
                                    DBExecute($link, "INSERT INTO BanhoCheckinFotos (id_fila, id_pet, foto_url) VALUES ($id_fila, $id_pet, '$relPath')");
                                }
                            }
                        }
                    }
                }

                $response['success'] = true;
                $response['message'] = "Check-in realizado com sucesso! Pet inserido na esteira e agendamento gerado.";
            } else {
                $response['message'] = "Erro ao dar entrada na fila: " . mysqli_error($link);
            }
            break;

        case 'update_etapa_banho':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            $id_fila = (int) ($_POST['id_fila'] ?? 0);
            $nova_etapa = mysqli_real_escape_string($link, $_POST['nova_etapa'] ?? '');
            $etapas_validas = ['aguardando', 'em_banho', 'secagem', 'tosa_finalizacao', 'pronto', 'finalizado'];

            if ($id_fila <= 0 || !in_array($nova_etapa, $etapas_validas)) {
                $response['message'] = "Parâmetros inválidos para alteração de etapa.";
                break;
            }

            // Buscar dados atuais da fila
            $resF = DBExecute($link, "SELECT * FROM BanhoProducaoFila WHERE id_fila = $id_fila");
            if (!$resF || mysqli_num_rows($resF) == 0) {
                $response['message'] = "Registro da fila não encontrado.";
                break;
            }
            $filaItem = mysqli_fetch_assoc($resF);
            $id_agendamento = (int)($filaItem['id_agendamento'] ?? 0);

            $saida_sql = ($nova_etapa === 'finalizado') ? ", horario_saida = NOW()" : "";
            $query = "UPDATE BanhoProducaoFila SET etapa = '$nova_etapa' $saida_sql WHERE id_fila = $id_fila";

            if (DBExecute($link, $query)) {
                // Sincronizar status do Agendamento vinculado
                if ($id_agendamento > 0) {
                    if (in_array($nova_etapa, ['em_banho', 'secagem', 'tosa_finalizacao'])) {
                        DBExecute($link, "UPDATE Agendamentos SET status = 'Em Andamento' WHERE id_agendamento = $id_agendamento");
                    } elseif ($nova_etapa === 'pronto') {
                        DBExecute($link, "UPDATE Agendamentos SET status = 'Realizado' WHERE id_agendamento = $id_agendamento");
                    } elseif ($nova_etapa === 'finalizado') {
                        DBExecute($link, "UPDATE Agendamentos SET status = 'Concluído' WHERE id_agendamento = $id_agendamento");
                    }
                }

                $response['success'] = true;
                $response['message'] = "Etapa atualizada com sucesso!";
            } else {
                $response['message'] = "Erro ao atualizar etapa: " . mysqli_error($link);
            }
            break;

        case 'editar_checkin_banho':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            $id_fila = (int) ($_POST['id_fila'] ?? 0);
            $id_servico = (int) ($_POST['id_servico'] ?? 0);
            $id_colaborador = !empty($_POST['id_colaborador']) ? (int)$_POST['id_colaborador'] : null;
            $id_colaborador_sql = $id_colaborador ? $id_colaborador : "NULL";
            $observacoes = mysqli_real_escape_string($link, $_POST['observacoes_estetica'] ?? '');

            if ($id_fila <= 0) {
                $response['message'] = "ID da fila não informado.";
                break;
            }

            // Buscar registro atual
            $resF = DBExecute($link, "SELECT * FROM BanhoProducaoFila WHERE id_fila = $id_fila");
            if (!$resF || mysqli_num_rows($resF) == 0) {
                $response['message'] = "Registro não encontrado.";
                break;
            }
            $filaItem = mysqli_fetch_assoc($resF);
            $id_agendamento = (int)($filaItem['id_agendamento'] ?? 0);
            $id_pet = (int)$filaItem['id_pet'];

            // Atualizar BanhoProducaoFila
            DBExecute($link, "UPDATE BanhoProducaoFila SET id_colaborador = $id_colaborador_sql, observacoes_estetica = '$observacoes' WHERE id_fila = $id_fila");

            // Atualizar Agendamento vinculado
            if ($id_agendamento > 0) {
                $updServ = $id_servico > 0 ? "id_servico = $id_servico," : "";
                DBExecute($link, "UPDATE Agendamentos SET $updServ id_vet = $id_colaborador_sql, descricao = '$observacoes' WHERE id_agendamento = $id_agendamento");
            }

            // Upload de novas fotos se enviadas
            if (isset($_FILES['fotos_checkin']) && !empty($_FILES['fotos_checkin']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/banho_fotos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $totalFiles = count($_FILES['fotos_checkin']['name']);
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['fotos_checkin']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['fotos_checkin']['tmp_name'][$i];
                        $origName = $_FILES['fotos_checkin']['name'][$i];
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $newFileName = 'checkin_' . $id_fila . '_' . time() . '_' . $i . '.' . $ext;
                            $destPath = $uploadDir . $newFileName;
                            if (move_uploaded_file($tmpName, $destPath)) {
                                $relPath = 'uploads/banho_fotos/' . $newFileName;
                                DBExecute($link, "INSERT INTO BanhoCheckinFotos (id_fila, id_pet, foto_url) VALUES ($id_fila, $id_pet, '$relPath')");
                            }
                        }
                    }
                }
            }

            $response['success'] = true;
            $response['message'] = "Registro de banho atualizado com sucesso!";
            break;

        case 'excluir_checkin_banho':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            $id_fila = (int) ($_POST['id_fila'] ?? 0);
            if ($id_fila <= 0) {
                $response['message'] = "ID da fila não informado.";
                break;
            }

            $resF = DBExecute($link, "SELECT * FROM BanhoProducaoFila WHERE id_fila = $id_fila");
            if (!$resF || mysqli_num_rows($resF) == 0) {
                $response['message'] = "Registro não encontrado.";
                break;
            }
            $filaItem = mysqli_fetch_assoc($resF);
            $id_agendamento = (int)($filaItem['id_agendamento'] ?? 0);

            // 1. Se consumiu crédito de pacote, devolver o crédito
            if ($id_agendamento > 0) {
                $resCons = DBExecute($link, "SELECT * FROM ClientePacoteConsumo WHERE id_agendamento = $id_agendamento");
                if ($resCons && $cons = mysqli_fetch_assoc($resCons)) {
                    $id_cp = (int)$cons['id_cliente_pacote'];
                    $id_srv = (int)$cons['id_servico'];
                    // Restituir saldo
                    DBExecute($link, "UPDATE ClientePacoteSaldos SET qtd_utilizada = GREATEST(0, qtd_utilizada - 1) WHERE id_cliente_pacote = $id_cp AND id_servico = $id_srv");
                    // Reativar pacote se estava esgotado
                    DBExecute($link, "UPDATE ClientePacotes SET status = 'ativo' WHERE id_cliente_pacote = $id_cp");
                    // Deletar log de consumo
                    DBExecute($link, "DELETE FROM ClientePacoteConsumo WHERE id_agendamento = $id_agendamento");
                }

                // Deletar o agendamento
                DBExecute($link, "DELETE FROM Agendamentos WHERE id_agendamento = $id_agendamento");
            }

            // 2. Deletar fotos associadas
            $resFotos = DBExecute($link, "SELECT foto_url FROM BanhoCheckinFotos WHERE id_fila = $id_fila");
            if ($resFotos) {
                while ($fot = mysqli_fetch_assoc($resFotos)) {
                    $filePath = __DIR__ . '/' . $fot['foto_url'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
            DBExecute($link, "DELETE FROM BanhoCheckinFotos WHERE id_fila = $id_fila");

            // 3. Deletar da Fila
            DBExecute($link, "DELETE FROM BanhoProducaoFila WHERE id_fila = $id_fila");

            $response['success'] = true;
            $response['message'] = "Pet removido da esteira e saldo restituído com sucesso!";
            break;

        case 'get_fotos_banho_checkin':
            $id_fila = (int) ($_REQUEST['id_fila'] ?? 0);
            if ($id_fila <= 0) {
                $response['message'] = "ID da fila não informado.";
                break;
            }

            $res = DBExecute($link, "SELECT * FROM BanhoCheckinFotos WHERE id_fila = $id_fila ORDER BY id_foto ASC");
            $fotos = [];
            if ($res) {
                while ($f = mysqli_fetch_assoc($res)) {
                    $fotos[] = $f;
                }
            }

            $response['success'] = true;
            $response['fotos'] = $fotos;
            break;

        case 'notificar_tutor_pronto_email':
            if (!AppHelper::isVetMode()) {
                $response['message'] = "Acesso exclusivo ao Modo Vet.";
                break;
            }

            $id_fila = (int) ($_POST['id_fila'] ?? 0);
            if ($id_fila <= 0) {
                $response['message'] = "ID da esteira inválido.";
                break;
            }

            $qFila = "SELECT f.*, p.nome as nome_pet, c.nome as nome_tutor, c.email as email_tutor 
                      FROM BanhoProducaoFila f
                      JOIN Pets p ON f.id_pet = p.id_pet
                      JOIN Clientes c ON p.id_cliente = c.id_cliente
                      WHERE f.id_fila = $id_fila";
            $resF = DBExecute($link, $qFila);
            if (!$resF || mysqli_num_rows($resF) == 0) {
                $response['message'] = "Registro não encontrado.";
                break;
            }

            $filaData = mysqli_fetch_assoc($resF);
            if (empty($filaData['email_tutor'])) {
                $response['message'] = "O tutor não possui e-mail cadastrado.";
                break;
            }

            // Fetch Emissor Name
            $empresa = "Clínica & Estética DinoVet";
            $resEmp = DBExecute($link, "SELECT nome_fantasia, razao_social FROM ConfiguracoesEmissor WHERE id_config = 1");
            if ($resEmp && $cfg = mysqli_fetch_assoc($resEmp)) {
                $empresa = $cfg['nome_fantasia'] ?: ($cfg['razao_social'] ?: $empresa);
            }

            require_once __DIR__ . '/helpers/GmailHelper.php';

            $subject = "🐾 Seu pet " . $filaData['nome_pet'] . " já está pronto e cheiroso!";
            $htmlBody = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="display: inline-block; background: #ccfbf1; color: #0d9488; font-size: 32px; padding: 15px; border-radius: 50%;">🐾</div>
                    <h2 style="color: #0f172a; margin: 15px 0 5px 0; font-size: 24px;">' . htmlspecialchars($filaData['nome_pet']) . ' está pronto!</h2>
                    <p style="color: #64748b; font-size: 14px; margin: 0;">' . htmlspecialchars($empresa) . '</p>
                </div>

                <p style="color: #334155; font-size: 15px; line-height: 1.6;">
                    Olá, <strong>' . htmlspecialchars($filaData['nome_tutor']) . '</strong>!
                </p>
                <p style="color: #334155; font-size: 15px; line-height: 1.6;">
                    Temos ótimas notícias! O atendimento de banho e estética do seu pet <strong>' . htmlspecialchars($filaData['nome_pet']) . '</strong> foi finalizado com muito carinho pela nossa equipe. Ele já está limpinho, cheiroso e pronto para ir para casa!
                </p>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin: 25px 0; text-align: center;">
                    <p style="color: #0d9488; font-weight: bold; margin: 0; font-size: 16px;">📍 Você já pode vir buscá-lo na nossa recepção.</p>
                </div>

                <p style="color: #94a3b8; font-size: 12px; text-align: center; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                    Atendimento prestado por ' . htmlspecialchars($empresa) . '. Agradecemos a confiança!
                </p>
            </div>';

            try {
                GmailHelper::sendEmail($filaData['email_tutor'], $subject, $htmlBody);
                $response['success'] = true;
                $response['message'] = "E-mail de notificação enviado com sucesso para " . htmlspecialchars($filaData['email_tutor']) . "!";
            } catch (Exception $e) {
                $response['message'] = "Falha ao enviar e-mail: " . $e->getMessage();
            }
            break;

        case 'get_horarios_disponiveis_banho':
            $data = mysqli_real_escape_string($link, $_REQUEST['data'] ?? date('Y-m-d'));
            $id_servico = (int) ($_REQUEST['id_servico'] ?? 0);
            $id_pet = (int) ($_REQUEST['id_pet'] ?? 0);

            if (empty($data)) {
                $response['message'] = "Data não informada.";
                break;
            }

            // 1. Duração base do serviço
            $duracao_base = 40;
            if ($id_servico > 0) {
                $resS = DBExecute($link, "SELECT duracao_minutos FROM Servicos WHERE id_servico = $id_servico");
                if ($resS && $s = mysqli_fetch_assoc($resS)) {
                    $duracao_base = (int)$s['duracao_minutos'] ?: 40;
                }
            }

            // 2. Multiplicador de porte e pelagem do pet
            $multiplicador = 1.0;
            if ($id_pet > 0) {
                $resP = DBExecute($link, "SELECT porte, tipo_pelagem FROM Pets WHERE id_pet = $id_pet");
                if ($resP && $pet = mysqli_fetch_assoc($resP)) {
                    $porte = strtoupper($pet['porte'] ?? 'P');
                    if ($porte === 'M') $multiplicador = 1.2;
                    else if ($porte === 'G') $multiplicador = 1.5;
                    else if ($porte === 'GG') $multiplicador = 1.8;

                    $pelagem = strtolower($pet['tipo_pelagem'] ?? '');
                    if (strpos($pelagem, 'long') !== false || strpos($pelagem, 'dupl') !== false) {
                        $multiplicador += 0.2;
                    }
                }
            }

            $duracao_estimada = (int) ceil($duracao_base * $multiplicador);

            // 3. Capacidade de atendimento simultâneo por horário (Configurada nas preferências do módulo)
            $capacidade_simultanea = 2;
            $resCfg = DBExecute($link, "SELECT banho_capacidade_simultanea FROM ConfiguracoesEmissor WHERE id_config = 1");
            if ($resCfg && $cfgB = mysqli_fetch_assoc($resCfg)) {
                $cap = (int)($cfgB['banho_capacidade_simultanea'] ?? 0);
                if ($cap > 0) {
                    $capacidade_simultanea = $cap;
                }
            }

            // 4. Buscar agendamentos existentes no dia
            $qAgend = "SELECT data_inicio, data_fim 
                       FROM Agendamentos 
                       WHERE tipo_agenda = 'banho_tosa' 
                         AND status NOT IN ('Cancelado') 
                         AND DATE(data_inicio) = '$data'";
            $resAgend = DBExecute($link, $qAgend);
            $agendamentosDia = [];
            if ($resAgend) {
                while ($ag = mysqli_fetch_assoc($resAgend)) {
                    $agendamentosDia[] = [
                        'inicio' => strtotime($ag['data_inicio']),
                        'fim' => strtotime($ag['data_fim'] ?: date('Y-m-d H:i:s', strtotime($ag['data_inicio'] . ' + 45 minutes')))
                    ];
                }
            }

            // 5. Gerar grade de horários (08:00 às 17:30)
            $slots = [];
            $horariosGrade = [
                '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
                '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
            ];

            $agoraTimestamp = time();
            $isHoje = ($data === date('Y-m-d'));

            foreach ($horariosGrade as $hora) {
                $slotInicioTimestamp = strtotime("$data $hora:00");
                $slotFimTimestamp = $slotInicioTimestamp + ($duracao_estimada * 60);

                // Se for hoje e o horário já passou
                if ($isHoje && $slotInicioTimestamp <= $agoraTimestamp + (15 * 60)) {
                    $slots[] = [
                        'hora' => $hora,
                        'disponivel' => false,
                        'vagas' => 0,
                        'motivo' => 'Horário já passou'
                    ];
                    continue;
                }

                // Contar sobreposições de agendamentos
                $ocupados = 0;
                foreach ($agendamentosDia as $ag) {
                    // Se houver intersecção entre [slotInicio, slotFim) e [agInicio, agFim)
                    if ($slotInicioTimestamp < $ag['fim'] && $slotFimTimestamp > $ag['inicio']) {
                        $ocupados++;
                    }
                }

                $vagasRestantes = max(0, $capacidade_simultanea - $ocupados);
                $disponivel = $vagasRestantes > 0;

                $slots[] = [
                    'hora' => $hora,
                    'disponivel' => $disponivel,
                    'vagas' => $vagasRestantes,
                    'motivo' => $disponivel ? 'Disponível' : 'Lotado'
                ];
            }

            $response['success'] = true;
            $response['data'] = $data;
            $response['duracao_estimada'] = $duracao_estimada;
            $response['slots'] = $slots;
            break;

    }
} else {
    $response['message'] = "Requisição inválida ou ação não informada.";
}

DBClose($link);
echo json_encode($response);
?>