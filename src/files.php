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
     * Asigna los datos necesarios para verificar los archivos de un servicio
     * @version 1.0.0
     * @param string $archivo Path o nombre del archivo
     * @return array|stdClass obj->file obj->es_lock obj->es_info obj->es_service
     */
    private function asigna_data_file_service(string $archivo): array|stdClass
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $es_lock = $this->es_lock_service(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al verificar file',data: $es_lock);
        }
        $es_info = $this->es_info_service(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al verificar file',data: $es_info);
        }
        $es_service = $this->es_service(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al verificar file',data: $es_service);
        }

        $name_service = $this->name_service(archivo:$archivo);
        if(errores::$error){
            return $this->error->error(mensaje:  'Error al obtener nombre del servicio',data: $name_service);
        }


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

    /**
     * Determina si el archivo es de tipo info para services
     * @version 1.0.0
     * @param string $archivo Ruta a verificar el tipo
     * @return bool|array
     */
    private function es_info_service(string $archivo): bool|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $extension = $this->extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener extension', data: $extension);
        }
        $es_lock = false;
        if($extension === 'info'){
            $es_lock = true;
        }
        return $es_lock;
    }

    /**
     * Te dice el archivo es un lock del paquete servicios
     * @version 1.0.0
     * @param string $archivo Path o nombre del archivo
     * @return bool|array verdadero si es lock falso si no, array error
     */
    private function es_lock_service(string $archivo): bool|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $extension = $this->extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener extension', data: $extension);
        }
        $es_info = false;
        if($extension === 'lock'){
            $es_info = true;
        }
        return $es_info;
    }

    /**
     * Determina si un file es un service para ejecucion de servicios
     * @version 1.0.0
     * @param string $archivo Ruta a verificar el tipo
     * @return bool|array
     */
    private function es_service(string $archivo): bool|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        $extension = $this->extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener extension', data: $extension);
        }
        $es_service = false;
        if($extension === 'php'){
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
     * Obtiene la extension de un archivo mandando solamente el nombre del doc
     * @param string $archivo Path o nombre del archivo
     * @return string|array string = extension del archivo array error
     * @version 1.0.0
     */
    public function extension(string $archivo): string|array
    {
        $valida = $this->valida_extension(archivo: $archivo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar extension', data: $valida);
        }

        return (new SplFileInfo($archivo))->getExtension();

    }

    /**
     * Ajusta los archivos dentro de la carpeta services para su maquetacion
     * @version 1.0.0
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
     * @version 0.2.0 Se integra UT para monitor de servicios
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

    private function name_service(string $archivo): string
    {
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
     * Verifica si la parte enviada esta vacia o no
     * @version 1.0.0
     * @param string $parte Parte de un name file
     * @return bool
     */
    PUBLIC function parte_to_name_file(string $parte): bool
    {
        $todo_vacio = true;
        $parte = trim($parte);
        if($parte !== ''){
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
     * @version 0.7.0
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
     * Verificar si todas las partes de un name file estan vacias
     * @version 1.0.0
     * @param array $explode conjunto de partes del nombre de un name file separados por .
     * @return bool|array Verdadero si todos los elementos estan vacios
     */
    private function todo_vacio(array $explode): bool|array
    {
        $todo_vacio = true;
        foreach ($explode as $parte){
            $todo_vacio = $this->parte_to_name_file(parte: $parte);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar parte del nombre del file', data: $todo_vacio);
            }
        }
        return $todo_vacio;
    }

    /**
     * Valida los datos de un archivo para obtener una extension
     * @version 1.0.0
     * @param string $archivo Ruta a verificar la extension
     * @return bool|array
     */
    public function valida_extension(string $archivo): bool|array
    {
        $archivo = trim($archivo);
        if($archivo === ''){
            return $this->error->error(mensaje: 'Error archivo no puede venir vacio', data: $archivo);
        }
        $explode = explode('.', $archivo);
        if(count($explode) === 1){
            return $this->error->error(mensaje: 'Error el archivo no tiene extension', data: $explode);
        }
        $todo_vacio = $this->todo_vacio(explode:$explode);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al validar si estan vacios todos los elementos de un name file', data: $todo_vacio);
        }
        if($todo_vacio){
            return $this->error->error(mensaje: 'Error el archivo solo tiene puntos', data: $archivo);
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
