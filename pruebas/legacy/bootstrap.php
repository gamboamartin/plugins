<?php
namespace {
    require '/deps/vendor/autoload.php';
    spl_autoload_register(static function (string $class): void {
        $prefix = 'gamboamartin\\plugins\\';
        if (str_starts_with($class, $prefix)) {
            require '/var/www/html/plugins/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        }
    }, true, true);
}
namespace gamboamartin\test {
    // Adaptador exclusivo de infraestructura: no constructor MySQL, mismas aserciones PHPUnit.
    class test extends \PHPUnit\Framework\TestCase {}
}
namespace config {
    class generales { public string $path_base = '/var/www/html/plugins/'; }
}
