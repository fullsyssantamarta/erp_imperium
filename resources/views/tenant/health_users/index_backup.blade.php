<!DOCTYPE html>
<html>
<head>
    <title>Usuarios Sector Salud - Debug</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>🏥 Usuarios Sector Salud - Sistema Funcionando</h1>
    
    <div style="background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <h3>✅ Estado del Sistema:</h3>
        <ul>
            <li><strong>Vista:</strong> health_users/index.blade.php cargada correctamente</li>
            <li><strong>Controlador:</strong> HealthUsersController funcionando</li>
            <li><strong>Base de datos:</strong> Conectada
                @if(isset($count))
                    - {{ $count }} usuarios encontrados
                @endif
            </li>
            <li><strong>Rutas:</strong> Configuradas correctamente</li>
        </ul>
    </div>

    <div style="background: #cce5ff; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <h3>🔗 Enlaces de Prueba:</h3>
        <ul>
            <li><a href="/health-users/data" target="_blank">Ver datos JSON (/health-users/data)</a></li>
            <li><a href="/health-users" target="_blank">Vista principal (/health-users)</a></li>
        </ul>
    </div>

    <div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <h3>⏭️ Próximos pasos:</h3>
        <ol>
            <li>Restaurar la vista completa con Vue.js</li>
            <li>Probar el componente health_users_ss.vue</li>
            <li>Integrar el formulario completo</li>
        </ol>
    </div>

    <script>
        console.log('Vista health_users cargada correctamente');
        console.log('Timestamp:', new Date().toLocaleString());
    </script>
