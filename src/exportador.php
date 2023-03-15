<?php

namespace gamboamartin\plugins;

use gamboamartin\errores\errores;
use gamboamartin\plugins\exportador\datos;
use gamboamartin\plugins\exportador\estilos;
use gamboamartin\plugins\exportador\output;
use JsonException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Throwable;

/**
 * PARAMS ORDER INTERNALS
 */
class exportador
{
    public array $columnas;
    public array $estilo_titulos;
    public array $estilo_contenido;
    public array $estilos;
    public Spreadsheet $libro;
    public errores $error;
    private int $num_hojas;

    public function __construct(int $num_hojas = 1)
    {
        $this->libro = new Spreadsheet();

        $letras = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S',
            'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

        $columnas = array();
        foreach ($letras as $letra) {
            $columnas[] = $letra;
        }

        foreach ($letras as $letra) {
            foreach ($letras as $letra_bis) {
                $letra_integrar = $letra . $letra_bis;
                $columnas[] = $letra_integrar;
            }
        }


        $this->columnas = $columnas;

        $this->estilo_titulos = array(
            'font' => array(
                'bold' => true,
                'size' => 8,
                'name' => 'Verdana'
            ));

        $this->estilo_contenido = array(
            'font' => array(
                'size' => 8,
                'name' => 'Verdana'
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
        if (trim($name) === '') {
            $error = $this->error->error('Error al $name no puede venir vacio', $name);
            if (!$header) {
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

        foreach ($nombre_hojas as $index => $nombre_hoja) {

            if ($index < $this->num_hojas - 1) {
                $libro->createSheet();
            }

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
    public function listado_base_xls(bool  $header, string $name, array $keys, string $path_base,
                                     array $registros, array $totales, array $centers = array(),
                                     int   $index = 0, array $keys_sum = array(), array $moneda = array(),
                                     array $moneda_sin_decimal = array(), array $size_columnas = array()): array|string
    {

        if (trim($name) === '') {
            $error = $this->error->error('Error al $name no puede venir vacio', $name);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }
        $libro = new Spreadsheet();
        $libro = (new datos())->genera_datos_libro(dato: $name, libro: $libro);
        if (errores::$error) {
            $error = $this->error->error('Error al aplicar generar datos del libro', $libro);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $genera_encabezados = (new datos())->genera_encabezados(columnas: $this->columnas, index: $index,
            keys: $keys, libro: $libro, keys_sum: $keys_sum);
        if (errores::$error) {
            $error = $this->error->error('Error al generar $genera_encabezados', $genera_encabezados);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        $llenado = (new datos())->llena_libro_xls(columnas: $this->columnas, estilo_contenido: $this->estilo_contenido,
            estilos: $this->estilos, index: $index, keys: $keys, libro: $libro, path_base: $path_base,
            registros: $registros, totales: $totales);

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

        $autosize = (new estilos())->aplica_autosize(columnas: $this->columnas, keys: $keys, libro: $libro);
        if (errores::$error) {
            $error = $this->error->error('Error en autosize', $autosize);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        try {
            $libro->getActiveSheet()->setTitle(substr($name, 0, 31));
            $libro->setActiveSheetIndex(0);
        } catch (Throwable $e) {
            $error = $this->error->error('Error al aplicar generar datos del libro', $e);
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

            $count = count($registros) + 1;
            $libro->getActiveSheet()->getStyle($center . '1:' . $center . $count)->applyFromArray($style);
        }

        foreach ($moneda_sin_decimal as $column) {
            $count = count($registros) + 1;
            $libro->getActiveSheet()->getStyle(
                $column . '1:' . $column . $count)->getNumberFormat()->setFormatCode("$#,00");
        }

        foreach ($moneda as $column) {
            $count = count($registros) + 1;
            $libro->getActiveSheet()->getStyle(
                $column . '1:' . $column . $count)->getNumberFormat()->setFormatCode(
                NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
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

    public function exportar_template(bool $header, string $path_base, string $name, array $data, array $styles = array())
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($data as $nombre => $hoja) {
            $worksheet = $spreadsheet->createSheet();
            $worksheet->setTitle($nombre);

            foreach ($hoja as $table) {
                $detalles = $table['detalles'] ?? array();
                $titulo = $table['title'] ?? "";
                $orientacion = $table['orientation'] ?? "horizontal";
                $headers = $table['headers'];
                $tableData = $table['data'];
                $startRow = $table['startRow'] ?? 1;
                $startColumn = $table['startColumn'] ?? "A";
                $totales = $table['totales'] ?? array();

                $startRow -= count($detalles);

                $punteroColumna = $startColumn;
                $punteroFila = $startRow;

                if (count($detalles) > 0) {
                    foreach ($detalles as $index => $detalle) {

                        if (!array_key_exists("titulo", $detalle)) {
                            $error = $this->error->error('Error no existe la key titulo para los detalles', $detalle);
                            if (!$header) {
                                return $error;
                            }
                            print_r($error);
                            die('Error');
                        }

                        if (!array_key_exists("valor", $detalle)) {
                            $error = $this->error->error('Error no existe la key valor para los detalles', $detalle);
                            if (!$header) {
                                return $error;
                            }
                            print_r($error);
                            die('Error');
                        }

                        $celda = $worksheet->getCell($punteroColumna . $punteroFila);
                        $celda->setValue($detalle["titulo"]);

                        $columnIndex = Coordinate::columnIndexFromString($punteroColumna);
                        $columnIndex += 1;
                        $punteroColumna = Coordinate::stringFromColumnIndex($columnIndex);
                        $celda = $worksheet->getCell($punteroColumna . $punteroFila++);
                        $celda->setValue($detalle["valor"]);
                        $punteroColumna = $startColumn;
                    }
                    $startRow = $punteroFila;
                }

                if ($orientacion === "horizontal") {

                    if (!empty($titulo)) {
                        $inicio = $startColumn . $startRow;
                        $aux = chr(ord($startColumn) + count($tableData[0]) - 1);
                        $fin = $aux . $startRow;
                        $worksheet->mergeCells("$inicio:$fin");
                        $celda = $worksheet->getCell($startColumn . $startRow);
                        $celda->setValue($titulo);
                        $punteroFila++;
                    }

                    foreach ($headers as $header) {
                        $celda = $worksheet->getCell($punteroColumna . $punteroFila);
                        $celda->setValue($header);
                        $punteroColumna = $celda->getColumn();
                        $columnIndex = Coordinate::columnIndexFromString($punteroColumna);
                        $columnIndex += 1;
                        $punteroColumna = Coordinate::stringFromColumnIndex($columnIndex);
                    }

                    foreach ($tableData as $i => $rowData) {
                        ++$punteroFila;
                        $punteroColumna = $startColumn;
                        $color = ($i % 2 == 0) ? 'FFFFFF' : 'DCE6FF';

                        foreach ($rowData as $cellData) {
                            $celda = $worksheet->getCell($punteroColumna . $punteroFila);
                            $celda->setValue($cellData);
                            $punteroColumna++;
                            $celda->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                                ->setARGB($color);
                        }
                    }
                } elseif ("vertical") {

                    if (!empty($titulo)) {
                        $inicio = $startColumn . $startRow;
                        $aux = chr(ord($startColumn) + count($tableData));
                        $fin = $aux . $startRow;
                        $worksheet->mergeCells("$inicio:$fin");
                        $celda = $worksheet->getCell($startColumn . $startRow);
                        $celda->setValue($titulo);
                        $punteroFila++;
                    }

                    foreach ($headers as $header) {
                        $celda = $worksheet->getCell($punteroColumna . $punteroFila);
                        $celda->setValue($header);
                        $punteroFila++;
                    }

                    foreach ($tableData as $i => $rowData) {
                        ++$punteroColumna;
                        $color = ($i % 2 == 0) ? 'FFFFFF' : 'DCE6FF';

                        $punteroFila = !empty($titulo) ? $startRow + 1 : $startRow;

                        foreach ($rowData as $cellData) {
                            $celda = $worksheet->getCell($punteroColumna . $punteroFila++);
                            $celda->setValue($cellData);
                            $celda->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                                ->setARGB($color);
                        }
                    }
                }
            }

            if (count($totales) > 0) {

                $punteroFila++;

                foreach ($totales as $index => $total){
                    if (!array_key_exists('columna', $total)){
                        $error = $this->error->error('Error no existe la key columna para totales', $total);
                        if (!$header) {
                            return $error;
                        }
                        print_r($error);
                        die('Error');
                    }

                    if (!array_key_exists('valor', $total)){
                        $error = $this->error->error('Error no existe la key valor para totales', $total);
                        if (!$header) {
                            return $error;
                        }
                        print_r($error);
                        die('Error');
                    }
                    $punteroColumna = $total['columna'] ?? $startColumn;
                    $valor = $total['valor'] ?? "";

                    $celda = $worksheet->getCell($punteroColumna . $punteroFila);
                    $celda->setValue($valor);
                    $celda->getStyle($punteroColumna . $punteroFila)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('BFBFBF');
                }
            }

            $ultima_olumna = $worksheet->getHighestColumn();
            $ultima_olumna = ++$ultima_olumna;

            $col = 'A';
            while ($col != $ultima_olumna){
                $worksheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }

            foreach ($styles as $range => $style) {
                $worksheet->getStyle($range)->applyFromArray($style);
            }
        }

        $out = (new exportador\output())->genera_salida_xls(header: $header, libro: $spreadsheet, name: $name,
            path_base: $path_base);
        if (isset($out['error'])) {
            $error = $this->error->error('Error al aplicar generar salida', $data);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        if (!$header) {
            return $out;
        }
        exit;
    }

    function exportar_template_1(bool $header, string $path_base, string $name, array $data)
    {
        $spreadsheet = new Spreadsheet();

        foreach ($data as $sheetName => $tables) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            foreach ($tables as $table) {
                $startRow = $table['startRow'] ?? 1;
                $startColumn = $table['startColumn'] ?? 'A';

                $headers = $table['headers'] ?? [];
                $style = $table['style'] ?? [];
                $orientation = $table['orientation'] ?? 'horizontal';

                $data = $table['data'] ?? [];

                if ($orientation == 'horizontal') {
                    $currentRow = $startRow;
                    foreach ($data as $row) {
                        $currentColumn = $startColumn;
                        foreach ($row as $cellValue) {
                            $cell = $sheet->getCell($currentColumn . $currentRow);
                            $cell->setValue($cellValue);
                            $currentColumn++;
                        }
                        $currentRow++;
                    }

                    if (!empty($headers)) {
                        $currentColumn = $startColumn;
                        foreach ($headers as $headerValue) {
                            $cell = $sheet->getCell($currentColumn . $startRow);
                            $cell->setValue($headerValue);
                            $currentColumn++;
                        }
                        $startRow++;
                    }
                } elseif ($orientation == 'vertical') {
                    $currentColumn = $startColumn;
                    foreach ($data as $row) {
                        $currentRow = $startRow;
                        foreach ($row as $cellValue) {
                            $cell = $sheet->getCell($currentColumn . $currentRow);
                            $cell->setValue($cellValue);
                            $currentRow++;
                        }
                        $currentColumn++;
                    }

                    if (!empty($headers)) {
                        $currentRow = $startRow;
                        foreach ($headers as $headerValue) {
                            $cell = $sheet->getCell($startColumn . $currentRow);
                            $cell->setValue($headerValue);
                            $currentRow++;
                        }
                        $startColumn++;
                    }
                }

                // Aplicar estilos a la tabla
                if (!empty($style)) {
                    $lastColumn = $sheet->getHighestColumn();
                    $lastRow = $sheet->getHighestRow();
                    $range = $startColumn . $startRow . ':' . $lastColumn . $lastRow;

                    $sheet->getStyle($range)->applyFromArray($style);
                }
            }
        }

        $out = (new exportador\output())->genera_salida_xls(header: $header, libro: $spreadsheet, name: $name,
            path_base: $path_base);
        if (isset($out['error'])) {
            $error = $this->error->error('Error al aplicar generar salida', $data);
            if (!$header) {
                return $error;
            }
            print_r($error);
            die('Error');
        }

        if (!$header) {
            return $out;
        }
        exit;
    }

}
