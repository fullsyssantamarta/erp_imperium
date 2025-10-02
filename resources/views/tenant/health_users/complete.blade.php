@extends('tenant.layouts.app')

@section('title', 'Usuarios Sector Salud - Completo')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-user-md text-primary"></i>
                    Usuarios Sector Salud
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createUserModal">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Panel de Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="total-users">{{ $count ?? 0 }}</h3>
                        <p>Total Usuarios</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="today-users">{{ $todayCount ?? 0 }}</h3>
                        <p>Registros Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="active-users">{{ $activeCount ?? 0 }}</h3>
                        <p>Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="inactive-users">{{ $inactiveCount ?? 0 }}</h3>
                        <p>Inactivos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter"></i> Filtros y Búsqueda
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchDocument">Documento</label>
                            <input type="text" class="form-control" id="searchDocument" placeholder="Buscar por documento">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchName">Nombre</label>
                            <input type="text" class="form-control" id="searchName" placeholder="Buscar por nombre">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filterStatus">Estado</label>
                            <select class="form-control" id="filterStatus">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filterRegime">Régimen</label>
                            <select class="form-control" id="filterRegime">
                                <option value="">Todos</option>
                                <option value="CONTRIBUTIVO">Contributivo</option>
                                <option value="SUBSIDIADO">Subsidiado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary btn-block" onclick="searchUsers()">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> Lista de Usuarios
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-info" onclick="refreshTable()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="usersTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Documento</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>EPS</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="row mt-3">
                    <div class="col-sm-5">
                        <div class="dataTables_info" id="tableInfo"></div>
                    </div>
                    <div class="col-sm-7">
                        <div class="dataTables_paginate paging_simple_numbers" id="tablePagination">
                            <!-- La paginación se generará automáticamente -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="createUserModalLabel">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario Sector Salud
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                <div class="modal-body">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="userTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">
                                <i class="fas fa-user"></i> Datos Personales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="health-tab" data-toggle="tab" href="#health" role="tab">
                                <i class="fas fa-heartbeat"></i> Información de Salud
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab">
                                <i class="fas fa-phone"></i> Contacto
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="additional-tab" data-toggle="tab" href="#additional" role="tab">
                                <i class="fas fa-info-circle"></i> Información Adicional
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content mt-3" id="userTabsContent">
                        <!-- Datos Personales -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="document_type">Tipo de Documento *</label>
                                        <select class="form-control" id="document_type" name="document_type" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="CC">Cédula de Ciudadanía</option>
                                            <option value="TI">Tarjeta de Identidad</option>
                                            <option value="CE">Cédula de Extranjería</option>
                                            <option value="PA">Pasaporte</option>
                                            <option value="RC">Registro Civil</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="document_number">Número de Documento *</label>
                                        <input type="text" class="form-control" id="document_number" name="document_number" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="first_name">Primer Nombre *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="second_name">Segundo Nombre</label>
                                        <input type="text" class="form-control" id="second_name" name="second_name">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="first_lastname">Primer Apellido *</label>
                                        <input type="text" class="form-control" id="first_lastname" name="first_lastname" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="second_lastname">Segundo Apellido</label>
                                        <input type="text" class="form-control" id="second_lastname" name="second_lastname">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="birth_date">Fecha de Nacimiento *</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="gender">Género *</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Salud -->
                        <div class="tab-pane fade" id="health" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="eps_code">Código EPS *</label>
                                        <input type="text" class="form-control" id="eps_code" name="eps_code" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="eps_name">Nombre EPS *</label>
                                        <input type="text" class="form-control" id="eps_name" name="eps_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="regime_type">Tipo de Régimen *</label>
                                        <select class="form-control" id="regime_type" name="regime_type" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="CONTRIBUTIVO">Contributivo</option>
                                            <option value="SUBSIDIADO">Subsidiado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="user_type">Tipo de Usuario *</label>
                                        <select class="form-control" id="user_type" name="user_type" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="COTIZANTE">Cotizante</option>
                                            <option value="BENEFICIARIO">Beneficiario</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="population_group">Grupo Poblacional</label>
                                        <select class="form-control" id="population_group" name="population_group">
                                            <option value="">Seleccionar...</option>
                                            <option value="1">Indígena</option>
                                            <option value="2">ROM (Gitano)</option>
                                            <option value="3">Raizal (Archipiélago San Andrés y Providencia)</option>
                                            <option value="4">Palenquero</option>
                                            <option value="5">Negro, mulato, afrocolombiano</option>
                                            <option value="6">Otros grupos poblacionales</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="disability_condition">Condición de Discapacidad</label>
                                        <select class="form-control" id="disability_condition" name="disability_condition">
                                            <option value="">Seleccionar...</option>
                                            <option value="N">No</option>
                                            <option value="S">Sí</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="tab-pane fade" id="contact" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Teléfono</label>
                                        <input type="text" class="form-control" id="phone" name="phone">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="department_code">Código Departamento</label>
                                        <input type="text" class="form-control" id="department_code" name="department_code">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="municipality_code">Código Municipio</label>
                                        <input type="text" class="form-control" id="municipality_code" name="municipality_code">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="residence_zone">Zona de Residencia</label>
                                        <select class="form-control" id="residence_zone" name="residence_zone">
                                            <option value="">Seleccionar...</option>
                                            <option value="U">Urbana</option>
                                            <option value="R">Rural</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Dirección</label>
                                <input type="text" class="form-control" id="address" name="address">
                            </div>
                        </div>

                        <!-- Información Adicional -->
                        <div class="tab-pane fade" id="additional" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="education_level">Nivel Educativo</label>
                                        <select class="form-control" id="education_level" name="education_level">
                                            <option value="">Seleccionar...</option>
                                            <option value="1">Ninguno</option>
                                            <option value="2">Preescolar</option>
                                            <option value="3">Básica primaria</option>
                                            <option value="4">Básica secundaria</option>
                                            <option value="5">Media académica o clásica</option>
                                            <option value="6">Media técnica</option>
                                            <option value="7">Normalista</option>
                                            <option value="8">Técnica profesional</option>
                                            <option value="9">Tecnológica</option>
                                            <option value="10">Profesional</option>
                                            <option value="11">Especialización</option>
                                            <option value="12">Maestría</option>
                                            <option value="13">Doctorado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="civil_status">Estado Civil</label>
                                        <select class="form-control" id="civil_status" name="civil_status">
                                            <option value="">Seleccionar...</option>
                                            <option value="S">Soltero(a)</option>
                                            <option value="C">Casado(a)</option>
                                            <option value="U">Unión libre</option>
                                            <option value="D">Divorciado(a)</option>
                                            <option value="V">Viudo(a)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="occupation">Ocupación</label>
                                        <input type="text" class="form-control" id="occupation" name="occupation">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="income_range">Rango de Ingresos</label>
                                        <select class="form-control" id="income_range" name="income_range">
                                            <option value="">Seleccionar...</option>
                                            <option value="1">Menos de 1 SMMLV</option>
                                            <option value="2">Entre 1 y 2 SMMLV</option>
                                            <option value="3">Entre 2 y 3 SMMLV</option>
                                            <option value="4">Entre 3 y 5 SMMLV</option>
                                            <option value="5">Más de 5 SMMLV</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                            <label class="custom-control-label" for="is_active">Usuario Activo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Usuario -->
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="viewUserModalLabel">
                    <i class="fas fa-eye"></i> Información del Usuario
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <!-- El contenido se cargará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Variables globales
let currentPage = 1;
let totalPages = 1;

