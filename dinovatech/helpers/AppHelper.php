<?php

class AppHelper
{
    public static function isVetMode()
    {
        // Verifica se a constante foi definida pelo config.php
        if (defined('APP_MODE_VET')) {
            return APP_MODE_VET === true || APP_MODE_VET === 'true' || APP_MODE_VET === 1 || APP_MODE_VET === '1';
        }

        // Fallback para verificar variável de ambiente diretamente
        $env = getenv('APP_MODE_VET');
        return $env === 'true' || $env === '1';
    }

    public static function getCompanyName()
    {
        $dbPath = dirname(__DIR__) . '/database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $link = DBConnect();
        if (!$link)
            return 'DinovaTech';

        $query = "SELECT nome_fantasia FROM ConfiguracoesEmissor LIMIT 1";
        $res = mysqli_query($link, $query);
        $name = 'DinovaTech';
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (!empty($row['nome_fantasia'])) {
                $name = $row['nome_fantasia'];
            }
        }
        DBClose($link);
        return $name;
    }
}
