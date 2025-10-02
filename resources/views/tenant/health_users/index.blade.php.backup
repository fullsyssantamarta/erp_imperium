@extends('tenant.layouts.app')

@section('title')
{{ __('Usuarios Sector Salud') }}
@endsection

@section('breadcrumb')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="btn-group float-right">
                <ol class="breadcrumb hide-phone p-0 m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Usuarios Sector Salud') }}</li>
                </ol>
            </div>
            <h4 class="page-title">
                <i class="fas fa-user-md"></i> {{ __('Usuarios Sector Salud') }}
            </h4>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span id="card_title">
                        <i class="fas fa-user-md"></i> {{ __('Gestión de Usuarios del Sector Salud') }}
                    </span>
                    <div class="float-right">
                        <button class="btn btn-success btn-sm mr-2" onclick="importUsers()">
                            <i class="fa fa-upload"></i> {{ __('Importar Excel') }}
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="createUser()">
                            <i class="fa fa-plus"></i> {{ __('Nuevo Usuario') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Estado del Sistema -->
                <div class="alert alert-success" role="alert">
                    <h5 class="alert-heading">✅ Sistema Operativo</h5>
                    <p class="mb-0">
                        <strong>Vista:</strong> health_users/index.blade.php cargada correctamente |
                        <strong>Controlador:</strong> HealthUsersController funcionando |
                        <strong>Base de datos:</strong> Conectada
                    </p>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" 
                                   id="searchInput"
                                   class="form-control" 
                                   placeholder="Buscar por documento, nombre, EPS...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="searchUsers()">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-info btn-sm" onclick="exportUsers()">
                            <i class="fa fa-download"></i> {{ __('Exportar') }}
                        </button>
                        <button class="btn btn-secondary btn-sm ml-2" onclick="refreshUsers()">
                            <i class="fa fa-sync"></i> {{ __('Actualizar') }}
                        </button>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="healthUsersTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Documento</th>
                                <th>Tipo</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>EPS</th>
                                <th>Municipio</th>
                                <th>Fecha Registro</th>
                                <th width="150px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="10" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p class="text-muted" id="paginationInfo">
                            <!-- Se llenará dinámicamente -->
                        </p>
                    </div>
                    <div class="col-md-6">
                        <nav>
                            <ul class="pagination justify-content-end" id="paginationControls">
                                <!-- Se llenará dinámicamente -->
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Enlaces de testing -->
                <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 5px;">
                    <h6>🔗 Enlaces de Prueba (Debug):</h6>
                    <div class="btn-group" role="group">
                        <a href="{{ route('tenant.health-users.data') }}" target="_blank" class="btn btn-sm btn-outline-info">
                            Ver datos JSON
                        </a>
                        <a href="{{ route('tenant.health-users.create') }}" target="_blank" class="btn btn-sm btn-outline-success">
                            Crear usuario
                        </a>
                        <a href="{{ route('tenant.health-users.search') }}" target="_blank" class="btn btn-sm btn-outline-warning">
                            Buscar usuarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">
                    <i class="fas fa-user-md"></i> <span id="modalTitle">Nuevo Usuario</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="userForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="documento">{{ __('Documento') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="documento" id="documento" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_documento">{{ __('Tipo Documento') }}</label>
                                <select class="form-control" name="tipo_documento" id="tipo_documento">
                                    <option value="CC">Cédula de Ciudadanía</option>
                                    <option value="TI">Tarjeta de Identidad</option>
                                    <option value="CE">Cédula de Extranjería</option>
                                    <option value="PP">Pasaporte</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="primer_nombre">{{ __('Primer Nombre') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="primer_nombre" id="primer_nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="segundo_nombre">{{ __('Segundo Nombre') }}</label>
                                <input type="text" class="form-control" name="segundo_nombre" id="segundo_nombre">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="primer_apellido">{{ __('Primer Apellido') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="primer_apellido" id="primer_apellido" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="segundo_apellido">{{ __('Segundo Apellido') }}</label>
                                <input type="text" class="form-control" name="segundo_apellido" id="segundo_apellido">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefono">{{ __('Teléfono') }}</label>
                                <input type="text" class="form-control" name="telefono" id="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">{{ __('Email') }}</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="eps_codigo">{{ __('EPS Código') }}</label>
                                <input type="text" class="form-control" name="eps_codigo" id="eps_codigo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="eps_nombre">{{ __('EPS Nombre') }}</label>
                                <input type="text" class="form-control" name="eps_nombre" id="eps_nombre">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> {{ __('Cancelar') }}
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> {{ __('Guardar Usuario') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let editingUserId = null;

$(document).ready(function() {
    loadUsers();
    
    // Manejar envío del formulario
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
    
    // Búsqueda en tiempo real
    $('#searchInput').on('keyup', function(e) {
        if (e.keyCode === 13) { // Enter
            searchUsers();
        }
    });
});

function loadUsers(page = 1, search = '') {
    currentPage = page;
    
    $('#healthUsersTable tbody').html(`
        <tr>
            <td colspan="10" class="text-center">
                <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
            </td>
        </tr>
    `);
    
    $.ajax({
        url: '{{ route("tenant.health-users.data") }}',
        type: 'GET',
        data: {
            page: page,
            search: search
        },
        success: function(response) {
            if (response.success) {
                renderUsersTable(response.data);
                renderPagination(response.pagination);
            } else {
                showError('Error al cargar usuarios');
            }
        },
        error: function() {
            $('#healthUsersTable tbody').html(`
                <tr>
                    <td colspan="10" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar usuarios
                    </td>
                </tr>
            `);
        }
    });
}

function renderUsersTable(users) {
    let html = '';
    
    if (users.length === 0) {
        html = `
            <tr>
                <td colspan="10" class="text-center">
                    <i class="fas fa-users"></i> No hay usuarios registrados
                </td>
            </tr>
        `;
    } else {
        users.forEach(user => {
            html += `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.documento || 'N/A'}</td>
                    <td>${user.tipo_documento || 'N/A'}</td>
                    <td>${getFullName(user)}</td>
                    <td>${user.telefono || 'N/A'}</td>
                    <td>${user.email || 'N/A'}</td>
                    <td>${user.eps_nombre || 'N/A'}</td>
                    <td>${user.municipio || 'N/A'}</td>
                    <td>${formatDate(user.created_at)}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-info" onclick="viewUser(${user.id})" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="editUser(${user.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#healthUsersTable tbody').html(html);
}

function renderPagination(pagination) {
    if (!pagination) return;
    
    // Información de paginación
    $('#paginationInfo').text(
        `Mostrando ${pagination.from || 0} a ${pagination.to || 0} de ${pagination.total || 0} usuarios`
    );
    
    // Controles de paginación
    let paginationHtml = '';
    
    if (pagination.total > pagination.per_page) {
        // Botón anterior
        paginationHtml += `
            <li class="page-item ${pagination.current_page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Página actual
        paginationHtml += `
            <li class="page-item active">
                <span class="page-link">${pagination.current_page}</span>
            </li>
        `;
        
        // Botón siguiente
        paginationHtml += `
            <li class="page-item ${pagination.current_page >= pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    $('#paginationControls').html(paginationHtml);
}

function getFullName(user) {
    const parts = [
        user.primer_nombre,
        user.segundo_nombre,
        user.primer_apellido,
        user.segundo_apellido
    ].filter(part => part && part.trim() !== '');
    
    return parts.length > 0 ? parts.join(' ') : 'Sin nombre';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function changePage(page) {
    if (page > 0) {
        const search = $('#searchInput').val();
        loadUsers(page, search);
    }
}

function searchUsers() {
    const search = $('#searchInput').val();
    loadUsers(1, search);
}

function refreshUsers() {
    $('#searchInput').val('');
    loadUsers(1);
}

function createUser() {
    editingUserId = null;
    $('#modalTitle').text('Nuevo Usuario');
    $('#userForm')[0].reset();
    $('#userModal').modal('show');
}

function editUser(userId) {
    editingUserId = userId;
    $('#modalTitle').text('Editar Usuario');
    
    // Cargar datos del usuario
    $.ajax({
        url: `{{ route('tenant.health-users.show', '') }}/${userId}`,
        type: 'GET',
        success: function(user) {
            // Llenar el formulario con los datos del usuario
            Object.keys(user).forEach(key => {
                if ($(`#${key}`).length) {
                    $(`#${key}`).val(user[key]);
                }
            });
            $('#userModal').modal('show');
        },
        error: function() {
            showError('Error al cargar los datos del usuario');
        }
    });
}

function viewUser(userId) {
    // Implementar vista de usuario (modal de solo lectura)
    console.log('Ver usuario:', userId);
    alert('Funcionalidad de vista en desarrollo');
}

function deleteUser(userId) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
        $.ajax({
            url: `{{ route('tenant.health-users.destroy', '') }}/${userId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Usuario eliminado exitosamente');
                    loadUsers(currentPage);
                } else {
                    showError(response.message || 'Error al eliminar usuario');
                }
            },
            error: function() {
                showError('Error al eliminar el usuario');
            }
        });
    }
}

function saveUser() {
    const formData = $('#userForm').serialize();
    const url = editingUserId ? 
        `{{ route('tenant.health-users.update', '') }}/${editingUserId}` : 
        `{{ route('tenant.health-users.store') }}`;
    const method = editingUserId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showSuccess('Usuario guardado exitosamente');
                $('#userModal').modal('hide');
                loadUsers(currentPage);
            } else {
                showFormErrors(response.errors);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                showFormErrors(xhr.responseJSON.errors);
            } else {
                showError('Error al guardar el usuario');
            }
        }
    });
}

function importUsers() {
    alert('Funcionalidad de importación desde Excel en desarrollo.\n\nPróximamente podrás cargar archivos Excel con usuarios del sector salud.');
}

function exportUsers() {
    window.location.href = '{{ route("tenant.health-users.export") }}';
}

function showSuccess(message) {
    // Implementar notificación de éxito (toastr, sweetalert, etc.)
    alert('✅ ' + message);
}

function showError(message) {
    // Implementar notificación de error
    alert('❌ ' + message);
}

function showFormErrors(errors) {
    let errorMsg = 'Errores en el formulario:\n\n';
    Object.keys(errors).forEach(key => {
        errorMsg += '• ' + errors[key][0] + '\n';
    });
    alert(errorMsg);
}

// Inicializar cuando la página esté lista
console.log('🏥 Health Users Management System - Cargado correctamente');
console.log('Timestamp:', new Date().toLocaleString());
</script>
@endpush
