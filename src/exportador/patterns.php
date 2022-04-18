<?php
namespace gamboamartin\plugins\exportador;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use stdClass;
use Throwable;

class patterns{
    public array $patterns;
    public string|bool $tipo_dato;
    public string|bool $tipo_xls;
    public stdClass $data;
    private errores $error;
    private validacion $validacion;

    /**
     * PARAMS ORDER, PARAMS INT
     */
    public function __construct(string $path_base, string $valor)
    {
        $this->error = new errores();
        $this->validacion = new validacion();
        $path_json = $path_base."config/default/regex.json";
        if(!file_exists($path_json)){
            $error = (new errores())->error('Error no existe archivo', $path_json);
            print_r($error);
            die('Error');
        }
        $data = file_get_contents($path_base."config/default/regex.json");

        try {
            $data = json_decode(trim($data), true, 512, JSON_THROW_ON_ERROR);
        }
        catch (Throwable $e){
            $error = (new errores())->error('Error al cargar json', $e);
            print_r($error);
            die('Error');
        }

        $this->patterns = $data['regex'];
        $this->data = new stdClass();


        $tipo_dato = $this->data(valor: $valor);
        if(errores::$error){
            $error = (new errores())->error('Error al obtener tipo_dato', $tipo_dato);
            print_r($error);
            die('Error');
        }


    }

    /**
     * P ORDER P INT PROBADO
     * @param array $pattern
     * @return stdClass|array
     */
    PUBLIC function asigna_data(array $pattern): stdClass|array
    {
        $keys = array('tipo_dato','xls');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $pattern);
        if(errores::$error){
            return $this->error->error('Error al validar', $valida);
        }
        $this->tipo_dato = $pattern['tipo_dato'];
        $this->tipo_xls = $pattern['xls'];
        $this->data->tipo_dato = $this->tipo_dato;
        $this->data->xls = $this->tipo_xls;
        return $this->data;
    }

    /**
     * PARAMS ORDER PARAMS INT
     * @param string $valor
     * @return stdClass
     */
    public function data(string $valor): stdClass
    {
        $this->tipo_dato = false;
        $this->tipo_xls = false;
        foreach ($this->patterns as $pattern){
            $break = $this->verifica_expresion(pattern: $pattern,valor: $valor);
            if(errores::$error){
                return  $this->error->error('Error al verificar expresion', $break);
            }
            if($break){
                break;
            }

        }
        return $this->data;
    }

    /**
     * P ORDER P INT
     * @param array $pattern
     * @param string $valor
     * @return bool|array
     */
    private function verifica_expresion(array $pattern, string $valor): bool|array
    {
        $break = false;
        $result = preg_match($pattern['expresion'], $valor);
        if((int)$result!==0){
            $data = $this->asigna_data(pattern: $pattern);
            if(errores::$error){
                return  $this->error->error('Error al asignar datos', $data);
            }
            $break = true;
        }
        return $break;
    }

}

