<?php
namespace tests\src;

use gamboamartin\errores\errores;
use gamboamartin\plugins\files;
use gamboamartin\test\liberator;
use gamboamartin\test\test;



class filesTest extends test {
    public errores $errores;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
    }

    public function test_es_lock_service(){
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->es_lock_service($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = '.';
        $resultado = $fl->es_lock_service($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = 'x.z';
        $resultado = $fl->es_lock_service($archivo);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;

        $archivo = 'x.lock';
        $resultado = $fl->es_lock_service($archivo);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_extension(){
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->extension($archivo);

        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = 'w';
        $resultado = $fl->extension($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = '.w';
        $resultado = $fl->extension($archivo);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("w", $resultado);

        errores::$error = false;
    }

    public function test_parte_to_name_file()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $parte = '';
        $resultado = $fl->parte_to_name_file($parte);
        $this->assertIsBool($resultado);
        $this->assertTrue($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
        $parte = 'a';
        $resultado = $fl->parte_to_name_file($parte);
        $this->assertIsBool($resultado);
        $this->assertNotTrue($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }


    }