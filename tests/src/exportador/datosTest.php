<?php
namespace tests\src\exportador;

use gamboamartin\errores\errores;
use gamboamartin\plugins\exportador\datos;
use gamboamartin\test\test;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class datosTest extends test {
    public errores $errores;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
    }

    public function test_boton_acciones_list(){
        errores::$error = false;
        $datos = new datos();
        //$inicializacion = new liberator($inicializacion);

        $dato = '';
        $libro = new Spreadsheet();
        $resultado = $datos->genera_datos_libro($dato, $libro);

        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error el dato esta vacio", $resultado['mensaje']);

        errores::$error = false;

        $dato = 'a';
        $libro = new Spreadsheet();
        $resultado = $datos->genera_datos_libro($dato, $libro);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);



        errores::$error = false;
    }


}