#!/usr/bin/env python3
"""Legacy original en copia descartable, namespace sin red y sin conexión DB."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import xml.etree.ElementTree as ET

raiz = Path(__file__).resolve().parents[2]
deps = Path(os.environ['DEPENDENCIAS_LEGACY']).resolve()
regex = Path(os.environ['CONFIG_REGEX_LEGACY']).resolve()
if hashlib.sha256(regex.read_bytes()).hexdigest() != '06ee3c86d299ad24a7e9e0130cd450cbdbd9a11affce49ca2005a18a29e6d5d9':
    raise RuntimeError('Config regex no acreditada')
if not (deps / 'vendor/autoload.php').is_file():
    raise RuntimeError('Faltan dependencias oficiales; instalarlas sin scripts/plugins')
if hashlib.sha256((deps / 'composer.lock').read_bytes()).digest() != hashlib.sha256((Path(__file__).parent / 'composer.lock').read_bytes()).digest():
    raise RuntimeError('Dependencias distintas del lock de pruebas')
informe = {'estado': 'BLOQUEADO', 'pruebas_esperadas': 33}
with tempfile.TemporaryDirectory(prefix='qa-plugins-legacy-') as temporal:
    copia = Path(temporal)
    for nombre in ['src', 'tests']:
        shutil.copytree(raiz / nombre, copia / nombre)
    (copia / 'config/default').mkdir(parents=True)
    shutil.copy2(regex, copia / 'config/default/regex.json')
    for fuente in (raiz / 'tests').rglob('*'):
        if fuente.is_file():
            assert hashlib.sha256(fuente.read_bytes()).digest() == hashlib.sha256((copia / fuente.relative_to(raiz)).read_bytes()).digest()
    comando = ['bwrap', '--unshare-net', '--ro-bind', '/usr', '/usr', '--ro-bind', '/lib', '/lib',
               '--ro-bind', '/lib64', '/lib64', '--proc', '/proc', '--dev', '/dev', '--tmpfs', '/tmp',
               '--dir', '/var/www/html', '--bind', str(copia), '/var/www/html/plugins',
               '--ro-bind', str(deps), '/deps', '--ro-bind', str(Path(__file__).parent / 'bootstrap.php'), '/bootstrap.php',
               '--chdir', '/var/www/html/plugins', '--', '/usr/bin/php8.3', '-n']
    for extension in ['dom', 'mbstring', 'xml', 'xmlwriter', 'xmlreader', 'zip', 'iconv', 'fileinfo', 'tokenizer', 'ctype', 'simplexml']:
        comando += ['-d', 'extension=' + extension]
    comando += ['-d', 'allow_url_fopen=0', '-d', 'disable_functions=shell_exec,exec,system,passthru,proc_open,popen',
                '/deps/vendor/phpunit/phpunit/phpunit', '--bootstrap', '/bootstrap.php', '--do-not-cache-result',
                '--fail-on-warning', '--fail-on-risky', '--fail-on-skipped', '--fail-on-incomplete',
                '--display-warnings', '--log-junit', '/var/www/html/plugins/resultado.xml', '/var/www/html/plugins/tests/src']
    proceso = subprocess.run(comando, capture_output=True, text=True, timeout=90)
    informe.update(codigo=proceso.returncode, salida=proceso.stdout, errores=proceso.stderr)
    if proceso.returncode == 0 and (copia / 'resultado.xml').exists():
        arbol = ET.parse(copia / 'resultado.xml')
        casos = arbol.findall('.//testcase')
        if len(casos) == 33 and not any(arbol.findall('.//' + n) for n in ['failure', 'error', 'skipped']):
            informe['estado'] = 'PASS_LEGACY_AISLADO'
    print(json.dumps(informe, ensure_ascii=False))
    destino = raiz / '.git/qa/legacy-reproducible.json'
    destino.parent.mkdir(parents=True, exist_ok=True)
    destino.write_text(json.dumps(informe, ensure_ascii=False, indent=2) + '\n')
raise SystemExit(0 if informe['estado'] == 'PASS_LEGACY_AISLADO' else 1)
