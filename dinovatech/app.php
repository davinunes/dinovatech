<?php
// app.php

include "../database.php"; // Seu arquivo com DBConnect, DBExecute, etc.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'criar_cliente':
            $nome = $_POST['nome'] ?? '';
            $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';

            if (empty($nome) || empty($cpf_cnpj)) {
                $response['message'] = "Nome e CPF/CNPJ são obrigatórios.";
            } else {
                $nome = mysqli_real_escape_string($link, $nome);
                $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
                $telefone = mysqli_real_escape_string($link, $telefone);
                $email = mysqli_real_escape_string($link, $email);
                
                $query = "INSERT INTO Clientes (nome, cpf_cnpj, telefone, email) 
                          VALUES ('$nome', '$cpf_cnpj', '$telefone', '$email')";
                
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
                $query = "SELECT id_cliente, nome, cpf_cnpj, telefone, email FROM Clientes WHERE id_cliente = '$id_cliente'";
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
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';

            if (empty($id_cliente) || empty($nome) || empty($cpf_cnpj)) {
                $response['message'] = "ID do cliente, Nome e CPF/CNPJ são obrigatórios para edição.";
            } else {
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $nome = mysqli_real_escape_string($link, $nome);
                $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
                $telefone = mysqli_real_escape_string($link, $telefone);
                $email = mysqli_real_escape_string($link, $email);

                $query = "UPDATE Clientes SET nome = '$nome', cpf_cnpj = '$cpf_cnpj', telefone = '$telefone', email = '$email' WHERE id_cliente = '$id_cliente'";
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

                $query = "INSERT INTO Servicos (nome_servico, valor_sugerido) 
                          VALUES ('$nome_servico', '$valor_sugerido')";
                
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
                $query = "SELECT id_servico, nome_servico, valor_sugerido FROM Servicos WHERE id_servico = '$id_servico'";
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

                $query = "UPDATE Servicos SET nome_servico = '$nome_servico', valor_sugerido = '$valor_sugerido' WHERE id_servico = '$id_servico'";
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

                $query = "INSERT INTO Faturas (id_cliente, data_emissao, data_vencimento, status) 
                          VALUES ('$id_cliente', '$data_emissao', '$data_vencimento', 'Em Aberto')";
                
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

					// A query da fatura principal não muda
					$query_fatura = "SELECT F.id_fatura, C.nome AS nome_cliente, F.data_emissao, F.data_vencimento, F.valor_total_fatura, F.status
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

                $query = "INSERT INTO Recorrencias (id_cliente, id_servico, quantidade, valor_sugerido_recorrencia, tipo_periodo, intervalo, data_inicio_cobranca, data_fim_cobranca)
                          VALUES ('$id_cliente', '$id_servico', '$quantidade', '$valor_sugerido_recorrencia', '$tipo_periodo', '$intervalo', '$data_inicio_cobranca', $data_fim_cobranca)";
                
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

        case 'incorporar_recorrencias_na_fatura': // Incorporar recorrências em uma fatura
            $id_fatura = $_POST['id_fatura'] ?? '';
            $id_cliente = $_POST['id_cliente'] ?? '';
            $mes_ano_fatura = $_POST['mes_ano_fatura'] ?? ''; // Formato YYYY-MM

            if (empty($id_fatura) || empty($id_cliente) || empty($mes_ano_fatura)) {
                $response['message'] = "Dados insuficientes para incorporar recorrências.";
            } else {
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);
                $id_cliente = mysqli_real_escape_string($link, $id_cliente);
                $mes_ano_fatura = mysqli_real_escape_string($link, $mes_ano_fatura);

                // 1. Buscar recorrências ativas para o cliente e para o mês/ano da fatura
                // E que AINDA NÃO ESTEJAM NESSA FATURA
                $query_recorrencias = "SELECT R.id_recorrencia, R.id_servico, R.quantidade, R.valor_sugerido_recorrencia, S.nome_servico
                                       FROM Recorrencias R
                                       JOIN Servicos S ON R.id_servico = S.id_servico
                                       WHERE R.id_cliente = '$id_cliente'
                                         AND R.data_inicio_cobranca <= '$mes_ano_fatura-31' 
                                         AND (R.data_fim_cobranca IS NULL OR R.data_fim_cobranca >= '$mes_ano_fatura-01')
                                         AND NOT EXISTS (SELECT 1 FROM ItensFatura WHERE id_fatura = '$id_fatura' AND id_recorrencia = R.id_recorrencia)"; // CORRIGIDO: Verifica se o item da recorrência já existe na fatura
                
                $result_recorrencias = DBExecute($link, $query_recorrencias);
                $itens_incorporados = 0;

                if ($result_recorrencias) {
                    while ($rec = mysqli_fetch_assoc($result_recorrencias)) {
                        $servico_id = mysqli_real_escape_string($link, $rec['id_servico']);
                        $quantidade = mysqli_real_escape_string($link, $rec['quantidade']);
                        $valor_unitario = mysqli_real_escape_string($link, $rec['valor_sugerido_recorrencia']);
                        $tag = mysqli_real_escape_string($link, "Recorrência - " . $rec['nome_servico'] . " (" . $mes_ano_fatura . ")");
                        $id_recorrencia_original = mysqli_real_escape_string($link, $rec['id_recorrencia']);

                        // 2. Inserir o item na fatura
                        $query_insert_item = "INSERT INTO ItensFatura (id_fatura, id_servico, quantidade, valor_unitario, tag, id_recorrencia)
                                              VALUES ('$id_fatura', '$servico_id', '$quantidade', '$valor_unitario', '$tag', '$id_recorrencia_original')"; // AGORA SALVA id_recorrencia
                        
                        $item_inserted = DBExecute($link, $query_insert_item);

                        if ($item_inserted) {
                            $itens_incorporados++;
                            // 3. Atualizar a recorrência com a última fatura gerada (para evitar duplicação em futuras execuções em NOVAS FATURAS)
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
            }
            break;

        case 'registrar_pagamento': // Registrar um pagamento
            $id_fatura = $_POST['id_fatura'] ?? '';
            $valor_pago = $_POST['valor_pago'] ?? '';
            $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
            $status_pagamento = $_POST['status_pagamento'] ?? 'Pendente';
            $observacao = $_POST['observacao'] ?? NULL;
            $itens_pagos_json = $_POST['itens_pagos_json'] ?? NULL;

            if (empty($id_fatura) || !is_numeric($valor_pago) || $valor_pago < 0 || empty($data_pagamento) || empty($status_pagamento)) { // Valor pode ser 0 para pagamentos parciais
                $response['message'] = "Dados obrigatórios do pagamento ausentes ou inválidos.";
            } else {
                $id_fatura = mysqli_real_escape_string($link, $id_fatura);
                $valor_pago = mysqli_real_escape_string($link, $valor_pago);
                $data_pagamento = mysqli_real_escape_string($link, $data_pagamento);
                $status_pagamento = mysqli_real_escape_string($link, $status_pagamento);
                $observacao = $observacao ? mysqli_real_escape_string($link, $observacao) : NULL;
                $itens_pagos_json = $itens_pagos_json ? mysqli_real_escape_string($link, $itens_pagos_json) : "NULL"; // Garante NULL se vazio

                $query_insert_pagamento = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento, observacao, itens_pagos_json)
                                           VALUES ('$id_fatura', '$valor_pago', '$data_pagamento', '$status_pagamento', " . ($observacao ? "'$observacao'" : "NULL") . ", " . $itens_pagos_json . ")"; // Removido aspas simples extras
                
                $result_insert_pagamento = DBExecute($link, $query_insert_pagamento);

                if ($result_insert_pagamento) {
                    // Atualiza o status da fatura com base no total pago
                    $query_update_fatura_status = "
                        UPDATE Faturas F
                        SET status = CASE
                            WHEN (SELECT COALESCE(SUM(P.valor_pago), 0) FROM Pagamentos P WHERE P.id_fatura = F.id_fatura AND P.status_pagamento = 'Confirmado') >= F.valor_total_fatura THEN 'Liquidada'
                            ELSE 'Em Aberto' 
                        END
                        WHERE F.id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_fatura_status);

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
                    $query_update_fatura_status = "
                        UPDATE Faturas F
                        SET status = CASE
                            WHEN (SELECT COALESCE(SUM(P.valor_pago), 0) FROM Pagamentos P WHERE P.id_fatura = F.id_fatura AND P.status_pagamento = 'Confirmado') >= F.valor_total_fatura THEN 'Liquidada'
                            ELSE 'Em Aberto' 
                        END
                        WHERE F.id_fatura = '$id_fatura'";
                    DBExecute($link, $query_update_fatura_status);

                    $response['success'] = true;
                    $response['message'] = "Pagamento estornado com sucesso!";
                } else {
                    $response['message'] = "Erro ao estornar pagamento: " . mysqli_error($link);
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

        default:
            $response['message'] = "Ação inválida.";
            break;
    }
} else {
    $response['message'] = "Requisição inválida (apenas POST permitido).";
}

DBClose($link);
echo json_encode($response);
?>
