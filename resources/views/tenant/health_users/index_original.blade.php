@extends('tenant.layouts.app')

@section('title')
Usuarios Sector Salud
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="/dashboard">Inicio</a></li>
<li class="breadcrumb-item active">Usuarios Sector Salud</li>
@endsection

@section('content')
<div class="page-header">
    <h1>🏥 Usuarios Sector Salud</h1>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Gestión de Usuarios del Sector Salud</h4>
                <div class="card-header-action">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#createUserModal">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                    <button class="btn btn-success" onclick="exportUsers()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="health-users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargan dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Usuario</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="health-user-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Número de Documento</label>
                                <input type="text" class="form-control" name="document_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Documento</label>
                                <select class="form-control" name="document_type">
                                    <option value="CC">Cédula de Ciudadanía</option>
                                    <option value="TI">Tarjeta de Identidad</option>
                                    <option value="CE">Cédula de Extranjería</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Nombre Completo</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Guardar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    loadHealthUsers();
});

function loadHealthUsers() {
    $('#health-users-table tbody').html('<tr><td colspan="6" class="text-center">Cargando...</td></tr>');
    
    fetch('/health-users/data')
        .then(response => response.json())
        .then(data => {
            let rows = '';
            if (data.data && data.data.length > 0) {
                data.data.forEach(user => {
                    rows += `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.document_number || ''}</td>
                            <td>${user.full_name || ''}</td>
                            <td>${user.email || ''}</td>
                            <td>${user.phone || ''}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editUser(${user.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                rows = '<tr><td colspan="6" class="text-center">No se encontraron usuarios</td></tr>';
            }
            $('#health-users-table tbody').html(rows);
        })
        .catch(error => {
            console.error('Error:', error);
            $('#health-users-table tbody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar datos</td></tr>');
        });
}

function saveUser() {
    const form = document.getElementById('health-user-form');
    const formData = new FormData(form);
    
    fetch('/health-users', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#createUserModal').modal('hide');
            loadHealthUsers();
            toastr.success('Usuario guardado exitosamente');
            form.reset();
        } else {
            toastr.error('Error al guardar usuario');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('Error en la conexión');
    });
}

function editUser(id) {
    fetch(`/health-users/${id}`)
        .then(response => response.json())
        .then(user => {
            // Cargar datos en el formulario
            document.querySelector('[name="document_number"]').value = user.document_number || '';
            document.querySelector('[name="document_type"]').value = user.document_type || 'CC';
            document.querySelector('[name="full_name"]').value = user.full_name || '';
            document.querySelector('[name="email"]').value = user.email || '';
            document.querySelector('[name="phone"]').value = user.phone || '';
            
            $('#createUserModal').modal('show');
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Error al cargar el usuario');
        });
}

function deleteUser(id) {
    if (confirm('¿Está seguro de eliminar este usuario?')) {
        fetch(`/health-users/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadHealthUsers();
                toastr.success('Usuario eliminado exitosamente');
            } else {
                toastr.error('Error al eliminar usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Error en la conexión');
        });
    }
}

function exportUsers() {
    window.open('/health-users/export', '_blank');
}
</script>
@endsection
