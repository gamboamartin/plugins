#!/usr/bin/env python3
"""36 casos SSL originales bajo FPM efímero, sin DB, bwrap ni servicio del host."""
import hashlib,json,os,pathlib,resource,signal,socket,subprocess,sys,tempfile,time,re
P=pathlib.Path
FILES=['src/ssl.php','pruebas/openssl/probar.php','pruebas/openssl/doble_errores.php','pruebas/openssl/registro.json']

def rutas_seguras(ipv4,ipv6):
    v4=[line.split() for line in ipv4.splitlines() if line.strip()]
    if v4 and v4[0][0]=='Iface':v4=v4[1:]
    if v4:return False
    for line in ipv6.splitlines():
        if not line.strip():continue
        campos=line.split()
        if len(campos)!=10:return False
        # Entradas de descarte instaladas por kernel, sin RTF_UP ni salida física.
        if not (campos[0]==campos[2]==campos[4]=='0'*32 and campos[1]==campos[3]=='00'
            and campos[5]=='ffffffff' and campos[8]=='00200200' and campos[9]=='lo'):
            return False
    return True

FALLOS_PROPIOS = ['Advertencia PHP no esperada en el arnes', 'CER PEM aceptado', 'Compuesto exacto', 'Compuesto historico aceptado', 'Contenido historico exacto', 'Convertir no impone pareja', 'Crear certificado ficticio', 'Crear espacio sintetico', 'Destino enlace intacto', 'El limite exacto llega al parser', 'El runner exige funciones externas deshabilitadas', 'Entrada intacta', 'Error estructurado y bandera', 'Error sin contenido ni parametros', 'Error sin secretos', 'Exportar fixture PEM sin cifrar', 'Exportar fixture cifrado', 'Exportar segunda clave ficticia', 'Generar claves ficticias', 'KEY DER convertida', 'KEY PEM cifrada convertida', 'KEY PEM plana convertida', 'Limite antes de interpretar', 'Misma clave publica', 'No destruir salida anterior', 'No escribir por enlace de directorio', 'No quedan temporales del lector', 'PEM exacto', 'Par DER coincide', 'Par PEM coincide', 'Preparar directorio temporal privado del arnes', 'Registro y orden de casos exactos', 'Retorno historico null', 'SALIDA ANTERIOR FICTICIA', 'Salida privada', 'Salida sin cifrar compatible', 'Trim historico']

def diagnosticar_respuesta(body, expected):
    """Extrae únicamente metadatos permitidos; nunca concede PASS."""
    diagnostico={'estado':'DIAGNOSTICO','lineas':body.count(b'\n')}
    try:
        ultimo=body.decode('utf-8',errors='strict').splitlines()[-1]
        valor=json.loads(ultimo)
        if not isinstance(valor,dict):return diagnostico
        fallo=valor.get('fallo')
        diagnostico['fallo']=fallo if isinstance(fallo,str) and fallo in FALLOS_PROPIOS else 'NO_LITERAL_PERMITIDO'
        casos=valor.get('casos')
        if isinstance(casos,list) and all(isinstance(c,str) and c in expected for c in casos):
            diagnostico['casos_reconocidos']=casos
        version=valor.get('php')
        if isinstance(version,str) and re.fullmatch(r'[0-9]+\.[0-9]+\.[0-9]+',version):diagnostico['php']=version
        huella=valor.get('sha256_fuente')
        if isinstance(huella,str) and re.fullmatch(r'[a-f0-9]{64}',huella):diagnostico['sha256_fuente']=huella
        if isinstance(valor.get('limpieza'),bool):diagnostico['limpieza']=valor['limpieza']
    except (ValueError,IndexError,UnicodeError):pass
    return diagnostico

