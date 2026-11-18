<?php
declare(strict_types=1);

namespace qa {
    final class Estado {
        public static string $raiz;
        public static string $password = 'QA-FICTICIA-NO-VALIDA-1811';
        public static array $filas = [];
        public static array $eventos = [];
        public static string $modo;
    }
    final class Transaccion extends \PDO {
        private array $previas = [];
        public function __construct() {}
        public function beginTransaction(): bool {
            $this->previas = Estado::$filas;
            Estado::$eventos[] = 'begin';
            return true;
        }
        public function commit(): bool { Estado::$eventos[] = 'commit'; return true; }
        public function rollBack(): bool {
            Estado::$filas = $this->previas;
            Estado::$eventos[] = 'rollback';
            return true;
        }
    }
}
namespace gamboamartin\system {
    class system {
        public \PDO $link;
        public object $errores;
        public string $tabla = 'fc_csd';
        public function alta_bd(bool $header, bool $ws = false): array|\stdClass {
            \qa\Estado::$filas['csd'] = ['id' => 1];
            return (object)['registro_id' => 1];
        }
        public function out_alta(...$args): \stdClass { return new \stdClass(); }
    }
    final class actions {
        public function init_alta_bd(): string { return 'modifica'; }
    }
}
namespace gamboamartin\documento\models {
    final class doc_extension {
        public function __construct(\PDO $link) {}
        public function extension(string $name_file): string { return pathinfo($name_file, PATHINFO_EXTENSION); }
    }
}
namespace gamboamartin\facturacion\models {
    class fc_key_csd {
        public string $tabla = 'fc_key_csd';
        public function __construct(\PDO $link) {}
        public function alta_registro(array $registro): \stdClass {
            $destino = \qa\Estado::$raiz . '/almacen/' . $this->tabla;
            if (!copy($_FILES['documento']['tmp_name'], $destino)) {
                throw new \RuntimeException('No se pudo preparar almacén ficticio');
            }
            chmod($destino, 0600);
            \qa\Estado::$filas[$this->tabla] = ['id' => 1, 'fc_csd_id' => 1];
            return (object)['registro_id' => 1];
        }
    }
    class fc_cer_csd extends fc_key_csd { public string $tabla = 'fc_cer_csd'; }
    final class fc_csd {
        public function __construct(\PDO $link) {}
        public function genera_pems(int $fc_csd_id): array|\stdClass {
            $ssl = new \gamboamartin\plugins\ssl();
            $base = \qa\Estado::$raiz . '/almacen/';
            // Orden del modelo real: primero KEY, después CER.
            $password = \qa\Estado::$modo === 'error_key' ? 'QA-ERRONEA' : \qa\Estado::$password;
            $r = $ssl->genera_key_pem($password, $base . 'fc_key_csd', $base . 'key.pem');
            if (\gamboamartin\errores\errores::$error) { return $r; }
            $r = $ssl->genera_cer_pem($base . 'fc_cer_csd', $base . 'cer.pem');
            if (\gamboamartin\errores\errores::$error) { return $r; }
            return new \stdClass();
        }
    }
}
namespace {
    $raiz = dirname(__DIR__, 3);
    $controlador = getenv('FUENTE_CONTROLADOR_CSD');
    $errores = getenv('FUENTE_ERRORES');
    if (!$controlador || !$errores || !is_file($controlador) || !is_file($errores)) {
        throw new \RuntimeException('Se requieren fuentes reales explícitas');
    }
    if (hash_file('sha256', $controlador) !== '647d6ea9476184521151ee85358a51caf092f80965a49ec38897931c423d380e'
        || hash_file('sha256', $errores) !== '4e307a08c4bea98be8a52a3789cb8f9a18e76c725b4112fc07ba84b2aa4911ad') {
        throw new \RuntimeException('Fuentes distintas de las documentadas');
    }
    require $errores;
    require $raiz . '/src/ssl.php';
    require $controlador;
    \qa\Estado::$modo = $argv[1] ?? 'exito';
    $dir = $raiz . '/.git/qa/tmp/controlador-' . bin2hex(random_bytes(8));
    mkdir($dir, 0700);
    mkdir($dir . '/almacen', 0700);
    \qa\Estado::$raiz = $dir;
    file_put_contents($dir . '/openssl.cnf', "[req]\ndistinguished_name=dn\n[dn]\n");
    $opciones = ['private_key_bits' => 2048, 'config' => $dir . '/openssl.cnf', 'digest_alg' => 'sha256'];
    $key = openssl_pkey_new($opciones);
    $csr = openssl_csr_new(['commonName' => 'QA CONTROLADOR FICTICIO'], $key, $opciones);
    $cert = openssl_csr_sign($csr, null, $key, 1, $opciones);
    openssl_x509_export($cert, $cerPem);
    openssl_pkey_export($key, $keyPem, \qa\Estado::$password, $opciones);
    file_put_contents($dir . '/entrada.cer', \qa\Estado::$modo === 'error_cer' ? 'CER FICTICIO INVALIDO' : $cerPem);
    file_put_contents($dir . '/entrada.key', $keyPem);
    chmod($dir . '/entrada.key', 0600);
    $_POST = ['password' => \qa\Estado::$password];
    $_SESSION = [];
    $_FILES = [
        'fc_cer_csd_doc' => ['name' => 'ficticio.cer', 'tmp_name' => $dir . '/entrada.cer'],
        'fc_key_csd_doc' => ['name' => 'ficticio.key', 'tmp_name' => $dir . '/entrada.key'],
    ];
    ob_start();
    register_shutdown_function(static function () use ($dir, $keyPem): void {
        $salida = ob_get_clean();
        $archivos = array_map('basename', glob($dir . '/almacen/*'));
        sort($archivos);
        $error = \qa\Estado::$modo !== 'exito';
        $eventosEsperados = ['begin', $error ? 'rollback' : 'commit'];
        $archivosEsperados = match (\qa\Estado::$modo) {
            'error_key' => ['fc_cer_csd', 'fc_key_csd'],
            'error_cer' => ['fc_cer_csd', 'fc_key_csd', 'key.pem'],
            default => ['cer.pem', 'fc_cer_csd', 'fc_key_csd', 'key.pem']
        };
        $sinSecretos = !str_contains($salida, \qa\Estado::$password)
            && !str_contains($salida, $keyPem)
            && !str_contains($salida, 'BEGIN ENCRYPTED PRIVATE KEY');
        $ok = \qa\Estado::$eventos === $eventosEsperados && $archivos === $archivosEsperados
            && ($error ? \qa\Estado::$filas === [] : count(\qa\Estado::$filas) === 3) && $sinSecretos;
        foreach (glob($dir . '/almacen/*') as $file) { unlink($file); }
        rmdir($dir . '/almacen');
        foreach (glob($dir . '/*') as $file) { unlink($file); }
        rmdir($dir);
        echo json_encode([
            'estado' => $ok ? 'PASS' : 'FAIL', 'escenario' => \qa\Estado::$modo,
            'eventos' => \qa\Estado::$eventos, 'filas_finales' => count(\qa\Estado::$filas),
            'archivos_antes_limpieza' => $archivos, 'salida_sin_secretos_ficticios' => $sinSecretos,
            'limpieza' => !file_exists($dir),
            'metodo_real' => 'controlador_fc_csd::alta_bd e inserta_files_doc',
            'sha256_controlador' => '647d6ea9476184521151ee85358a51caf092f80965a49ec38897931c423d380e',
            'limite' => 'Caracterización de residuos. Transacción, modelos y almacén dobles. No alta integral ni recuperación aprobada.'
        ], JSON_UNESCAPED_SLASHES), PHP_EOL;
        if (!$ok) { exit(1); }
    });
    $ref = new \ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_csd::class);
    $c = $ref->newInstanceWithoutConstructor();
    $c->link = new \qa\Transaccion();
    $c->errores = new \gamboamartin\errores\errores();
    $c->alta_bd(false);
}
