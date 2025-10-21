# Sistema ERP Multi-tenant - Pro2

Sistema de planificación de recursos empresariales (ERP) multi-tenant desarrollado con Laravel 5.8, diseñado para gestionar múltiples empresas desde una única instalación.

## Descripción

Pro2 es un sistema ERP completo que incluye:

- Gestión de facturación electrónica
- Administración de inventarios
- Control de ventas y compras
- Gestión de clientes y proveedores
- Reportes y análisis
- Integración con DIAN Colombia
- Soporte para múltiples empresas (multi-tenant)
- Facturación del sector salud (RIPS)

## Tecnologías

- **Framework**: Laravel 5.8
- **Base de datos**: MariaDB 10.5.6
- **Servidor web**: Nginx
- **PHP**: 7.2.18 (PHP-FPM)
- **Multi-tenancy**: hyn/multi-tenant
- **Contenedorización**: Docker + Docker Compose

## Estructura del Proyecto

```
pro2/
├── app/                    # Lógica de aplicación
│   ├── Console/           # Comandos Artisan
│   ├── Exceptions/        # Manejo de excepciones
│   ├── Http/              # Controllers, Middleware, Requests
│   └── Providers/         # Service Providers
├── bootstrap/             # Archivos de arranque
├── config/                # Archivos de configuración
├── database/              # Migraciones y seeders
│   ├── migrations/
│   └── seeders/
├── modules/               # Módulos del sistema
│   ├── Factcolombia1/    # Facturación electrónica
│   └── [otros módulos]
├── public/                # Archivos públicos
├── resources/             # Vistas, assets
│   ├── views/
│   └── templates/
├── routes/                # Definición de rutas
├── storage/               # Archivos generados
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/                 # Tests automatizados
├── vendor/                # Dependencias de Composer
├── .env                   # Variables de entorno
├── artisan                # CLI de Laravel
├── composer.json          # Dependencias PHP
└── docker-compose.yml     # Configuración Docker
```

## Requisitos del Sistema

- Docker 20.10+
- Docker Compose 1.29+
- Mínimo 4GB RAM
- 20GB espacio en disco
- Puertos disponibles: 80, 443, 8080, 3306

## Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/fullsyssantamarta/erp_imperium.git
cd erp_imperium
```

### 2. Configurar Variables de Entorno

```bash
cp .env.example .env
nano .env
```

Variables principales:
```env
APP_NAME="Pro2 ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=pro2
DB_USERNAME=root
DB_PASSWORD=tu_password_seguro

TENANCY_DATABASE=tenancy
```

### 3. Iniciar Contenedores Docker

```bash
docker-compose up -d
```

### 4. Instalar Dependencias

```bash
docker exec fpm_app composer install
```

### 5. Generar Clave de Aplicación

```bash
docker exec fpm_app php artisan key:generate
```

### 6. Ejecutar Migraciones

```bash
docker exec fpm_app php artisan migrate
```

### 7. Configurar Permisos

```bash
docker exec fpm_app chown -R www-data:www-data storage bootstrap/cache
docker exec fpm_app chmod -R 775 storage bootstrap/cache
```

## Uso

### Acceso al Sistema

```
URL: https://tu-dominio.com
Usuario administrador: Se configura durante la instalación
```

### Comandos Artisan Útiles

```bash
# Limpiar caché
docker exec fpm_app php artisan cache:clear
docker exec fpm_app php artisan config:clear
docker exec fpm_app php artisan view:clear

# Listar rutas
docker exec fpm_app php artisan route:list

# Ejecutar seeders
docker exec fpm_app php artisan db:seed

# Ver logs en tiempo real
docker exec fpm_app tail -f storage/logs/laravel.log
```

### Gestión de Tenants

```bash
# Crear nuevo tenant
docker exec fpm_app php artisan tenancy:create nombre_empresa dominio.com

# Listar tenants
docker exec fpm_app php artisan tenancy:list

# Migrar tenant específico
docker exec fpm_app php artisan tenancy:migrate --tenant=dominio.com
```

## Configuración de Facturación Electrónica

### Integración con APIDIAN

El sistema se integra con la API DIAN para facturación electrónica:

```env
APIDIAN_URL=https://api.dominio.com
APIDIAN_TOKEN=tu_token_api
```

### Configuración RIPS (Sector Salud)

Para habilitar facturación del sector salud:

```bash
docker exec fpm_app php artisan migrate --path=/database/migrations/health
```

## Mantenimiento

### Backup

```bash
# Ejecutar backup completo
/root/backup/backup_imperium.sh

# Configurar backups automáticos
/root/backup/setup_automatic_backups.sh
```

### Actualización

```bash
# Detener servicios
docker-compose down

# Actualizar código
git pull origin master

# Actualizar dependencias
docker-compose up -d
docker exec fpm_app composer install