def interpretar_respuesta(body, expected):
    """Contrato textual completo del arnés, sin descartar líneas inesperadas."""
    assert len(expected)==36, 'Registro distinto de 36 casos'
    texto=body.decode('utf-8',errors='strict')
    assert texto.endswith('\n'), 'Falta terminador del informe'
    lineas=texto[:-1].split('\n')
    assert len(lineas)==37, 'Número inesperado de líneas'
    assert lineas[:-1]==['PASS '+caso for caso in expected], 'Líneas PASS distintas del registro'
    def sin_duplicados(pares):
        resultado={}
        for clave,valor in pares:
            assert clave not in resultado, 'Clave JSON duplicada'
            resultado[clave]=valor
        return resultado
    result=json.loads(lineas[-1],object_pairs_hook=sin_duplicados)
    assert isinstance(result,dict), 'Informe no es objeto'
    assert set(result)=={'estado','casos','fallo','limpieza','php','openssl','sha256_fuente'}, 'Campos de informe inesperados'
    assert result['estado']=='PASS' and result['casos']==expected, 'Informe no coincide con casos'
    assert result['fallo'] is None and result['limpieza'] is True, 'Fallo o limpieza incompleta'
    assert all(isinstance(result[k],str) and result[k] for k in ['php','openssl','sha256_fuente']), 'Metadatos inválidos'
    return result

def red_aislada():
    v6=P('/proc/net/ipv6_route')
    assert rutas_seguras(P('/proc/net/route').read_text(),v6.read_text() if v6.exists() else ''),'Ruta de red no permitida'


def run(args):
    subprocess.run(args,check=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=10)

def limits():
    resource.setrlimit(resource.RLIMIT_AS,(1024**3,1024**3))
    resource.setrlimit(resource.RLIMIT_CPU,(80,80))
    resource.setrlimit(resource.RLIMIT_NOFILE,(256,256))
    resource.setrlimit(resource.RLIMIT_CORE,(0,0))
    os.sched_setaffinity(0,sorted(os.sched_getaffinity(0))[:2])

def bind(source,target):
    target.parent.mkdir(parents=True,exist_ok=True)
    if source.is_dir(): target.mkdir(exist_ok=True)
    else: target.touch()
    run(['/usr/bin/mount','--bind',str(source),str(target)])
    run(['/usr/bin/mount','-o','remount,bind,ro',str(target)])

def mount_stage(root,repo,script):
    assert os.readlink('/proc/self/ns/net')!=os.environ['QA_OUTER_NET']
    red_aislada()
    run(['/usr/bin/mount','-t','tmpfs','-o','size=64m,mode=700','tmpfs',str(root)])
    bind(P('/usr'),root/'usr')
    for name,target in [('lib','usr/lib'),('lib64','usr/lib64'),('bin','usr/bin'),('sbin','usr/sbin')]:
        (root/name).symlink_to(target)
    for d in ['proc','tmp','run','etc','dev','qa','app/.git/qa','app/pruebas/openssl','app/src']:(root/d).mkdir(parents=True,exist_ok=True)
    for d in ['tmp','run','app/.git/qa']:run(['/usr/bin/mount','-t','tmpfs','-o','size=32m,mode=700','tmpfs',str(root/d)])
    run(['/usr/bin/mount','-t','proc','proc',str(root/'proc')])
    for dev in ['null','urandom']:bind(P('/dev')/dev,root/'dev'/dev)
    for f in FILES:bind(repo/f,root/'app'/f)
    bind(script,root/'qa/runner.py')
    (root/'app/.git/qa/tmp').mkdir(mode=0o700)
    (root/'qa/prepend.php').write_text("<?php if(PHP_SAPI!=='fpm-fcgi'){exit(2);} header('Content-Type: application/json'); header('X-QA-SAPI: '.PHP_SAPI);\n")
    (root/'qa/openssl.cnf').write_text('[ req ]\ndistinguished_name = dn\nprompt = no\n[ dn ]\nCN = QA FICTICIO SIN VALIDEZ\n')
    (root/'qa/php.ini').write_text('''[PHP]
expose_php=Off
memory_limit=128M
max_execution_time=35
error_reporting=E_ALL
display_errors=Off
log_errors=On
error_log=/tmp/php-error.log
allow_url_fopen=Off
allow_url_include=Off
auto_prepend_file=/qa/prepend.php
disable_functions=shell_exec,exec,system,passthru,proc_open,popen,pcntl_exec,fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec,socket_create,socket_connect,ftp_connect
''')
    (root/'qa/fpm.conf').write_text('''[global]
pid=/run/qa.pid
error_log=/tmp/fpm.log
daemonize=no
[qa]
listen=/run/qa.sock
listen.mode=0600
pm=static
pm.max_children=1
pm.max_requests=2
request_terminate_timeout=40s
clear_env=yes
chdir=/app
security.limit_extensions=.php
''')
    run(['/usr/bin/mount','-n','-i','-t','tmpfs','-o',
         'remount,ro,relatime,size=64m,mode=700','tmpfs',str(root)])
    mounts=[line.split() for line in P('/proc/self/mountinfo').read_text().splitlines()
            if line.split()[4]==str(root)]
    assert len(mounts)==1 and 'ro' in mounts[0][5].split(','),'Rootfs no es readonly'
    separator=mounts[0].index('-')
    assert 'ro' in mounts[0][separator+3].split(','),'Superbloque no es readonly'
    try:
        (root/'escritura_prohibida').write_text('ficticio')
    except OSError as error:
        assert error.errno==30,'Escritura bloqueada por causa distinta de EROFS'
    else:
        raise AssertionError('Rootfs permitió escritura')
    os.chroot(root);os.chdir('/')
    os.execve('/usr/bin/setpriv',['setpriv','--bounding-set=-all','--inh-caps=-all','--ambient-caps=-all','--no-new-privs','/usr/bin/python3','-B','/qa/runner.py','--fpm'],{'PATH':'/usr/bin:/usr/sbin','HOME':'/tmp','PHP_INI_SCAN_DIR':'','OPENSSL_CONF':'/qa/openssl.cnf','QA_OUTER_NET':os.environ['QA_OUTER_NET']})

