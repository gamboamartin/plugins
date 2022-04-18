<?php
namespace tests\src\exportador;

use gamboamartin\errores\errores;
use gamboamartin\plugins\exportador\datos;
use gamboamartin\plugins\exportador\patterns;
use gamboamartin\test\test;
use JsonException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use stdClass;


class patternsTest extends test {
    public errores $errores;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
    }

    /**
     * @throws JsonException

     */
    public function test_asigna_data(){
        errores::$error = false;
        $path_base = '/var/www/html/plugins/';
        $valor = '';
        $patt = new patterns(path_base: $path_base, valor: $valor);
        //$inicializacion = new liberator($inicializacion);

        $pattern = array();
        $resultado = $patt->asigna_data($pattern);

        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar", $resultado['mensaje']);

        errores::$error = false;

        $pattern = array();
        $pattern['tipo_dato'] = 'a';
        $pattern['xls'] = 'a';
        $resultado = $patt->asigna_data($pattern);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("a", $resultado->tipo_dato);


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