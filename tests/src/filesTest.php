<?php
namespace tests\src\exportador;

use gamboamartin\errores\errores;
use gamboamartin\plugins\exportador\datos;
use gamboamartin\plugins\files;
use gamboamartin\test\test;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class filesTest extends test {
    public errores $errores;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
    }

    public function test_extension(){
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->extension($archivo);

        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error archivo no puede venir vacio", $resultado['mensaje']);

        errores::$error = false;

        $archivo = 'w';
        $resultado = $fl->extension($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error el archivo no tiene extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = '.w';
        $resultado = $fl->extension($archivo);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("w", $resultado);

        errores::$error = false;
    }


}