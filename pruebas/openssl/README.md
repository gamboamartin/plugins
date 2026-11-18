## Estado para publicación, 18 de noviembre de 2026

Este documento conserva cronológicamente los diagnósticos anteriores; las menciones a pruebas pendientes corresponden a esas etapas, no al cierre descrito aquí.

Candidato: conversión PHP OpenSSL y guardia de celdas vacías del importador. Batería exigida antes de publicar: 36 casos criptográficos, 6 contratos de consumidor/error y 33 pruebas legacy aisladas. Las dos expectativas de orden de directorio fueron ajustadas con autorización, conservando el conjunto completo y una copia histórica recuperable. El lock del arnés legacy se versiona para reproducibilidad.

También se acreditaron 36 casos en FPM aislado del servidor (PHP 8.3.36/OpenSSL 3.0.2), y el consumidor Facturación se probó con documentos enteramente ficticios. La compensación documental y validación de pareja en el alta corresponden a cambios separados de Facturación y Documento; este paquete no promete por sí solo recuperación SQL/archivos ni autorización para transmitir claves al PAC. No se incluyen certificados, claves, credenciales ni configuración operativa en la publicación.

## Histórico conservado

# Conversión OpenSSL PHP: candidato aislado

## Procedencia y alcance

Paquete propietario `gamboa.martin/plugins`, repositorio `gamboamartin/plugins`, base main `d67d677b1b42374b887fd0a9d59e1261e897ba04`, equivalente al paquete v6.22.10 instalado en FC3. La copia fue creada sin alterar checkouts existentes. Cambios: `src/ssl.php`, declaración `ext-openssl` en Composer y este conjunto de pruebas/documentación. Sin commit, tag, push, despliegue ni modificación de configuración PAC/FPM/SQL.

La necesidad es convertir CSD sin habilitar `shell_exec`, actualmente deshabilitada en FPM de FC3. La solución usa la extensión OpenSSL PHP, no procesos externos ni contraseñas en comandos. Los certificados de prueba son RSA de 2048 bits, autofirmados y enteramente ficticios. No certifican compatibilidad universal con SAT, políticas fiscales, vigencia real, revocación ni funcionamiento del PAC.

## API y contratos preservados

- `genera_cer_pem(ruta_in, ruta_out)` y `genera_key_pem(pass, ruta_in, ruta_out)` conservan firmas y retorno **null en éxito**; errores estructurados mediante `errores::error`.
- El comando histórico `openssl x509 -pubkey -out` produce **PUBLIC KEY + CERTIFICATE**, no únicamente el certificado. Se caracterizó con un certificado ficticio y se conserva la concatenación exacta. La referencia CLI no forma parte del runner ni se ejecuta sobre claves reales.
- La clave exportada queda en PEM **sin cifrar**, como el comando previo. Debe permanecer privada. La contraseña sigue recortándose con `trim`, comportamiento histórico; no se afirma soporte para una contraseña significativa con espacios exteriores.
- `valida_par_cer_key` es una comprobación explícita y separada. No se añade implícitamente a métodos que antes solo convertían, ni se cambia el flujo de negocio FC3.
- Entradas soportadas por el candidato: certificado DER o PEM, incluyendo el PEM compuesto histórico; clave DER PKCS#8 cifrada, PEM PKCS#8 cifrado o PEM sin cifrar de los tipos declarados. PEM tradicional cifrado con cabeceras `Proc-Type`/`DEK-Info` no está soportado por este parser. No se generaliza el resultado RSA a todos los algoritmos de CSD reales.

## Seguridad y límites

Se rechazan wrappers/URLs, NUL, archivos vacíos o superiores a 2 MiB, enlaces simbólicos de entrada/salida y de sus componentes padre, salida igual a entrada, directorios como salida y PEM con bloques ajenos o múltiples bloques ambiguos. La envoltura DER exterior debe ser SEQUENCE con longitud definida, mínima y exacta, sin datos anexos; OpenSSL sigue validando el contenido criptográfico. No es un parser ASN.1 completo ni una validación fiscal.

