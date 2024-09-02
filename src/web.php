<?php

namespace gamboamartin\plugins;

use DOMDocument;
use DOMXPath;
use gamboamartin\errores\errores;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;

class web
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

    /**
     * Obtiene el contenido de una página web
     * @param string $html Contenido de la página web
     * @return stdClass Objeto con los datos obtenidos de la página web
     */
    public function contenido_web_formateado(string $html): stdClass
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $rfc_node = $xpath->query('//ul[@data-role="listview"][1]/li[contains(text(), "El RFC:")]');
        $datos_identificacion = $xpath->query('//ul[@data-role="listview"][2]//table/tbody/tr');
        $datos_ubicacion = $xpath->query('//ul[@data-role="listview"][3]//table/tbody/tr');
        $datos_fiscales = $xpath->query('//ul[@data-role="listview"][4]//table/tbody/tr');

        function extraer_rfc($nodes)
        {
            $rfc = '';

            if ($nodes->length > 0) {
                $rfc_text = $nodes->item(0)->textContent;
                if (preg_match('/El RFC:\s*([A-Z0-9]{10,13})/', $rfc_text, $matches)) {
                    if (isset($matches[1])) {
                        $rfc = trim($matches[1]);
                    }
                }
            }

            return $rfc;
        }

        function extraer_datos($nodes)
        {
            $datos = [];
            foreach ($nodes as $row) {
                $tds = $row->getElementsByTagName('td');
                if ($tds->length == 2) {
                    $key = trim($tds->item(0)->textContent);
                    $value = trim($tds->item(1)->textContent);

                    if (preg_match('/^\$\(/', $key) === 0) {
                        $key = strtolower($key);
                        $key = str_replace(':', '', $key);
                        $key = str_replace(' ', '_', $key);
                        $key = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
                        $datos[$key] = $value;
                    }
                }
            }
            return $datos;
        }

        $datos_rfc = extraer_rfc($rfc_node);
        $datos_identificacion = extraer_datos($datos_identificacion);
        $datos_ubicacion = extraer_datos($datos_ubicacion);
        $datos_fiscales = extraer_datos($datos_fiscales);

        $salida = new stdClass();
        $salida->rfc = $datos_rfc;
        $salida->datos_identificacion = $datos_identificacion;
        $salida->datos_ubicacion = $datos_ubicacion;
        $salida->datos_fiscales = $datos_fiscales;

        return $salida;
    }
}