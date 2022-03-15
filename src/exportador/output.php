<?php
namespace gamboamartin\plugins\exportador;
use gamboamartin\errores\errores;
use JetBrains\PhpStorm\Pure;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class output{
    private errores $error;
    #[Pure] public function __construct(){
        $this->error = new errores();
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


}