</body>
</html>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span id="card_title">
                            <i class="fas fa-user-md"></i> {{ __('Usuarios Sector Salud') }}
                        </span>
                        <div class="float-right">
                            <button class="btn btn-primary btn-sm float-right" onclick="createUser()">
                                <i class="fa fa-plus"></i> {{ __('Nuevo Usuario Completo') }}
                            </button>
                            <button class="btn btn-success btn-sm float-right mr-2" onclick="importUsers()">
                                <i class="fa fa-upload"></i> {{ __('Importar Excel') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Área para el componente Vue.js -->
                    <div id="health-users-app">
                        <health-users-management></health-users-management>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario Completo -->
<div class="modal fade" id="userCompleteModal" tabindex="-1" role="dialog" aria-labelledby="userCompleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userCompleteModalLabel">
                    <i class="fas fa-user-md"></i> Gestión Completa de Usuario del Sector Salud
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <!-- Aquí se cargará el componente Vue completo -->
                <div id="health-user-form-app">
                    <health-users-s-s 
                        ref="healthUserForm"
                        @user-saved="onUserSaved"
                        @user-selected="onUserSelected">
                    </health-users-s-s>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-success" onclick="saveCompleteUser()">
                    <i class="fas fa-save"></i> Guardar Usuario
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// App principal para la gestión de usuarios
const healthUsersApp = new Vue({
    el: '#health-users-app',
    components: {
        'health-users-management': {
            template: `
                <div>
                    <!-- Filtros y búsqueda -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" 
                                       v-model="searchTerm" 
                                       @keyup.enter="searchUsers"
                                       class="form-control" 
                                       placeholder="Buscar por documento, nombre, EPS...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            @click="searchUsers">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button class="btn btn-info btn-sm" @click="exportUsers">
                                <i class="fa fa-download"></i> Exportar
                            </button>
                            <button class="btn btn-primary btn-sm ml-2" @click="refreshUsers">
                                <i class="fa fa-sync"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de usuarios -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
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
                                <tr v-if="loading">
                                    <td colspan="10" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
                                    </td>
                                </tr>
                                <tr v-else-if="users.length === 0">
                                    <td colspan="10" class="text-center">
                                        No hay usuarios registrados
                                    </td>
                                </tr>
                                <tr v-else v-for="user in users" :key="user.id">
                                    <td>{{ user.id }}</td>
                                    <td>{{ user.documento }}</td>
                                    <td>{{ user.tipo_documento }}</td>
                                    <td>{{ getFullName(user) }}</td>
                                    <td>{{ user.telefono || 'N/A' }}</td>
                                    <td>{{ user.email || 'N/A' }}</td>
                                    <td>{{ user.eps_nombre || 'N/A' }}</td>
                                    <td>{{ user.municipio || 'N/A' }}</td>
                                    <td>{{ formatDate(user.created_at) }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                @click="viewUser(user)" 
                                                title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning ml-1" 
                                                @click="editUser(user)" 
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger ml-1" 
                                                @click="deleteUser(user)" 
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="row mt-3" v-if="pagination.total > pagination.per_page">
                        <div class="col-md-6">
                            <p class="text-muted">
                                Mostrando {{ pagination.from }} a {{ pagination.to }} 
                                de {{ pagination.total }} usuarios
                            </p>
                        </div>
                        <div class="col-md-6">
                            <nav>
                                <ul class="pagination justify-content-end">
                                    <li class="page-item" :class="{disabled: !pagination.prev_page_url}">
                                        <a class="page-link" @click="changePage(pagination.current_page - 1)">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link">{{ pagination.current_page }}</span>
                                    </li>
                                    <li class="page-item" :class="{disabled: !pagination.next_page_url}">
                                        <a class="page-link" @click="changePage(pagination.current_page + 1)">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            `,
            data() {
                return {
                    users: [],
                    loading: true,
                    searchTerm: '',
                    pagination: {
                        current_page: 1,
                        per_page: 15,
                        total: 0,
                        from: 0,
                        to: 0,
                        prev_page_url: null,
                        next_page_url: null
                    }
                }
            },
            mounted() {
                this.loadUsers();
            },
            methods: {
                async loadUsers(page = 1) {
                    this.loading = true;
                    try {
                        const response = await axios.get('{{ route("tenant.health-users.data") }}', {
                            params: {
                                page: page,
                                search: this.searchTerm
                            }
                        });
                        
                        if (response.data.success) {
                            this.users = response.data.data;
                            this.pagination = response.data.pagination || this.pagination;
                        }
                    } catch (error) {
                        console.error('Error cargando usuarios:', error);
                        this.$toast.error('Error al cargar usuarios');
                    }
                    this.loading = false;
                },
                
                searchUsers() {
                    this.loadUsers(1);
                },
                
                refreshUsers() {
                    this.searchTerm = '';
                    this.loadUsers(1);
                },
                
                changePage(page) {
                    if (page > 0) {
                        this.loadUsers(page);
                    }
                },
                
                getFullName(user) {
                    const parts = [
                        user.primer_nombre,
                        user.segundo_nombre,
                        user.primer_apellido,
                        user.segundo_apellido
                    ].filter(part => part && part.trim());
                    
                    return parts.length > 0 ? parts.join(' ') : user.nombre_completo || 'Sin nombre';
                },
                
                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    return new Date(dateString).toLocaleDateString('es-CO');
                },
                
                viewUser(user) {
                    // Abrir modal con información del usuario
                    editingUser = user;
                    $('#userCompleteModal').modal('show');
                    
                    // Cargar datos en el componente Vue
                    this.$nextTick(() => {
                        if (healthUserFormApp && healthUserFormApp.$refs.healthUserForm) {
                            healthUserFormApp.$refs.healthUserForm.loadUserData(user);
                        }
                    });
                },
                
                editUser(user) {
                    this.viewUser(user);
                },
                
                deleteUser(user) {
                    if (confirm(`¿Está seguro de eliminar al usuario ${this.getFullName(user)}?`)) {
                        this.performDelete(user.id);
                    }
                },
                
                async performDelete(userId) {
                    try {
                        const response = await axios.delete(`{{ route('tenant.health-users.destroy', '') }}/${userId}`);
                        if (response.data.success) {
                            this.$toast.success('Usuario eliminado exitosamente');
                            this.loadUsers();
                        }
                    } catch (error) {
                        console.error('Error eliminando usuario:', error);
                        this.$toast.error('Error al eliminar usuario');
                    }
                },
                
                exportUsers() {
                    window.location.href = '{{ route("tenant.health-users.export") }}';
                }
            }
        }
    }
});

// App para el formulario completo
let editingUser = null;
const healthUserFormApp = new Vue({
    el: '#health-user-form-app',
    components: {
        // Aquí se registrará el componente health-users-s-s cuando esté disponible
    },
    methods: {
        onUserSaved(userData) {
            console.log('Usuario guardado:', userData);
            $('#userCompleteModal').modal('hide');
            // Recargar lista de usuarios
            if (healthUsersApp.$children[0]) {
                healthUsersApp.$children[0].loadUsers();
            }
        },
        
        onUserSelected(userData) {
            console.log('Usuario seleccionado:', userData);
        }
    }
});

// Funciones globales para compatibilidad
function createUser() {
    editingUser = null;
    $('#userCompleteModal').modal('show');
    
    // Limpiar formulario
    healthUserFormApp.$nextTick(() => {
        if (healthUserFormApp.$refs.healthUserForm) {
            healthUserFormApp.$refs.healthUserForm.resetForm();
        }
    });
}

function saveCompleteUser() {
    if (healthUserFormApp.$refs.healthUserForm) {
        healthUserFormApp.$refs.healthUserForm.saveUser();
    }
}

function importUsers() {
    alert('Funcionalidad de importación en desarrollo');
}

// Toast notifications helper
Vue.prototype.$toast = {
    success(message) {
        // Implementar notificación de éxito
        alert('✅ ' + message);
    },
    error(message) {
        // Implementar notificación de error
        alert('❌ ' + message);
    }
};
</script>
@endpush
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>EPS</th>
                                    <th>Municipio</th>
                                    <th>Fecha Registro</th>
                                    <th width="120px">Acciones</th>
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
                <h5 class="modal-title" id="userModalLabel">Crear Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="documento">Documento *</label>
                                <input type="text" class="form-control" id="documento" name="documento" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_documento">Tipo Documento *</label>
                                <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                                    <option value="">Seleccionar</option>
                                    <option value="CC">Cédula de Ciudadanía</option>
                                    <option value="CE">Cédula de Extranjería</option>
                                    <option value="TI">Tarjeta de Identidad</option>
                                    <option value="RC">Registro Civil</option>
                                    <option value="PT">Pasaporte</option>
                                    <option value="SC">Salvoconducto</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="primer_nombre">Primer Nombre *</label>
                                <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="segundo_nombre">Segundo Nombre</label>
                                <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="primer_apellido">Primer Apellido *</label>
                                <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="segundo_apellido">Segundo Apellido</label>
                                <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select class="form-control" id="genero" name="genero">
                                    <option value="">Seleccionar</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                    <option value="O">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="eps_nombre">EPS</label>
                                <input type="text" class="form-control" id="eps_nombre" name="eps_nombre">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="municipio">Municipio</label>
                                <input type="text" class="form-control" id="municipio" name="municipio">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Ver Usuario -->
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">Información del Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="userDetailsBody">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let editingUserId = null;

$(document).ready(function() {
    loadUsers();

    // Manejar envío del formulario
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
});

function loadUsers() {
    $.get("{{ route('tenant.health-users.data') }}", function(response) {
        let tbody = $('#health-users-table tbody');
        tbody.empty();
        
        if (response.data && response.data.length > 0) {
            response.data.forEach(function(user) {
                let row = `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.documento}</td>
                        <td>${user.tipo_documento}</td>
                        <td>${user.nombre_completo}</td>
                        <td>${user.telefono}</td>
                        <td>${user.email}</td>
                        <td>${user.eps_nombre}</td>
                        <td>${user.municipio}</td>
                        <td>${user.created_at}</td>
                        <td>${user.actions}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            tbody.append('<tr><td colspan="10" class="text-center">No hay usuarios registrados</td></tr>');
        }
    });
}

function createUser() {
    editingUserId = null;
    $('#userModalLabel').text('Crear Nuevo Usuario');
    $('#userForm')[0].reset();
    $('#userModal').modal('show');
}

function editUser(id) {
    editingUserId = id;
    $('#userModalLabel').text('Editar Usuario');
    
    // Cargar datos del usuario
    $.get(`{{ route('tenant.health-users.show', '') }}/${id}`, function(data) {
        Object.keys(data).forEach(key => {
            if ($(`#${key}`).length) {
                $(`#${key}`).val(data[key]);
            }
        });
        $('#userModal').modal('show');
    });
}

function viewUser(id) {
    $.get(`{{ route('tenant.health-users.show', '') }}/${id}`, function(data) {
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Documento:</strong> ${data.documento || 'N/A'}</p>
                    <p><strong>Tipo:</strong> ${data.tipo_documento || 'N/A'}</p>
                    <p><strong>Nombre:</strong> ${data.nombre_completo || 'N/A'}</p>
                    <p><strong>Teléfono:</strong> ${data.telefono || 'N/A'}</p>
                    <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha Nacimiento:</strong> ${data.fecha_nacimiento || 'N/A'}</p>
                    <p><strong>Género:</strong> ${data.genero || 'N/A'}</p>
                    <p><strong>EPS:</strong> ${data.eps_nombre || 'N/A'}</p>
                    <p><strong>Municipio:</strong> ${data.municipio || 'N/A'}</p>
                    <p><strong>Dirección:</strong> ${data.direccion || 'N/A'}</p>
                </div>
            </div>
        `;
        $('#userDetailsBody').html(html);
        $('#viewUserModal').modal('show');
    });
}

function deleteUser(id) {
    if (confirm('¿Está seguro de que desea eliminar este usuario?')) {
        $.ajax({
            url: `{{ route('tenant.health-users.destroy', '') }}/${id}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Usuario eliminado exitosamente');
                    loadUsers();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al eliminar el usuario');
            }
        });
    }
}

function saveUser() {
    let formData = $('#userForm').serialize();
    let url = editingUserId ? 
        `{{ route('tenant.health-users.update', '') }}/${editingUserId}` : 
        `{{ route('tenant.health-users.store') }}`;
    let method = editingUserId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('Usuario guardado exitosamente');
                $('#userModal').modal('hide');
                loadUsers();
            } else {
                let errors = response.errors;
                let errorMsg = '';
                Object.keys(errors).forEach(key => {
                    errorMsg += errors[key][0] + '\n';
                });
                alert('Errores:\n' + errorMsg);
            }
        },
        error: function() {
            alert('Error al guardar el usuario');
        }
    });
}

function searchUsers() {
    let term = $('#searchInput').val();
    // Implementar búsqueda simple
    console.log('Buscando: ' + term);
}

function importUsers() {
    alert('Funcionalidad de importación en desarrollo');
}

function exportUsers() {
    window.location.href = '{{ route("tenant.health-users.export") }}';
}
</script>
@endpush
