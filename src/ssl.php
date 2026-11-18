<?php
namespace gamboamartin\plugins;

use gamboamartin\errores\errores;

class ssl
{
    private errores $error;
    private const LIMITE_ENTRADA = 2097152;

    public function __construct()
    {
        $this->error = new errores();
    }

    /** Conserva PUBLIC KEY + CERTIFICATE del comando histórico y null en éxito. */
    final public function genera_cer_pem(string $ruta_in, string $ruta_out): false|array|string|null
    {
        $ruta_in = trim($ruta_in);
        $ruta_out = trim($ruta_out);
        $validacion = $this->valida_rutas($ruta_in, $ruta_out);
        if (is_array($validacion)) {
            return $validacion;
        }
        $contenido = $this->lee_archivo($ruta_in);
        if (is_array($contenido)) {
            return $contenido;
        }
        $certificado = $this->certificado($contenido);
        if (is_array($certificado)) {
            return $certificado;
        }
        $publica = openssl_pkey_get_public($certificado);
        $datos = $publica === false ? false : openssl_pkey_get_details($publica);
        if ($datos === false || !openssl_x509_export($certificado, $pem)) {
            return $this->fallo('No fue posible exportar el certificado');
        }
        return $this->escribe_pem($ruta_out, $datos['key'] . $pem);
    }

    /** No incluye contraseña ni clave en comandos, errores o valor de retorno. */
    final public function genera_key_pem(string $pass, string $ruta_in, string $ruta_out): false|array|string|null
    {
        // Compatibilidad: el método previo también recortaba la contraseña.
        $pass = trim($pass);
        if ($pass === '') {
            return $this->fallo('Error pass vacio');
        }
        $ruta_in = trim($ruta_in);
        $ruta_out = trim($ruta_out);
        $validacion = $this->valida_rutas($ruta_in, $ruta_out);
        if (is_array($validacion)) {
            return $validacion;
        }
        $contenido = $this->lee_archivo($ruta_in);
        if (is_array($contenido)) {
            return $contenido;
        }
        $clave = $this->clave($contenido, $pass);
        if (is_array($clave)) {
            return $clave;
        }
        if (!openssl_pkey_export($clave, $pem, null)) {
            return $this->fallo('No fue posible exportar la clave');
        }
        // Como pkcs8 histórico, la salida es PEM sin cifrar; debe permanecer privada.
        return $this->escribe_pem($ruta_out, $pem);
    }

    /** Validación local explícita, separada de convertir; no certifica política SAT. */
    final public function valida_par_cer_key(string $ruta_cer, string $ruta_key, string $pass): bool|array
    {
        $pass = trim($pass);
        if ($pass === '') {
            return $this->fallo('Error pass vacio');
        }
        $cer = $this->lee_archivo(trim($ruta_cer));
        if (is_array($cer)) {
            return $cer;
        }
        $key = $this->lee_archivo(trim($ruta_key));
        if (is_array($key)) {
            return $key;
        }
        $certificado = $this->certificado($cer);
        if (is_array($certificado)) {
            return $certificado;
        }
        $clave = $this->clave($key, $pass);
        if (is_array($clave)) {
            return $clave;
        }
        if (!openssl_x509_check_private_key($certificado, $clave)) {
            return $this->fallo('El certificado y la clave no corresponden');
        }
        return true;
    }

    private function fallo(string $mensaje): array
    {
        // No devolver la cola OpenSSL, paths, contenido ni contraseña en errores.
        while (openssl_error_string() !== false) {
        }
        return $this->error->error(mensaje: $mensaje, data: []);
    }

    private function ruta_local(string $ruta): bool
    {
        return $ruta !== '' && !str_contains($ruta, "\0") && !str_contains($ruta, '://');
    }

    /** Rechaza enlaces en los padres; no cambia ni amplía permisos del almacén. */
    private function padres_sin_enlaces(string $ruta): bool
    {
        $directorio = dirname($ruta);
        while (true) {
            if (is_link($directorio)) {
                return false;
            }
            $padre = dirname($directorio);
            if ($padre === $directorio) {
                return true;
            }
            $directorio = $padre;
        }
    }

    private function valida_rutas(string $entrada, string $salida): bool|array
    {
        if ($entrada === '') {
            return $this->fallo('Error ruta_in vacio');
        }
        if ($salida === '') {
            return $this->fallo('Error ruta_out vacio');
        }
        if (!$this->ruta_local($entrada) || !$this->ruta_local($salida)
            || !$this->padres_sin_enlaces($entrada) || !$this->padres_sin_enlaces($salida)
            || is_link($salida) || is_dir($salida)) {
            return $this->fallo('Ruta de archivo no permitida');
        }
        $origen = realpath($entrada);
        $destino = realpath($salida);
        if ($origen !== false && $destino === $origen) {
            return $this->fallo('Entrada y salida deben ser distintas');
        }
        return true;
    }

    private function lee_archivo(string $ruta): string|array
    {
        if (!$this->ruta_local($ruta) || !$this->padres_sin_enlaces($ruta)
            || is_link($ruta) || !is_file($ruta) || !is_readable($ruta)) {
            return $this->fallo('Archivo de entrada no disponible');
        }
        $tamano = filesize($ruta);
        if ($tamano === false || $tamano <= 0 || $tamano > self::LIMITE_ENTRADA) {
            return $this->fallo('Archivo de entrada fuera del limite permitido');
        }
        $contenido = @file_get_contents($ruta, false, null, 0, self::LIMITE_ENTRADA + 1);
        if ($contenido === false || strlen($contenido) > self::LIMITE_ENTRADA) {
            return $this->fallo('No fue posible leer la entrada');
        }
        return $contenido;
    }

