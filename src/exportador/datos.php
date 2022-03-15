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
     * PARAMS ORDER INTERNALS
     * @param Spreadsheet $libro
     * @param string $dato
     * @return array|Spreadsheet
     */
    public function genera_datos_libro(string $dato, Spreadsheet $libro):array|Spreadsheet{ //FIN PROT
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
     * @param array $columnas
     * @param int $index
     * @param array $keys
     * @param Spreadsheet $libro
     * @return string[]
     */
    public function genera_encabezados(array $columnas, int $index, array $keys,Spreadsheet $libro ): array
    {
        $fila = 1;
        $i = 0;
        foreach($keys as $key){
            try {
                $libro->setActiveSheetIndex($index)
                    ->setCellValue($columnas[$i] . $fila, $key);
                $i++;
            }
            catch (Throwable $e){
                return $this->error->error('Error al aplicar key en xls',
                    array($e,$columnas[$i],$fila,$key));
            }
        }
        return array('mensaje'=>'encabezados aplicados');
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param string $campo
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
    private function llena_datos_xls(string $campo, array $estilo_contenido, array $estilos, int $fila, int $index,
                                    Spreadsheet $libro, int $numero_columna, string $path_base, array $registro): array
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
    public function llena_libro_xls(array $estilo_contenido, array $estilos, int $index, array $keys,
                                     Spreadsheet $libro, string $path_base, array $registros, array $totales ): array
    {
        $fila = 2;
        foreach($registros as $registro) {
            if(!is_array($registro)){
                return $this->error->error('Error registro debe ser un array',$registro);
            }
            $llenado = $this->llena_registro_xls(estilo_contenido:$estilo_contenido, estilos: $estilos,
                fila: $fila, index: $index, keys: $keys, libro: $libro, path_base: $path_base, registro: $registro);

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
    private function llena_registro_xls(array $estilo_contenido, array $estilos, int $fila, int $index, array $keys,
                                        Spreadsheet$libro, string $path_base, array $registro):array{
        $i=0;
        $data=array();
        foreach($keys as $campo){
            $llenado = $this->llena_datos_xls(campo: $campo,estilo_contenido: $estilo_contenido,
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