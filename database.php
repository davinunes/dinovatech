<?php

define('DB_HOSTNAME', getenv('DB_HOSTNAME') ?: 'xxx');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'xxx');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'xxx');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'xxx');
define('DB_PREFIX', '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'UTF8');

function DBConnect()
{ # Abre Conexão com Database
        $link = @mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        if (!$link) {
                // Em vez de die(), registre o erro e retorne false
                error_log("Erro de conexão com o banco de dados: " . mysqli_connect_error());
                return false;
        }
        mysqli_set_charset($link, DB_CHARSET); // Não use die() aqui
        return $link;
}

function DBClose($link)
{ # Fecha Conexão com Database
        if ($link) { // Garante que a conexão existe antes de tentar fechar
                @mysqli_close($link); // Não use die() aqui
        }
}

// MODIFICADO: Esta função agora recebe a conexão ($link) como parâmetro
// e NÃO abre nem fecha a conexão. Ela apenas executa a query.
function DBExecute($link, $query)
{ # Executa um Comando na Conexão
        if (!$link) {
                error_log("Erro: Conexão com o banco de dados não está ativa para DBExecute.");
                return false;
        }
        $result = mysqli_query($link, $query); // Não use die() aqui, retorne o resultado
        return $result;
}

function dump($el)
{
        echo "<pre>";
        print_r($el);
        echo "</pre>";
}
?>