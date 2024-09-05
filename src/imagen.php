<?php

namespace gamboamartin\plugins;

use gamboamartin\errores\errores;
use Zxing\QrReader;

class imagen
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

    function obtener_qr(array $imagenes): string|array
    {
        $old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);

        foreach ($imagenes as $ruta_imagen) {

            $qrcode = new QrReader($ruta_imagen);

            if (empty($qrcode->result)) {
                continue;
            }

            $texto = $qrcode->text();

            if ($texto !== null) {
                error_reporting($old_error_reporting);
                return $ruta_imagen;
            }
        }

        error_reporting($old_error_reporting);

        return $this->error->error(mensaje: "No se encontro un código QR valido", data: $imagenes);
    }

}