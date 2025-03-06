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

    /**
     * REG
     * Asigna información a los archivos dentro de un directorio.
     *
     * Esta función recorre un directorio abierto y asigna datos a cada archivo encontrado utilizando `asigna_data_file()`.
     * Valida que el parámetro `$directorio` sea un recurso de tipo `opendir()`, de lo contrario, retorna un error.
     *
     * ### Flujo de ejecución:
     * 1. **Validación del directorio:**
     *    - Si `$directorio` no es un recurso válido de `opendir()`, retorna un error.
     * 2. **Inicializa el array `$archivos`** para almacenar los datos de los archivos.
     * 3. **Recorre el directorio con `readdir()`:**
     *    - Para cada archivo, llama a `asigna_data_file()` para asignar sus datos.
     *    - Si ocurre un error en `asigna_data_file()`, se retorna un mensaje de error.
     *    - Agrega la información del archivo al array `$archivos`.
     * 4. **Retorna el array `$archivos`** con los datos de los archivos del directorio.
     *
     * @param mixed $directorio Recurso del directorio obtenido con `opendir()`.
     *
     * @return array Retorna un array con la información de los archivos dentro del directorio.
     * En caso de error, retorna un array con el mensaje del problema.
     *
     * ### Estructura de salida esperada (`stdClass` por archivo):
     * ```php
     * [
     *     stdClass Object
     *     (
     *         [es_directorio] => false
     *         [name_file] => "archivo1.txt"
     *     ),
     *     stdClass Object
     *     (
     *         [es_directorio] => true
     *         [name_file] => "subcarpeta"
     *     )
     * ]
     * ```
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Obtener archivos de un directorio
     * **Entrada:**
     * ```php
     * $dir = opendir('/ruta/archivos');
     * $resultado = asigna_archivos($dir);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     stdClass Object
     *     (
     *         [es_directorio] => false
     *         [name_file] => "documento.pdf"
     *     ),
     *     stdClass Object
     *     (
     *         [es_directorio] => true
     *         [name_file] => "imagenes"
     *     )
     * ]
     * ```
     *
     * #### Ejemplo 2: Intentar pasar una cadena en lugar de un recurso `opendir()`
     * **Entrada:**
     * ```php
     * $resultado = asigna_archivos('/ruta/archivos');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error $directorio tiene que ser un recurso opendir",
     *     "data" => "/ruta/archivos"
     * ]
     * ```
     *
     * #### Ejemplo 3: Directorio vacío
     * **Entrada:**
     * ```php
     * $dir = opendir('/ruta/vacia');
     * $resultado = asigna_archivos($dir);
     * ```
     * **Salida esperada:**
     * ```php
     * []
     * ```
     *
     * ### Notas:
     * - El parámetro `$directorio` debe ser un recurso válido de `opendir()`.
     * - Se usa `asigna_data_file()` para asignar datos a cada archivo o subdirectorio.
     * - Si no hay archivos en el directorio, retorna un array vacío sin errores.
     *
     * @throws errores Si `$directorio` no es un recurso `opendir()`.
     * @version 1.0.0
     */

    private function asigna_archivos(mixed $directorio): array
    {
        if(!is_resource($directorio)){
            return $this->error->error(mensaje: 'Error $directorio tiene que ser un recurso opendir',
                data: $directorio);
        }
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

    /**
     * REG
     * Asigna datos a un archivo o directorio basado en su ruta.
     *
     * Esta función recibe una ruta, la valida y genera un objeto `stdClass` con información sobre si es un directorio
     * y el nombre del archivo o carpeta. Si la ruta está vacía, devuelve un error.
     *
     * ### Flujo de ejecución:
     * 1. **Elimina espacios en blanco** en la ruta utilizando `trim()`.
     * 2. **Verifica que la ruta no esté vacía:** Si está vacía, retorna un error.
     * 3. **Inicializa un objeto `stdClass` (`$data`).**
     * 4. **Determina si la ruta corresponde a un directorio:**
     *    - Si `is_dir($ruta)` es `true`, asigna `es_directorio = true`.
     *    - Si no, asigna `es_directorio = false`.
     * 5. **Guarda el nombre del archivo o carpeta** en `name_file`.
     * 6. **Retorna el objeto `$data` con los datos asignados.**
     *
     * @param string $ruta Ruta del archivo o directorio a evaluar.
     *
     * @return array|stdClass Retorna un objeto con la información de la ruta o un array con un mensaje de error si la ruta es inválida.
     *
     * ### Estructura de salida (`stdClass`):
     * ```php
     * stdClass Object
     * (
     *     [es_directorio] => true|false  // Indica si la ruta es un directorio.
     *     [name_file] => "nombre.ext"    // Nombre del archivo o carpeta.
     * )
     * ```
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Ruta de un archivo
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file('/var/www/proyecto/index.php');
     * ```
     * **Salida esperada:**
     * ```php
     * stdClass Object
     * (
     *     [es_directorio] => false
     *     [name_file] => "/var/www/proyecto/index.php"
     * )
     * ```
     *
     * #### Ejemplo 2: Ruta de un directorio
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file('/var/www/proyecto/');
     * ```
     * **Salida esperada:**
     * ```php
     * stdClass Object
     * (
     *     [es_directorio] => true
     *     [name_file] => "/var/www/proyecto/"
     * )
     * ```
     *
     * #### Ejemplo 3: Ruta vacía
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error $ruta esta vacia",
     *     "data" => "",
     *     "es_final" => true
     * ]
     * ```
     *
     * #### Ejemplo 4: Ruta inexistente (se evalúa como archivo)
     * **Entrada:**
     * ```php
     * $resultado = asigna_data_file('/ruta/inexistente.txt');
     * ```
     * **Salida esperada:**
     * ```php
     * stdClass Object
     * (
     *     [es_directorio] => false
     *     [name_file] => "/ruta/inexistente.txt"
     * )
     * ```
     *
     * ### Notas:
     * - La función **no verifica si el archivo o directorio existe**, solo si es un directorio.
     * - Si la ruta está vacía, retorna un error.
     * - Puede utilizarse para obtener información básica de una ruta antes de procesarla.
     *
     * @throws errores Si la ruta está vacía.
     * @version 1.0.0
     */

    private function asigna_data_file(string $ruta): array|stdClass
    {
        $ruta = trim($ruta);
        if($ruta === ''){
            return $this->error->error(mensaje: 'Error $ruta esta vacia', data: $ruta, es_final: true);
        }
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
     * REG
     * Asigna los datos de un archivo a un servicio.
     *
     * Esta función toma un objeto `$archivo` con información sobre un archivo y un array `$servicio`.
     * Valida que `$archivo` contenga ciertas claves necesarias (`es_service`, `es_lock`, `es_info`, `file`),
     * inicializa `$servicio` asegurando que tenga las claves requeridas, y asigna los valores según el tipo de archivo.
     *
     * ### Flujo de ejecución:
     * 1. **Validación del objeto `$archivo`:**
     *    - Verifica que `$archivo` tenga las claves `es_service`, `es_lock`, `es_info` y `file`.
     *    - Si alguna clave falta, retorna un error.
     * 2. **Inicialización del array `$servicio`:**
     *    - Usa `init_data_file_service()` para garantizar que `$servicio` tenga las claves `file`, `file_lock` y `file_info`.
     * 3. **Asignación de valores:**
     *    - Si `$archivo->es_service` es `true`, se asigna `file` en `$servicio`.
     *    - Si `$archivo->es_lock` es `true`, se asigna `file_lock` en `$servicio`.
     *    - Si `$archivo->es_info` es `true`, se asigna `file_info` en `$servicio`.
     * 4. **Retorno del array `$servicio`** con los valores asignados.
     *
     * @param stdClass $archivo Objeto con los datos del archivo. Debe contener:
     *  - `es_service` (bool): Indica si el archivo es un servicio (`.php`).
     *  - `es_lock` (bool): Indica si el archivo es de tipo `lock`.
     *  - `es_info` (bool): Indica si el archivo es de tipo `info`.
     *  - `file` (string): Nombre del archivo.
     * @param array $servicio Array de datos del servicio en el que se asignarán los valores.
     *
     * @return array Retorna `$servicio` con los datos del archivo asignados en las claves correctas.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo de servicio (`.php`)
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "es_service" => true,
     *     "es_lock" => false,
     *     "es_info" => false,
     *     "file" => "servicio.php"
     * ];
     * $resultado = asigna_data_service($archivo, []);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "file" => "servicio.php",
     *     "file_lock" => "",
     *     "file_info" => ""
     * ]
     * ```
     *
     * #### Ejemplo 2: Archivo de tipo `lock`
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "es_service" => false,
     *     "es_lock" => true,
     *     "es_info" => false,
     *     "file" => "proceso.lock"
     * ];
     * $resultado = asigna_data_service($archivo, []);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "file" => "",
     *     "file_lock" => "proceso.lock",
     *     "file_info" => ""
     * ]
     * ```
     *
     * #### Ejemplo 3: Archivo de información (`.info`)
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "es_service" => false,
     *     "es_lock" => false,
     *     "es_info" => true,
     *     "file" => "datos.info"
     * ];
     * $resultado = asigna_data_service($archivo, []);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "file" => "",
     *     "file_lock" => "",
     *     "file_info" => "datos.info"
     * ]
     * ```
     *
     * #### Ejemplo 4: Archivo sin claves necesarias
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "file" => "archivo_sin_flags.txt"
     * ];
     * $resultado = asigna_data_service($archivo, []);
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error al validar archivo",
     *     "data" => [...]
     * ]
     * ```
     *
     * ### Notas:
     * - `$archivo` debe contener las claves `es_service`, `es_lock`, `es_info` y `file`, de lo contrario, se genera un error.
     * - `$servicio` es inicializado con `init_data_file_service()` antes de asignar valores.
     * - Cada tipo de archivo (`.php`, `.lock`, `.info`) se asigna a una clave específica en `$servicio`.
     *
     * @throws errores Si la validación de `$archivo` falla.
     * @version 1.0.0
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
     * REG
     * Asigna los datos de un archivo a un conjunto de servicios.
     *
     * Esta función toma un objeto `$archivo` con información sobre un archivo y un array `$servicios`
     * para estructurar y almacenar los archivos organizados por nombre de servicio.
     * Primero, valida que `$archivo` contenga las claves necesarias (`name_service`, `es_service`, `es_lock`, `es_info`, `file`),
     * luego asigna los datos del archivo al servicio correspondiente en `$servicios`.
     *
     * ### Flujo de ejecución:
     * 1. **Validación del objeto `$archivo`:**
     *    - Se verifica que `$archivo` contenga `name_service`, de lo contrario, se retorna un error.
     *    - Se valida la existencia de `es_service`, `es_lock`, `es_info`, y `file`.
     * 2. **Inicialización del servicio en `$servicios`:**
     *    - Si `$archivo->name_service` no está presente en `$servicios`, se inicializa como un array vacío.
     * 3. **Asignación de los datos del archivo al servicio:**
     *    - Se llama a `asigna_data_service()` para estructurar los datos correctamente.
     * 4. **Retorno del array `$servicios`** con los datos del archivo asignados correctamente.
     *
     * @param stdClass $archivo Objeto con los datos del archivo. Debe contener:
     *  - `name_service` (string): Nombre del servicio al que pertenece el archivo.
     *  - `es_service` (bool): Indica si el archivo es un servicio (`.php`).
     *  - `es_lock` (bool): Indica si el archivo es de tipo `lock`.
     *  - `es_info` (bool): Indica si el archivo es de tipo `info`.
     *  - `file` (string): Nombre del archivo.
     * @param array $servicios Array con la estructura de servicios. Puede estar vacío o contener datos previos.
     *
     * @return array Retorna `$servicios` con los datos del archivo organizados bajo la clave `name_service`.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Agregar un archivo de servicio (`.php`) a un conjunto vacío
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "name_service" => "servicio1",
     *     "es_service" => true,
     *     "es_lock" => false,
     *     "es_info" => false,
     *     "file" => "servicio1.php"
     * ];
     * $resultado = asigna_servicios($archivo, []);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "servicio1" => [
     *         "file" => "servicio1.php",
     *         "file_lock" => "",
     *         "file_info" => ""
     *     ]
     * ]
     * ```
     *
     * #### Ejemplo 2: Agregar un archivo `lock` a un servicio existente
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "name_service" => "servicio1",
     *     "es_service" => false,
     *     "es_lock" => true,
     *     "es_info" => false,
     *     "file" => "servicio1.lock"
     * ];
     * $servicios = [
     *     "servicio1" => [
     *         "file" => "servicio1.php",
     *         "file_lock" => "",
     *         "file_info" => ""
     *     ]
     * ];
     * $resultado = asigna_servicios($archivo, $servicios);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "servicio1" => [
     *         "file" => "servicio1.php",
     *         "file_lock" => "servicio1.lock",
     *         "file_info" => ""
     *     ]
     * ]
     * ```
     *
     * #### Ejemplo 3: Archivo sin `name_service`
     * **Entrada:**
     * ```php
     * $archivo = (object) [
     *     "es_service" => true,
     *     "es_lock" => false,
     *     "es_info" => false,
     *     "file" => "servicio1.php"
     * ];
     * $resultado = asigna_servicios($archivo, []);
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error al validar archivo",
     *     "data" => [...]
     * ]
     * ```
     *
     * ### Notas:
     * - `$archivo` debe contener `name_service` y las claves necesarias (`es_service`, `es_lock`, `es_info`, `file`).
     * - Si un servicio ya existe en `$servicios`, los datos se asignan correctamente sin sobrescribir los existentes.
     * - Se usa `asigna_data_service()` para mantener la estructura de los archivos dentro de `$servicios`.
     *
     * @throws errores Si la validación de `$archivo` falla.
     * @version 1.0.0
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
     * REG
     * Obtiene la estructura de un directorio y asigna información a sus archivos y subdirectorios.
     *
     * Esta función valida que la ruta proporcionada sea un directorio válido, lo abre y extrae su contenido,
     * organizando la información de cada archivo y subdirectorio utilizando `asigna_archivos()`.
     *
     * ### Flujo de ejecución:
     * 1. **Validación de la ruta:**
     *    - Se eliminan los espacios en blanco con `trim()`.
     *    - Se valida que la ruta sea un directorio existente usando `valida_folder()`.
     *    - Si la validación falla, retorna un error.
     * 2. **Apertura del directorio:**
     *    - Se intenta abrir el directorio con `opendir($ruta)`.
     *    - Si la apertura falla, se retorna un error.
     * 3. **Obtención y asignación de archivos:**
     *    - Se llama a `asigna_archivos()` para obtener la información de los archivos y subdirectorios dentro de la ruta.
     *    - Si hay un error en la asignación, se retorna un mensaje de error.
     * 4. **Retorno de la estructura de archivos y subdirectorios.**
     *
     * @param string $ruta Ruta del directorio a analizar.
     *
     * @return array Retorna un array con la información de los archivos y subdirectorios encontrados en `$ruta`.
     * En caso de error, retorna un array con el mensaje del problema.
     *
     * ### Estructura de salida esperada (`stdClass` por archivo/directorio):
     * ```php
     * [
     *     stdClass Object
     *     (
     *         [es_directorio] => false
     *         [name_file] => "archivo1.txt"
     *     ),
     *     stdClass Object
     *     (
     *         [es_directorio] => true
     *         [name_file] => "subcarpeta"
     *     )
     * ]
     * ```
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Obtener la estructura de un directorio válido
     * **Entrada:**
     * ```php
     * $resultado = estructura('/var/www/proyecto/');
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     stdClass Object
     *     (
     *         [es_directorio] => false
     *         [name_file] => "index.php"
     *     ),
     *     stdClass Object
     *     (
     *         [es_directorio] => true
     *         [name_file] => "css"
     *     )
     * ]
     * ```
     *
     * #### Ejemplo 2: Ruta inexistente
     * **Entrada:**
     * ```php
     * $resultado = estructura('/ruta/inexistente/');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error al validar ruta",
     *     "data" => [...]
     * ]
     * ```
     *
     * #### Ejemplo 3: Directorio vacío
     * **Entrada:**
     * ```php
     * $resultado = estructura('/ruta/vacia/');
     * ```
     * **Salida esperada:**
     * ```php
     * []
     * ```
     *
     * ### Notas:
     * - La función **no crea el directorio**, solo valida su existencia.
     * - Si la ruta es válida pero no contiene archivos, retorna un array vacío sin errores.
     * - Se usa `opendir()` para abrir la carpeta, pero **no se cierra automáticamente**, ya que `readdir()` se encarga de la lectura.
     *
     * @throws errores Si la validación de la ruta falla o el directorio no puede abrirse.
     * @version 1.0.0
     */

    private function estructura(string $ruta): array
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
     * REG
     * Obtiene y procesa los archivos dentro de un directorio de servicios.
     *
     * Esta función recorre un directorio abierto y extrae los archivos válidos para su procesamiento,
     * excluyendo aquellos que sean directorios o archivos específicos como `index.php` e `init.php`.
     * Solo se incluyen archivos que tengan una extensión válida.
     *
     * ### Flujo de ejecución:
     * 1. **Validación del directorio:** Si el parámetro `$directorio` es una cadena en lugar de un recurso `opendir()`, retorna un error.
     * 2. **Inicialización de la lista de archivos:** Se declara un array vacío `$archivos`.
     * 3. **Recorrido del directorio:** Se utiliza `readdir()` para iterar sobre los archivos dentro del directorio.
     * 4. **Filtrado de archivos:**
     *    - Si el elemento es un directorio (`is_dir($archivo)`), se omite.
     *    - Si el archivo es `index.php` o `init.php`, se omite.
     *    - Si el archivo no tiene extensión (`tiene_extension($archivo) === false`), se omite.
     * 5. **Asignación de datos al archivo:** Se llama a `asigna_data_file_service($archivo)`, validando su contenido.
     * 6. **Manejo de errores:** Si la asignación falla (`errores::$error` es `true`), se retorna un error.
     * 7. **Ordenación de la lista de archivos:** Se ordena el array `$archivos` usando `asort()`.
     * 8. **Retorno del resultado:** Devuelve un array con los archivos procesados.
     *
     * @param mixed $directorio Recurso del directorio obtenido con `opendir()`.
     *
     * @return array Un array de archivos procesados, excluyendo directorios y archivos irrelevantes.
     * En caso de error, retorna un array con la información del problema.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Directorio con archivos válidos
     * **Entrada:**
     * ```php
     * $dir = opendir('/ruta/servicios');
     * $resultado = files_services($dir);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     stdClass Object
     *     (
     *         [file] => servicio1.php
     *         [es_lock] => false
     *         [es_info] => false
     *         [es_service] => true
     *         [name_service] => servicio1
     *     ),
     *     stdClass Object
     *     (
     *         [file] => servicio2.php
     *         [es_lock] => false
     *         [es_info] => false
     *         [es_service] => true
     *         [name_service] => servicio2
     *     )
     * ]
     * ```
     *
     * #### Ejemplo 2: Directorio con archivos sin extensión
     * **Entrada:**
     * ```php
     * $dir = opendir('/ruta/servicios');
     * $resultado = files_services($dir);
     * ```
     * **Salida esperada (si solo hay archivos sin extensión):**
     * ```php
     * []
     * ```
     *
     * #### Ejemplo 3: Intento de pasar un string en lugar de un recurso `opendir()`
     * **Entrada:**
     * ```php
     * $resultado = files_services('/ruta/servicios');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el directorio no puede ser un string",
     *     "data" => "/ruta/servicios"
     * ]
     * ```
     *
     * ### Notas:
     * - La función **no cierra el directorio**, la gestión de `closedir()` debe hacerse externamente.
     * - Se excluyen archivos `index.php` e `init.php` por convención.
     * - La validación de la extensión se hace con `tiene_extension()`, evitando archivos sin extensión.
     * - Se devuelve un array vacío si no hay archivos válidos.
     *
     * @throws errores Si la validación de archivos o directorio falla.
     * @version 1.0.0
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
     * REG
     * Obtiene y organiza los archivos de servicios desde un directorio.
     *
     * Esta función procesa un directorio abierto, extrae los archivos válidos y los organiza en una estructura de servicios.
     * Primero, obtiene los archivos del directorio utilizando `files_services()`, luego estructura los datos con `maqueta_files_service()`.
     *
     * ### Flujo de ejecución:
     * 1. **Validación del directorio:**
     *    - Si `$directorio` es una cadena en lugar de un recurso `opendir()`, retorna un error.
     * 2. **Obtención de archivos:**
     *    - Llama a `files_services()` para recuperar los archivos del directorio.
     *    - Si hay un error en la obtención, retorna un mensaje de error.
     * 3. **Estructuración de archivos en servicios:**
     *    - Llama a `maqueta_files_service()` para organizar los archivos en un array de servicios.
     *    - Si hay un error en la maquetación, retorna un mensaje de error.
     * 4. **Retorno de los servicios organizados.**
     *
     * @param mixed $directorio Recurso del directorio obtenido con `opendir()`.
     *
     * @return array Retorna un array con los archivos organizados por `name_service`.
     * En caso de error, retorna un array con el mensaje del problema.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Obtención de archivos desde un directorio válido
     * **Entrada:**
     * ```php
     * $dir = opendir('/ruta/servicios');
     * $resultado = get_files_services($dir);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "servicio1" => [
     *         "file" => "servicio1.php",
     *         "file_lock" => "servicio1.lock",
     *         "file_info" => ""
     *     ],
     *     "servicio2" => [
     *         "file" => "servicio2.php",
     *         "file_lock" => "",
     *         "file_info" => ""
     *     ]
     * ]
     * ```
     *
     * #### Ejemplo 2: Pasar un string en lugar de un recurso `opendir()`
     * **Entrada:**
     * ```php
     * $resultado = get_files_services('/ruta/servicios');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el directorio no puede ser un string",
     *     "data" => "/ruta/servicios",
     *     "es_final" => true
     * ]
     * ```
     *
     * #### Ejemplo 3: Directorio sin archivos válidos
     * **Entrada:**
     * ```php
     * $dir = opendir('/ruta/vacia');
     * $resultado = get_files_services($dir);
     * ```
     * **Salida esperada:**
     * ```php
     * []
     * ```
     *
     * ### Notas:
     * - El parámetro `$directorio` debe ser un recurso `opendir()`, no una cadena de texto.
     * - Se usa `files_services()` para extraer archivos válidos del directorio.
     * - Se usa `maqueta_files_service()` para estructurar los archivos en servicios.
     * - Si no hay archivos válidos en el directorio, retorna un array vacío sin errores.
     *
     * @throws errores Si `$directorio` no es válido o hay un problema en la extracción de archivos.
     * @version 1.0.0
     */

    private function get_files_services(mixed $directorio): array
    {
        if(is_string($directorio)){
            return $this->error->error(mensaje:  'Error el directorio no puede ser un string',data: $directorio,
                es_final: true);
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
     * REG
     * Inicializa un array con claves predeterminadas para un servicio de archivo.
     *
     * Esta función asegura que el array `$servicio` contenga las claves necesarias (`file`, `file_lock`, `file_info`).
     * Si alguna de estas claves no existe en el array, se inicializa con un valor vacío (`''`).
     *
     * ### Flujo de ejecución:
     * 1. **Verificación y asignación de claves:**
     *    - Si la clave `'file'` no está presente en `$servicio`, se establece con `''`.
     *    - Si la clave `'file_lock'` no está presente en `$servicio`, se establece con `''`.
     *    - Si la clave `'file_info'` no está presente en `$servicio`, se establece con `''`.
     * 2. **Retorno del array ajustado:** Devuelve `$servicio` con las claves garantizadas.
     *
     * @param array $servicio Array de datos del servicio. Puede estar vacío o contener algunas claves.
     *
     * @return array Retorna el array `$servicio` asegurando que contiene las claves `file`, `file_lock` y `file_info`.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Array vacío
     * **Entrada:**
     * ```php
     * $resultado = init_data_file_service([]);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "file" => "",
     *     "file_lock" => "",
     *     "file_info" => ""
     * ]
     * ```
     *
     * #### Ejemplo 2: Array con una clave preexistente
     * **Entrada:**
     * ```php
     * $resultado = init_data_file_service(["file" => "servicio.php"]);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "file" => "servicio.php",
     *     "file_lock" => "",
     *     "file_info" => ""
     * ]
     * ```
     *
     * #### Ejemplo 3: Array con todas las claves definidas
     * **Entrada:**
     * ```php
     * $resultado = init_data_file_service([
     *     "file" => "servicio.php",
     *     "file_lock" => "servicio.lock",
     *     "file_info" => "servicio.info"
     * ]);
     * ```
     * **Salida esperada (sin cambios):**
     * ```php
     * [
     *     "file" => "servicio.php",
     *     "file_lock" => "servicio.lock",
     *     "file_info" => "servicio.info"
     * ]
     * ```
     *
     * ### Notas:
     * - Si el array de entrada ya contiene todas las claves, no se modifica.
     * - Si una clave no existe, se agrega con el valor `''`.
     * - Útil para garantizar que el array tenga la estructura correcta antes de procesarlo.
     *
     * @version 1.0.0
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
     * REG
     * Organiza y estructura los archivos de servicio en un array de servicios.
     *
     * Esta función toma un array de archivos y los agrupa en un array estructurado por nombre de servicio.
     * Para cada archivo, se verifica que sea un objeto (`stdClass`), y luego se asigna al servicio correspondiente
     * utilizando `asigna_servicios()`.
     *
     * ### Flujo de ejecución:
     * 1. **Inicialización del array `$servicios`.**
     * 2. **Iteración sobre el array `$archivos`:**
     *    - Verifica si `$archivo` es un objeto (`stdClass`). Si no lo es, retorna un error.
     *    - Llama a `asigna_servicios()` para asignar los archivos al servicio correspondiente.
     *    - Si ocurre un error en `asigna_servicios()`, se retorna un mensaje de error.
     * 3. **Retorno del array `$servicios` con la estructura organizada.**
     *
     * @param array $archivos Array de archivos de servicio. Cada elemento debe ser un objeto `stdClass`.
     *
     * @return array Retorna un array `$servicios` estructurado con los archivos organizados por `name_service`.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Organización de archivos de servicio
     * **Entrada:**
     * ```php
     * $archivos = [
     *     (object) [
     *         "name_service" => "servicio1",
     *         "es_service" => true,
     *         "es_lock" => false,
     *         "es_info" => false,
     *         "file" => "servicio1.php"
     *     ],
     *     (object) [
     *         "name_service" => "servicio1",
     *         "es_service" => false,
     *         "es_lock" => true,
     *         "es_info" => false,
     *         "file" => "servicio1.lock"
     *     ],
     *     (object) [
     *         "name_service" => "servicio2",
     *         "es_service" => true,
     *         "es_lock" => false,
     *         "es_info" => false,
     *         "file" => "servicio2.php"
     *     ]
     * ];
     * $resultado = maqueta_files_service($archivos);
     * ```
     * **Salida esperada:**
     * ```php
     * [
     *     "servicio1" => [
     *         "file" => "servicio1.php",
     *         "file_lock" => "servicio1.lock",
     *         "file_info" => ""
     *     ],
     *     "servicio2" => [
     *         "file" => "servicio2.php",
     *         "file_lock" => "",
     *         "file_info" => ""
     *     ]
     * ]
     * ```
     *
     * #### Ejemplo 2: Archivo inválido (no es un objeto `stdClass`)
     * **Entrada:**
     * ```php
     * $archivos = [
     *     [
     *         "name_service" => "servicio1",
     *         "file" => "servicio1.php"
     *     ]
     * ];
     * $resultado = maqueta_files_service($archivos);
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error el archivo debe ser un stdclass",
     *     "data" => [...]
     * ]
     * ```
     *
     * ### Notas:
     * - Todos los elementos en `$archivos` deben ser instancias de `stdClass`.
     * - Se usa `asigna_servicios()` para estructurar correctamente los datos.
     * - Si `$archivos` está vacío, retorna un array vacío sin errores.
     *
     * @throws errores Si algún archivo no es un objeto `stdClass`.
     * @version 1.0.0
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

    /**
     * REG
     * Verifica si un archivo tiene una extensión válida.
     *
     * Esta función evalúa si el nombre de un archivo contiene una extensión, basándose en la presencia de un punto (`.`).
     * Si el archivo no tiene un punto o solo tiene uno sin contenido posterior, se considera que no tiene extensión.
     *
     * ### Flujo de ejecución:
     * 1. **Eliminar espacios en blanco:** Se recorta el nombre del archivo utilizando `trim()`.
     * 2. **Dividir el nombre por los puntos:** Se usa `explode('.', $archivo)`.
     * 3. **Contar los fragmentos resultantes:** Si solo hay un fragmento (`count($explode) === 1`), el archivo no tiene extensión.
     * 4. **Retornar el resultado:** Devuelve `true` si tiene extensión, `false` si no la tiene.
     *
     * @param string $archivo Nombre del archivo a evaluar.
     *
     * @return bool `true` si el archivo tiene una extensión válida, `false` si no la tiene.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Archivo con extensión válida
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('documento.txt');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 2: Archivo sin extensión
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('documento');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * #### Ejemplo 3: Archivo que termina en punto
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('archivo.');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * #### Ejemplo 4: Archivo con múltiples extensiones
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('backup.tar.gz');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 5: Archivo compuesto solo por puntos
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('...');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * #### Ejemplo 6: Archivo con espacios en blanco alrededor
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('   foto.png   ');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 7: Nombre vacío
     * **Entrada:**
     * ```php
     * $resultado = tiene_extension('');
     * ```
     * **Salida esperada:**
     * ```php
     * false
     * ```
     *
     * ### Notas:
     * - La función **solo verifica la presencia de un punto (`.`)**, no si la extensión es válida.
     * - Si el archivo **termina en un punto (`archivo.`)**, se considera que **no tiene extensión**.
     * - Si un archivo **tiene varias extensiones (`backup.tar.gz`)**, el resultado sigue siendo `true`.
     * - Se recomienda **validar que la extensión sea correcta** en otra función si es necesario.
     *
     * @throws errores Si el parámetro no es una cadena de texto.
     * @version 1.0.0
     */

    private function tiene_extension(string $archivo): bool
    {
        $archivo = trim($archivo);
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
     * REG
     * Valida si una ruta corresponde a un directorio existente.
     *
     * Esta función verifica que la ruta proporcionada no esté vacía y que corresponda a un directorio válido en el sistema de archivos.
     * Si la ruta no cumple con estos criterios, devuelve un error detallado.
     *
     * ### Flujo de ejecución:
     * 1. **Elimina espacios en blanco** en la ruta utilizando `trim()`.
     * 2. **Verifica que la ruta no esté vacía:** Si está vacía, retorna un error.
     * 3. **Verifica que la ruta sea un directorio válido:** Si la ruta no existe o no es un directorio, retorna un error.
     * 4. **Si la ruta es válida, retorna `true`.**
     *
     * @param string $ruta Ruta del directorio a validar.
     *
     * @return bool|array Retorna `true` si la ruta es válida, o un array con un mensaje de error en caso contrario.
     *
     * ### Ejemplos de uso:
     *
     * #### Ejemplo 1: Validar un directorio existente
     * **Entrada:**
     * ```php
     * $resultado = valida_folder('/var/www/proyecto/');
     * ```
     * **Salida esperada:**
     * ```php
     * true
     * ```
     *
     * #### Ejemplo 2: Ruta vacía
     * **Entrada:**
     * ```php
     * $resultado = valida_folder('');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error la ruta esta vacio",
     *     "data" => "",
     *     "es_final" => true
     * ]
     * ```
     *
     * #### Ejemplo 3: Ruta no existente
     * **Entrada:**
     * ```php
     * $resultado = valida_folder('/ruta/invalida/');
     * ```
     * **Salida esperada (error):**
     * ```php
     * [
     *     "error" => true,
     *     "mensaje" => "Error la ruta no existe o no es una carpeta",
     *     "data" => "/ruta/invalida/",
     *     "es_final" => true
     * ]
     * ```
     *
     * ### Notas:
     * - La función **no crea la carpeta** si no existe, solo valida su existencia.
     * - Si la ruta está vacía o no es un directorio, se retorna un error detallado.
     * - Se recomienda llamar a esta función antes de realizar operaciones con directorios.
     *
     * @throws errores Si la ruta no es válida o está vacía.
     * @version 1.0.0
     */

    private function valida_folder(string $ruta): bool|array
    {
        $ruta = trim($ruta);
        if($ruta === ''){
            return $this->error->error(mensaje: 'Error la ruta esta vacio', data: $ruta, es_final: true);
        }
        if(!is_dir($ruta)){
            return $this->error->error(mensaje: 'Error la ruta no existe o no es una carpeta', data: $ruta,
                es_final: true);
        }
        return true;
    }


}
