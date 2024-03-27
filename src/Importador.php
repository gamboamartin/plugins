<?php

namespace gamboamartin\plugins;

use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use JsonException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use stdClass;
use Throwable;

class Importador
{
    public errores $error;
    private static $instance;

    public function __construct()
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

    final public function leer(string $ruta_absoluta)
    {
        $columns = $this->primer_row(celda_inicio: 'A1',ruta_absoluta:  $ruta_absoluta);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener columns',data:  $columns);
        }

        $rows = $this->leer_registros(ruta_absoluta:  $ruta_absoluta, columnas: $columns);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener rows',data:  $rows);
        }

        $datos = new stdClass();
        $datos->columns = $columns;
        $datos->rows = $rows;

        return $datos;

    }

    /**
     * Lee los registros de un archivo excel
     * @param string $ruta_absoluta // ruta del archivo a leer
     * @param array $columnas // Nombre de columnas que contiene el archivo
     * @param array $fechas // Nombre de columnas que aplican para formato fecha
     * @param string $inicio // Donde iniciara a leer los registros
     * @return array
     */
    final public function leer_registros(string $ruta_absoluta, array $columnas, array $fechas = array(),
                                         string $inicio = 'A1'): array
    {
        $inputFileType = IOFactory::identify($ruta_absoluta);

        $rows = $this->rows(celda_inicio: $inicio,inputFileType:  $inputFileType,ruta_absoluta:  $ruta_absoluta);
        if(errores::$error){
            return $this->error->error('Error al obtener rows de archivo', $rows);
        }

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

    final public function primer_row(string $celda_inicio, string $ruta_absoluta)
    {
        $inputFileType = IOFactory::identify($ruta_absoluta);

        $rows = $this->rows(celda_inicio: $celda_inicio,inputFileType:  $inputFileType,
            ruta_absoluta: $ruta_absoluta,max_cell_row: 1);
        if(errores::$error){
            return $this->error->error('Error al obtener row de archivo', $rows);
        }
        return $rows[0];
    }

    private function rows(string $celda_inicio, string $inputFileType, string $ruta_absoluta,
                          int $max_cell_row = -1): array
    {

        $valida = $this->valida_in_calc(celda_inicio: $celda_inicio,inputFileType:  $inputFileType,
            ruta_absoluta:  $ruta_absoluta);
        if(errores::$error){
            return $this->error->error('Error al validar parametros', $valida);
        }

        try {
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($ruta_absoluta);
            $sheet = $spreadsheet->getSheet(0);
            $maxCell = $sheet->getHighestRowAndColumn();
            if($max_cell_row === -1){
                $max_cell_row = $maxCell['row'];
            }
            $rows = $sheet->rangeToArray("$celda_inicio:" . $maxCell['column'] . $max_cell_row);
        }
        catch (Throwable $e){
            return $this->error->error('Error: al leer datos', $e);
        }
        return $rows;
    }

    /**
     * POR DOCUMENTAR EN WIKI FINAL REV
     * Esta método privado valida information para un proceso de importación en una hoja de cálculo.
     *
     * @param string $celda_inicio   La celda de inicio en una hoja de cálculo.
     * @param string $inputFileType  El tipo de archivo que se va a importar (ej. 'Xlsx' , 'Csv').
     * @param string $ruta_absoluta  La ruta absoluta del archivo que se va a importar.
     *
     * Primero, esta función verifica si la celda_inicio, el inputFileType y la ruta_absoluta están vacíos. Si está vacío, muestra un error.
     * Después, verifica si la ruta_absoluta existe. Si no existe, muestra un error.
     * Luego, valida si la celda de inicio es una celda válida en la hoja de cálculo.
     * Si todo es válido, la función devuelve true, de lo contrario, muestra un error.
     *
     * @return true|array Retorna `true` si todas las validaciones pasan correctamente, de lo contrario retorna `false`.
     *
     * @throws errores  Esta excepción es lanzada si ocurrió un error durante las validaciones.
     * @version 6.6.0
     */
    private function valida_in_calc(string $celda_inicio, string $inputFileType, string $ruta_absoluta): true|array
    {
        $celda_inicio = trim($celda_inicio);
        if($celda_inicio === ''){
            return $this->error->error('Error: celda_inicio esta vacia', $celda_inicio, es_final: true);
        }
        $inputFileType = trim($inputFileType);
        if($inputFileType === ''){
            return $this->error->error('Error: inputFileType esta vacia', $inputFileType, es_final: true);
        }
        $ruta_absoluta = trim($ruta_absoluta);
        if($ruta_absoluta === ''){
            return $this->error->error('Error: ruta_absoluta esta vacia', $ruta_absoluta, es_final: true);
        }
        if(!file_exists($ruta_absoluta)){
            return $this->error->error('Error: ruta_absoluta no existe doc', $ruta_absoluta, es_final: true);
        }
        $valida = (new validacion())->valida_celda_calc(celda: $celda_inicio);
        if(errores::$error){
            return $this->error->error('Error al validar celda_inicio', $valida);
        }
        return true;

    }
}