    /** Solo valida la envoltura DER; OpenSSL sigue validando la estructura criptográfica. */
    private function der_completo(string $contenido): bool
    {
        $tamano = strlen($contenido);
        if ($tamano < 2 || ord($contenido[0]) !== 0x30) {
            return false;
        }
        $longitud = ord($contenido[1]);
        $cabecera = 2;
        if (($longitud & 0x80) !== 0) {
            $octetos = $longitud & 0x7f;
            // DER no admite longitud indefinida ni ceros iniciales en la longitud.
            if ($octetos === 0 || $octetos > 4 || $tamano < 2 + $octetos
                || ord($contenido[2]) === 0) {
                return false;
            }
            $longitud = 0;
            for ($indice = 0; $indice < $octetos; $indice++) {
                $longitud = ($longitud << 8) | ord($contenido[2 + $indice]);
            }
            if ($longitud < 128) {
                return false;
            }
            $cabecera += $octetos;
        }
        return $longitud <= self::LIMITE_ENTRADA && $cabecera + $longitud === $tamano;
    }

    private function envuelve_der(string $contenido, string $tipo): string
    {
        return "-----BEGIN $tipo-----\n" . chunk_split(base64_encode($contenido), 64, "\n")
            . "-----END $tipo-----\n";
    }

    /** Rechaza bloques ajenos y texto adicional; admite el PEM compuesto histórico. */
    private function bloques_pem(string $contenido, array $permitidos): array|false
    {
        $patron = '/-----BEGIN ([A-Z ]+)-----\s*([A-Za-z0-9+\/=\r\n]+)-----END \1-----/';
        preg_match_all($patron, $contenido, $bloques, PREG_SET_ORDER);
        if ($bloques === [] || trim(preg_replace($patron, '', $contenido)) !== '') {
            return false;
        }
        $resultado = [];
        foreach ($bloques as $bloque) {
            if (!in_array($bloque[1], $permitidos, true) || isset($resultado[$bloque[1]])) {
                return false;
            }
            $base64 = preg_replace('/\s/', '', $bloque[2]);
            $der = base64_decode($base64, true);
            if ($der === false || base64_encode($der) !== $base64 || !$this->der_completo($der)) {
                return false;
            }
            $resultado[$bloque[1]] = $bloque[0] . "\n";
        }
        return $resultado;
    }

    private function certificado(string $contenido): \OpenSSLCertificate|array
    {
        $publicaAdicional = null;
        if (str_contains($contenido, '-----BEGIN')) {
            $bloques = $this->bloques_pem($contenido, ['CERTIFICATE', 'PUBLIC KEY']);
            if ($bloques === false || !isset($bloques['CERTIFICATE'])) {
                return $this->fallo('Formato de certificado no valido');
            }
            $pem = $bloques['CERTIFICATE'];
            $publicaAdicional = $bloques['PUBLIC KEY'] ?? null;
        } else {
            if (!$this->der_completo($contenido)) {
                return $this->fallo('Certificado no valido');
            }
            $pem = $this->envuelve_der($contenido, 'CERTIFICATE');
        }
        $certificado = @openssl_x509_read($pem);
        if ($certificado === false) {
            return $this->fallo('Certificado no valido');
        }
        if ($publicaAdicional !== null) {
            $extra = @openssl_pkey_get_public($publicaAdicional);
            $publica = openssl_pkey_get_public($certificado);
            if ($extra === false || $publica === false
                || openssl_pkey_get_details($extra)['key'] !== openssl_pkey_get_details($publica)['key']) {
                return $this->fallo('Clave publica adicional no corresponde al certificado');
            }
        }
        return $certificado;
    }

    private function clave(string $contenido, string $pass): \OpenSSLAsymmetricKey|array
    {
        if (str_contains($contenido, '-----BEGIN')) {
            $bloques = $this->bloques_pem($contenido, ['ENCRYPTED PRIVATE KEY', 'PRIVATE KEY', 'RSA PRIVATE KEY']);
            if ($bloques === false || count($bloques) !== 1) {
                return $this->fallo('Formato de clave no valido');
            }
            $candidatos = [reset($bloques)];
        } else {
            if (!$this->der_completo($contenido)) {
                return $this->fallo('Clave o contrasena no validas');
            }
            $candidatos = [$this->envuelve_der($contenido, 'ENCRYPTED PRIVATE KEY')];
        }
        foreach ($candidatos as $pem) {
            $clave = @openssl_pkey_get_private($pem, $pass);
            if ($clave !== false) {
                while (openssl_error_string() !== false) {
                }
                return $clave;
            }
        }
        return $this->fallo('Clave o contrasena no validas');
    }

    private function escribe_pem(string $ruta, string $contenido): null|array
    {
        $directorio = realpath(dirname($ruta));
        if ($directorio === false || !is_writable($directorio) || is_link($ruta)) {
            return $this->fallo('Directorio de salida no disponible');
        }
        $temporal = @tempnam($directorio, '.csd-');
        if ($temporal === false) {
            return $this->fallo('No fue posible preparar la salida');
        }
        try {
            // tempnam crea un archivo privado; no ampliar permisos del PEM privado.
            if (file_put_contents($temporal, $contenido, LOCK_EX) !== strlen($contenido)
                || !@rename($temporal, $ruta)) {
                return $this->fallo('No fue posible escribir la salida');
            }
            return null;
        } finally {
            if (is_file($temporal)) {
                unlink($temporal);
            }
        }
    }
}
