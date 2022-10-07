<?php
namespace gamboamartin\plugins\exportador;
use gamboamartin\errores\errores;
use JetBrains\PhpStorm\Pure;
use JsonException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

class estilos{
    private errores $error;
    #[Pure] public function __construct(){
        $this->error = new errores();
    }

    /**
     * PARAMS-ORDER INTERNALS
     * @param array $columnas
     * @param array $keys
     * @param Spreadsheet $libro
     * @return string[]
     */
    public function aplica_autosize(array $columnas, array $keys,Spreadsheet $libro):array{

        $i = 0;
        foreach($keys as $campo){
            try {
                $libro->getActiveSheet()->getColumnDimension($columnas[$i])->setAutoSize(true);
                $i++;
            }
            catch (Throwable $e){
                return $this->error->error('Error en autosize',$e);
            }
        }
        return $columnas;
    }

    /**
     * PARAMS ORDER INTERNALS
     * @param array $estilo_titulos
     * @param Spreadsheet $libro
     * @return array[]
     */
    public function asigna_estilos_titulo(array $estilo_titulos, Spreadsheet $libro): array{
        try {
            $libro->getActiveSheet()->getStyle('A1:ZZ1')->applyFromArray($estilo_titulos);
        }
        catch (Throwable $e){
            return $this->error->error('Error al aplicar autosize',$e);
        }
        return $estilo_titulos;
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
}