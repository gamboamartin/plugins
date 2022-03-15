<?php
namespace gamboamartin\plugins;
use gamboamartin\errores\errores;
use JsonException;
use stdClass;

class patterns{
    public array $patterns;
    public string|bool $tipo_dato;
    public string|bool $tipo_xls;
    public stdClass $data;

    /**
     * PARAMS ORDER, PARAMS INT
     * @throws JsonException
     */
    public function __construct(string $path_base, string $valor)
    {
        $data = file_get_contents($path_base."config/default/regex.json");
        $data = json_decode(trim($data), true, 512, JSON_THROW_ON_ERROR);
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
     * PARAMS ORDER PARAMS INT
     * @param string $valor
     * @return stdClass
     */
    public function data(string $valor): stdClass
    {
        $this->tipo_dato = false;
        $this->tipo_xls = false;
        foreach ($this->patterns as $pattern){
            $result = preg_match($pattern['expresion'], $valor);
            if((int)$result!==0){
                $this->tipo_dato = $pattern['tipo_dato'];
                $this->tipo_xls = $pattern['xls'];
                $this->data->tipo_dato = $this->tipo_dato;
                $this->data->xls = $this->tipo_xls;
                break;
            }
        }
        return $this->data;
    }

}

