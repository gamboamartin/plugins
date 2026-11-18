#!/bin/sh
set -eu
RAIZ=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
mkdir -p "$RAIZ/.git/qa/tmp"
cd "$RAIZ"
BLOQUEADAS='shell_exec,exec,system,passthru,proc_open,popen,pcntl_exec,fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec,socket_create,socket_connect,ftp_connect'
php -n -d allow_url_fopen=0 -d allow_url_include=0 -d "disable_functions=$BLOQUEADAS" pruebas/openssl/probar.php
if [ -n "${FUENTE_ERRORES:-}" ] && [ -n "${FUENTE_CERT_FC3:-}" ]; then
    php -n -d allow_url_fopen=0 -d allow_url_include=0 -d "disable_functions=$BLOQUEADAS" pruebas/openssl/contrato_fc3.php
else
    printf '%s\n' 'PENDIENTE contrato FC3: definir FUENTE_ERRORES y FUENTE_CERT_FC3 con los hashes documentados.'
    exit 2
fi
