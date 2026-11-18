<?php
declare(strict_types=1);

require __DIR__ . '/doble_errores.php';
require dirname(__DIR__, 2) . '/src/ssl.php';

use gamboamartin\errores\errores;
use gamboamartin\plugins\ssl;

function exigir(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

set_error_handler(function (int $nivel, string $mensaje): bool {
    if (!(error_reporting() & $nivel)) {
        return false;
    }
    // No incluir argumentos ni contenido criptográfico al informar un fallo del arnés.
    throw new RuntimeException('Advertencia PHP no esperada en el arnes');
});

$raiz = dirname(__DIR__, 2);
$temporalBase = $raiz . '/.git/qa/tmp';
exigir(is_dir($temporalBase), 'Preparar directorio temporal privado del arnes');
$directorio = $temporalBase . '/ssl-' . bin2hex(random_bytes(8));
exigir(mkdir($directorio, 0700), 'Crear espacio sintetico');
$pasaron = [];
$fallo = null;

try {
    foreach (['shell_exec', 'exec', 'system', 'passthru', 'proc_open', 'popen', 'fsockopen', 'stream_socket_client'] as $funcion) {
        exigir(!function_exists($funcion), 'El runner exige funciones externas deshabilitadas');
    }
    $config = $directorio . '/openssl.cnf';
    file_put_contents($config, "[req]\ndistinguished_name=dn\n[dn]\n[ext]\nbasicConstraints=critical,CA:FALSE\nkeyUsage=digitalSignature\n");
    $opciones = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'digest_alg' => 'sha256', 'config' => $config];
    $clave = openssl_pkey_new($opciones);
    $otraClave = openssl_pkey_new($opciones);
    exigir($clave !== false && $otraClave !== false, 'Generar claves ficticias');
    $csr = openssl_csr_new(['commonName' => 'QA SINTETICO SIN VALIDEZ FISCAL'], $clave, $opciones);
    $certificado = openssl_csr_sign($csr, null, $clave, 1, $opciones + ['x509_extensions' => 'ext'], 1);
    exigir($certificado !== false && openssl_x509_export($certificado, $cerPem), 'Crear certificado ficticio');
    $password = 'QA ficticio $;&|() no ejecutar';
    exigir(openssl_pkey_export($clave, $keyCifrada, $password, $opciones), 'Exportar fixture cifrado');
    exigir(openssl_pkey_export($otraClave, $otraCifrada, $password, $opciones), 'Exportar segunda clave ficticia');
    exigir(openssl_pkey_export($clave, $keyPlana, null, $opciones), 'Exportar fixture PEM sin cifrar');
    $aDer = function (string $pem): string {
        return base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $pem), true);
    };
    $cerDer = $aDer($cerPem);
    $keyDer = $aDer($keyCifrada);
    $publica = openssl_pkey_get_details($clave)['key'];
    foreach (['cer.der' => $cerDer, 'cer.pem' => $cerPem, 'key.der' => $keyDer,
        'key.pem' => $keyCifrada, 'key-plana.pem' => $keyPlana, 'otra.pem' => $otraCifrada,
        'compuesto.pem' => $publica . $cerPem, 'vacio' => '', 'basura' => 'NO ES UN CERTIFICADO',
        'grande' => str_repeat('x', 2097153), 'limite' => str_repeat('x', 2097152)] as $nombre => $contenido) {
        file_put_contents($directorio . '/' . $nombre, $contenido);
    }
    $servicio = new ssl();
    $ruta = fn(string $nombre): string => $directorio . '/' . $nombre;
    $salida = $ruta('salida.pem');
    $esperarError = function (mixed $resultado) use ($password): void {
        exigir(is_array($resultado) && errores::$error, 'Error estructurado y bandera');
        $json = json_encode($resultado);
        exigir(!str_contains($json, $password) && !str_contains($json, 'PRIVATE KEY'), 'Error sin secretos');
        exigir($resultado['data'] === [], 'Error sin contenido ni parametros');
    };
    $probar = function (string $nombre, callable $caso) use (&$pasaron): void {
        errores::$error = false;
        $caso();
        $pasaron[] = $nombre;
        echo "PASS $nombre\n";
    };

    $probar('cer_der_contrato_publica_mas_certificado_null', function () use ($servicio, $ruta, $salida, $publica, $cerPem): void {
        exigir($servicio->genera_cer_pem($ruta('cer.der'), $salida) === null, 'Retorno historico null');
        exigir(file_get_contents($salida) === $publica . $cerPem && !errores::$error, 'Contenido historico exacto');
    });
    $probar('cer_pem_conversion', function () use ($servicio, $ruta, $salida, $publica, $cerPem): void {
        exigir($servicio->genera_cer_pem($ruta('cer.pem'), $salida) === null, 'CER PEM aceptado');
        exigir(file_get_contents($salida) === $publica . $cerPem, 'PEM exacto');
    });
    $probar('cer_compuesto_historico_conversion', function () use ($servicio, $ruta, $salida, $publica, $cerPem): void {
        exigir($servicio->genera_cer_pem($ruta('compuesto.pem'), $salida) === null, 'Compuesto historico aceptado');
        exigir(file_get_contents($salida) === $publica . $cerPem, 'Compuesto exacto');
    });
    $probar('key_der_cifrada_conversion_null', function () use ($servicio, $ruta, $salida, $password, $clave): void {
        exigir($servicio->genera_key_pem($password, $ruta('key.der'), $salida) === null, 'KEY DER convertida');
        $exportada = openssl_pkey_get_private(file_get_contents($salida));
        exigir($exportada !== false && openssl_pkey_get_details($exportada)['key'] === openssl_pkey_get_details($clave)['key'], 'Misma clave publica');
        exigir(!str_contains(file_get_contents($salida), 'ENCRYPTED'), 'Salida sin cifrar compatible');
        exigir((fileperms($salida) & 0777) === 0600, 'Salida privada');
    });
    $probar('key_pem_cifrada_conversion', function () use ($servicio, $ruta, $salida, $password): void {
        exigir($servicio->genera_key_pem($password, $ruta('key.pem'), $salida) === null, 'KEY PEM cifrada convertida');
    });
    $probar('key_pem_sin_cifrar_conversion', function () use ($servicio, $ruta, $salida, $password): void {
        exigir($servicio->genera_key_pem($password, $ruta('key-plana.pem'), $salida) === null, 'KEY PEM plana convertida');
    });
    $probar('par_der_correcto', function () use ($servicio, $ruta, $password): void {
        exigir($servicio->valida_par_cer_key($ruta('cer.der'), $ruta('key.der'), $password) === true, 'Par DER coincide');
    });
    $probar('par_pem_correcto', function () use ($servicio, $ruta, $password): void {
        exigir($servicio->valida_par_cer_key($ruta('compuesto.pem'), $ruta('key.pem'), $password) === true, 'Par PEM coincide');
    });
    $probar('par_diferente_rechazado', function () use ($servicio, $ruta, $password, $esperarError): void {
        $esperarError($servicio->valida_par_cer_key($ruta('cer.pem'), $ruta('otra.pem'), $password));
    });
    $probar('password_incorrecta_preserva_salida', function () use ($servicio, $ruta, $salida, $esperarError): void {
        file_put_contents($salida, 'SALIDA ANTERIOR FICTICIA');
        $esperarError($servicio->genera_key_pem('incorrecta', $ruta('key.der'), $salida));
        exigir(file_get_contents($salida) === 'SALIDA ANTERIOR FICTICIA', 'No destruir salida anterior');
    });
    $probar('password_recortada_contrato_historico', function () use ($servicio, $ruta, $salida, $password): void {
        exigir($servicio->genera_key_pem(' ' . $password . ' ', $ruta('key.der'), $salida) === null, 'Trim historico');
    });
    $probar('password_vacia_error', function () use ($servicio, $ruta, $salida, $esperarError): void {
        $esperarError($servicio->genera_key_pem(' ', $ruta('key.der'), $salida));
    });
    $probar('entrada_igual_salida_rechazada', function () use ($servicio, $ruta, $cerDer, $esperarError): void {
        $esperarError($servicio->genera_cer_pem($ruta('cer.der'), $ruta('cer.der')));
        exigir(file_get_contents($ruta('cer.der')) === $cerDer, 'Entrada intacta');
    });
    $probar('entrada_symlink_rechazada', function () use ($servicio, $ruta, $salida, $esperarError): void {
        symlink($ruta('cer.der'), $ruta('enlace-entrada'));
        $esperarError($servicio->genera_cer_pem($ruta('enlace-entrada'), $salida));
    });
    $probar('salida_symlink_rechazada', function () use ($servicio, $ruta, $cerDer, $esperarError): void {
        symlink($ruta('cer.der'), $ruta('enlace-salida'));
        $esperarError($servicio->genera_cer_pem($ruta('cer.der'), $ruta('enlace-salida')));
        exigir(file_get_contents($ruta('cer.der')) === $cerDer, 'Destino enlace intacto');
    });
    $probar('uri_remota_rechazada', function () use ($servicio, $salida, $esperarError): void {
        $esperarError($servicio->genera_cer_pem('https://no-contactar.invalid/cer', $salida));
    });
    $probar('wrapper_local_rechazado', function () use ($servicio, $salida, $esperarError): void {
        $esperarError($servicio->genera_cer_pem('php://memory', $salida));
    });
    $probar('archivo_vacio_rechazado', function () use ($servicio, $ruta, $salida, $esperarError): void {
        $esperarError($servicio->genera_cer_pem($ruta('vacio'), $salida));
    });
    $probar('archivo_ausente_rechazado', function () use ($servicio, $ruta, $salida, $esperarError): void {
        $esperarError($servicio->genera_cer_pem($ruta('ausente'), $salida));
    });
    $probar('archivo_mayor_limite_rechazado', function () use ($servicio, $ruta, $salida, $esperarError): void {
        $resultado = $servicio->genera_cer_pem($ruta('grande'), $salida);
        $esperarError($resultado);
        exigir(str_contains($resultado['mensaje'], 'limite'), 'Limite antes de interpretar');
    });
    $probar('limite_exacto_se_valida_como_certificado', function () use ($servicio, $ruta, $salida, $esperarError): void {
        $resultado = $servicio->genera_cer_pem($ruta('limite'), $salida);
        $esperarError($resultado);
        exigir($resultado['mensaje'] === 'Certificado no valido', 'El limite exacto llega al parser');
    });
    $probar('der_truncado_rechazado', function () use ($servicio, $ruta, $salida, $cerDer, $esperarError): void {
        file_put_contents($ruta('truncado'), substr($cerDer, 0, 30));
        $esperarError($servicio->genera_cer_pem($ruta('truncado'), $salida));
    });
    $probar('pem_con_clave_oculta_rechazado', function () use ($servicio, $ruta, $salida, $cerPem, $keyPlana, $esperarError): void {
        file_put_contents($ruta('mezclado'), $cerPem . $keyPlana);
        $esperarError($servicio->genera_cer_pem($ruta('mezclado'), $salida));
    });
    $probar('cert_con_publica_ajena_rechazado', function () use ($servicio, $ruta, $salida, $cerPem, $otraClave, $esperarError): void {
        file_put_contents($ruta('publica-ajena'), openssl_pkey_get_details($otraClave)['key'] . $cerPem);
        $esperarError($servicio->genera_cer_pem($ruta('publica-ajena'), $salida));
    });
    $probar('salida_directorio_rechazada', function () use ($servicio, $ruta, $directorio, $esperarError): void {
        $esperarError($servicio->genera_cer_pem($ruta('cer.der'), $directorio));
    });
    $probar('salida_padre_ausente_rechazada', function () use ($servicio, $ruta, $esperarError): void {
        $esperarError($servicio->genera_cer_pem($ruta('cer.der'), $ruta('ausente/salida.pem')));
    });
    $probar('rutas_vacias_rechazadas', function () use ($servicio, $ruta, $salida, $esperarError): void {
        $esperarError($servicio->genera_cer_pem('', $salida));
        errores::$error = false;
        $esperarError($servicio->genera_cer_pem($ruta('cer.der'), ''));
    });
    $probar('conversion_no_exige_par_implícito', function () use ($servicio, $ruta, $salida, $password): void {
        exigir($servicio->genera_key_pem($password, $ruta('otra.pem'), $salida) === null, 'Convertir no impone pareja');
    });
    $probar('sin_temporales_de_salida_residuales', function () use ($directorio): void {
        exigir(glob($directorio . '/.csd-*') === [], 'No quedan temporales del lector');
    });
    $probar('der_con_bytes_anexos_rechazado', function () use ($servicio, $ruta, $salida, $cerDer, $esperarError): void {
        file_put_contents($ruta('anexado'), $cerDer . 'BASURA');
        $esperarError($servicio->genera_cer_pem($ruta('anexado'), $salida));
    });
    $probar('key_der_anexada_rechazada', function () use ($servicio, $ruta, $salida, $keyDer, $password, $esperarError): void {
        file_put_contents($ruta('key-anexada'), $keyDer . 'BASURA');
        $esperarError($servicio->genera_key_pem($password, $ruta('key-anexada'), $salida));
    });
    $probar('pem_con_der_anexado_rechazado', function () use ($servicio, $ruta, $salida, $cerDer, $esperarError): void {
        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($cerDer . 'BASURA'), 64, "\n") . "-----END CERTIFICATE-----\n";
        file_put_contents($ruta('pem-anexado'), $pem);
        $esperarError($servicio->genera_cer_pem($ruta('pem-anexado'), $salida));
    });
    $probar('der_longitud_indefinida_rechazada', function () use ($servicio, $ruta, $salida, $esperarError): void {
        file_put_contents($ruta('indefinida'), hex2bin('30800201010000'));
        $esperarError($servicio->genera_cer_pem($ruta('indefinida'), $salida));
    });
    $probar('der_longitud_no_minima_rechazada', function () use ($servicio, $ruta, $salida, $esperarError): void {
        file_put_contents($ruta('no-minima'), hex2bin('308103020101'));
        $esperarError($servicio->genera_cer_pem($ruta('no-minima'), $salida));
    });
    $probar('padre_symlink_entrada_rechazado', function () use ($servicio, $ruta, $salida, $directorio, $esperarError): void {
        symlink($directorio, $ruta('padre-enlace'));
        $esperarError($servicio->genera_cer_pem($ruta('padre-enlace/cer.der'), $salida));
    });
    $probar('padre_symlink_salida_rechazado', function () use ($servicio, $ruta, $esperarError): void {
        $esperarError($servicio->genera_cer_pem($ruta('cer.der'), $ruta('padre-enlace/nuevo.pem')));
        exigir(!file_exists($ruta('nuevo.pem')), 'No escribir por enlace de directorio');
    });
    $registro = json_decode(file_get_contents(__DIR__ . '/registro.json'), true);
    exigir($pasaron === $registro['casos'], 'Registro y orden de casos exactos');
} catch (Throwable $error) {
    $fallo = $error->getMessage();
} finally {
    $entradas = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($entradas as $entrada) {
        if ($entrada->isLink() || $entrada->isFile()) {
            unlink($entrada->getPathname());
        } else {
            rmdir($entrada->getPathname());
        }
    }
    rmdir($directorio);
}
$informe = ['estado' => $fallo === null ? 'PASS' : 'BLOQUEADO', 'casos' => $pasaron,
    'fallo' => $fallo, 'limpieza' => !file_exists($directorio), 'php' => PHP_VERSION,
    'openssl' => OPENSSL_VERSION_TEXT, 'sha256_fuente' => hash_file('sha256', $raiz . '/src/ssl.php')];
file_put_contents($raiz . '/.git/qa/resultado-openssl.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo json_encode($informe, JSON_UNESCAPED_UNICODE) . "\n";
exit($fallo === null && $informe['limpieza'] ? 0 : 1);
