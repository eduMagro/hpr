# Docupipe OCR integration

This project now relies entirely on [Docupipe](https://docupipe.com) for OCR. The OpenAI Vision path was removed, so you must enable and configure Docupipe before uploads will succeed.

## When Docupipe is used

-   Only routes associated with configured schemas will use Docupipe. The code currently maps:
    -   `siderurgica` → schema defined in `DOCUPIPE_SCHEMA_SISE`
    -   `megasa` → schema defined in `DOCUPIPE_SCHEMA_MEGASA`
-   If `DOCUPIPE_SCHEMA_DEFAULT` is set, that schema is used when the provider is not recognized explicitly.
-   Docupipe is opt-in: the feature is disabled unless `DOCUPIPE_ENABLED=true`.

## Required environment variables

Add the following values to your `.env` (or system overrides) before uploading albaranes:

| Variable                   | Description                                                                        |
| -------------------------- | ---------------------------------------------------------------------------------- |
| `DOCUPIPE_ENABLED`         | Set to `true` to route matching providers through Docupipe. Defaults to `false`.   |
| `DOCUPIPE_BASE_URL`        | Base URL for the Docupipe API (defaults to `https://app.docupipe.com`).            |
| `DOCUPIPE_SUBMIT_PATH`     | Path appended to the base URL when sending documents (`/v1/documents` by default). |
| `DOCUPIPE_API_KEY`         | API key from your Docupipe account.                                                |
| `DOCUPIPE_SCHEMA_SISE`     | Schema name that handles the SISE documents (e.g. `SISE`).                         |
| `DOCUPIPE_SCHEMA_MEGASA`   | Schema name that handles the Megasa documents (e.g. `MEGASA`).                     |
| `DOCUPIPE_SCHEMA_DEFAULT`  | Optional fallback schema used for other providers when enabled.                    |
| `DOCUPIPE_REQUEST_TIMEOUT` | Timeout in seconds for uploading documents. Defaults to `120`.                     |

## Notes

-   Docupipe receives the same image (or the first page of a PDF converted to JPEG) that was previously sent to OpenAI, and the raw JSON it returns is stored verbatim in the `entrada_import_logs` table.
-   Laravel now logs the Docupipe request/response around `extractWithDocupipe`. Check `storage/logs/laravel.log` for entries like `Docupipe request start` or `Docupipe response received` to see the schema, status and duration; if a response comes back empty you will also get a `Docupipe response returned empty payload` warning with the body so you can inspect what Docupipe actually sent before determining whether to adjust `normalizeDocupipeResponse`.
-   If you adjust your schema names in Docupipe, update the corresponding environment variables so the right provider maps to the right schema.