La escritura usa temporal privado en el mismo directorio y renombrado atómico. Si conversión/validación falla, una salida anterior permanece intacta; el temporal se retira. Una conversión **exitosa sí reemplaza** el archivo de salida y no conserva automáticamente una versión anterior. El nuevo PEM conserva modo 0600 de `tempnam`; no se amplían permisos de directorios ni archivos reales durante pruebas.

La comprobación de enlaces no elimina todas las carreras TOCTOU: los directorios y escritores del almacén deben estar controlados. No se afirma protección frente a un administrador concurrente malicioso.

Los errores del conversor no llevan contraseña, contenido, rutas ni la cola OpenSSL. Se contrastó el manejador real: usa `debug_backtrace(2)` (`DEBUG_BACKTRACE_IGNORE_ARGS`), y se pasa `data=[]`, dejando `params=[]`. El ensayo adicional verificó retorno, estado de sesión y salida del error real sin claves/contraseña ficticias. No se garantiza que todos los demás caminos de la aplicación nunca registren secretos.

## Ejecución reproducible

Requisitos: PHP 8.3 con OpenSSL y un checkout con `.git` escribible para evidencias y temporales privados. No requiere Composer install, PHPUnit, red, un PAC, base de datos ni archivos criptográficos reales.

```sh
# Fuentes de código reales, no archivos de configuración ni credenciales:
export FUENTE_ERRORES=/ruta/errores/src/errores.php
export FUENTE_CERT_FC3=/ruta/copia-facturacion/orm/_cert.php
sh pruebas/openssl/ejecutar.sh
```

El runner crea `.git/qa/tmp`, ejecuta PHP con `-n`, wrappers URL deshabilitados y funciones de shell/red deshabilitadas. No lanza OpenSSL CLI desde PHP. Si no se suministran fuentes del contrato real, ejecuta conversión pero termina con código 2 y marca pendiente esa segunda capa, no un PASS completo.

Fuentes exactas verificadas para el contrato:

- `errores.php`: SHA256 `4e307a08c4bea98be8a52a3789cb8f9a18e76c725b4112fc07ba84b2aa4911ad`.
- `orm/_cert.php`: SHA256 `f0103eeccced2d7bae7ba65812e233e6f154fbf4de9b0bd9ee84a0da085c4fea` (copia y FC3 iguales).

No se ejecutan constructores PAC ni autoload de aplicación. El contrato real usa `_cert::genera_pem` por reflexión y modelos/configuración ficticios, sin SQL. Los fixtures criptográficos se generan y eliminan en la misma ejecución, también al fallar. Las evidencias JSON no contienen claves ni contraseñas.

## Registro y resultados

`registro.json` enumera los **36 casos** de conversión/seguridad y el arnés exige coincidencia exacta. `contrato_fc3.php` ejecuta otros **6 casos** del consumidor y error reales con colaboradores ficticios.

Resultado sobre el candidato final: **36/36 PASS + 6/6 PASS, limpieza verificada**. PHP 8.3.17 y OpenSSL 3.0.13 en Desarrollo. El FPM consultado informa OpenSSL 3.0.2; no se ejecutó aún este candidato en él. Las diferencias de biblioteca deben considerarse antes de desplegar.

Primer fallo conservado: OpenSSL aceptaba un certificado DER válido con `BASURA` anexada. Se informó el fallo y, tras autorización, se añadió validación exacta de la envoltura DER; **no se cambió la expectativa**. La evidencia original bloqueada se conserva en `.git/qa/resultado-der-anexado-bloqueado.json`. Resultado último en `resultado-openssl.json`; contrato en `resultado-contrato-fc3.json`, dentro de `.git/qa`.

## Legacy y separación de capas

`inventario-legacy.json` conserva hashes de los nueve archivos previos de `tests/`, sin modificarlos ni retirarlos. Las suites legacy dependen de rutas absolutas (`/var/www/html/plugins`), configuración externa, orden de `opendir` y un fixture ODS. **No se ejecutaron ciegamente ni se ajustaron sus expectativas.** La nueva suite no sustituye esa limitación: antes de publicar debe resolverse su ejecución equivalente o discutirse el alcance con Martín. No se declara batería completa del paquete aprobada.

