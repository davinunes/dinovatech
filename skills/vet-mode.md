# Vet Mode (Clinic Mode) Skills

## Overview
The application has a specialized "Vet Mode" (or Clinic Mode) that alters certain behaviors and UI elements to be tailored for veterinary clinics.

## Configuration
- **Environment Variable**: `APP_MODE_VET`
- **Values**: `true` or `1` enables the mode. Any other value disables it.
- **Definition**: Can be set in `config.php` (as a constant) or in the server environment variables.

## Implementation
- **Helper**: `AppHelper::isVetMode()`
- **Usage**:
    ```php
    if (AppHelper::isVetMode()) {
        // Show Vet-specific fields (e.g., Pet Name, Breed)
        // Hide standard business fields
    }
    ```

## Affected Areas
- **Invoices**: May display Pet/Patient details instead of standard service descriptions.
- **Clients**: Client registration may include pet ownership details.
- **Reports**: customized for clinical outputs.

## Development
When developing new features, check if `isVetMode()` affects the logic. Use the helper method rather than checking the environment variable directly.
