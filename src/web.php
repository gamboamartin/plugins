<?php

namespace gamboamartin\plugins;

use gamboamartin\errores\errores;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Web
{
    public errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }

    public function leer_contenido(string $url) : string|array
    {
        $client = new Client([
            'curl' => [
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT:!DH'
            ]
        ]);

        try {
            $response = $client->request('GET', $url);
        } catch (GuzzleException $e) {
            return $this->error->error(mensaje: 'Error al leer contenido de la página web: '. $e->getMessage(),
                data: $e->getMessage());
        }

        if ($response->getStatusCode() != 200) {
            return $this->error->error(mensaje: 'No se pudo leer el contenido', data: $response->getStatusCode());
        }

        return (string)$response->getBody();
    }
}