La regresión general de Facturación (155 casos y 21 frontend) es otra capa. No sustituye el contrato de conversión ni demuestra una alta CSD de extremo a extremo. Las pruebas por modelo no ejercitan almacenamiento documental, asociación en DB, UI de carga o rollback fiscal.

## Ciclo de vida FC3 pendiente

`controlador_fc_csd::alta_bd` convierte antes de commit y utiliza rollback SQL ante error. La lectura de ese método no acredita que ese rollback retire todos los CER/KEY/PEM ya copiados a almacenamiento: los archivos no participan en una transacción SQL. Esta corrección se limita al conversor y su temporal; **no promete limpieza integral de una alta FC3 fallida**. Será necesario un ensayo sintético separado del flujo completo, con inventario de archivos y DB, respaldo y limpieza, antes de cargar CSD reales.

No se debe activar un PAC productivo ni transmitir claves privadas como parte de estas pruebas. Factúralo y el emisor confirmados por el usuario no constituyen autorización para enviar KEY/PEM al PAC.

Comprobación complementaria final: regresión **base** de Facturación 155 casos / 539 aserciones PASS y 21 frontend PASS. No se inyectó la dependencia candidata en esa batería; la cobertura de enlace candidato–consumidor es exclusivamente el contrato real de seis casos descrito arriba. No equiparar ambos resultados con una alta CSD UI/DB de extremo a extremo.

Los certificados públicos sintéticos usados para caracterizar el comando histórico también se retiraron; quedan únicamente hashes y el resultado de comparación. No quedan subdirectorios de fixtures en `.git/qa/tmp`. El checkout contenedor tiene modo 0700, y cada directorio criptográfico temporal se creó 0700 antes de su eliminación.

## Compatibilidad aislada en servidor FC3

El 18 de noviembre de 2026 se ejecutaron los 36 casos sintéticos con el mismo
SHA-256 del candidato `193162b30f2ff2d0d3cbf8ed77e2cfda8aae1b8eaea2e24331c12afdabdb1bfd`
en PHP CLI 8.3.36 / OpenSSL 3.0.2: **36 PASS**. Las funciones de red y ejecución
de procesos se deshabilitaron para ese proceso; no se usaron DB, PAC ni llaves
reales. Se verificó la limpieza de fixtures y el retiro del directorio temporal.
Esto acredita compatibilidad CLI con la biblioteca del servidor, no una petición
FPM ni el flujo integral de alta CSD, conversión y compensación de archivos.
El recibo local de resultados se conserva fuera del conjunto publicado.

## Investigación de batería legacy

Se inventariaron 33 métodos originales: files (24), Importador (5), datos (2) y
patterns (2), sin modificar sus bytes ni expectativas. Se instalaron dependencias
oficiales en una copia privada, sin scripts ni plugins Composer. Para evitar la
conexión MySQL del constructor de la clase base histórica se preparó un adaptador
exclusivamente de infraestructura hacia PHPUnit TestCase, conservando liberator,
validacion, errores, Importador y files reales. La ejecución se realiza dentro de
un namespace sin red y sin montar configuración de aplicación ni bases reales.

La primera ejecución efectiva se detuvo en ImportadorTest línea 32 (1 prueba,
2 aserciones, 1 fallo). El diagnóstico separado acreditó falta de SimpleXML en el
perfil `php -n`: `simplexml_load_string()` no disponible. No se modificaron
expectativas ni se presenta la batería legacy como aprobada. Los intentos previos
fallaron antes de ejecutar pruebas por resolución del binario PHP y por falta de
extensiones tokenizer/ctype. Sus salidas se conservan separadamente.

