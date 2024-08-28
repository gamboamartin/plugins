<?php

namespace gamboamartin\plugins;

use Exception;
use gamboamartin\errores\errores;
use Smalot\PdfParser\Parser;

class PDF
{
    public errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }

    public function leer_pdf(string $directorio, string $prefijo_imagen, string $ruta_pdf): string|array
    {
        $texto = $this->leer_texto_pdf(ruta_pdf: $ruta_pdf);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al leer el texto del pdf', data: $texto);
        }

        $imagenes = $this->leer_imagen_pdf(directorio: $directorio, prefijo_imagen: $prefijo_imagen,
            ruta_pdf: $ruta_pdf);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al leer las imágenes del pdf', data: $imagenes);
        }

        return array('texto' => $texto, 'imagenes' => $imagenes);
    }

    public function leer_texto_pdf(string $ruta_pdf): string|array
    {
        $parser = new Parser();

        try {
            $pdf = $parser->parseFile($ruta_pdf);
            return $pdf->getText();
        } catch (Exception $e) {
            return $this->error->error(mensaje: 'Error al leer pdf', data: $e->getMessage());
        }
    }

    public function leer_imagen_pdf(string $directorio, string $prefijo_imagen, string $ruta_pdf): array
    {
        $imagenes = [];

        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $output_dir = realpath($directorio) . DIRECTORY_SEPARATOR . $prefijo_imagen;
        $command = escapeshellcmd("pdfimages -png \"$ruta_pdf\" \"$output_dir\"");

        $output = [];
        $return_var = null;
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            return $this->error->error(mensaje: "Error al ejecutar pdfimages. Código de retorno: $return_var",
                data: $return_var);
        }

        foreach (glob("$output_dir-*.png") as $imagePath) {
            $imagenes[] = $imagePath;
        }

        return $imagenes;
    }
}
