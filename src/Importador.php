<?php

namespace gamboamartin\plugins;

use gamboamartin\errores\errores;
use JsonException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use stdClass;

class Importador
{
    public errores $error;
    private static $instance;

    private function __construct()
    {
        $this->error = new errores();
    }

    public static function getInstance(): Importador
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Lee los registros de un archivo excel
     * @param string $ruta_absoluta // ruta del archivo a leer
     * @param array $columnas // Nombre de columnas que contiene el archivo
     * @param array $fechas // Nombre de columnas que aplican para formato fecha
     * @param string $inicio // Donde iniciara a leer los registros
     * @return array
     * @throws JsonException
     */
    public function leer_registros(string $ruta_absoluta, array $columnas, array $fechas = array(), string $inicio = 'A1'): array
    {
        $inputFileType = IOFactory::identify($ruta_absoluta);
        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($ruta_absoluta);
        $sheet = $spreadsheet->getSheet(0);
        $maxCell = $sheet->getHighestRowAndColumn();
        $rows = $sheet->rangeToArray("$inicio:" . $maxCell['column'] . $maxCell['row']);

        $salida = array();

        for ($i = 1; $i < count($rows); $i++) {
            $registros = new stdClass();
            for ($j = 0; $j < count($rows[$i]); $j++) {

                if (count($rows[$i]) !== count($columnas)) {
                    return $this->error->error('Error: el numero de columnas no coincide', $columnas);
                }

                $columna = $columnas[$j];
                $registros->$columna = !is_null($rows[$i][$j])? str_replace("'", "", $rows[$i][$j]) : $rows[$i][$j];

                if (in_array($columna, $fechas) && !empty($registros->$columna)) {
                    if (strtotime($registros->$columna)) {
                        $registros->$columna = Date::PHPToExcel($registros->$columna);
                    }

                    if (!is_numeric($registros->$columna)) {
                        return $this->error->error('Error: la fecha no tiene el formato correcto', $registros->$columna);
                    }

                    $registros->$columna = Date::excelToDateTimeObject($registros->$columna)->format('Y-m-d');
                }
            }
            $salida[] = $registros;
        }

        return $salida;
    }
}