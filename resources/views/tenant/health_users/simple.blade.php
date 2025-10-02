@extends('tenant.layouts.app')

@section('title')
Usuarios Sector Salud
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Usuarios del Sector Salud</h4>
                    <button class="btn btn-primary btn-sm float-right" data-toggle="modal" data-target="#userModal" onclick="newUser()">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                </div>
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            <h5>Error al cargar usuarios:</h5>
                            <p>{{ $error }}</p>
                        </div>
                    @endif
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->documento }}</td>
                                    <td>{{ $user->nombre_completo ?: ($user->primer_nombre . ' ' . $user->primer_apellido) }}</td>
                                    <td>{{ $user->email ?: '-' }}</td>
                                    <td>{{ $user->telefono ?: '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editUser({{ $user->id }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser({{ $user->id }})" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No hay usuarios registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form id="userForm">
                @csrf
                <input type="hidden" id="userId" name="id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Usuario del Sector Salud</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="userTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">
                                <i class="fas fa-user"></i> Datos Personales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="salud-tab" data-toggle="tab" href="#salud" role="tab">
                                <i class="fas fa-heartbeat"></i> Información de Salud
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="procedimientos-tab" data-toggle="tab" href="#procedimientos" role="tab">
                                <i class="fas fa-stethoscope"></i> Procedimientos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="financiero-tab" data-toggle="tab" href="#financiero" role="tab">
                                <i class="fas fa-dollar-sign"></i> Información Financiera
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="prestador-tab" data-toggle="tab" href="#prestador" role="tab">
                                <i class="fas fa-hospital"></i> Prestador
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content mt-3">
                        <!-- Datos Personales -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="documento">Documento <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="documento" name="documento" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="tipo_documento">Tipo Documento</label>
                                        <select class="form-control" id="tipo_documento" name="tipo_documento">
                                            <option value="CC">Cédula de Ciudadanía</option>
                                            <option value="TI">Tarjeta de Identidad</option>
                                            <option value="RC">Registro Civil</option>
                                            <option value="CE">Cédula de Extranjería</option>
                                            <option value="PA">Pasaporte</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="primer_nombre">Primer Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="segundo_nombre">Segundo Nombre</label>
                                        <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="primer_apellido">Primer Apellido <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="segundo_apellido">Segundo Apellido</label>
                                        <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="celular">Celular</label>
                                        <input type="text" class="form-control" id="celular" name="celular">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="direccion">Dirección</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="1"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fecha_nacimiento">Fecha Nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="edad">Edad</label>
                                        <input type="number" class="form-control" id="edad" name="edad" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="estado_civil">Estado Civil</label>
                                        <select class="form-control" id="estado_civil" name="estado_civil">
                                            <option value="">Seleccionar</option>
                                            <option value="S">Soltero(a)</option>
                                            <option value="C">Casado(a)</option>
                                            <option value="U">Unión Libre</option>
                                            <option value="D">Divorciado(a)</option>
                                            <option value="V">Viudo(a)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="zona">Zona</label>
                                        <select class="form-control" id="zona" name="zona">
                                            <option value="">Seleccionar</option>
                                            <option value="U">Urbana</option>
                                            <option value="R">Rural</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="departamento">Departamento</label>
                                        <input type="text" class="form-control" id="departamento" name="departamento">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="municipio">Municipio</label>
                                        <input type="text" class="form-control" id="municipio" name="municipio">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Salud -->
                        <div class="tab-pane fade" id="salud" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="eps_codigo">Código EPS</label>
                                        <input type="text" class="form-control" id="eps_codigo" name="eps_codigo">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="eps_nombre">Nombre EPS</label>
                                        <input type="text" class="form-control" id="eps_nombre" name="eps_nombre">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="tipo_afiliacion">Tipo Afiliación</label>
                                        <select class="form-control" id="tipo_afiliacion" name="tipo_afiliacion">
                                            <option value="">Seleccionar</option>
                                            <option value="C">Cotizante</option>
                                            <option value="B">Beneficiario</option>
                                            <option value="A">Adicional</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="regimen">Régimen</label>
                                        <select class="form-control" id="regimen" name="regimen">
                                            <option value="">Seleccionar</option>
                                            <option value="S">Subsidiado</option>
                                            <option value="C">Contributivo</option>
                                            <option value="E">Especial</option>
                                            <option value="P">Particular</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="grupo_poblacional">Grupo Poblacional</label>
                                        <input type="text" class="form-control" id="grupo_poblacional" name="grupo_poblacional">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="nivel_sisben">Nivel SISBEN</label>
                                        <input type="text" class="form-control" id="nivel_sisben" name="nivel_sisben">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" class="form-check-input" id="discapacidad" name="discapacidad">
                                            <label class="form-check-label" for="discapacidad">Discapacidad</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group" id="tipo_discapacidad_group" style="display: none;">
                                        <label for="tipo_discapacidad">Tipo de Discapacidad</label>
                                        <input type="text" class="form-control" id="tipo_discapacidad" name="tipo_discapacidad">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Procedimientos -->
                        <div class="tab-pane fade" id="procedimientos" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="codigo_cups">Código CUPS</label>
                                        <input type="text" class="form-control" id="codigo_cups" name="codigo_cups">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="descripcion_procedimiento">Descripción Procedimiento</label>
                                        <input type="text" class="form-control" id="descripcion_procedimiento" name="descripcion_procedimiento">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="cie10">Código CIE-10</label>
                                        <input type="text" class="form-control" id="cie10" name="cie10">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="descripcion_diagnostico">Descripción Diagnóstico</label>
                                        <input type="text" class="form-control" id="descripcion_diagnostico" name="descripcion_diagnostico">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información Financiera -->
                        <div class="tab-pane fade" id="financiero" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="valor_procedimiento">Valor Procedimiento</label>
                                        <input type="number" step="0.01" class="form-control" id="valor_procedimiento" name="valor_procedimiento">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="copago">Copago</label>
                                        <input type="number" step="0.01" class="form-control" id="copago" name="copago">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="cuota_moderadora">Cuota Moderadora</label>
                                        <input type="number" step="0.01" class="form-control" id="cuota_moderadora" name="cuota_moderadora">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="valor_neto">Valor Neto</label>
                                        <input type="number" step="0.01" class="form-control" id="valor_neto" name="valor_neto" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="retencion_fuente">Retención en la Fuente</label>
                                        <input type="number" step="0.01" class="form-control" id="retencion_fuente" name="retencion_fuente">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="retencion_ica">Retención ICA</label>
                                        <input type="number" step="0.01" class="form-control" id="retencion_ica" name="retencion_ica">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="retencion_cree">Retención CREE</label>
                                        <input type="number" step="0.01" class="form-control" id="retencion_cree" name="retencion_cree">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Prestador -->
                        <div class="tab-pane fade" id="prestador" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="prestador_codigo">Código Prestador</label>
                                        <input type="text" class="form-control" id="prestador_codigo" name="prestador_codigo">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="prestador_nombre">Nombre Prestador</label>
                                        <input type="text" class="form-control" id="prestador_nombre" name="prestador_nombre">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profesional_tratante">Profesional Tratante</label>
                                        <input type="text" class="form-control" id="profesional_tratante" name="profesional_tratante">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="registro_profesional">Registro Profesional</label>
                                        <input type="text" class="form-control" id="registro_profesional" name="registro_profesional">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fecha_atencion">Fecha Atención</label>
                                        <input type="datetime-local" class="form-control" id="fecha_atencion" name="fecha_atencion">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modalidad_atencion">Modalidad Atención</label>
                                        <input type="text" class="form-control" id="modalidad_atencion" name="modalidad_atencion">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="finalidad_consulta">Finalidad Consulta</label>
                                        <input type="text" class="form-control" id="finalidad_consulta" name="finalidad_consulta">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="numero_autorizacion">Número Autorización</label>
                                        <input type="number" class="form-control" id="numero_autorizacion" name="numero_autorizacion">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-10">
                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                                            <label class="form-check-label" for="activo">Usuario Activo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function newUser() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').textContent = 'Nuevo Usuario del Sector Salud';
    // Activar el primer tab
    $('#personal-tab').tab('show');
    // Usuario activo por defecto
    document.getElementById('activo').checked = true;
    $('#userModal').modal('show');
}

function editUser(id) {
    fetch(`{{ url('/health-users') }}/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                const user = data.user;
                
                // Llenar todos los campos
                document.getElementById('userId').value = user.id;
                document.getElementById('documento').value = user.documento || '';
                document.getElementById('tipo_documento').value = user.tipo_documento || 'CC';
                document.getElementById('primer_nombre').value = user.primer_nombre || '';
                document.getElementById('segundo_nombre').value = user.segundo_nombre || '';
                document.getElementById('primer_apellido').value = user.primer_apellido || '';
                document.getElementById('segundo_apellido').value = user.segundo_apellido || '';
                document.getElementById('telefono').value = user.telefono || '';
                document.getElementById('celular').value = user.celular || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('direccion').value = user.direccion || '';
                
                // Fecha y datos demográficos
                document.getElementById('fecha_nacimiento').value = user.fecha_nacimiento || '';
                document.getElementById('edad').value = user.edad || '';
                document.getElementById('genero').value = user.genero || '';
                document.getElementById('estado_civil').value = user.estado_civil || '';
                document.getElementById('departamento').value = user.departamento || '';
                document.getElementById('municipio').value = user.municipio || '';
                document.getElementById('zona').value = user.zona || '';
                
                // Información de salud
                document.getElementById('eps_codigo').value = user.eps_codigo || '';
                document.getElementById('eps_nombre').value = user.eps_nombre || '';
                document.getElementById('tipo_afiliacion').value = user.tipo_afiliacion || '';
                document.getElementById('regimen').value = user.regimen || '';
                document.getElementById('grupo_poblacional').value = user.grupo_poblacional || '';
                document.getElementById('nivel_sisben').value = user.nivel_sisben || '';
                document.getElementById('discapacidad').checked = user.discapacidad == 1;
                document.getElementById('tipo_discapacidad').value = user.tipo_discapacidad || '';
                
                // Procedimientos
                document.getElementById('codigo_cups').value = user.codigo_cups || '';
                document.getElementById('descripcion_procedimiento').value = user.descripcion_procedimiento || '';
                document.getElementById('cie10').value = user.cie10 || '';
                document.getElementById('descripcion_diagnostico').value = user.descripcion_diagnostico || '';
                
                // Información financiera
                document.getElementById('valor_procedimiento').value = user.valor_procedimiento || '';
                document.getElementById('copago').value = user.copago || '';
                document.getElementById('cuota_moderadora').value = user.cuota_moderadora || '';
                document.getElementById('valor_neto').value = user.valor_neto || '';
                document.getElementById('retencion_fuente').value = user.retencion_fuente || '';
                document.getElementById('retencion_ica').value = user.retencion_ica || '';
                document.getElementById('retencion_cree').value = user.retencion_cree || '';
                
                // Prestador
                document.getElementById('prestador_codigo').value = user.prestador_codigo || '';
                document.getElementById('prestador_nombre').value = user.prestador_nombre || '';
                document.getElementById('profesional_tratante').value = user.profesional_tratante || '';
                document.getElementById('registro_profesional').value = user.registro_profesional || '';
                document.getElementById('fecha_atencion').value = user.fecha_atencion ? user.fecha_atencion.substring(0, 16) : '';
                document.getElementById('modalidad_atencion').value = user.modalidad_atencion || '';
                document.getElementById('finalidad_consulta').value = user.finalidad_consulta || '';
                document.getElementById('numero_autorizacion').value = user.numero_autorizacion || '';
                document.getElementById('observaciones').value = user.observaciones || '';
                document.getElementById('activo').checked = user.activo == 1;
                
                // Cambiar título y mostrar modal
                document.getElementById('modalTitle').textContent = 'Editar Usuario del Sector Salud';
                $('#personal-tab').tab('show');
                toggleDiscapacidadField();
                $('#userModal').modal('show');
            } else {
                alert('Error al cargar los datos del usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
}

function saveUser() {
    const userId = document.getElementById('userId').value;
    const isEditing = userId !== '';
    
    // Validaciones básicas
    if (!document.getElementById('documento').value.trim()) {
        alert('El documento es obligatorio');
        $('#personal-tab').tab('show');
        document.getElementById('documento').focus();
        return;
    }
    
    if (!document.getElementById('primer_nombre').value.trim()) {
        alert('El primer nombre es obligatorio');
        $('#personal-tab').tab('show');
        document.getElementById('primer_nombre').focus();
        return;
    }
    
    if (!document.getElementById('primer_apellido').value.trim()) {
        alert('El primer apellido es obligatorio');
        $('#personal-tab').tab('show');
        document.getElementById('primer_apellido').focus();
        return;
    }
    
    const formData = new FormData(document.getElementById('userForm'));
    const url = isEditing ? `{{ url('/health-users') }}/${userId}` : '{{ route("tenant.health-users.store") }}';
    const method = isEditing ? 'PUT' : 'POST';
    
    // Convertir FormData a objeto para PUT
    let body = formData;
    if (method === 'PUT') {
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        body = JSON.stringify(data);
    }
    
    const headers = {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
    };
    
    if (method === 'PUT') {
        headers['Content-Type'] = 'application/json';
    }
    
    fetch(url, {
        method: method,
        headers: headers,
        body: body
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#userModal').modal('hide');
            alert(data.message || 'Usuario guardado exitosamente');
            location.reload(); // Recargar la página para mostrar los cambios
        } else {
            if (data.errors) {
                let errorMessage = 'Errores de validación:\n';
                Object.values(data.errors).forEach(errors => {
                    if (Array.isArray(errors)) {
                        errors.forEach(error => errorMessage += '- ' + error + '\n');
                    } else {
                        errorMessage += '- ' + errors + '\n';
                    }
                });
                alert(errorMessage);
            } else {
                alert(data.message || 'Error al guardar usuario');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function deleteUser(id) {
    if (confirm('¿Está seguro de eliminar este usuario?')) {
        fetch(`{{ url('/health-users') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Usuario eliminado exitosamente');
                location.reload();
            } else {
                alert('Error al eliminar usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }
}

// Función para calcular edad automáticamente
function calculateAge() {
    const birthDate = document.getElementById('fecha_nacimiento').value;
    if (birthDate) {
        const birth = new Date(birthDate);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        document.getElementById('edad').value = age >= 0 ? age : '';
    }
}

// Función para calcular valor neto
function calculateValorNeto() {
    const valorProcedimiento = parseFloat(document.getElementById('valor_procedimiento').value) || 0;
    const copago = parseFloat(document.getElementById('copago').value) || 0;
    const cuotaModerada = parseFloat(document.getElementById('cuota_moderadora').value) || 0;
    const retencionFuente = parseFloat(document.getElementById('retencion_fuente').value) || 0;
    const retencionIca = parseFloat(document.getElementById('retencion_ica').value) || 0;
    const retencionCree = parseFloat(document.getElementById('retencion_cree').value) || 0;

    const valorNeto = valorProcedimiento - copago - cuotaModerada - retencionFuente - retencionIca - retencionCree;
    document.getElementById('valor_neto').value = Math.max(0, valorNeto).toFixed(2);
}

// Función para mostrar/ocultar tipo de discapacidad
function toggleDiscapacidadField() {
    const discapacidad = document.getElementById('discapacidad').checked;
    const tipoGroup = document.getElementById('tipo_discapacidad_group');
    
    if (discapacidad) {
        tipoGroup.style.display = 'block';
    } else {
        tipoGroup.style.display = 'none';
        document.getElementById('tipo_discapacidad').value = '';
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Calcular edad al cambiar fecha de nacimiento
    const fechaNacElement = document.getElementById('fecha_nacimiento');
    if (fechaNacElement) {
        fechaNacElement.addEventListener('change', calculateAge);
    }
    
    // Calcular valor neto al cambiar campos financieros
    ['valor_procedimiento', 'copago', 'cuota_moderadora', 'retencion_fuente', 'retencion_ica', 'retencion_cree'].forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            element.addEventListener('input', calculateValorNeto);
        }
    });
    
    // Toggle discapacidad
    const discapacidadElement = document.getElementById('discapacidad');
    if (discapacidadElement) {
        discapacidadElement.addEventListener('change', toggleDiscapacidadField);
    }
});
</script>
@endsection