def fpm_stage():
    report={'estado':'BLOQUEADO','alcance':'SSL real y 36 casos originales con doble de errores, FPM sin DB; no alta CSD ni UI'}
    fpm=None
    identity=None
    workers=[]
    try:
        assert os.getuid()==65534 and os.getgid()==65534
        assert 'CapEff:\t0000000000000000' in P('/proc/self/status').read_text()
        assert not P('/home').exists() and not P('/etc/mysql').exists()
        assert os.readlink('/proc/self/ns/net')!=os.environ['QA_OUTER_NET']
        red_aislada()
        report['netns']=os.readlink('/proc/self/ns/net')
        fpm=subprocess.Popen(['/usr/sbin/php-fpm8.3','--nodaemonize','--fpm-config','/qa/fpm.conf','--php-ini','/qa/php.ini'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
        deadline=time.monotonic()+10
        while not P('/run/qa.sock').is_socket():
            assert fpm.poll() is None,'FPM terminó al iniciar'
            assert time.monotonic()<deadline,'FPM no creó socket'
            time.sleep(.1)
        assert P('/run/qa.pid').read_text().strip()==str(fpm.pid)
        identity=P('/proc/'+str(fpm.pid)+'/stat').read_text().split(') ',1)[1].split()[19]
        workers=P('/proc/'+str(fpm.pid)+'/task/'+str(fpm.pid)+'/children').read_text().split()
        env={'PATH':'/usr/bin','SCRIPT_FILENAME':'/app/pruebas/openssl/probar.php','SCRIPT_NAME':'/probar.php','REQUEST_METHOD':'GET','REQUEST_URI':'/probar.php','SERVER_PROTOCOL':'HTTP/1.1','GATEWAY_INTERFACE':'CGI/1.1','SERVER_NAME':'qa.invalid','SERVER_PORT':'80','REDIRECT_STATUS':'200','CONTENT_LENGTH':'0'}
        response=subprocess.run(['/usr/bin/cgi-fcgi','-bind','-connect','/run/qa.sock'],env=env,capture_output=True,timeout=45)
        assert response.returncode==0 and not response.stderr,'Cliente FCGI falló'
        headers,body=response.stdout.split(b'\r\n\r\n',1)
        report['respuesta_bytes']=len(body)
        report['respuesta_sha256']=hashlib.sha256(body).hexdigest()
        statuses=[line.split(b':',1)[1].strip().split(b' ',1)[0] for line in headers.split(b'\r\n') if line.lower().startswith(b'status:')]
        assert len(statuses)<=1 and all(x.isdigit() and len(x)==3 for x in statuses),'Status FCGI inválido'
        report['status_http']=int(statuses[0]) if statuses else 200
        assert report['status_http']==200,'Status FCGI no exitoso'
        assert b'X-QA-SAPI: fpm-fcgi' in headers,'SAPI no acreditado'
        expected=json.loads(P('/app/pruebas/openssl/registro.json').read_text())['casos']
        report['diagnostico_respuesta']=diagnosticar_respuesta(body,expected)
        result=interpretar_respuesta(body,expected)
        assert result['estado']=='PASS' and result['casos']==expected and len(expected)==36 and result['limpieza'] is True,'36 casos no aprobados'
        assert result['sha256_fuente']==hashlib.sha256(P('/app/src/ssl.php').read_bytes()).hexdigest()
        assert list(P('/app/.git/qa/tmp').iterdir())==[],'Residuos criptográficos'
        report.update(estado='PASS',casos=36,php=result['php'],openssl=result['openssl'],sha256_fuente=result['sha256_fuente'],limpieza_fixtures=True)
    except Exception as e:report['bloqueo']=str(e) if isinstance(e,AssertionError) else type(e).__name__
    finally:
        if fpm is not None:
            try:
                if fpm.poll() is None:
                    assert P('/run/qa.pid').read_text().strip()==str(fpm.pid)
                    assert identity is not None and P('/proc/'+str(fpm.pid)+'/stat').read_text().split(') ',1)[1].split()[19]==identity
                    os.kill(fpm.pid,signal.SIGQUIT);fpm.wait(timeout=10)
                report['fpm_exit']=fpm.returncode
                report['workers_restantes']=[x for x in workers if P('/proc/'+x).exists()]
                report['socket_retirado']=not P('/run/qa.sock').exists()
                assert fpm.returncode==0 and report['workers_restantes']==[] and report['socket_retirado']
            except Exception as e:report.update(estado='BLOQUEADO',cleanup_error=type(e).__name__)
        if P('/tmp/php-error.log').exists():
            log=P('/tmp/php-error.log').read_bytes()
            report['php_log_no_vacio']=bool(log)
            report['php_log_bytes']=len(log)
            report['php_log_sha256']=hashlib.sha256(log).hexdigest()
            # Solo ubicación del código montado; nunca mensajes, argumentos ni traza cruda.
            report['php_ubicaciones']=sorted(set(re.findall(
                r'in (/app/(?:src/ssl\.php|pruebas/openssl/(?:probar|doble_errores)\.php)) on line ([0-9]+)',
                log.decode('utf-8',errors='replace'))))
            if report['php_log_no_vacio']:report.update(estado='BLOQUEADO',bloqueo='Log PHP inesperado')
        print(json.dumps(report));return 0 if report['estado']=='PASS' else 1

if len(sys.argv)>1 and sys.argv[1]=='--mount':
    mount_stage(P(sys.argv[2]),P(sys.argv[3]),P(sys.argv[4]))
elif len(sys.argv)>1 and sys.argv[1]=='--fpm':sys.exit(fpm_stage())
else:
    script=P(__file__).resolve();repo=script.parents[2]
    hashes={f:hashlib.sha256((repo/f).read_bytes()).hexdigest() for f in FILES}
    root=P(tempfile.mkdtemp(prefix='qa-fpm-ssl-'))
    command=['/usr/bin/timeout','--kill-after=5s','100s','/usr/bin/unshare','--user','--map-user=65534','--map-group=65534','--keep-caps','--net','--mount','--pid','--fork','--kill-child','--propagation','private','/usr/bin/python3','-B',str(script),'--mount',str(root),str(repo),str(script)]
    env={'PATH':'/usr/bin:/usr/sbin','HOME':'/tmp','QA_OUTER_NET':os.readlink('/proc/self/ns/net')}
    result=subprocess.run(command,env=env,capture_output=True,text=True,preexec_fn=limits)
    print(result.stdout);print(result.stderr,file=sys.stderr)
    root.rmdir()
    unchanged=hashes=={f:hashlib.sha256((repo/f).read_bytes()).hexdigest() for f in FILES}
    print(json.dumps({'launcher_exit':result.returncode,'rootfs_host_retirado':not root.exists(),'fuentes_inalteradas':unchanged,'sha256_fuentes':hashes}))
    sys.exit(result.returncode if unchanged else 2)