# Ejecutar migraciones
docker exec fpm_app php artisan migrate

# Limpiar caché
docker exec fpm_app php artisan cache:clear
```

### Logs

```bash
# Logs de Laravel
docker exec fpm_app tail -f storage/logs/laravel.log

# Logs de Docker
docker-compose logs -f fpm_app

# Logs de Nginx
docker-compose logs -f nginx_app
```

## Solución de Problemas

### Error 500 Internal Server Error

```bash
# Verificar permisos
docker exec fpm_app chown -R www-data:www-data storage bootstrap/cache

# Verificar logs
docker exec fpm_app cat storage/logs/laravel.log
```

### Problemas de Base de Datos

```bash
# Verificar conexión
docker exec fpm_app php artisan migrate:status

# Reiniciar MariaDB
docker restart mariadb
```

### Problemas de Caché

```bash
# Limpiar todo el caché
docker exec fpm_app php artisan cache:clear
docker exec fpm_app php artisan config:clear
docker exec fpm_app php artisan view:clear
docker exec fpm_app php artisan route:clear
```

## Arquitectura Multi-tenant

El sistema utiliza la estrategia de bases de datos separadas para cada tenant:

- **Base de datos central**: Gestiona información de tenants
- **Bases de datos de tenant**: Una por cada empresa
- **Dominios**: Cada tenant tiene su propio subdominio

```
Estructura:
- tenancy (DB central)
  - tenants
  - hostnames
  - websites

- tenant_empresa1 (DB tenant)
  - users
  - invoices
  - products
  - etc.
```

## Módulos Disponibles

### Factcolombia1
- Facturación electrónica DIAN
- Generación de XML/PDF
- Envío automático a DIAN
- Consulta de estados
- Notas crédito/débito

### Otros Módulos
- Inventarios
- Compras
- Ventas
- Clientes
- Proveedores
- Reportes

## Seguridad

### Recomendaciones

1. Cambiar contraseñas por defecto
2. Configurar SSL/TLS
3. Actualizar dependencias regularmente
4. Realizar backups periódicos
5. Monitorear logs de acceso
6. Implementar rate limiting
7. Usar contraseñas fuertes

### Permisos de Usuario

El sistema maneja roles y permisos granulares:
- Super Admin
- Admin de Tenant
- Usuario Contable
- Usuario Vendedor
- Usuario Solo Lectura

## Rendimiento

### Optimizaciones

```bash
# Cachear configuración
docker exec fpm_app php artisan config:cache

# Cachear rutas
docker exec fpm_app php artisan route:cache

# Cachear vistas
docker exec fpm_app php artisan view:cache

# Optimizar autoload
docker exec fpm_app composer dump-autoload -o
```

## Desarrollo

### Entorno de Desarrollo

```bash
# Modo debug
APP_ENV=local
APP_DEBUG=true

# Instalar dependencias de desarrollo
docker exec fpm_app composer install

# Ejecutar tests
docker exec fpm_app php artisan test
```

### Contribuir

1. Fork del repositorio
2. Crear rama feature: `git checkout -b feature/nueva-funcionalidad`
3. Commit cambios: `git commit -am 'Agregar nueva funcionalidad'`
4. Push a la rama: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request

## Documentación Adicional

- [Documentación de Backups](/root/documentacion/DOCUMENTACION_BACKUPS.md)
- [Documentación de Laravel 5.8](https://laravel.com/docs/5.8)
- [Documentación DIAN](https://www.dian.gov.co)

## Soporte Técnico

### Contacto

**Fullsys Tecnología**
- **Desarrollador**: Fulvio Leonardo Badillo Caseres
- **Email**: fullsyssantamarta@gmail.com
- **Celular**: +57 302 548 0682
- **Ubicación**: Santa Marta, Colombia

### Horario de Soporte

- Lunes a Viernes: 8:00 AM - 6:00 PM (COT)
- Sábados: 9:00 AM - 1:00 PM (COT)
- Emergencias: Disponible por WhatsApp

### Canales de Soporte

1. **Email**: fullsyssantamarta@gmail.com
2. **WhatsApp**: +57 302 548 0682
3. **Issues GitHub**: https://github.com/fullsyssantamarta/erp_imperium/issues

## Licencia

Copyright © 2025 Fullsys Tecnología - Santa Marta, Colombia

Todos los derechos reservados. Este software es propiedad de Fullsys Tecnología y está protegido por las leyes de derechos de autor colombianas e internacionales.

## Créditos

Desarrollado y mantenido por:
- **Fullsys Tecnología**
- **Fulvio Leonardo Badillo Caseres**
- Santa Marta, Magdalena, Colombia

---

**Versión**: 2.0  
**Última actualización**: Octubre 2025  
**Estado**: Producción
