<?php
namespace gamboamartin\plugins;
use gamboamartin\errores\errores;
use JsonException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function __construct(){
        $this->libro =  new Spreadsheet();
        $this->columnas =  array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S',
            'T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO',
            'AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ',
            'BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT');

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

    }

    /**
     *  PROBADO-PARAMS-ORDER INTERNALS
     * @param array $keys
     * @param Spreadsheet $libro
     * @return string[]
     */
    PUBLIC function aplica_autosize(array $keys,Spreadsheet $libro):array{

        $i = 0;
        foreach($keys as $campo){
            try {
                $libro->getActiveSheet()->getColumnDimension($this->columnas[$i])->setAutoSize(true);
                $i++;
            }
            catch (Throwable $e){
                return $this->error->error('Error en autosize',$e);
            }
        }
        return $this->columnas;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param Spreadsheet $libro
     * @return array[]
     */
    public function asigna_estilos_titulo(Spreadsheet $libro): array{ //FIN PROT
        try {
            $libro->getActiveSheet()->getStyle('A1:Z1')->applyFromArray($this->estilo_titulos);
        }
        catch (Throwable $e){
            return $this->error->error('Error al aplicar autosize',$e);
        }
        return $this->estilo_titulos;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param Spreadsheet $libro
     * @param string $dato
     * @return array|Spreadsheet
     */
    private function genera_datos_libro(string $dato, Spreadsheet $libro):array|Spreadsheet{ //FIN PROT
        if(trim($dato) === ''){
            return $this->error->error('Error en dato',$dato);
        }
        try {
            $libro->getProperties()->setCreator("Sistema")
                ->setLastModifiedBy("Sistema")
                ->setTitle($dato)
                ->setSubject($dato)
                ->setDescription($dato)
                ->setKeywords($dato)
                ->setCategory($dato);
        }
        catch (Throwable $e){
            return $this->error->error('Error en dato libro xls', $e);
        }

        return $libro;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param array $keys
     * @param Spreadsheet $libro
     * @param int $index
     * @return string[]
     */
    private function genera_encabezados(int $index, array $keys,Spreadsheet $libro ): array
    {
        $fila = 1;
        $i = 0;
        foreach($keys as $key){
            try {
                $libro->setActiveSheetIndex($index)
                    ->setCellValue($this->columnas[$i] . $fila, $key);
                $i++;
            }
            catch (Throwable $e){
                return $this->error->error('Error al aplicar key en xls',
                    array($e,$this->columnas[$i],$fila,$key));
            }
        }
        return array('mensaje'=>'encabezados aplicados');
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param bool $header
     * @param Spreadsheet $libro
     * @param string $name
     * @param string $path_base
     * @return array|string
     */
    public function genera_salida_xls(bool $header, Spreadsheet $libro, string $name, string $path_base): array|string
    {
        if(trim($name) === ''){
            $error = $this->error->error('Error al name no puede venir vacio',$name);

            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }
        if($header) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');

            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            try {
                $writer = new Xlsx($libro);
                $writer->save('php://output');
            }
            catch (Throwable $e){
                $error = $this->error->error('Error al dar salida del archivo',$e);
                print_r($error);
                die('Error');
            }
            exit;
        }

        try {
            $writer = new Xlsx($libro);
            $name_file = $path_base . 'archivos/' . time() . '.xlsx';
            $writer->save($name_file);
        }
        catch (Throwable $e){
            return $this->error->error('Error al dar salida del archivo',$e);
        }

        $data_64 = base64_encode(file_get_contents($name_file));
        unlink($name_file);

        return $data_64;
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
        $libro = $this->genera_datos_libro(dato: $name, libro: $libro);
        if(errores::$error){
            $error = $this->error->error('Error al aplicar generar datos del libro',$libro);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $genera_encabezados = $this->genera_encabezados(index: $index, keys: $keys,libro: $libro);
        if(errores::$error){
            $error = $this->error->error('Error al generar $genera_encabezados',$genera_encabezados);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $llenado = $this->llena_libro_xls(index: $index, keys: $keys,libro: $libro, path_base: $path_base,
            registros: $registros, totales:  $totales);

        if(errores::$error){
            $error = $this->error->error('Error al generar $llenado',$llenado);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $estilos_titulo = $this->asigna_estilos_titulo(libro: $libro);
        if(isset($estilos_titulo['error'])){
            $error = $this->error->error('Error al aplicar $estilos_titulo',$estilos_titulo);
            if(!$header){
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $autosize = $this->aplica_autosize(keys: $keys,libro: $libro);
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


        $data = $this->genera_salida_xls(header: $header, libro: $libro,name: $name,path_base: $path_base);
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

    /**
     * PARAMS ORDER INTERNALS
     * @param string $campo
     * @param int $fila
     * @param int $index
     * @param Spreadsheet $libro
     * @param int $numero_columna
     * @param string $path_base
     * @param array $registro
     * @return array
     */
    public function llena_datos_xls(string $campo, int $fila, int $index, Spreadsheet $libro, int $numero_columna,
                                    string $path_base, array $registro): array
    {

        if($numero_columna<0){
            return $this->error->error('Error $numero_columna debe ser mayor o igual a 0', $numero_columna);
        }
        if($fila<0){
            return $this->error->error('Error $fila debe ser mayor o igual a 0',$fila);
        }
        if(!isset($this->columnas[$numero_columna])){
            return $this->error->error('Error la columna en la posicion '.$numero_columna.' no existe',
                $numero_columna);
        }

        if( isset($registro[$campo]) ) {
            if(is_array($registro[$campo])){
                return $this->error->error('Error $registro['.$campo.'] debe ser un string', $registro);
            }
            $valor = trim($registro[$campo]);
            $celda = $this->columnas[$numero_columna] . $fila;

            $estilo = $this->estilos_format(path_base: $path_base,valor:  $valor);
            if(errores::$error){
                return $this->error->error('Error al obtener estilo', $estilo);
            }
            if($estilo === 'fecha'){
                $valor  = strtotime($valor);
                $valor =($valor/86400)+25569+(-5/24);
            }
            try {
                $libro->setActiveSheetIndex($index)->setCellValue($celda, $valor);
                $libro->getActiveSheet()->getStyle($celda)->applyFromArray($this->estilo_contenido);
                if($estilo) {
                    $libro->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode(
                        $this->estilos[$estilo]);
                }
            }
            catch (Throwable $e){
                return $this->error->error('Error al asignar dato', $e);
            }
        }
        return $registro;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param string $path_base
     * @param string $valor
     * @return string|bool
     * @throws JsonException
     */
    public function estilos_format(string $path_base, string $valor): string|bool
    {
        $estilo = false;
        $patterns = new patterns(path_base: $path_base, valor: $valor);
        if($patterns->tipo_xls){
            $estilo = $patterns->tipo_xls;
        }
        return $estilo;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param array $registros
     * @param array $keys
     * @param Spreadsheet $libro
     * @param string $path_base
     * @param array $totales
     * @param int $index
     * @return array
     */
    public function llena_libro_xls(int $index, array $keys, Spreadsheet $libro, string $path_base, array $registros,
                                    array $totales ): array
    {
        $fila = 2;
        foreach($registros as $registro) {
            if(!is_array($registro)){
                return $this->error->error('Error registro debe ser un array',$registro);
            }
            $llenado = $this->llena_registro_xls(fila: $fila, index: $index, keys: $keys, libro: $libro,
                path_base: $path_base, registro: $registro);

            if(errores::$error){
                return $this->error->error('Error al aplicar $llenado',
                    $llenado);
            }

            $fila++;
        }

        $fila++;

        foreach($totales as $key =>$total){
            try {
                $libro->setActiveSheetIndex($index)->setCellValue('A' . $fila, $key);
                $libro->setActiveSheetIndex($index)->setCellValue('B' . $fila, $total);
                $libro->getActiveSheet()->getStyle('A' . $fila)->applyFromArray($this->estilo_contenido);
                $libro->getActiveSheet()->getStyle('B' . $fila)->applyFromArray($this->estilo_contenido);
            }
            catch (Throwable $e){
                return $this->error->error('Error al asignar valores', $e);
            }

            $fila ++;
        }


        return $registros;
    }

    /**
     * PARAMS-ORDER INTERNALS
     * @param int $fila
     * @param int $index
     * @param array $keys
     * @param Spreadsheet $libro
     * @param string $path_base
     * @param array $registro
     * @return array
     */
    public function llena_registro_xls(int $fila, int $index, array $keys, Spreadsheet$libro, string $path_base,
                                       array $registro):array{
        $i=0;
        $data=array();
        foreach($keys as $campo){
            $llenado = $this->llena_datos_xls(campo: $campo, fila: $fila, index:  $index, libro: $libro,
                numero_columna:$i, path_base: $path_base, registro: $registro);
            if(errores::$error){
                return $this->error->error('Error al aplicar $llenado',$llenado);
            }
            $data[]=$llenado;
            $i++;
        }
        return $data;
    }


}
