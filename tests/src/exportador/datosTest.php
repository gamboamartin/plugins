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

    public function test_genera_encabezados(){
        errores::$error = false;
        $datos = new datos();
        //$inicializacion = new liberator($inicializacion);

        $columnas = array();
        $index = 0;
        $keys = array();
        $libro = new Spreadsheet();
        $resultado = $datos->genera_encabezados(columnas: $columnas, index: $index,keys:  $keys,libro:  $libro);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
        $columnas = array();
        $index = 0;
        $keys = array();
        $libro = new Spreadsheet();
        $keys[] = '';
        $resultado = $datos->genera_encabezados(columnas: $columnas, index: $index,keys:  $keys,libro:  $libro);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error key esta vacio", $resultado['mensaje']);

        errores::$error = false;
        $columnas = array();
        $index = 0;
        $keys = array();
        $libro = new Spreadsheet();
        $keys[] = 'a';
        $resultado = $datos->genera_encabezados(columnas: $columnas, index: $index,keys:  $keys,libro:  $libro);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error no existe columnas[0]", $resultado['mensaje']);

        errores::$error = false;
        $columnas = array();
        $index = 0;
        $keys = array();
        $libro = new Spreadsheet();
        $keys[] = 'a';
        $columnas[0] = '';
        $resultado = $datos->genera_encabezados(columnas: $columnas, index: $index,keys:  $keys,libro:  $libro);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error esta vacia la coordenada columnas[0]", $resultado['mensaje']);

        errores::$error = false;
        $columnas = array();
        $index = 0;
        $keys = array();
        $libro = new Spreadsheet();
        $keys[] = 'a';
        $columnas[0] = 'A';
        $resultado = $datos->genera_encabezados(columnas: $columnas, index: $index,keys:  $keys,libro:  $libro);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }


}