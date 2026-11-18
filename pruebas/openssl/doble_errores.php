<?php
namespace gamboamartin\errores;

/** Doble sin logging, DB o configuración; conserva bandera y retorno array del contrato. */
final class errores
{
    public static bool $error = false;
    public function error(string $mensaje, mixed $data): array
    {
        self::$error = true;
        return ['error' => true, 'mensaje' => $mensaje, 'data' => $data];
    }
}
