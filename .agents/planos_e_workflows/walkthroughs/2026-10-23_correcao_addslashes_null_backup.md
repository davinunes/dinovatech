# Walkthrough: Correção de Deprecation `addslashes(): Passing null` na Rotina de Backup

## 1. Problema
No PHP 8.1+, a função `addslashes()` deixou de aceitar `null` silenciosamente, emitindo o seguinte aviso:
```text
Deprecated: addslashes(): Passing null to parameter #1 ($string) of type string is deprecated in /var/www/html/dinovatech/app.php on line 1478
```
Isso ocorria repetidamente durante a execução da ação `fazer_backup`, poluindo a saída HTTP e quebrando a integridade de colunas `NULL` no arquivo `.sql` gerado.

## 2. Solução Aplicada
Em [dinovatech/app.php](file:///d:/dev/github/dinovatech/dinovatech/app.php):
- Validado se o valor retornado do banco é `null` antes de qualquer manipulação de string:
  ```php
  if ($row[$j] === null) {
      $dadosContent .= 'NULL';
  } else {
      $val = addslashes((string)$row[$j]);
      $val = str_replace(["\r\n", "\r", "\n"], ["\\r\\n", "\\r", "\\n"], $val);
      $dadosContent .= '"' . $val . '"';
  }
  ```

## 3. Benefícios
1. **Compatibilidade PHP 8.1 / 8.2 / 8.3**: Fim dos avisos de depreciação na saída da requisição.
2. **Integridade do Backup SQL**: Colunas `NULL` agora são exportadas fielmente como `NULL` (sem aspas) em vez de strings vazias `""`.