// Inicialización
$(document).ready(function() {
    loadUsers();
    updateStatistics();
});

// Función para cargar usuarios
function loadUsers(page = 1) {
    const searchParams = {
        page: page,
        document: $('#searchDocument').val(),
        name: $('#searchName').val(),
        status: $('#filterStatus').val(),
        regime: $('#filterRegime').val()
    };

    $('#usersTable tbody').html(`
        <tr>
            <td colspan="7" class="text-center">
                <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
            </td>
        </tr>
    `);

    $.ajax({
        url: '/health-users/data',
        method: 'GET',
        data: searchParams,
        success: function(response) {
            renderUsersTable(response);
            renderPagination(response);
            updateTableInfo(response);
            currentPage = response.current_page;
            totalPages = response.last_page;
        },
        error: function(xhr) {
            $('#usersTable tbody').html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar usuarios: ${xhr.responseText || 'Error desconocido'}
                    </td>
                </tr>
            `);
        }
    });
}

// Función para renderizar la tabla de usuarios
function renderUsersTable(response) {
    let html = '';
    
    if (response.data && response.data.length > 0) {
        response.data.forEach(function(user) {
            const fullName = `${user.first_name || ''} ${user.second_name || ''} ${user.first_lastname || ''} ${user.second_lastname || ''}`.trim();
            const statusBadge = user.is_active ? 
                '<span class="badge badge-success">Activo</span>' : 
                '<span class="badge badge-danger">Inactivo</span>';
            
            html += `
                <tr>
                    <td>${user.document_number || '-'}</td>
                    <td>${fullName || '-'}</td>
                    <td>${user.email || '-'}</td>
                    <td>${user.phone || '-'}</td>
                    <td>${user.eps_name || '-'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-info" onclick="viewUser(${user.id})" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-warning" onclick="editUser(${user.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger" onclick="deleteUser(${user.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    } else {
        html = `
            <tr>
                <td colspan="7" class="text-center">
                    <i class="fas fa-info-circle"></i> No se encontraron usuarios
                </td>
            </tr>
        `;
    }
    
    $('#usersTable tbody').html(html);
}

// Función para renderizar paginación
function renderPagination(response) {
    let html = '';
    
    if (response.last_page > 1) {
        // Botón anterior
        if (response.current_page > 1) {
            html += `<button class="btn btn-sm btn-outline-primary mr-1" onclick="loadUsers(${response.current_page - 1})">Anterior</button>`;
        }
        
        // Números de página
        for (let i = 1; i <= response.last_page; i++) {
            if (i === response.current_page) {
                html += `<button class="btn btn-sm btn-primary mr-1">${i}</button>`;
            } else {
                html += `<button class="btn btn-sm btn-outline-primary mr-1" onclick="loadUsers(${i})">${i}</button>`;
            }
        }
        
        // Botón siguiente
        if (response.current_page < response.last_page) {
            html += `<button class="btn btn-sm btn-outline-primary" onclick="loadUsers(${response.current_page + 1})">Siguiente</button>`;
        }
    }
    
    $('#tablePagination').html(html);
}

// Función para actualizar información de la tabla
function updateTableInfo(response) {
    const start = ((response.current_page - 1) * response.per_page) + 1;
    const end = Math.min(response.current_page * response.per_page, response.total);
    const info = `Mostrando ${start} a ${end} de ${response.total} registros`;
    $('#tableInfo').text(info);
}

// Función para buscar usuarios
function searchUsers() {
    loadUsers(1);
}

// Función para refrescar tabla
function refreshTable() {
    loadUsers(currentPage);
    updateStatistics();
}

// Función para actualizar estadísticas
function updateStatistics() {
    $.ajax({
        url: '/health-users',
        method: 'GET',
        success: function(response) {
            if (response.count !== undefined) $('#total-users').text(response.count);
            if (response.todayCount !== undefined) $('#today-users').text(response.todayCount);
            if (response.activeCount !== undefined) $('#active-users').text(response.activeCount);
            if (response.inactiveCount !== undefined) $('#inactive-users').text(response.inactiveCount);
        }
    });
}

// Función para ver usuario
function viewUser(id) {
    $.ajax({
        url: `/health-users/${id}`,
        method: 'GET',
        success: function(response) {
            const user = response.user;
            const fullName = `${user.first_name || ''} ${user.second_name || ''} ${user.first_lastname || ''} ${user.second_lastname || ''}`.trim();
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user"></i> Datos Personales</h5>
                        <table class="table table-sm">
                            <tr><th>Tipo Documento:</th><td>${user.document_type || '-'}</td></tr>
                            <tr><th>Número Documento:</th><td>${user.document_number || '-'}</td></tr>
                            <tr><th>Nombre Completo:</th><td>${fullName || '-'}</td></tr>
                            <tr><th>Fecha Nacimiento:</th><td>${user.birth_date || '-'}</td></tr>
                            <tr><th>Género:</th><td>${user.gender === 'M' ? 'Masculino' : (user.gender === 'F' ? 'Femenino' : '-')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-heartbeat"></i> Información de Salud</h5>
                        <table class="table table-sm">
                            <tr><th>EPS:</th><td>${user.eps_name || '-'} (${user.eps_code || '-'})</td></tr>
                            <tr><th>Régimen:</th><td>${user.regime_type || '-'}</td></tr>
                            <tr><th>Tipo Usuario:</th><td>${user.user_type || '-'}</td></tr>
                            <tr><th>Estado:</th><td>${user.is_active ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-phone"></i> Información de Contacto</h5>
                        <table class="table table-sm">
                            <tr><th>Email:</th><td>${user.email || '-'}</td></tr>
                            <tr><th>Teléfono:</th><td>${user.phone || '-'}</td></tr>
                            <tr><th>Dirección:</th><td>${user.address || '-'}</td></tr>
                        </table>
                    </div>
                </div>
            `;
            
            $('#viewUserContent').html(html);
            $('#viewUserModal').modal('show');
        },
        error: function(xhr) {
            alert('Error al cargar la información del usuario: ' + (xhr.responseText || 'Error desconocido'));
        }
    });
}

// Función para editar usuario
function editUser(id) {
    $.ajax({
        url: `/health-users/${id}`,
        method: 'GET',
        success: function(response) {
            const user = response.user;
            
            // Llenar el formulario con los datos del usuario
            $('#userId').val(user.id);
            $('#document_type').val(user.document_type);
            $('#document_number').val(user.document_number);
            $('#first_name').val(user.first_name);
            $('#second_name').val(user.second_name);
            $('#first_lastname').val(user.first_lastname);
            $('#second_lastname').val(user.second_lastname);
            $('#birth_date').val(user.birth_date);
            $('#gender').val(user.gender);
            $('#eps_code').val(user.eps_code);
            $('#eps_name').val(user.eps_name);
            $('#regime_type').val(user.regime_type);
            $('#user_type').val(user.user_type);
            $('#population_group').val(user.population_group);
            $('#disability_condition').val(user.disability_condition);
            $('#email').val(user.email);
            $('#phone').val(user.phone);
            $('#department_code').val(user.department_code);
            $('#municipality_code').val(user.municipality_code);
            $('#residence_zone').val(user.residence_zone);
            $('#address').val(user.address);
            $('#education_level').val(user.education_level);
            $('#civil_status').val(user.civil_status);
            $('#occupation').val(user.occupation);
            $('#income_range').val(user.income_range);
            $('#is_active').prop('checked', user.is_active);
            
            // Cambiar el título del modal
            $('#createUserModalLabel').html('<i class="fas fa-edit"></i> Editar Usuario Sector Salud');
            
            // Mostrar el modal
            $('#createUserModal').modal('show');
        },
        error: function(xhr) {
            alert('Error al cargar la información del usuario: ' + (xhr.responseText || 'Error desconocido'));
        }
    });
}

// Función para guardar usuario (crear o actualizar)
function saveUser(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('userForm'));
    const userId = $('#userId').val();
    const isEdit = userId && userId !== '';
    
    const url = isEdit ? `/health-users/${userId}` : '/health-users';
    const method = isEdit ? 'PUT' : 'POST';
    
    // Convertir FormData a objeto para PUT request
    let data = {};
    if (method === 'PUT') {
        for (let pair of formData.entries()) {
            data[pair[0]] = pair[1];
        }
        // Convertir checkbox a boolean
        data.is_active = $('#is_active').is(':checked') ? 1 : 0;
    } else {
        // Para POST, mantener FormData
        formData.set('is_active', $('#is_active').is(':checked') ? '1' : '0');
        data = formData;
    }
    
    $.ajax({
        url: url,
        method: method,
        data: data,
        processData: method !== 'POST',
        contentType: method === 'POST' ? false : 'application/x-www-form-urlencoded',
        success: function(response) {
            $('#createUserModal').modal('hide');
            loadUsers(currentPage);
            updateStatistics();
            
            // Mostrar mensaje de éxito
            const message = isEdit ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente';
            showAlert('success', message);
            
            // Limpiar formulario
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#createUserModalLabel').html('<i class="fas fa-user-plus"></i> Nuevo Usuario Sector Salud');
        },
        error: function(xhr) {
            let errorMessage = 'Error al guardar el usuario';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMessage = xhr.responseText;
            }
            showAlert('error', errorMessage);
        }
    });
}

// Función para eliminar usuario
function deleteUser(id) {
    if (confirm('¿Está seguro de que desea eliminar este usuario?')) {
        $.ajax({
            url: `/health-users/${id}`,
            method: 'DELETE',
            success: function(response) {
                loadUsers(currentPage);
                updateStatistics();
                showAlert('success', 'Usuario eliminado correctamente');
            },
            error: function(xhr) {
                let errorMessage = 'Error al eliminar el usuario';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
                showAlert('error', errorMessage);
            }
        });
    }
}

// Función para mostrar alertas
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="${icon}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Agregar la alerta al principio del contenido
    $('.content-header').after(alertHtml);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Limpiar formulario al cerrar modal
$('#createUserModal').on('hidden.bs.modal', function () {
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#createUserModalLabel').html('<i class="fas fa-user-plus"></i> Nuevo Usuario Sector Salud');
    // Volver a la primera pestaña
    $('#personal-tab').tab('show');
});

// Permitir búsqueda con Enter
$('#searchDocument, #searchName').on('keypress', function(e) {
    if (e.which === 13) {
        searchUsers();
    }
});
</script>
@endsection
