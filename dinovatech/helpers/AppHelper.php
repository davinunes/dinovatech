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
}
