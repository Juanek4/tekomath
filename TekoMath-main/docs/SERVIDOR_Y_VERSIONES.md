# Fila 1: servidor y versiones

Estado: preparación local implementada; contratación y prueba en alojamiento pendientes.
No se cambió XAMPP ni se migraron las bases existentes.

## Requisitos para elegir alojamiento

- PHP 8.4, con el último parche de seguridad disponible. Extensiones PDO MySQL, mbstring, cURL, OpenSSL, fileinfo y session.
- MariaDB 11.4 LTS, con el último parche disponible, y espacio para las dos bases de TekoMath.
- Apache 2.4 con mod_rewrite y AllowOverride que permita las reglas del `.htaccess` existente. Otro servidor requiere traducir y verificar esas reglas antes de exponer el proyecto.
- Acceso SSH o consola para ejecutar PHP CLI con la misma versión y extensiones del proceso web, y configurar variables de entorno o un archivo privado fuera del directorio público.
- Posibilidad de crear un entorno aislado de prueba y consultar errores PHP y Apache. No utilizar datos estudiantiles reales para la primera comprobación.

La elección de estas ramas es un objetivo de compatibilidad, no una afirmación de que otras versiones sean inseguras. PHP 8.4 tiene soporte de seguridad hasta el 31/12/2028; MariaDB 11.4 tiene mantenimiento comunitario hasta el 29/05/2029. Revisar nuevamente las fechas y parches al contratar y actualizar el servidor:

- https://www.php.net/supported-versions.php
- https://mariadb.org/about/

## Comprobación reproducible

Con las variables `TEKO_*` o la configuración privada documentada en `config/runtime.example.php`, ejecutar desde la raíz:

```sh
php scripts/check_hosting.php
```

El resultado es JSON y el código de salida es 0 si todas las comprobaciones pasan, o 2 si hay pendientes. Abre cada base directamente por PDO y ejecuta solamente SELECT VERSION() y SELECT 1. No usa conectores de aplicación, no crea tablas, no prepara esquemas, no activa SQLite y no imprime credenciales. Es una herramienta CLI; no crear un endpoint público para ejecutarla.

Una respuesta correcta confirma únicamente versiones objetivo, extensiones y conectividad. El administrador debe confirmar el último parche disponible y que PHP web coincide con PHP CLI desde el panel del proveedor, sin publicar phpinfo().

Después de preparar las bases de prueba, ejecutar también `php scripts/check_runtime.php` para validar tablas y configuración de aplicación. Ese script existente usa conectores de la aplicación: debe ejecutarse con `TEKO_ENV=production` para evitar la preparación automática local de esquemas.

## Criterio para cerrar esta fila

1. Registrar proveedor, plan, versiones completas y fecha de instalación del último parche.
2. Ejecutar el comprobador en el alojamiento y conservar el resultado fuera del directorio público.
3. En un entorno aislado con ambas bases de prueba preparadas, verificar inicio y cierre de sesión, dashboard de alumno y docente, guardado y lectura de conversación, y persistencia de progreso de Arcade. Revisar errores PHP, incluidas advertencias de compatibilidad con PHP 8.4.
4. Registrar resultados, versiones del proceso web y errores corregidos. Hasta entonces la fila sigue pendiente.

El dominio, HTTPS y las migraciones definitivas se abordarán en sus respectivas filas.
