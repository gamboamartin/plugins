<?php
namespace gamboamartin\test\src;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\plugins\Importador;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


class ImportadorTest extends test {
    public errores $errores;
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->errores = new errores();
    }

    public function test_asigna_data_file_service()
    {
        errores::$error = false;
        $importador = new Importador();
        $importador = new liberator($importador);

        $ruta_absoluta = (new generales())->path_base.'tests/cat_sat_tipo_relacion';

        $inputFileType = 'Ods';
        $celda_inicio = 'A1';
        $resultado = $importador->rows($celda_inicio, $inputFileType, $ruta_absoluta);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("id", $resultado[0][0]);
        $this->assertEquals("Notas de Crédito de Documentos Relacionados", $resultado[1][1]);
        $this->assertEquals("02", $resultado[2][2]);
        $this->assertEquals("3", $resultado[3][0]);
        errores::$error = false;

        $inputFileType = 'Ods';
        $celda_inicio = 'A1';
        $resultado = $importador->rows($celda_inicio, $inputFileType, $ruta_absoluta,1);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertCount(1,$resultado);
        $this->assertCount(3,$resultado[0]);

        errores::$error = false;

    }

    public function test_rows()
    {
        errores::$error = false;
        $importador = new Importador();
        $importador = new liberator($importador);

        $ruta_absoluta = (new generales())->path_base.'tests/cat_sat_tipo_relacion';

        //$ruta_absoluta = 'C';
        $inputFileType = 'Ods';
        $celda_inicio = 'A1';
        $resultado = $importador->rows($celda_inicio, $inputFileType, $ruta_absoluta);
        //print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("id", $resultado[0][0]);
        $this->assertEquals("Notas de Crédito de Documentos Relacionados", $resultado[1][1]);
        $this->assertEquals("02", $resultado[2][2]);
        $this->assertEquals("3", $resultado[3][0]);
        errores::$error = false;

        $inputFileType = 'Ods';
        $celda_inicio = 'A1';
        $resultado = $importador->rows($celda_inicio, $inputFileType, $ruta_absoluta,1);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertCount(1,$resultado);
        $this->assertCount(3,$resultado[0]);

        errores::$error = false;

    }

    public function test_valida_in_calc()
    {
        errores::$error = false;
        $importador = new Importador();
        $importador = new liberator($importador);

        $ruta_absoluta = (new generales())->path_base.'tests/cat_sat_tipo_relacion';
        //$ruta_absoluta = 'C';
        $inputFileType = 'B';
        $celda_inicio = 'A1';
        $resultado = $importador->valida_in_calc($celda_inicio, $inputFileType, $ruta_absoluta);
        //print_r($resultado);exit;
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }




}