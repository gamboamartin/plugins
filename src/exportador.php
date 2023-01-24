<?php
namespace gamboamartin\plugins;
use gamboamartin\errores\errores;
use gamboamartin\plugins\exportador\datos;
use gamboamartin\plugins\exportador\estilos;
use gamboamartin\plugins\exportador\output;
use JsonException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Throwable;

/**
 * PARAMS ORDER INTERNALS
 */
class exportador{
    public array $columnas;
    public array $estilo_titulos;
    public array $estilo_contenido;
    public array $estilos;
    public Spreadsheet $libro;
    public errores $error;
    private int $num_hojas;

    public function __construct(int $num_hojas = 1){
        $this->libro =  new Spreadsheet();

        $letras = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S',
            'T','U','V','W','X','Y','Z');

        $columnas = array();
        foreach ($letras as $letra){
            $columnas[] = $letra;
        }

        foreach ($letras as $letra){
            foreach ($letras as $letra_bis){
                $letra_integrar = $letra.$letra_bis;
                $columnas[] = $letra_integrar;
            }
        }


        $this->columnas =  $columnas;

        $this->estilo_titulos = array(
            'font'  => array(
                'bold'  => true,
                'size'  => 8,
                'name'  => 'Verdana'
            ));

        $this->estilo_contenido = array(
            'font'  => array(
                'size'  => 8,
                'name'  => 'Verdana'
            ));

        $this->error = new errores();

        $this->estilos['txt_numero'] = '@';
        $this->estilos['fecha'] = 'yyyy-mm-dd';
        $this->estilos['moneda'] = '[$$-80A]#,##0.00;[RED]-[$$-80A]#,##0.00';

        if ($num_hojas < 1) {
            $error = $this->error->error('Error $num_hojas no puede ser menor a 1', $num_hojas);
            print_r($error);
            die('Error');
        }

