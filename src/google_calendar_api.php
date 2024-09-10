<?php

namespace gamboamartin\plugins;

use Exception;
use InvalidArgumentException;
use PhpParser\Node\Scalar\String_;

class google_calendar_api
{
    const string OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
    const string CALENDAR_TIMEZONE_URI = 'https://www.googleapis.com/calendar/v3/users/me/settings/timezone';
    const string CALENDAR_LIST_URI = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
    const string CALENDAR_EVENT = 'https://www.googleapis.com/calendar/v3/calendars/';

    const string GOOGLE_OAUTH_SCOPE = 'https://www.googleapis.com/auth/calendar';

    function __construct($params = array())
    {
        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    function inicializar($params = array()): void
    {
        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * @param string $google_client_id Id del cliente de Google API (se obtiene en la consola de Google API)
     * @param string $google_redirect_uri URL de redirección después de la autorización del usuario en Google API
     * (se configura en la consola de Google API)
     * @return string
     */
    public function get_oauth_url(string $google_client_id, string $google_redirect_uri): string
    {
        $query_params = [
            'scope' => self::GOOGLE_OAUTH_SCOPE,
            'redirect_uri' => $google_redirect_uri,
            'response_type' => 'code',
            'client_id' => $google_client_id,
            'access_type' => 'online'
        ];

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($query_params);
    }

    /**
     * @param string $client_id Id del cliente de Google API (se obtiene en la consola de Google API)
     * @param string $redirect_uri URL de redirección después de la autorización del usuario en Google API
     * (se configura en la consola de Google API)
     * @param string $client_secret Clave secreta del cliente de Google API (se obtiene en la consola de Google API)
     * @param string $code Código de autorización obtenido en el paso anterior
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function get_access_token(string $client_id, string $redirect_uri, string $client_secret, string $code,
                                           bool   $ssl_verify = false): array
    {
        $campos_post = http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'client_secret' => $client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::OAUTH2_TOKEN_URI);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $campos_post);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception('Falló la petición para obtener el token de acceso: ' . $response);
        }

        $data = json_decode($response, true);

        if (!is_array($data) || empty($data['access_token'])) {
            throw new Exception('No se pudo obtener el token de acceso: ' . $response);
        }

        return $data;
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para obtener la zona horaria del calendario
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function get_calendar_timezone(string $access_token, bool $ssl_verify = false): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::CALENDAR_TIMEZONE_URI);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error_message = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error_message);
        }

        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception('Falló la petición para obtener la zona horaria del calendario: ' . $response);
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['value'])) {
            throw new Exception('No se pudo obtener la zona horaria del calendario: ' . $response);
        }

        return $data['value'];
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para obtener la lista de calendarios
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function get_calendar_list(string $access_token, bool $ssl_verify = false): array
    {
        $url_params = [
            'fields' => 'items(id,summary,timeZone)',
            'minAccessRole' => 'owner'
        ];

        $url_calendars = self::CALENDAR_LIST_URI . '?' . http_build_query($url_params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_calendars);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception('Falló la petición para obtener la lista de calendarios: ' . $response);
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['items'])) {
            throw new Exception('No se pudo obtener la lista de calendarios: ' . $response);
        }

        return $data['items'];
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para obtener los eventos del calendario
     * @param string $calendar_id Id del calendario de Google Calendar
     * @param string|null $time_min Fecha y hora mínima para los eventos (opcional)
     * @param string|null $time_max Fecha y hora máxima para los eventos (opcional)
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function get_calendar_events(string $access_token, string $calendar_id, string $time_min = null,
                                              string $time_max = null, bool $ssl_verify = false): array
    {
        $url_params = [
            'orderBy' => 'startTime',
            'singleEvents' => 'true',
            'timeZone' => 'UTC',
        ];

        if (!empty($time_min)) {
            $url_params['timeMin'] = date('c', strtotime($time_min)); //Formato ISO 8601
        }

        if (!empty($time_max)) {
            $url_params['timeMax'] = date('c', strtotime($time_max));  //Formato ISO 8601
        }

        $url_events = self::CALENDAR_EVENT . $calendar_id . '/events?' . http_build_query($url_params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_events);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception('Falló la petición para obtener los eventos del calendario: ' . $response);
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['items'])) {
            throw new Exception('No se pudo obtener los eventos del calendario: ' . $response);
        }

        return $data['items'];
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para crear un calendario
     * @param string $summary Nombre del calendario
     * @param string|null $description Descripción del calendario (opcional)
     * @param string $timeZone Zona horaria del calendario (por defecto es UTC)
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function crear_calendario(string $access_token, string $summary, ?string $description = null,
                                           string $timeZone = 'UTC', bool $ssl_verify = false): array
    {
        $calendar_data = [
            'summary' => $summary,
            'timeZone' => $timeZone,
        ];

        if (!empty($description)) {
            $calendar_data['description'] = $description;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::CALENDAR_EVENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($calendar_data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != 200) {
            $error = curl_errno($ch) ? curl_error($ch) : 'Error desconocido';
            throw new Exception('Error al crear el calendario: ' . $http_code . ' - ' . $error);
        }

        $data = json_decode($response, true);

        curl_close($ch);

        return $data;
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para actualizar un calendario
     * @param string $calendar_id Id del calendario de Google Calendar
     * @param string $summary Nombre del calendario
     * @param string|null $description Descripción del calendario (opcional)
     * @param string|null $timeZone Zona horaria del calendario (por defecto es UTC)
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @return mixed
     * @throws Exception
     */
    final public function actualizar_calendario(string  $access_token, string $calendar_id, string $summary,
                                                ?string $description = null, ?string $timeZone = 'UTC',
                                                bool    $ssl_verify = false): array
    {
        $calendar_data = [
            'summary' => $summary,
        ];

        if (!empty($description)) {
            $calendar_data['description'] = $description;
        }

        if (!empty($timeZone)) {
            $calendar_data['timeZone'] = $timeZone;
        }

        if (empty($calendar_data)) {
            throw new InvalidArgumentException('No se proporcionaron datos para actualizar el calendario');
        }

        $api_url = self::CALENDAR_EVENT . urlencode($calendar_id);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($calendar_data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != 200) {
            $error = curl_errno($ch) ? curl_error($ch) : 'Error desconocido';
            throw new Exception('Error al actualizar el calendario: ' . $http_code . ' - ' . $error);
        }

        $data = json_decode($response, true);

        curl_close($ch);

        return $data;
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para eliminar un calendario
     * @param string $calendar_id Id del calendario de Google Calendar
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function eliminar_calendario(string $access_token, string $calendar_id, bool $ssl_verify = false): bool
    {
        $api_url = self::CALENDAR_EVENT . urlencode($calendar_id);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != 204) {
            $error = curl_errno($ch) ? curl_error($ch) : 'Error desconocido';
            throw new Exception('Error al eliminar el calendario: ' . $http_code . ' - ' . $error);
        }

        curl_close($ch);

        return true;
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para crear un evento en el calendario
     * @param string $calendar_id Id del calendario de Google Calendar
     * @param string $summary Título del evento
     * @param string|null $description Descripción del evento (opcional)
     * @param string|null $location Ubicación del evento (opcional)
     * @param array|null $start_datetime Fecha y hora de inicio del evento (opcional)
     * @param array|null $end_datetime Fecha y hora de finalización del evento (opcional)
     * @param string|null $timeZone Zona horaria del evento (por defecto es UTC)
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function crear_evento_calendario(string  $access_token, string $calendar_id, string $summary,
                                            ?string $description = null, ?string $location = null,
                                            ?array  $start_datetime = null, ?array $end_datetime = null,
                                            ?string $timeZone = null, bool $ssl_verify = false) : array
    {
        $event_data = [
            'summary' => $summary,
        ];

        if (!empty($description)) {
            $event_data['description'] = $description;
        }

        if (!empty($location)) {
            $event_data['location'] = $location;
        }

        if (!empty($start_datetime)) {
            $event_data['start'] = [
                'dateTime' => $start_datetime['dateTime'],
                'timeZone' => $timeZone ?? 'UTC',
            ];
        }

        if (!empty($end_datetime)) {
            $event_data['end'] = [
                'dateTime' => $end_datetime['dateTime'],
                'timeZone' => $timeZone ?? 'UTC',
            ];
        }

        $api_url = self::CALENDAR_EVENT . urlencode($calendar_id) . '/events';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event_data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != 200) {
            $error = curl_errno($ch) ? curl_error($ch) : 'Error desconocido';
            throw new Exception('Error al crear el evento: ' . $http_code . ' - ' . $error);
        }

        $data = json_decode($response, true);

        curl_close($ch);

        return $data;
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para actualizar un evento en el calendario
     * @param string $calendar_id Id del calendario de Google Calendar
     * @param string $event_id Id del evento de Google Calendar
     * @param string $summary Título del evento
     * @param string|null $description Descripción del evento (opcional)
     * @param string|null $location Ubicación del evento (opcional)
     * @param array|null $start_datetime Fecha y hora de inicio del evento (opcional)
     * @param array|null $end_datetime Fecha y hora de finalización del evento (opcional)
     * @param string|null $timeZone Zona horaria del evento (por defecto es UTC)
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function actualizar_evento_calendario(string  $access_token, string $calendar_id, string $event_id,
                                                 string  $summary, ?string $description = null, ?string $location = null,
                                                 ?array  $start_datetime = null, ?array $end_datetime = null,
                                                 ?string $timeZone = null, bool $ssl_verify = false) : array
    {
        $event_data = [
            'summary' => $summary,
        ];

        if (!is_null($description)) {
            $event_data['description'] = $description;
        }

        if (!is_null($location)) {
            $event_data['location'] = $location;
        }

        if (!is_null($start_datetime) && isset($start_datetime['dateTime'])) {
            $event_data['start'] = [
                'dateTime' => $start_datetime['dateTime'],
                'timeZone' => $timeZone ?? 'UTC',
            ];
        }

        if (!is_null($end_datetime) && isset($end_datetime['dateTime'])) {
            $event_data['end'] = [
                'dateTime' => $end_datetime['dateTime'],
                'timeZone' => $timeZone ?? 'UTC',
            ];
        }

        $api_url = self::CALENDAR_EVENT . urlencode($calendar_id) . '/events/' . urlencode($event_id);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event_data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != 200) {
            $error = curl_errno($ch) ? curl_error($ch) : 'Error desconocido';
            throw new Exception('Error al actualizar el evento: ' . $http_code . ' - ' . $error);
        }

        $data = json_decode($response, true);

        curl_close($ch);

        return $data;
    }

    /**
     * @param string $access_token Token de acceso de Google API (se obtiene en el paso anterior)
     * para eliminar un evento en el calendario
     * @param string $calendar_id Id del calendario de Google Calendar
     * @param string $event_id Id del evento de Google Calendar
     * @param bool $ssl_verify Verificar certificado SSL (por defecto es falso),
     * debería ser verdadero en entornos de producción
     * @throws Exception
     */
    final public function eliminar_evento_calendario(string $access_token, string $calendar_id, string $event_id,
                                               bool   $ssl_verify = false) : bool
    {
        $api_url = self::CALENDAR_EVENT . urlencode($calendar_id) . '/events/' . urlencode($event_id);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != 204) {
            $error = curl_errno($ch) ? curl_error($ch) : 'Error desconocido';
            throw new Exception('Error al eliminar el evento: ' . $http_code . ' - ' . $error);
        }

        curl_close($ch);

        return true;
    }
}