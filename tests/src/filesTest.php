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
    public function test_estructura(){
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $ruta = '';
        $resultado = $fl->estructura($ruta);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar ruta", $resultado['mensaje']);

        errores::$error = false;
        $ruta = 'X';
        $resultado = $fl->estructura($ruta);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error al validar ruta", $resultado['mensaje']);

        errores::$error = false;
        $ruta = '/var/www/html/plugins/tests/services';
        $resultado = $fl->estructura($ruta);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("service1.php.lock", $resultado[0]->name_file);
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

    public function test_get_files_services()
    {
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        //$directorio = '/var/www/html/plugins/tests/services';
        $directorio = opendir('/var/www/html/plugins/tests/services');
        $resultado = $fl->get_files_services($directorio);
        //print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('service1.php',$resultado['service1']['file']);

        errores::$error = false;
    }

    public function test_get_data_service()
    {
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $ruta = '/var/www/html/plugins/tests/services';
        $resultado = $fl->get_data_service($ruta,'service1');
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('service1.php',$resultado['file']);

        errores::$error = false;
    }

    public function test_guarda_archivo_fisico()
    {
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $contenido_file = '';
        $ruta_file = '';
        $resultado = $fl->guarda_archivo_fisico($contenido_file, $ruta_file);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase('Error $ruta_file esta vacia', $resultado['mensaje']);

        errores::$error = false;

        $contenido_file = '';
        $ruta_file = 'zzz';
        $resultado = $fl->guarda_archivo_fisico($contenido_file, $ruta_file);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase('Error $contenido_file esta vacio', $resultado['mensaje']);

        errores::$error = false;

        $contenido_file = 'x';
        $ruta_file = 'zzz';
        $resultado = $fl->guarda_archivo_fisico($contenido_file, $ruta_file);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('zzz', $resultado);
        unlink('zzz');
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

    public function test_rmdir_recursive(){
        errores::$error = false;
        $fl = new files();
        //$fl = new liberator($fl);

        $dir = '/var/www/html/plugins/tests/del/';
        if(!file_exists($dir)) {
            mkdir($dir);
        }

        $dir1 = '/var/www/html/plugins/tests/del/1';
        if(!file_exists($dir1)) {
            mkdir($dir1);
        }


        $dir2 = '/var/www/html/plugins/tests/del/1/2';
        if(!file_exists($dir2)) {
            mkdir($dir2);
        }

        $resultado = $fl->rmdir_recursive(dir:$dir,mismo: true);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertFileDoesNotExist($dir);
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

    public function test_valida_folder(){
        errores::$error = false;
        $fl = new files();
        $fl = new liberator($fl);

        $ruta = '';
        $resultado = $fl->valida_folder($ruta);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error la ruta esta vacio", $resultado['mensaje']);

        errores::$error = false;

        $ruta = 'zzz';
        $resultado = $fl->valida_folder($ruta);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error la ruta no existe o no es una carpeta", $resultado['mensaje']);

        errores::$error = false;

        $ruta = '/var/www/html/plugins/tests/services/service1.php';
        $resultado = $fl->valida_folder($ruta);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("Error la ruta no existe o no es una carpeta", $resultado['mensaje']);

        errores::$error = false;

        $ruta = '/var/www/html/plugins/tests/services/';
        $resultado = $fl->valida_folder($ruta);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }


}