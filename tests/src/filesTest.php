<?php
namespace tests\src;

use gamboamartin\errores\errores;
use gamboamartin\plugins\files;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;


class filesTest extends test {
    public errores $errores;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
    }

    public function test_asigna_data_file_service()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->asigna_data_file_service($archivo);
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

    }

    public function test_asigna_data_service()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivo = new stdClass();
        $archivo->es_service = false;
        $archivo->es_lock = false;
        $archivo->es_info = false;
        $archivo->file = 'a';
        $servicio = array();
        $resultado = $fl->asigna_data_service($archivo, $servicio);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_asigna_servicios(){
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivo = new stdClass();
        $archivo->name_service = 'a';
        $archivo->es_service = true;
        $archivo->es_info = false;
        $archivo->es_lock = false;
        $archivo->file = 'a';
        $servicios = array();
        $resultado = $fl->asigna_servicios($archivo, $servicios);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_es_info_service()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->es_info_service($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;


        $archivo = 'x.info';
        $resultado = $fl->es_info_service($archivo);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_es_lock_service(){
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

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

    public function test_es_service()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->es_service($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = '.';
        $resultado = $fl->es_service($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = 'x.z';
        $resultado = $fl->es_service($archivo);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;

        $archivo = 'x.php';
        $resultado = $fl->es_service($archivo);
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

    public function test_files_services()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $directorio = '';
        $resultado = $fl->files_services($directorio);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error el directorio no puede ser un string", $resultado['mensaje']);

        errores::$error = false;

        $directorio = opendir('/var/www/html/plugins/src/');
        $resultado = $fl->files_services($directorio);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }

    public function test_maqueta_files_services()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivos = array();

        $resultado = $fl->maqueta_files_service($archivos);
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

        $archivos = array();
        $archivos[0] = new stdClass();
        $archivos[0]->name_service = 'a';
        $archivos[0]->es_service = true;
        $archivos[0]->es_lock = true;
        $archivos[0]->es_info = true;
        $archivos[0]->file = 'z';

        $resultado = $fl->maqueta_files_service($archivos);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);

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
    public function test_todo_vacio()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $explode = array();
        $resultado = $fl->todo_vacio($explode);
        $this->assertIsBool($resultado);
        $this->assertTrue($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_valida_extension()
    {
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $archivo = '';
        $resultado = $fl->valida_extension($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error archivo no puede venir vacio", $resultado['mensaje']);

        errores::$error = false;

        $archivo = 'a';
        $resultado = $fl->valida_extension($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error el archivo no tiene extension", $resultado['mensaje']);

        errores::$error = false;

        $archivo = 'a.';
        $resultado = $fl->valida_extension($archivo);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error el archivo solo tiene puntos", $resultado['mensaje']);
        errores::$error = false;

        $archivo = 'a.z';
        $resultado = $fl->valida_extension($archivo);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }


}