Actualización del diagnóstico legacy: tras cargar SimpleXML y montar el archivo
regex versionado del consumidor (SHA-256
`06ee3c86d299ad24a7e9e0130cd450cbdbd9a11affce49ca2005a18a29e6d5d9`),
la ejecución se detuvo en **10 pruebas / 77 aserciones, 1 fallo y 2 warnings**.
`filesTest::test_asigna_archivos` espera literalmente `src` en la posición 1 de
`readdir`; obtuvo `config`. No se reordenó el directorio para forzar aprobación.
Los warnings de `ImportadorTest::test_data_xls` provienen de la lectura de
`$rows[$i][$j]` con un array vacío en Importador.php:32. Los 23 casos restantes
no se ejecutaron en esta pasada. La batería legacy continúa bloqueada y no se
ha autorizado publicación del candidato.

## Caracterización focal del controlador CSD

`controlador/escenario.php` ejecuta el método real `controlador_fc_csd::alta_bd`
y sus métodos privados de inserción, omitiendo el constructor de UI. La fuente
real se verifica por SHA-256. Son dobles el padre de persistencia, PDO transaccional,
los modelos de documentos/CSD y el almacén; la conversión usa el candidato real.
El doble de CSD respeta el orden acreditado del modelo: KEY antes de CER.
No equivale a una alta integral con ORM, DB ni almacén real.

Tres escenarios reproducidos:
- Éxito: begin/commit; tres filas simuladas y cuatro archivos.
- Contraseña incorrecta: begin/rollback; cero filas, permanecen los dos archivos
  de entrada en el almacén simulado.
- CER inválido después de KEY correcto: begin/rollback; cero filas, permanecen
  las dos entradas y `key.pem`.

PASS significa que se caracterizó ese comportamiento residual, **no** que la
recuperación de archivos o la seguridad global esté aprobada. El arnés retiró sus
archivos propios y comprobó limpieza. El chequeo de secretos se limita a la
salida capturada: no afirma ausencia en POST (contiene la contraseña por diseño)
ni garantiza el comportamiento del modelo real reemplazado.

Para repetir cada escenario, definir `FUENTE_CONTROLADOR_CSD` y `FUENTE_ERRORES`
con las fuentes reales indicadas por sus hashes en el arnés y ejecutar:

```sh
for caso in exito error_key error_cer; do
  php -n -d extension=pdo -d allow_url_fopen=0 \
    -d disable_functions=shell_exec,exec,system,passthru,proc_open,popen,fsockopen,stream_socket_client \
    pruebas/openssl/controlador/escenario.php "$caso" || exit
 done
```


## Cierre de legacy después de decisiones aprobadas

Martín aprobó validar conjuntos completos sin depender de posiciones de `readdir`
para las dos aserciones afectadas. Se preservó el archivo original íntegro en
`historico/filesTest-antes-orden.php.txt` (SHA-256
`326d286f40df3f0f6ab78e71749faf249c39d7c372e86a12fc5aebd8b14206a5`).
El fallo de `src` como no-directorio correspondía al cwd del arnés; desde la
raíz se acreditó la semántica sin modificar producción. La guardia autorizada
`$rows[$i][$j] ?? null` conserva el valor previo en celdas ausentes y evita los
dos warnings de Importador, sin modificar expectativas.

Runner reproducible: `pruebas/legacy/ejecutar.py`, con lock de dependencias
oficiales y bootstrap explícito sin constructor DB. Requiere
`DEPENDENCIAS_LEGACY` (directorio de instalación del lock de pruebas) y
`CONFIG_REGEX_LEGACY` (archivo versionado consumidor con hash comprobado).
Instalar dependencias con `composer install --no-scripts --no-plugins` en copia
privada; no reutilizar configuración activa. El runner monta una copia
regenerada, namespace sin red, PHP sin PDO, y exige 33 casos JUnit sin fallos,
warnings, omisiones o riesgos. Resultado: 33 pruebas aprobadas; la cantidad de
aserciones de enumeración puede crecer por el archivo JUnit del propio ensayo,
pero se comprueba siempre el conjunto completo sin omitir elementos.

La batería documental dependiente y alta CSD integral tienen estado separado.
No se ha realizado commit, push ni despliegue de esta corrección.