        $this->num_hojas = $num_hojas;

    }

    public function genera_xls(bool  $header, string $name, array $nombre_hojas, array $keys_hojas, string $path_base,
                               array $size_columnas = array(), array $centers = array(), array $moneda = array(),
                               array $moneda_sin_decimal = array()): array|string
    {
        if(trim($name) === ''){
            $error = $this->error->error('Error al $name no puede venir vacio', $name);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        if (empty($nombre_hojas)) {
            $error = $this->error->error('Error nombre_hojas no puede venir vacio', $nombre_hojas);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        if (sizeof($nombre_hojas) !== $this->num_hojas) {
            $error = $this->error->error('Error tiene que existir la misma cantidad de nombres de hojas que 
            el total de $num_hojas declaradas', $nombre_hojas);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        foreach ($nombre_hojas as $nombre_hoja) {
            if (trim($nombre_hoja) === '') {
                $error = $this->error->error('Error $nombre_hoja no puede venir vacio', $nombre_hoja);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            if (!is_string($nombre_hoja)) {
                $error = $this->error->error('Error $nombre_hoja tiene que ser una cadena de texto', $nombre_hoja);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }
        }

        $libro = new Spreadsheet();
        $libro->createSheet();

        foreach ($nombre_hojas as $index => $nombre_hoja) {

            if (!array_key_exists($nombre_hoja, $keys_hojas)) {
                $error = $this->error->error("Error ($nombre_hoja) no es un objeto", $keys_hojas);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            if (!property_exists($keys_hojas[$nombre_hoja], "keys")) {
                $error = $this->error->error("Error ($nombre_hoja) no tiene asignado la propiedad keys", $keys_hojas);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            if (!property_exists($keys_hojas[$nombre_hoja], "registros")) {
                $error = $this->error->error("Error ($nombre_hoja) no tiene asignado la propiedad registros", $keys_hojas);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            if (!is_array($keys_hojas[$nombre_hoja]->keys)) {
                $error = $this->error->error("Error la propiedad keys de ($nombre_hoja) no es un array", $keys_hojas);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            if (!is_array($keys_hojas[$nombre_hoja]->registros)) {
                $error = $this->error->error("Error la propiedad registros de ($nombre_hoja) no es un array", $keys_hojas);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }



            $libro = (new datos())->genera_datos_libro(dato: $nombre_hoja, libro: $libro);
            if (errores::$error) {
                $error = $this->error->error('Error al generar datos del libro', $libro);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            $genera_encabezados = (new datos())->genera_encabezados(columnas: $this->columnas, index: $index,
                keys: $keys_hojas[$nombre_hoja]->keys, libro: $libro);
            if (errores::$error) {
                $error = $this->error->error('Error al generar $genera_encabezados', $genera_encabezados);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            $llenado = (new datos())->llena_libro_xls(columnas: $this->columnas, estilo_contenido: $this->estilo_contenido,
                estilos: $this->estilos, index: $index, keys: $keys_hojas[$nombre_hoja]->keys, libro: $libro, path_base: $path_base,
                registros: $keys_hojas[$nombre_hoja]->registros, totales: array());
            if (errores::$error) {
                $error = $this->error->error('Error al generar $llenado', $llenado);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            $estilos_titulo = (new estilos())->asigna_estilos_titulo(estilo_titulos: $this->estilo_titulos, libro: $libro);
            if (isset($estilos_titulo['error'])) {
                $error = $this->error->error('Error al aplicar $estilos_titulo', $estilos_titulo);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            $autosize = (new estilos())->aplica_autosize(columnas: $this->columnas, keys: $keys_hojas[$nombre_hoja]->keys,
                libro: $libro);
            if (errores::$error) {
                $error = $this->error->error('Error en autosize', $autosize);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

            foreach ($size_columnas as $columna => $size_column) {
                $libro->getActiveSheet()->getColumnDimension($columna)->setAutoSize(false);
                $libro->getActiveSheet()->getColumnDimension($columna)->setWidth($size_column);
            }

            foreach ($centers as $center) {
                $style = array(
                    'alignment' => array(
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    )
                );

                $count = count($keys_hojas[$nombre_hoja]->registros) + 1;
                $libro->getActiveSheet()->getStyle($center . '1:' . $center . $count)->applyFromArray($style);
            }

            foreach ($moneda_sin_decimal as $column) {
                $count = count($keys_hojas[$nombre_hoja]->registros) + 1;
                $libro->getActiveSheet()->getStyle(
                    $column . '1:' . $column . $count)->getNumberFormat()->setFormatCode("$#,00");
            }

            foreach ($moneda as $column) {
                $count = count($keys_hojas[$nombre_hoja]->registros) + 1;
                $libro->getActiveSheet()->getStyle(
                    $column . '1:' . $column . $count)->getNumberFormat()->setFormatCode(
                    NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            }

            try {
                $libro->getActiveSheet()->setTitle(substr($nombre_hoja, 0, 31));
                $libro->setActiveSheetIndex($index);
            } catch (Throwable $e) {
                $error = $this->error->error('Error al aplicar generar datos del libro', $e);
                if (!$header) {
                    return $error;
                }
                print_r($error);
                die('Error');
            }

        }

        $data = (new output())->genera_salida_xls(header: $header, libro: $libro, name: $name, path_base: $path_base);
        if (isset($data['error'])) {
            $error = $this->error->error('Error al aplicar generar salida', $data);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        if (!$header) {
            return $data;
        }
        exit;
    }


    /**
     * PARAMS ORDER INTERNALS
     * @param bool $header
     * @param string $name
     * @param array $keys
     * @param string $path_base
     * @param array $registros
     * @param array $totales
     * @param array $centers
     * @param int $index
     * @param array $moneda
     * @param array $moneda_sin_decimal
     * @param array $size_columnas
     * @return array|string
     * @throws JsonException
     */
    public function listado_base_xls( bool $header, string $name, array $keys, string $path_base, array $registros,
                                      array  $totales, array $centers = array(), int $index = 0,
                                      array $moneda = array(), array $moneda_sin_decimal = array(),
                                      array $size_columnas= array()): array|string
    {

        if(trim($name) === ''){
            $error = $this->error->error('Error al $name no puede venir vacio', $name);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }
        $libro = new Spreadsheet();
        $libro = (new datos())->genera_datos_libro(dato: $name, libro: $libro);
        if(errores::$error){
            $error = $this->error->error('Error al aplicar generar datos del libro',$libro);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $genera_encabezados = (new datos())->genera_encabezados(columnas: $this->columnas, index: $index,
            keys: $keys,libro: $libro);
        if(errores::$error){
            $error = $this->error->error('Error al generar $genera_encabezados',$genera_encabezados);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $llenado = (new datos())->llena_libro_xls(columnas:$this->columnas, estilo_contenido:$this->estilo_contenido,
            estilos: $this->estilos, index: $index, keys: $keys,libro: $libro, path_base: $path_base,
            registros: $registros, totales:  $totales);

        if(errores::$error){
            $error = $this->error->error('Error al generar $llenado',$llenado);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $estilos_titulo = (new estilos())->asigna_estilos_titulo(estilo_titulos:$this->estilo_titulos, libro: $libro);
        if(isset($estilos_titulo['error'])){
            $error = $this->error->error('Error al aplicar $estilos_titulo',$estilos_titulo);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $autosize = (new estilos())->aplica_autosize(columnas: $this->columnas, keys: $keys,libro: $libro);
        if(errores::$error){
            $error = $this->error->error('Error en autosize',$autosize);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        try {
            $libro->getActiveSheet()->setTitle(substr($name, 0, 31));
            $libro->setActiveSheetIndex(0);
        }
        catch (Throwable $e){
            $error = $this->error->error('Error al aplicar generar datos del libro', $e);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }


        foreach ($size_columnas as $columna =>$size_column){

            $libro->getActiveSheet()->getColumnDimension($columna)->setAutoSize(false);
            $libro->getActiveSheet()->getColumnDimension($columna)->setWidth($size_column);
        }

        foreach ($centers as $center){
            $style = array(
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                )
            );

            $count = count($registros)+1;
            $libro->getActiveSheet()->getStyle($center.'1:'.$center.$count)->applyFromArray($style);
        }

        foreach ($moneda_sin_decimal as $column){
            $count = count($registros)+1;
            $libro->getActiveSheet()->getStyle(
                $column.'1:'.$column.$count)->getNumberFormat()->setFormatCode("$#,00");
        }

        foreach ($moneda as $column){
            $count = count($registros)+1;
            $libro->getActiveSheet()->getStyle(
                $column.'1:'.$column.$count)->getNumberFormat()->setFormatCode(
                    NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        }


        $data = (new output())->genera_salida_xls(header: $header, libro: $libro,name: $name,path_base: $path_base);
        if(isset($data['error'])){
            $error = $this->error->error('Error al aplicar generar salida',$data);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        if(!$header){
            return $data;
        }
        exit;
    }

}
