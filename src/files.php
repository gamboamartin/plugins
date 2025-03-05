<?php
namespace gamboamartin\plugins;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use JetBrains\PhpStorm\Pure;
use SplFileInfo;
use stdClass;

class files{
    private errores $error;
    #[Pure] public function __construct(){
        $this->error = new errores();
    }

    private function asigna_archivos(mixed $directorio): array
    {
        $archivos = array();
        while ($archivo = readdir($directorio)){
            $data = $this->asigna_data_file(ruta: $archivo);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al asignar datos', data: $data);
            }
            $archivos[] = $data;
        }
        return $archivos;
    }

    private function asigna_data_file(string $ruta): stdClass
    {
        $data = new stdClass();
        $data->es_directorio = false;
        if(is_dir($ruta)){
            $data->es_directorio = true;
        }
        $data->name_file = $ruta;

        return $data;
    }

    /**
     * REG
     * Asigna los datos necesarios para verificar los archivos de un servicio.
     *
     * Esta función determina las propiedades de un archivo en función de su tipo y extensión.
     * Evalúa si el archivo es un servicio, un archivo de bloqueo (`.lock`) o un archivo de información (`.info`).
     * También extrae el nombre del servicio si el archivo es válido.
     *
     * ### Flujo de ejecución:
     * 1. **Validar la extensión del archivo**:
     *    - Si la validación falla, devuelve un error.
     * 2. **Verificar si el archivo es de tipo lock (`.lock`)**.
     * 3. **Verificar si el archivo es de tipo info (`.info`)**.
     * 4. **Verificar si el archivo es un servicio (`.php`)**.
     * 5. **Extraer el nombre del servicio eliminando la extensión**.
     * 6. **Retornar un objeto con los datos evaluados**.
     *
     * @param string $archivo Ruta o nombre del archivo a evaluar.
     *
     * @return array|stdClass Un objeto con las siguientes propiedades:
     *  - `file` (string): Nombre del archivo.
     *  - `es_lock` (bool): `true` si el archivo es de tipo `.lock`, `false` en caso contrario.
     *  - `es_info` (bool): `true` si el archivo es de tipo `.info`, `false` en caso contrario.
     *  - `es_service` (bool): `true` si el archivo es de tipo `.php` (servicio), `false` en caso contrario.
     *  - `name_service` (string): Nombre del servicio extraído del archivo.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo de servicio (`.php`)
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file_service('mi_servicio.php');
     * ```
     * **Salida esperada:**
     * ```php
     * stdClass Object
     * (
     *     [file] => mi_servicio.php
     *     [es_lock] => false
     *     [es_info] => false
     *     [es_service] => true
     *     [name_service] => mi_servicio
     * )
     * ```
     *
     * #### Ejemplo 2: Archivo de bloqueo (`.lock`)
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file_service('proceso.lock');
     * ```
     * **Salida esperada:**
     * ```php
     * stdClass Object
     * (
     *     [file] => proceso.lock
     *     [es_lock] => true
     *     [es_info] => false
     *     [es_service] => false
     *     [name_service] => proceso.lock
     * )
     * ```
     *
     * #### Ejemplo 3: Archivo de información (`.info`)
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file_service('datos.info');
     * ```
     * **Salida esperada:**
     * ```php
     * stdClass Object
     * (
     *     [file] => datos.info
     *     [es_lock] => false
     *     [es_info] => true
     *     [es_service] => false
     *     [name_service] => datos.info
     * )
     * ```
     *
     * #### Ejemplo 4: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file_service('archivo_sin_extension');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error al validar extension",
     *     "data" => "archivo_sin_extension"
     * ]
     * ```
     *
     * #### Ejemplo 5: Nombre de archivo vacío
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file_service('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error al validar extension",
     *     "data" => ""
     * ]
     * ```
     *
     * ### Notas:
     * - La función **no verifica si el archivo existe en el sistema**.
     * - Solo detecta archivos de tipo `.php`, `.lock`, y `.info`.
     * - Si un archivo no tiene extensión, se genera un error.
     * - Utiliza `stdClass` en lugar de arrays para un acceso más estructurado a los datos.
     *
     * @throws errores Si la validación de extensión falla o hay un problema con la identificación del archivo.
     * @version 1.0.0
     */
    private function asigna_data_file_service(string $archivo): array|stdClass
    {
        // Validar que el archivo tenga una extensión
        $valida = $this->valida_extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        // Evaluar si el archivo es un lock file
        $es_lock = $this->es_lock_service(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al verificar file', data: $es_lock);
        }

        // Evaluar si el archivo es un info file
        $es_info = $this->es_info_service(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al verificar file', data: $es_info);
        }

        // Evaluar si el archivo es un servicio PHP
        $es_service = $this->es_service(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al verificar file', data: $es_service);
        }

        // Obtener el nombre del servicio sin la extensión
        $name_service = $this->name_service(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener nombre del servicio', data: $name_service);
        }

        // Crear un objeto con los datos del archivo
        $data = new stdClass();
        $data->file = $archivo;
        $data->es_lock = $es_lock;
        $data->es_info = $es_info;
        $data->es_service = $es_service;
        $data->name_service = $name_service;

        return $data;
    }


    /**
     * Se asignan los datos de un servicio
     * @version 1.0.0
     * @param stdClass $archivo File de services a verificar
     * @param array $servicio servicio en verificacion
     * @return array $servicio[file,file_lock,file_info]
     */
    private function asigna_data_service(stdClass $archivo, array $servicio): array
    {
        $keys = array('es_service','es_lock','es_info','file');
        $valida = (new validacion())->valida_existencia_keys(keys:$keys, registro: $archivo, valida_vacio: false);
        if(errores::$error){
            return $this->error->error('Error al validar archivo', $valida);
        }


        $servicio = $this->init_data_file_service(servicio: $servicio);
        if(errores::$error){
            return $this->error->error('Error al inicializar servicio', $servicio);
        }


        if($archivo->es_service){
            $servicio['file'] =  $archivo->file;
        }
        if($archivo->es_lock){
            $servicio['file_lock'] =  $archivo->file;
        }
        if($archivo->es_info){
            $servicio['file_info'] =  $archivo->file;
        }
        return $servicio;
    }

    /**
     * Se asignan los archivos de una carpeta de servicios
     * @version 1.0.0
     * @param stdClass $archivo datos ocn ruta del servicio
     * @param array $servicios conjunto de servicios recursivos
     * @return array retorna los servicios ajustados  $servicios[name_service][file,file_lock,file_info] pueden ser
     * varios
     */
    private function asigna_servicios(stdClass $archivo, array $servicios): array
    {
        $keys = array('name_service');
        $valida = (new validacion())->valida_existencia_keys(keys:$keys, registro: $archivo, valida_vacio: false);
        if(errores::$error){
            return $this->error->error('Error al validar archivo', $valida);
        }

        $keys = array('es_service','es_lock','es_info','file');
        $valida = (new validacion())->valida_existencia_keys(keys:$keys, registro: $archivo, valida_vacio: false);
        if(errores::$error){
            return $this->error->error('Error al validar archivo', $valida);
        }

        if(!isset($servicios[$archivo->name_service])){
            $servicios[$archivo->name_service] = array();
        }
        $servicio = $servicios[$archivo->name_service];
        $service = $this->asigna_data_service(archivo: $archivo, servicio: $servicio);
        if(errores::$error){
            return $this->error->error('Error al asignar datos', $service);
        }
        $servicios[$archivo->name_service] = $service;
        return $servicios;
    }

    public static function del_dir_full(string $dir): bool|array
    {
        $dir = trim($dir);
        if($dir === ''){
            return (new errores())->error(mensaje: 'Error el directorio esta vacio', data: $dir);
        }
        if(!file_exists($dir)){
            return (new errores())->error(mensaje: 'Error no existe la ruta', data: $dir);
        }

        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            if(is_dir("$dir/$file")){
                (new files())->del_dir_full("$dir/$file");
            }
            else{
                unlink("$dir/$file");
            }
        }

        if(!file_exists($dir)){
            return (new errores())->error(mensaje: 'Error no existe la ruta', data: $dir);
        }

        return rmdir($dir);

    }

    /**
     * REG
     * Verifica si un archivo tiene la extensión `.info`.
     *
     * Esta función valida que el archivo tenga una extensión válida utilizando `valida_extension()`.
     * Luego, extrae su extensión con `extension()`, y si la extensión es `info`, retorna `true`,
     * indicando que el archivo es un archivo de información de servicio.
     *
     * ### Flujo de ejecución:
     * 1. Se valida que el archivo tenga una extensión llamando a `valida_extension()`.
     * 2. Si la validación falla, se devuelve un error.
     * 3. Se obtiene la extensión del archivo usando `extension()`.
     * 4. Si la extensión es `"info"`, retorna `true`; de lo contrario, retorna `false`.
     *
     * @param string $archivo Ruta o nombre del archivo a verificar.
     *
     * @return bool|array `true` si el archivo tiene la extensión `.info`, `false` si no la tiene.
     * En caso de error, retorna un array con los detalles del problema.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión `.info`
     * **Entrada:**
     * ```php
     * $resultado = es_info_service('servicio.info');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 2: Archivo con otra extensión
     * **Entrada:**
     * ```php
     * $resultado = es_info_service('servicio.log');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * #### Ejemplo 3: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = es_info_service('servicio');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo no tiene extension",
     *     "data" => ["servicio"]
     * ]
     * ```
     *
     * #### Ejemplo 4: Archivo con múltiples extensiones
     * **Entrada:**
     * ```php
     * $resultado = es_info_service('config.backup.info');
     * ```
     * **Salida esperada:**
     * ```php
     * true  // La última extensión es "info"
     * ```
     *
     * #### Ejemplo 5: Nombre de archivo vacío
     * **Entrada:**
     * ```php
     * $resultado = es_info_service('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error archivo no puede venir vacio",
     *     "data" => ""
     * ]
     * ```
     *
     * ### Notas:
     * - Usa `valida_extension()` antes de verificar la extensión para evitar errores con archivos inválidos.
     * - Se apoya en `extension()` para obtener la extensión real del archivo.
     * - Devuelve `true` solo si la extensión exacta es `"info"`.
     * - Puede utilizarse para identificar archivos de información en un sistema de servicios.
     *
     * @throws errores Si `valida_extension()` o `extension()` detectan un problema con el archivo.
     * @version 6.5.0
     */
    private function es_info_service(string $archivo): bool|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $extension = $this->extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener extension', data: $extension);
        }

        $es_info = false;
        if ($extension === 'info') {
            $es_info = true;
        }

        return $es_info;
    }


    /**
     * REG
     * Verifica si un
     * archivo tiene la extensión `.lock`.
     *
     * Esta función primero valida que el archivo tenga una extensión utilizando `valida_extension()`.
     * Luego, extrae la extensión con `extension()`, y si la extensión es `"lock"`,
     * retorna `true`, indicando que el archivo es un archivo de bloqueo de servicio.
     *
     * ### Flujo de ejecución:
     * 1. Se valida que el archivo tenga una extensión llamando a `valida_extension()`.
     * 2. Si la validación falla, se devuelve un error.
     * 3. Se obtiene la extensión del archivo usando `extension()`.
     * 4. Si la extensión es `"lock"`, retorna `true`; de lo contrario, retorna `false`.
     *
     * @param string $archivo Ruta o nombre del archivo a verificar.
     *
     * @return bool|array `true` si el archivo tiene la extensión `.lock`, `false` si no la tiene.
     * En caso de error, retorna un array con los detalles del problema.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión `.lock`
     * **Entrada:**
     * ```php
     * $resultado = es_lock_service('servicio.lock');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 2: Archivo con otra extensión
     * **Entrada:**
     * ```php
     * $resultado = es_lock_service('servicio.txt');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * #### Ejemplo 3: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = es_lock_service('servicio');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo no tiene extension",
     *     "data" => ["servicio"]
     * ]
     * ```
     *
     * #### Ejemplo 4: Archivo con múltiples extensiones
     * **Entrada:**
     * ```php
     * $resultado = es_lock_service('config.backup.lock');
     * ```
     * **Salida esperada:**
     * ```php
     * true  // La última extensión es "lock"
     * ```
     *
     * #### Ejemplo 5: Nombre de archivo vacío
     * **Entrada:**
     * ```php
     * $resultado = es_lock_service('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error archivo no puede venir vacio",
     *     "data" => ""
     * ]
     * ```
     *
     * ### Notas:
     * - Usa `valida_extension()` antes de verificar la extensión para evitar errores con archivos inválidos.
     * - Se apoya en `extension()` para obtener la extensión real del archivo.
     * - Devuelve `true` solo si la extensión exacta es `"lock"`.
     * - Puede utilizarse para identificar archivos de bloqueo en un sistema de servicios.
     *
     * @throws errores Si `valida_extension()` o `extension()` detectan un problema con el archivo.
     * @version 6.5.0
     */
    private function es_lock_service(string $archivo): bool|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $extension = $this->extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener extension', data: $extension);
        }

        $es_lock = false;
        if ($extension === 'lock') {
            $es_lock = true;
        }

        return $es_lock;
    }


    /**
     * REG
     * Determina si un archivo es un servicio basado en su extensión.
     *
     * Esta función valida si un archivo tiene la extensión `.php`, lo que indica que es un archivo de servicio.
     * Primero, verifica que el archivo tenga una extensión válida utilizando `valida_extension()`.
     * Luego, extrae la extensión con `extension()`, y si la extensión es `"php"`,
     * retorna `true`, indicando que el archivo es un servicio ejecutable en el sistema.
     *
     * ### Flujo de ejecución:
     * 1. Se valida que el archivo tenga una extensión llamando a `valida_extension()`.
     * 2. Si la validación falla, se devuelve un error.
     * 3. Se obtiene la extensión del archivo usando `extension()`.
     * 4. Si la extensión es `"php"`, retorna `true`; de lo contrario, retorna `false`.
     *
     * @param string $archivo Ruta o nombre del archivo a verificar.
     *
     * @return bool|array `true` si el archivo tiene la extensión `.php`, `false` si no la tiene.
     * En caso de error, retorna un array con los detalles del problema.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión `.php`
     * **Entrada:**
     * ```php
     * $resultado = es_service('servicio.php');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 2: Archivo con otra extensión
     * **Entrada:**
     * ```php
     * $resultado = es_service('config.json');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * #### Ejemplo 3: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = es_service('script');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo no tiene extension",
     *     "data" => ["script"]
     * ]
     * ```
     *
     * #### Ejemplo 4: Archivo con múltiples extensiones
     * **Entrada:**
     * ```php
     * $resultado = es_service('backup.old.php');
     * ```
     * **Salida esperada:**
     * ```php
     * true  // La última extensión es "php"
     * ```
     *
     * #### Ejemplo 5: Nombre de archivo vacío
     * **Entrada:**
     * ```php
     * $resultado = es_service('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error archivo no puede venir vacio",
     *     "data" => ""
     * ]
     * ```
     *
     * ### Notas:
     * - Usa `valida_extension()` antes de verificar la extensión para evitar errores con archivos inválidos.
     * - Se apoya en `extension()` para obtener la extensión real del archivo.
     * - Devuelve `true` solo si la extensión exacta es `"php"`.
     * - Puede utilizarse para filtrar archivos ejecutables en un sistema de servicios.
     *
     * @throws errores Si `valida_extension()` o `extension()` detectan un problema con el archivo.
     * @version 6.5.0
     */
    private function es_service(string $archivo): bool|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $extension = $this->extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener extension', data: $extension);
        }

        $es_service = false;
        if ($extension === 'php') {
            $es_service = true;
        }

        return $es_service;
    }


    /**
     * Obtiene la estructura de una carpeta
     * @version 1.0.0
     * @param string $ruta debe ser una carpeta con ruta absoluta
     * @return array un array de objetos $result[n]->es_directorio y $result[n]->name_file
     */
    public function estructura(string $ruta): array
    {
        $ruta = trim($ruta);
        $valida = $this->valida_folder(ruta: $ruta);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar ruta', data: $valida);
        }

        $directorio = opendir($ruta);
        if(!$directorio){
            return $this->error->error(mensaje: 'Error al abrir ruta', data: $ruta);
        }
        $archivos = $this->asigna_archivos(directorio: $directorio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar archivos', data: $archivos);
        }

        return $archivos;

    }

    /**
     * REG
     * Obtiene la extensión de un archivo después de validar su nombre.
     *
     * Esta función primero valida que el archivo tenga una extensión utilizando `valida_extension()`.
     * Si la validación es exitosa, extrae la extensión del archivo utilizando la clase `SplFileInfo`.
     *
     * ### Flujo de ejecución:
     * 1. Se llama a `valida_extension()` para verificar si el archivo tiene una extensión válida.
     * 2. Si `valida_extension()` retorna un error, la función devuelve ese error.
     * 3. Si la validación es exitosa, se usa `SplFileInfo::getExtension()` para obtener la extensión del archivo.
     * 4. La extensión obtenida se retorna como un string.
     *
     * @param string $archivo Nombre del archivo del que se extraerá la extensión.
     *
     * @return string|array La extensión del archivo si es válida, o un array con el error si la validación falla.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión válida
     * **Entrada:**
     * ```php
     * $resultado = extension('documento.pdf');
     * ```
     * **Salida esperada:**
     * ```php
     * "pdf"
     * ```
     *
     * #### Ejemplo 2: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = extension('documento');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo no tiene extension",
     *     "data" => ["documento"]
     * ]
     * ```
     *
     * #### Ejemplo 3: Archivo con múltiples puntos en el nombre
     * **Entrada:**
     * ```php
     * $resultado = extension('mi.archivo.tar.gz');
     * ```
     * **Salida esperada:**
     * ```php
     * "gz"  // Se devuelve la última extensión
     * ```
     *
     * #### Ejemplo 4: Nombre vacío
     * **Entrada:**
     * ```php
     * $resultado = extension('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error archivo no puede venir vacio",
     *     "data" => ""
     * ]
     * ```
     *
     * ### Notas:
     * - Usa `valida_extension()` antes de extraer la extensión, asegurando que el archivo es válido.
     * - Devuelve la última parte del nombre después del último `.`.
     * - Se apoya en `SplFileInfo`, que es una clase nativa de PHP para trabajar con archivos.
     * - Puede ser útil para validar archivos antes de procesarlos en una aplicación.
     *
     * @throws errores Si `valida_extension()` detecta un problema con el nombre del archivo.
     * @version 6.4.0
     */
    final public function extension(string $archivo): string|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        return (new SplFileInfo($archivo))->getExtension();
    }


    /**
     * Ajusta los archivos dentro de la carpeta services para su maquetacion
     * @param mixed $directorio Recurso tipo opendir
     * @return array un arreglo de objetos
     */
    private function files_services(mixed $directorio): array
    {
        if(is_string($directorio)){
            return $this->error->error(mensaje:  'Error el directorio no puede ser un string',data: $directorio);
        }
        $archivos = array();
        while ($archivo = readdir($directorio)){
            if(is_dir($archivo)){
                continue;
            }
            if($archivo === 'index.php' || $archivo === 'init.php'){
                continue;
            }
            $tiene_extension = $this->tiene_extension(archivo: $archivo);
            if(!$tiene_extension){
                continue;
            }
            $data = $this->asigna_data_file_service(archivo: $archivo);
            if(errores::$error){
                return $this->error->error(mensaje:  'Error al asignar file',data: $data);
            }
            $archivos[] = $data;
        }

        asort($archivos);
        return $archivos;
    }

    /**
     * Funcion donde se obtienen los datos de un servicio
     * @param string $ruta
     * @param string $name_service
     * @return array
     */
    public function get_data_service(string $ruta, string $name_service): array
    {
        $ruta = trim($ruta);
        $name_service = trim($name_service);

        $valida = $this->valida_folder(ruta: $ruta);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar ruta', data: $valida);
        }
        $directorio = opendir($ruta);
        $data = $this->get_files_services(directorio: $directorio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener servicios', data: $data);
        }
        return $data[$name_service] ?? $this->error->error(mensaje: 'Error no existe el servicio', data: $data);


    }

    private function get_files_folder(string $ruta): array
    {
        $ruta = trim($ruta);
        $valida = $this->valida_folder(ruta: $ruta);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar ruta', data: $valida);
        }
        $estructura = $this->estructura(ruta: $ruta);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al obtener estructura',data: $estructura);
        }
        $archivos = array();
        foreach ($estructura as $data){
            if(!$data->es_directorio){
                $archivos[] = $data;
            }
        }
        return $archivos;
    }

    /**
     * @param mixed $directorio Recurso tipo opendir
     * @return array retorna los servicios ajustados  $servicios[name_service][file,file_lock,file_info]
     * pueden ser varios
     */
    public function get_files_services(mixed $directorio): array
    {
        if(is_string($directorio)){
            return $this->error->error(mensaje:  'Error el directorio no puede ser un string',data: $directorio);
        }

        $archivos = $this->files_services(directorio: $directorio);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al asignar files',data: $archivos);
        }

        $servicios = $this->maqueta_files_service(archivos: $archivos);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al maquetar files',data: $servicios);
        }
        return $servicios;
    }

    /**
     * P ORDER P INT
     * Funcion guarda el documento en la ruta definida
     *
     * @param string $ruta_file Ruta fisica donde está guardado el documento en el server
     * @param string $contenido_file
     *
     * @example
     *      $guarda = $controlador->guarda_archivo_fisico('./archivos/factura/'.$prefijo.$opciones['folio'].'.xml' ,trim($data_xml));
     *
     * @return string|array ruta de guardado
     * @uses formato_valuador
     * @uses todo el sistema
     */
    public function guarda_archivo_fisico(string $contenido_file, string $ruta_file):string|array{
        if($ruta_file === ''){
            return $this->error->error(mensaje: 'Error $ruta_file esta vacia',data:  $ruta_file);
        }
        if($contenido_file === '') {
            return $this->error->error(mensaje: 'Error $contenido_file esta vacio', data: $contenido_file);
        }
        $ruta_file = strtolower($ruta_file);
        if(!file_put_contents($ruta_file, $contenido_file)){
            return $this->error->error(mensaje:'Error al guardar archivo', data: $ruta_file);
        }
        if(!file_exists($ruta_file)){
            return $this->error->error(mensaje:'Error no existe el doc', data: $ruta_file);
        }

        return $ruta_file;
    }

    /**
     * Si los keys de file, file_lock y file_info no existen los inicializa como vacios
     * @param array $servicio servicio en verificacion puede estar vacio
     * @return array $servicio[file,file_lock,file_info] todos vacios si no existen
     */
    private function init_data_file_service(array $servicio): array
    {
        if(!isset( $servicio['file'])){
            $servicio['file'] = '';
        }
        if(!isset( $servicio['file_lock'])){
            $servicio['file_lock'] = '';
        }
        if(!isset( $servicio['file_info'])){
            $servicio['file_info'] = '';
        }
        return $servicio;
    }

    /**
     * P ORDER P INT
     * @param string $ruta
     * @param array $datas
     * @return array
     */
    public function listar_archivos(string $ruta, array $datas = array()):array{
        if (is_dir($ruta)) {
            if ($dh = opendir($ruta)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_dir($ruta . $file) && $file !== "." && $file !== ".."){
                        $datas = $this->listar_archivos(ruta: $ruta . $file . "/",datas:  $datas);
                        if(errores::$error){
                            return $this->error->error('Error al listar archivos', $datas);
                        }
                    }
                    if(($file !== "." && $file !== "..")){
                        $datas[] = $ruta.'/'.$file;
                    }
                }
                closedir($dh);
            }
        }
        else {
            return $this->error->error('Error directorio invalido',$ruta);
        }
        return $datas;
    }

    /**
     * Maqueta los archivos para dar salida a un array con los servicios a mostrar en un index
     * @version 1.0.0
     * @param array $archivos conjunto de datos de archivos para su maquetacion
     * @return array retorna los servicios ajustados  $servicios[name_service][file,file_lock,file_info] pueden ser varios
     */
    private function maqueta_files_service(array $archivos): array
    {
        $servicios = array();
        foreach($archivos as $archivo){
            if(!is_object($archivo)){
                return $this->error->error('Error el archivo debe ser un stdclass', $archivo);
            }
            $servicios = $this->asigna_servicios(archivo: $archivo,servicios: $servicios);
            if(errores::$error){
                return $this->error->error('Error al asignar datos servicios', $servicios);
            }
        }
        return $servicios;
    }

    /**
     * Determina si el archivo se mostrara o no en el index de services
     * @param stdClass $archivo Nombre del archivo a validar
     * @return bool
     */
    public function muestra_en_service(stdClass $archivo): bool
    {
        $muestra = true;
        if(is_dir($archivo->file)){
            $muestra = false;
        }
        if($archivo->file==='index.php'){
            $muestra = false;
        }
        if($archivo->file==='init.php'){
            $muestra = false;
        }
        if($archivo->es_lock){
            $muestra = false;
        }
        if($archivo->es_info){
            $muestra = false;
        }

        return $muestra;
    }

    /**
     * REG
     * Extrae el nombre base de un archivo de servicio eliminando la extensión `.php`.
     *
     * Esta función toma el nombre de un archivo como entrada y retorna su nombre sin la extensión `.php`.
     * Se utiliza principalmente para identificar los nombres de servicios en sistemas que manejan archivos PHP como procesos.
     * Si el archivo está vacío, retorna un error.
     *
     * ### Flujo de ejecución:
     * 1. Se recorta cualquier espacio en blanco del nombre del archivo.
     * 2. Si el nombre del archivo está vacío, se retorna un error.
     * 3. Se divide el nombre en base al delimitador `.php` usando `explode('.php', $archivo)`.
     * 4. Se devuelve la primera parte del resultado, eliminando la extensión.
     *
     * @param string $archivo Nombre del archivo con su extensión.
     *
     * @return string|array Nombre del servicio sin la extensión `.php`, o un array con el error si el nombre del archivo está vacío.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión `.php`
     * **Entrada:**
     * ```php
     * $resultado = name_service('mi_servicio.php');
     * ```
     * **Salida esperada:**
     * ```php
     * 'mi_servicio'
     * ```
     *
     * #### Ejemplo 2: Archivo con varias extensiones
     * **Entrada:**
     * ```php
     * $resultado = name_service('backup.old.php');
     * ```
     * **Salida esperada:**
     * ```php
     * 'backup.old'
     * ```
     *
     * #### Ejemplo 3: Archivo sin la extensión `.php`
     * **Entrada:**
     * ```php
     * $resultado = name_service('documento.txt');
     * ```
     * **Salida esperada:**
     * ```php
     * 'documento.txt'
     * ```
     *
     * #### Ejemplo 4: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = name_service('script');
     * ```
     * **Salida esperada:**
     * ```php
     * 'script'
     * ```
     *
     * #### Ejemplo 5: Nombre de archivo vacío
     * **Entrada:**
     * ```php
     * $resultado = name_service('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error archivo vacio",
     *     "data" => "",
     *     "es_final" => true
     * ]
     * ```
     *
     * ### Notas:
     * - La función **no valida si el archivo existe** en el sistema.
     * - Si el archivo no tiene la extensión `.php`, **se devuelve sin cambios**.
     * - Utiliza `explode('.php', $archivo)` en lugar de `str_replace()` para evitar reemplazos no intencionados en nombres de archivos.
     *
     * @throws errores Si el nombre del archivo está vacío.
     * @version 6.2.0
     */
    private function name_service(string $archivo): string|array
    {
        $archivo = trim($archivo);
        if ($archivo === '') {
            return $this->error->error(mensaje: 'Error archivo vacio', data: $archivo, es_final: true);
        }
        $explode_name = explode('.php', $archivo);

        return $explode_name[0];
    }


    public function nombre_doc(int $tipo_documento_id, string $extension): string
    {
        $nombre = $tipo_documento_id .'.';
        for ($i = 0; $i < 6; $i++){
            $nombre.= rand(10,99);
        }

        return $nombre.".".$extension;
    }

    /**
     * REG
     * Verifica si una cadena de texto (parte del nombre de un archivo) está completamente vacía.
     *
     * Esta función toma una cadena de texto y elimina los espacios en blanco al inicio y al final.
     * Luego, determina si la cadena está vacía. Si la cadena está vacía después del recorte,
     * retorna `true`, indicando que no contiene contenido válido. Si la cadena tiene algún valor,
     * retorna `false`, indicando que hay al menos un carácter válido.
     *
     * ### Flujo de la función:
     * 1. Recibe una cadena de texto como entrada.
     * 2. Aplica `trim()` para eliminar espacios en blanco.
     * 3. Verifica si la cadena resultante está vacía.
     * 4. Retorna `true` si está vacía o `false` si contiene caracteres.
     *
     * @param string $parte La parte del nombre del archivo que se quiere validar.
     *
     * @return bool `true` si la cadena está vacía después del recorte, `false` si contiene caracteres.
     *
     * ### Ejemplos de entrada y salida:
     *
     * #### Ejemplo 1: Cadena vacía
     * **Entrada:**
     * ```php
     * $resultado = parte_to_name_file('');
     * ```
     * **Salida esperada:**
     * ```php
     * true  // Indica que la cadena está vacía
     * ```
     *
     * #### Ejemplo 2: Solo espacios en blanco
     * **Entrada:**
     * ```php
     * $resultado = parte_to_name_file('   ');
     * ```
     * **Salida esperada:**
     * ```php
     * true  // Indica que la cadena está vacía después del trim()
     * ```
     *
     * #### Ejemplo 3: Cadena con contenido
     * **Entrada:**
     * ```php
     * $resultado = parte_to_name_file('archivo');
     * ```
     * **Salida esperada:**
     * ```php
     * false  // La cadena tiene contenido válido
     * ```
     *
     * #### Ejemplo 4: Cadena con espacios y contenido
     * **Entrada:**
     * ```php
     * $resultado = parte_to_name_file('   archivo  ');
     * ```
     * **Salida esperada:**
     * ```php
     * false  // Después del trim(), la cadena sigue teniendo contenido
     * ```
     *
     * ### Notas:
     * - Se utiliza para verificar si una parte del nombre de un archivo es válida o está vacía.
     * - Útil para funciones que manejan nombres de archivos y necesitan validar su estructura.
     * - Si la entrada es solo espacios en blanco, se considera vacía.
     *
     * @version 4.3.0
     */
    private function parte_to_name_file(string $parte): bool
    {
        $todo_vacio = true;
        $parte = trim($parte);
        if ($parte !== '') {
            $todo_vacio = false;
        }
        return $todo_vacio;
    }


    /**
     * Elimina un carpeta con archivos de manera recursiva
     * @param string $dir Directorio
     * @param array $data datos previos
     * @param bool $mismo si mismo elimina la ruta en dir
     * @return array|mixed
     */
    public function rmdir_recursive(string $dir, array $data = array(), bool $mismo = false): mixed
    {
        $dir = trim($dir);
        if($dir === ''){
            return $this->error->error(mensaje: 'Error dir esta vacio',data: $dir);
        }
        if(!file_exists($dir)){
            return $this->error->error(mensaje: 'Error no existe el directorio',data: $dir);
        }
        $files = scandir($dir);
        array_shift($files);    // remove '.' from array
        array_shift($files);    // remove '..' from array

        foreach ($files as $file) {
            $file = $dir . '/' . $file;
            if (is_dir($file)) {
                $data = $this->rmdir_recursive(dir: $file, data: $data);
                if(errores::$error){
                    return $this->error->error(mensaje: 'Error al eliminar directorio',data: $data);
                }
                rmdir($file);
                if(file_exists($file)){
                    return $this->error->error(mensaje: 'Error no se elimino directorio',data: $file);
                }
            }
            else {
                unlink($file);

                if(file_exists($file)){
                    return $this->error->error(mensaje: 'Error no se elimino directorio',data: $file);
                }

                $data[] = $file;
            }
        }
        if($mismo){
            rmdir($dir);
            if(file_exists($dir)){
                return $this->error->error(mensaje: 'Error no se elimino directorio',data: $dir);
            }
        }
        return $data;
    }

    private function tiene_extension(string $archivo): bool
    {
        $tiene_extension = true;
        $explode = explode('.', $archivo);
        if(count($explode) === 1){
            $tiene_extension = false;
        }
        return $tiene_extension;
    }

    /**
     * REG
     * Verifica si todos los elementos de un array están vacíos o contienen solo espacios en blanco.
     *
     * Esta función recorre un array de partes de un nombre de archivo y verifica si cada una de ellas está vacía.
     * Para ello, utiliza la función `parte_to_name_file()`, que valida si una cadena contiene contenido o solo espacios.
     * Si todas las partes están vacías, devuelve `true`. Si al menos una parte tiene contenido, devuelve `false`.
     *
     * ### Flujo de la función:
     * 1. Se inicializa `$todo_vacio` en `true`, asumiendo que todos los elementos están vacíos.
     * 2. Se recorre cada elemento del array `$explode`.
     * 3. Se llama a `parte_to_name_file()` para verificar si el elemento está vacío.
     * 4. Si un elemento no está vacío, `$todo_vacio` cambia a `false`.
     * 5. Si ocurre un error en la validación, retorna un mensaje de error.
     * 6. Devuelve `true` si todos los elementos están vacíos, o `false` si al menos uno tiene contenido.
     *
     * @param array $explode Array de strings a verificar.
     *
     * @return bool|array `true` si todos los elementos están vacíos, `false` si al menos uno tiene contenido.
     * En caso de error, devuelve un array con el mensaje de error.
     *
     * ### Ejemplos de entrada y salida:
     *
     * #### Ejemplo 1: Todos los elementos vacíos
     * **Entrada:**
     * ```php
     * $resultado = todo_vacio(['', '  ', '   ']);
     * ```
     * **Salida esperada:**
     * ```php
     * true  // Todos los elementos están vacíos o son espacios
     * ```
     *
     * #### Ejemplo 2: Al menos un elemento con contenido
     * **Entrada:**
     * ```php
     * $resultado = todo_vacio(['', '  ', 'archivo']);
     * ```
     * **Salida esperada:**
     * ```php
     * false  // Un elemento contiene texto válido
     * ```
     *
     * #### Ejemplo 3: Error en validación
     * **Simulación de error en `parte_to_name_file()`**
     * ```php
     * // Supongamos que `parte_to_name_file` devuelve un error en vez de `true` o `false`
     * $resultado = todo_vacio(['', null, '   ']);
     * ```
     * **Salida esperada (error manejado):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error al validar parte del nombre del file",
     *     "data" => false
     * ]
     * ```
     *
     * ### Notas:
     * - Se usa para validar si un nombre de archivo está compuesto solo por separadores (`.`).
     * - Ayuda a verificar si un nombre de archivo sin extensión es válido o solo tiene espacios.
     * - Si `parte_to_name_file()` detecta un error, la ejecución se detiene y se retorna un mensaje de error.
     *
     * @throws errores Si ocurre un problema en `parte_to_name_file()`.
     * @version 4.4.0
     */
    private function todo_vacio(array $explode): bool|array
    {
        $todo_vacio = true;
        foreach ($explode as $parte) {
            $todo_vacio = $this->parte_to_name_file(parte: $parte);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar parte del nombre del file', data: $todo_vacio);
            }
        }
        return $todo_vacio;
    }


    /**
     * REG
     * Valida si un archivo tiene una extensión válida.
     *
     * Esta función verifica si el nombre del archivo recibido contiene una extensión.
     * Si el archivo no tiene extensión, contiene solo puntos (`.`) o está vacío,
     * retorna un error con un mensaje descriptivo.
     *
     * ### Flujo de ejecución:
     * 1. Se recorta (`trim()`) el nombre del archivo para eliminar espacios en blanco al inicio y al final.
     * 2. Si el archivo está vacío, devuelve un error.
     * 3. Se divide el nombre en partes utilizando `explode('.')`.
     * 4. Si el resultado tiene solo un elemento, significa que no hay extensión, por lo que se retorna un error.
     * 5. Se verifica si todas las partes del nombre están vacías con `todo_vacio()`.
     * 6. Si todas las partes están vacías (nombre inválido), se devuelve un error.
     * 7. Si todas las validaciones pasan, retorna `true`, indicando que el archivo tiene una extensión válida.
     *
     * @param string $archivo Nombre del archivo a validar.
     *
     * @return true|array `true` si el archivo tiene una extensión válida, o un array con el error si no la tiene.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión válida
     * **Entrada:**
     * ```php
     * $resultado = valida_extension('documento.pdf');
     * ```
     * **Salida esperada:**
     * ```php
     * true  // El archivo tiene una extensión válida
     * ```
     *
     * #### Ejemplo 2: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = valida_extension('documento');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo no tiene extension",
     *     "data" => ["documento"]
     * ]
     * ```
     *
     * #### Ejemplo 3: Archivo compuesto solo por puntos
     * **Entrada:**
     * ```php
     * $resultado = valida_extension('....');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo solo tiene puntos",
     *     "data" => "...."
     * ]
     * ```
     *
     * #### Ejemplo 4: Nombre vacío
     * **Entrada:**
     * ```php
     * $resultado = valida_extension('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error archivo no puede venir vacio",
     *     "data" => ""
     * ]
     * ```
     *
     * ### Notas:
     * - Esta función es útil para validar archivos antes de realizar operaciones con ellos.
     * - Puede utilizarse antes de subir archivos para verificar su validez.
     * - Usa `todo_vacio()` para determinar si el nombre del archivo es solo puntos.
     *
     * @throws errores Si ocurre un problema en la validación del nombre del archivo.
     * @version 6.2.0
     */
    final public function valida_extension(string $archivo): true|array
    {
        $archivo = trim($archivo);
        if ($archivo === '') {
            return $this->error->error(mensaje: 'Error archivo no puede venir vacio', data: $archivo, es_final: true);
        }

        $explode = explode('.', $archivo);
        if (count($explode) === 1) {
            return $this->error->error(mensaje: 'Error el archivo no tiene extension', data: $explode);
        }

        $todo_vacio = $this->todo_vacio(explode: $explode);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar si estan vacios todos los elementos de un name file', data: $todo_vacio
            );
        }

        if ($todo_vacio) {
            return $this->error->error(mensaje: 'Error el archivo solo tiene puntos', data: $archivo, es_final: true);
        }

        return true;
    }


    /**
     * Verifica que la ruta sea un folder
     * @version 1.0.0
     * @param string $ruta Ruta a verificar
     * @return bool|array true si es correcto
     */
    private function valida_folder(string $ruta): bool|array
    {
        $ruta = trim($ruta);
        if($ruta === ''){
            return $this->error->error(mensaje: 'Error la ruta esta vacio', data: $ruta);
        }
        if(!is_dir($ruta)){
            return $this->error->error(mensaje: 'Error la ruta no existe o no es una carpeta', data: $ruta);
        }
        return true;
    }


}
