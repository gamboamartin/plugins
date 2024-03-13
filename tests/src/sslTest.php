<?php
namespace gamboamartin\test\src;

use gamboamartin\errores\errores;
use gamboamartin\plugins\files;
use gamboamartin\plugins\ssl;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;


class sslTest extends test {
    public errores $errores;
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->errores = new errores();
    }

    /*public function test_asigna_data_file_service()
    {
        errores::$error = false;
        $ssl = new ssl();
        //$fl = new liberator($fl);

        $pass = '.';
        $ruta_in = '';
        $ruta_out = 'c';
        $resultado = $ssl->genera_key_pem($pass, $ruta_in, $ruta_out);
        print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;


        $archivo = 'a.info';
        $resultado = $fl->asigna_data_file_service($archivo);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("a.info", $resultado->file);

        errores::$error = false;


        $archivo = 'a.info.php.lock';
        $resultado = $fl->asigna_data_file_service($archivo);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("a.info", $resultado->name_service);


        errores::$error = false;

    }*/




}