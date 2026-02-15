# Oracle Object Storage (S3 Compatible) Skills

## Overview
This skill handles file uploads to Oracle Object Storage using S3-compatible pre-authenticated URLs.
- **Protocol**: HTTP/HTTPS PUT
- **Authentication**: Pre-authenticated Request (PAR) URL. No API Keys needed in the request headers generally for PARs.

## Configuration
- **Storage**: The Pre-authenticated URL is stored in the database.
- **Table**: `ConfiguracoesEmissor`
- **Column**: `api_oracle_url`
- **Management**: Configurable via the "Configurações > Fiscal" page.
    - Example URL: `https://objectstorage.us-ashburn-1.oraclecloud.com/p/.../`

## Upload Process (`app.php`)
1. **Validation**: Checks file type (PDF/Images/XML) and size (Max 10MB).
2. **Naming**: Generates a unique filename: `arquivos/TIMESTAMP_IDFATURA_HASH.EXT`.
3. **Configuration Load**: Fetches `api_oracle_url` from `ConfiguracoesEmissor`.
4. **Upload**: 
    - Uses PHP `curl` extension.
    - Methods: `PUT`
    - Body: Raw file content (`file_get_contents`).
    - Headers: `Content-Type`, `Content-Length`.
5. **Database**: 
    - Inserts metadata into `Arquivos`.
    - Links to Invoice via `FaturaArquivos`.

## Known Issues
- Ensure the Pre-authenticated URL in the database settings is correct, has Write permissions, and hasn't expired.
- Check `upload_max_filesize` and `post_max_size` in `php.ini` if 10MB limit is hit on server side before logic check.