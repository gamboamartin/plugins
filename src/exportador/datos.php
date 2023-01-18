<?php
namespace gamboamartin\plugins\exportador;
use gamboamartin\errores\errores;
use JetBrains\PhpStorm\Pure;
use JsonException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

class datos{
    private errores $error;
    #[Pure] public function __construct(){
        $this->error = new errores();
    }

    /**
     * PROBADO PARAMS ORDER INTERNALS
     * @param Spreadsheet $libro
     * @param string $dato
     * @return array|Spreadsheet
     */
    final public function genera_datos_libro(string $dato, Spreadsheet $libro):array|Spreadsheet{ //FIN PROT
        if(trim($dato) === ''){
            return $this->error->error('Error el dato esta vacio',$dato);
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
     * PROBADO PARAMS ORDER INTERNALS
     * @param array $columnas
     * @param int $index
     * @param array $keys
     * @param Spreadsheet $libro
     * @return array|bool
     */
    public function genera_encabezados(array $columnas, int $index, array $keys,Spreadsheet $libro ): array|bool
    {
        $fila = 1;
        $i = 0;
        foreach($keys as $key){
            $key = trim($key);
            if($key === ''){
                return $this->error->error('Error key esta vacio', $key);
            }
            if(!isset($columnas[$i])){
                return $this->error->error("Error no existe columnas[$i]", $columnas);
            }
            if(trim($columnas[$i]) === ''){
                return $this->error->error("Error esta vacia la coordenada columnas[$i]", $columnas);
            }
            try {
                $libro->setActiveSheetIndex($index)->setCellValue($columnas[$i] . $fila, $key);
                $i++;
            }
            catch (Throwable $e){
                return $this->error->error('Error al aplicar key en xls', $e);
            }
        }
        return true;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param string $campo
     * @param array $columnas
     * @param array $estilo_contenido
     * @param array $estilos
     * @param int $fila
     * @param int $index
     * @param Spreadsheet $libro
     * @param int $numero_columna
     * @param string $path_base
     * @param array $registro
     * @return array
     * @throws JsonException
     */
    private function llena_datos_xls(string $campo, array $columnas, array $estilo_contenido, array $estilos,
                                     int $fila, int $index, Spreadsheet $libro, int $numero_columna,
                                     string $path_base, array $registro): array
    {

        if($numero_columna<0){
            return $this->error->error('Error $numero_columna debe ser mayor o igual a 0', $numero_columna);
        }
        if($fila<0){
            return $this->error->error('Error $fila debe ser mayor o igual a 0',$fila);
        }
        if(!isset($columnas[$numero_columna])){
            return $this->error->error('Error la columna en la posicion '.$numero_columna.' no existe',
                $numero_columna);
        }

        if( isset($registro[$campo]) ) {
            if(is_array($registro[$campo])){
                return $this->error->error('Error $registro['.$campo.'] debe ser un string', $registro);
            }
            $valor = trim($registro[$campo]);
            $celda = $columnas[$numero_columna] . $fila;

            $estilo = (new estilos())->estilos_format(path_base: $path_base,valor:  $valor);
            if(errores::$error){
                return $this->error->error('Error al obtener estilo', $estilo);
            }
            if($estilo === 'fecha'){
                $valor  = strtotime($valor);
                $valor =($valor/86400)+25569+(-5/24);
            }
            try {
                $libro->setActiveSheetIndex($index)->setCellValue($celda, $valor);
                $libro->getActiveSheet()->getStyle($celda)->applyFromArray($estilo_contenido);
                if($estilo) {
                    $libro->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode(
                        $estilos[$estilo]);
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
     * @param array $columnas
     * @param array $estilo_contenido
     * @param array $estilos
     * @param int $index
     * @param array $keys
     * @param Spreadsheet $libro
     * @param string $path_base
     * @param array $registros
     * @param array $totales
     * @return array
     * @throws JsonException
     */
    public function llena_libro_xls(array $columnas, array $estilo_contenido, array $estilos, int $index, array $keys,
                                     Spreadsheet $libro, string $path_base, array $registros, array $totales ): array
    {
        $fila = 2;
        foreach($registros as $registro) {
            if(!is_array($registro)){
                return $this->error->error('Error registro debe ser un array',$registro);
            }
            $llenado = $this->llena_registro_xls(columnas:$columnas, estilo_contenido:$estilo_contenido,
                estilos: $estilos, fila: $fila, index: $index, keys: $keys, libro: $libro, path_base: $path_base,
                registro: $registro);

            if(errores::$error){
                return $this->error->error('Error al aplicar $llenado', $llenado);
            }

            $fila++;
        }

        $fila++;

        foreach($totales as $key =>$total){
            try {
                $libro->setActiveSheetIndex($index)->setCellValue('A' . $fila, $key);
                $libro->setActiveSheetIndex($index)->setCellValue('B' . $fila, $total);
                $libro->getActiveSheet()->getStyle('A' . $fila)->applyFromArray($estilo_contenido);
                $libro->getActiveSheet()->getStyle('B' . $fila)->applyFromArray($estilo_contenido);
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
     * @param array $columnas
     * @param array $estilo_contenido
     * @param array $estilos
     * @param int $fila
     * @param int $index
     * @param array $keys
     * @param Spreadsheet $libro
     * @param string $path_base
     * @param array $registro
     * @return array
     * @throws JsonException
     */
    private function llena_registro_xls(array $columnas, array $estilo_contenido, array $estilos, int $fila,
                                        int $index, array $keys, Spreadsheet$libro, string $path_base,
                                        array $registro):array{
        $i=0;
        $data=array();
        foreach($keys as $campo){
            $llenado = $this->llena_datos_xls(campo: $campo, columnas: $columnas,estilo_contenido: $estilo_contenido,
                estilos: $estilos, fila: $fila, index:  $index, libro: $libro, numero_columna:$i,
                path_base: $path_base, registro: $registro);
            if(errores::$error){
                return $this->error->error('Error al aplicar $llenado',$llenado);
            }
            $data[]=$llenado;
            $i++;
        }
        return $data;
    }




}