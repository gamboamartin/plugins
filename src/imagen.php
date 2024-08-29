<?php

namespace gamboamartin\plugins;

use gamboamartin\errores\errores;
use Zxing\QrReader;

class Imagen
{
    public errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }

    public function leer_codigo_qr(string $ruta_qr): string|array
    {
        if (!file_exists($ruta_qr)) {
            return $this->error->error(mensaje: "La imagen no existe", data: $ruta_qr);
        }

        $old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);

        $qrcode = new QrReader($ruta_qr);
        $texto = $qrcode->text();

        error_reporting($old_error_reporting);

        if (empty($texto)) {
            return $this->error->error(mensaje: "No se pudo leer el código QR", data: $texto);
        }

        return $texto;
    }

}