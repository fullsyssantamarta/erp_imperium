@extends('tenant.layouts.app')

@section('title', 'Usuarios Sector Salud')

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
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="#">Ventas</a></li>
                    <li class="breadcrumb-item active">Usuarios S.S</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $count ?? 0 }}</h3>
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
                        <h3>{{ \App\Models\TenancyHealthUser::whereDate('created_at', today())->count() }}</h3>
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
                        <h3>{{ \App\Models\TenancyHealthUser::whereNotNull('eps_nombre')->count() }}</h3>
                        <p>Con EPS</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hospital"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ \App\Models\TenancyHealthUser::whereDate('updated_at', '>=', now()->subDays(7))->count() }}</h3>
                        <p>Actualizados (7d)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sync"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta Principal -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Gestión de Usuarios del Sector Salud
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createUserModal">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Filtros de Búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Buscar por Documento:</label>
                            <input type="text" class="form-control" id="search-document" placeholder="Número de documento">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Buscar por Nombre:</label>
                            <input type="text" class="form-control" id="search-name" placeholder="Nombre completo">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>EPS:</label>
                            <select class="form-control" id="search-eps">
                                <option value="">Todas</option>
                                <option value="SURA">SURA</option>
                                <option value="SANITAS">SANITAS</option>
                                <option value="FAMISANAR">FAMISANAR</option>
                                <option value="COMPENSAR">COMPENSAR</option>
                                <option value="NUEVA EPS">NUEVA EPS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Municipio:</label>
                            <input type="text" class="form-control" id="search-city" placeholder="Ciudad">
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

                <!-- Tabla de Usuarios -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="users-table">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">Documento</th>
                                <th width="20%">Nombre Completo</th>
                                <th width="12%">Teléfono</th>
                                <th width="15%">Email</th>
                                <th width="12%">EPS</th>
                                <th width="12%">Municipio</th>
                                <th width="10%">Fecha Reg.</th>
                                <th width="9%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="row mt-3">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info" id="table-info">
                            Mostrando 0 a 0 de 0 registros
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div class="dataTables_paginate paging_simple_numbers">
                            <ul class="pagination" id="pagination">
                                <!-- Paginación dinámica -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title">
                    <i class="fas fa-user-md"></i>
                    <span id="modal-title">Nuevo Usuario Sector Salud</span>
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            
            <form id="user-form" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Formulario completo del usuario -->
                    @include('tenant.health_users.form')
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
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title">
                    <i class="fas fa-eye"></i>
                    Detalles del Usuario
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="user-details">
                <!-- Contenido dinámico -->
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
$(document).ready(function() {
    // Cargar usuarios al inicio
    loadUsers();
    
    // Búsqueda en tiempo real
    $('#search-document, #search-name, #search-city').on('keyup', function() {
        if($(this).val().length >= 3 || $(this).val().length == 0) {
            searchUsers();
        }
    });
    
    $('#search-eps').on('change', function() {
        searchUsers();
    });
});

// Variables globales
let currentPage = 1;
let isEditing = false;
let editingUserId = null;

// Cargar usuarios
function loadUsers(page = 1, search = {}) {
    $.ajax({
        url: '/health-users/data',
        method: 'GET',
        data: {
            page: page,
            ...search
        },
        success: function(response) {
            renderUsersTable(response.data);
            renderPagination(response);
            updateTableInfo(response);
        },
        error: function(xhr) {
            console.error('Error cargando usuarios:', xhr);
            showAlert('Error cargando usuarios', 'error');
            $('#users-tbody').html('<tr><td colspan="8" class="text-center text-danger">Error cargando datos</td></tr>');
        }
    });
}

