<?php

namespace gamboamartin\plugins;

use Exception;

class telegram_api
{
    /**
     * Función para obtener la URL base de la API de Telegram
     * @param string $bot_token Token del bot de Telegram
     * @return string URL base de la API de Telegram
     * @throws Exception
     */
    public function url_base(string $bot_token): string
    {
        if (!$this->validar_token($bot_token)) {
            throw new Exception('Token de bot inválido');
        }

        return "https://api.telegram.org/bot$bot_token";
    }

    /**
     * Valida si el token del bot tiene el formato correcto
     * @param string $bot_token Token del bot de Telegram
     * @return bool
     */
    private function validar_token(string $bot_token): bool
    {
        return preg_match('/^\d{10}:[A-Za-z0-9_-]{35}$/', $bot_token) === 1;
    }

    /**
     * Función para enviar un mensaje a un chat de Telegram usando la API de Telegram
     * @param string $bot_token Token del bot de Telegram
     * @param string $chat_id ID del chat al que se enviará el mensaje
     * @param string $mensaje Mensaje a enviar
     * @param array $opciones Opciones adicionales como 'parse_mode'
     * @return array Respuesta de la API de Telegram en formato array
     * @throws Exception
     */
    public function enviar_mensaje(string $bot_token, string $chat_id, string $mensaje, array $opciones = []): array
    {
        $url = $this->url_base($bot_token) . "/sendMessage";

        if (empty($chat_id) || empty($mensaje)) {
            throw new Exception('El chat_id y el mensaje son obligatorios');
        }

        $data = array_merge([
            'chat_id' => $chat_id,
            'text' => $mensaje,
        ], $opciones);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Error en la solicitud cURL: $error");
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded_response = json_decode($response, true);

        if ($http_code !== 200 || isset($decoded_response['ok']) && !$decoded_response['ok']) {
            $error_message = $decoded_response['description'] ?? 'Error desconocido';
            throw new Exception("Error en la API de Telegram: $error_message");
        }

        return $decoded_response;
    }
}