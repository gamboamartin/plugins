<?php
namespace gamboamartin\plugins;

use gamboamartin\errores\errores;

class ssl
{
    private errores $error;

    public function __construct()
    {
        $this->error = new errores();

    }

    final public function genera_cer_pem(string $ruta_in, string $ruta_out): false|array|string|null
    {
        $ruta_in = trim($ruta_in);
        if($ruta_in === ''){
            return $this->error->error(mensaje:'Error ruta_in vacio',data:  $ruta_in);
        }
        $ruta_out = trim($ruta_out);
        if($ruta_out === ''){
            return $this->error->error(mensaje:'Error ruta_out vacio',data:  $ruta_out);
        }
        if(!file_exists($ruta_in)){
            return $this->error->error(mensaje:'Error ruta_in no existe',data:  $ruta_in);
        }

        $comando = "openssl x509 -inform DER -outform PEM -in $ruta_in -pubkey -out $ruta_out";
        $salida = shell_exec($comando);
        return $salida;
    }
    final public function genera_key_pem(string $pass, string $ruta_in, string $ruta_out): false|array|string|null
    {
        $pass = trim($pass);
        if($pass === ''){
            return $this->error->error(mensaje:'Error pass vacio',data:  $pass);
        }
        $ruta_in = trim($ruta_in);
        if($ruta_in === ''){
            return $this->error->error(mensaje:'Error ruta_in vacio',data:  $ruta_in);
        }
        $ruta_out = trim($ruta_out);
        if($ruta_out === ''){
            return $this->error->error(mensaje:'Error ruta_out vacio',data:  $ruta_out);
        }
        if(!file_exists($ruta_in)){
            return $this->error->error(mensaje:'Error ruta_in no existe',data:  $ruta_in);
        }

        $comando = "openssl pkcs8 -inform DER -in $ruta_in -passin pass:$pass -out $ruta_out";
        $salida = shell_exec($comando);
        return $salida;
    }

}