// Renderizar tabla de usuarios
function renderUsersTable(users) {
    let html = '';
    
    if(users.length === 0) {
        html = '<tr><td colspan="8" class="text-center">No se encontraron usuarios</td></tr>';
    } else {
        users.forEach(function(user) {
            html += `
                <tr>
                    <td>
                        <span class="badge badge-secondary">${user.tipo_documento || 'CC'}</span><br>
                        <strong>${user.documento}</strong>
                    </td>
                    <td>
                        <strong>${user.nombre_completo}</strong>
                    </td>
                    <td>${user.telefono}</td>
                    <td>${user.email}</td>
                    <td>
                        ${user.eps_nombre ? '<span class="badge badge-info">' + user.eps_nombre + '</span>' : '-'}
                    </td>
                    <td>${user.municipio}</td>
                    <td>${user.created_at}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info btn-sm" onclick="viewUser(${user.id})" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="editUser(${user.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#users-tbody').html(html);
}

// Renderizar paginación
function renderPagination(response) {
    let html = '';
    
    if(response.last_page > 1) {
        // Botón anterior
        if(response.current_page > 1) {
            html += `<li class="paginate_button page-item previous">
                        <a href="#" class="page-link" onclick="loadUsers(${response.current_page - 1})">Anterior</a>
                     </li>`;
        }
        
        // Números de página
        let start = Math.max(1, response.current_page - 2);
        let end = Math.min(response.last_page, response.current_page + 2);
        
        for(let i = start; i <= end; i++) {
            let activeClass = i === response.current_page ? 'active' : '';
            html += `<li class="paginate_button page-item ${activeClass}">
                        <a href="#" class="page-link" onclick="loadUsers(${i})">${i}</a>
                     </li>`;
        }
        
        // Botón siguiente
        if(response.current_page < response.last_page) {
            html += `<li class="paginate_button page-item next">
                        <a href="#" class="page-link" onclick="loadUsers(${response.current_page + 1})">Siguiente</a>
                     </li>`;
        }
    }
    
    $('#pagination').html(html);
}

// Actualizar información de la tabla
function updateTableInfo(response) {
    let from = (response.current_page - 1) * response.per_page + 1;
    let to = Math.min(from + response.per_page - 1, response.total);
    $('#table-info').text(`Mostrando ${from} a ${to} de ${response.total} registros`);
}

// Buscar usuarios
function searchUsers() {
    let search = {
        document: $('#search-document').val(),
        name: $('#search-name').val(),
        eps: $('#search-eps').val(),
        city: $('#search-city').val()
    };
    
    loadUsers(1, search);
}

// Ver usuario
function viewUser(id) {
    $.ajax({
        url: `/health-users/${id}`,
        method: 'GET',
        success: function(user) {
            renderUserDetails(user);
            $('#viewUserModal').modal('show');
        },
        error: function(xhr) {
            showAlert('Error cargando detalles del usuario', 'error');
        }
    });
}

// Renderizar detalles del usuario
function renderUserDetails(user) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fas fa-id-card"></i> Información Personal</h5>
                <table class="table table-sm">
                    <tr><th>Documento:</th><td>${user.tipo_documento} ${user.documento}</td></tr>
                    <tr><th>Nombre:</th><td>${user.nombre_completo}</td></tr>
                    <tr><th>Fecha Nacimiento:</th><td>${user.fecha_nacimiento || '-'}</td></tr>
                    <tr><th>Género:</th><td>${user.genero || '-'}</td></tr>
                    <tr><th>Estado Civil:</th><td>${user.estado_civil || '-'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-phone"></i> Contacto</h5>
                <table class="table table-sm">
                    <tr><th>Teléfono:</th><td>${user.telefono || '-'}</td></tr>
                    <tr><th>Email:</th><td>${user.email || '-'}</td></tr>
                    <tr><th>Dirección:</th><td>${user.direccion || '-'}</td></tr>
                    <tr><th>Municipio:</th><td>${user.municipio || '-'}</td></tr>
                    <tr><th>Departamento:</th><td>${user.departamento || '-'}</td></tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h5><i class="fas fa-hospital"></i> Información EPS</h5>
                <table class="table table-sm">
                    <tr><th>EPS:</th><td>${user.eps_nombre || '-'}</td></tr>
                    <tr><th>Código EPS:</th><td>${user.eps_codigo || '-'}</td></tr>
                    <tr><th>Régimen:</th><td>${user.regimen || '-'}</td></tr>
                    <tr><th>Nivel:</th><td>${user.nivel || '-'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-money-bill"></i> Información Financiera</h5>
                <table class="table table-sm">
                    <tr><th>Valor Consulta:</th><td>$${formatMoney(user.valor_consulta)}</td></tr>
                    <tr><th>Copago:</th><td>$${formatMoney(user.copago)}</td></tr>
                    <tr><th>Cuota Moderadora:</th><td>$${formatMoney(user.cuota_moderadora)}</td></tr>
                </table>
            </div>
        </div>
    `;
    
    $('#user-details').html(html);
}

// Editar usuario
function editUser(id) {
    isEditing = true;
    editingUserId = id;
    
    $.ajax({
        url: `/health-users/${id}`,
        method: 'GET',
        success: function(user) {
            populateForm(user);
            $('#modal-title').text('Editar Usuario Sector Salud');
            $('#createUserModal').modal('show');
        },
        error: function(xhr) {
            showAlert('Error cargando usuario para editar', 'error');
        }
    });
}

// Poblar formulario con datos del usuario
function populateForm(user) {
    // Aquí poblaremos todos los campos del formulario
    Object.keys(user).forEach(function(key) {
        let field = $(`#${key}`);
        if(field.length > 0) {
            field.val(user[key]);
        }
    });
}

// Eliminar usuario
function deleteUser(id) {
    if(confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
        $.ajax({
            url: `/health-users/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showAlert('Usuario eliminado correctamente', 'success');
                loadUsers();
            },
            error: function(xhr) {
                showAlert('Error eliminando usuario', 'error');
            }
        });
    }
}

// Manejar envío del formulario
$('#user-form').on('submit', function(e) {
    e.preventDefault();
    
    let url = isEditing ? `/health-users/${editingUserId}` : '/health-users';
    let method = isEditing ? 'PUT' : 'POST';
    
    let formData = new FormData(this);
    if(isEditing) {
        formData.append('_method', 'PUT');
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showAlert(isEditing ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente', 'success');
            $('#createUserModal').modal('hide');
            resetForm();
            loadUsers();
        },
        error: function(xhr) {
            let errors = xhr.responseJSON?.errors;
            if(errors) {
                showValidationErrors(errors);
            } else {
                showAlert('Error guardando usuario', 'error');
            }
        }
    });
});

// Resetear formulario
function resetForm() {
    $('#user-form')[0].reset();
    isEditing = false;
    editingUserId = null;
    $('#modal-title').text('Nuevo Usuario Sector Salud');
    clearValidationErrors();
}

// Mostrar errores de validación
function showValidationErrors(errors) {
    clearValidationErrors();
    
    Object.keys(errors).forEach(function(field) {
        let fieldElement = $(`#${field}`);
        if(fieldElement.length > 0) {
            fieldElement.addClass('is-invalid');
            fieldElement.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
        }
    });
}

// Limpiar errores de validación
function clearValidationErrors() {
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
}

// Mostrar alertas
function showAlert(message, type) {
    let alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    let icon = type === 'success' ? 'fa-check' : 'fa-exclamation-triangle';
    
    let alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${icon}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.content').prepend(alert);
    
    // Auto-dismiss after 3 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 3000);
}

// Formatear dinero
function formatMoney(amount) {
    if(!amount) return '0';
    return new Intl.NumberFormat('es-CO').format(amount);
}

// Reset modal al cerrar
$('#createUserModal').on('hidden.bs.modal', function() {
    resetForm();
});
</script>
@endsection
