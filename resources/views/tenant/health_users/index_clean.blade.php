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
                    <button class="btn btn-primary" data-toggle="modal" data-target="#userModal" onclick="resetForm()">
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

<!-- Modal para Crear/Editar Usuario Completo -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">
                    <i class="fas fa-user-md"></i> <span id="modalTitle">Nuevo Usuario del Sector Salud</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="userForm">
                @csrf
                <div class="modal-body">
                    <!-- Navegación por tabs -->
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
                            <a class="nav-link" id="procedure-tab" data-toggle="tab" href="#procedure" role="tab">
                                <i class="fas fa-stethoscope"></i> Procedimientos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="billing-tab" data-toggle="tab" href="#billing" role="tab">
                                <i class="fas fa-dollar-sign"></i> Facturación
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="userTabContent">
                        <!-- Tab 1: Datos Personales -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="mt-3">
                                <h6 class="text-primary"><i class="fas fa-id-card"></i> Identificación</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="documento">{{ __('Documento') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="documento" id="documento" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tipo_documento">{{ __('Tipo Documento') }}</label>
                                            <select class="form-control" name="tipo_documento" id="tipo_documento">
                                                <option value="CC">Cédula de Ciudadanía</option>
                                                <option value="TI">Tarjeta de Identidad</option>
                                                <option value="CE">Cédula de Extranjería</option>
                                                <option value="PP">Pasaporte</option>
                                                <option value="RC">Registro Civil</option>
                                                <option value="AS">Adulto sin Identificación</option>
                                                <option value="MS">Menor sin Identificación</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="edad">{{ __('Edad') }}</label>
                                            <input type="number" class="form-control" name="edad" id="edad" min="0" max="120">
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary mt-3"><i class="fas fa-user-tag"></i> Nombres y Apellidos</h6>
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

                                <h6 class="text-primary mt-3"><i class="fas fa-info-circle"></i> Información Personal</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fecha_nacimiento">{{ __('Fecha de Nacimiento') }}</label>
                                            <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="genero">{{ __('Género') }}</label>
                                            <select class="form-control" name="genero" id="genero">
                                                <option value="">Seleccionar...</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                                <option value="O">Otro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estado_civil">{{ __('Estado Civil') }}</label>
                                            <select class="form-control" name="estado_civil" id="estado_civil">
                                                <option value="">Seleccionar...</option>
                                                <option value="S">Soltero(a)</option>
                                                <option value="C">Casado(a)</option>
                                                <option value="U">Unión Libre</option>
                                                <option value="D">Divorciado(a)</option>
                                                <option value="V">Viudo(a)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary mt-3"><i class="fas fa-phone"></i> Contacto y Ubicación</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="telefono">{{ __('Teléfono') }}</label>
                                            <input type="text" class="form-control" name="telefono" id="telefono">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="celular">{{ __('Celular') }}</label>
                                            <input type="text" class="form-control" name="celular" id="celular">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="email">{{ __('Email') }}</label>
                                            <input type="email" class="form-control" name="email" id="email">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="direccion">{{ __('Dirección') }}</label>
                                            <input type="text" class="form-control" name="direccion" id="direccion">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="departamento">{{ __('Departamento') }}</label>
                                            <input type="text" class="form-control" name="departamento" id="departamento">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="municipio">{{ __('Municipio') }}</label>
                                            <input type="text" class="form-control" name="municipio" id="municipio">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="zona">{{ __('Zona') }}</label>
                                            <select class="form-control" name="zona" id="zona">
                                                <option value="">Seleccionar...</option>
                                                <option value="U">Urbana</option>
                                                <option value="R">Rural</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Información de Salud -->
                        <div class="tab-pane fade" id="health" role="tabpanel">
                            <div class="mt-3">
                                <h6 class="text-primary"><i class="fas fa-hospital"></i> EPS y Afiliación</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="eps_codigo">{{ __('Código EPS') }}</label>
                                            <input type="text" class="form-control" name="eps_codigo" id="eps_codigo">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="eps_nombre">{{ __('Nombre EPS') }}</label>
                                            <input type="text" class="form-control" name="eps_nombre" id="eps_nombre">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tipo_afiliacion">{{ __('Tipo de Afiliación') }}</label>
                                            <select class="form-control" name="tipo_afiliacion" id="tipo_afiliacion">
                                                <option value="">Seleccionar...</option>
                                                <option value="C">Cotizante</option>
                                                <option value="B">Beneficiario</option>
                                                <option value="A">Adicional</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="regimen">{{ __('Régimen') }}</label>
                                            <select class="form-control" name="regimen" id="regimen">
                                                <option value="">Seleccionar...</option>
                                                <option value="S">Subsidiado</option>
                                                <option value="C">Contributivo</option>
                                                <option value="E">Especial</option>
                                                <option value="P">Excepción</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="nivel_sisben">{{ __('Nivel SISBEN') }}</label>
                                            <input type="text" class="form-control" name="nivel_sisben" id="nivel_sisben">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="grupo_poblacional">{{ __('Grupo Poblacional') }}</label>
                                            <select class="form-control" name="grupo_poblacional" id="grupo_poblacional">
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Población general</option>
                                                <option value="2">Desplazados</option>
                                                <option value="3">Víctimas de violencia</option>
                                                <option value="4">Adulto mayor</option>
                                                <option value="5">Discapacitados</option>
                                                <option value="6">Población indígena</option>
                                                <option value="7">Población gitana</option>
                                                <option value="8">Población afrocolombiana</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="discapacidad" id="discapacidad" value="1">
                                                <label class="form-check-label" for="discapacidad">
                                                    {{ __('Presenta Discapacidad') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="tipo_discapacidad">{{ __('Tipo de Discapacidad') }}</label>
                                            <input type="text" class="form-control" name="tipo_discapacidad" id="tipo_discapacidad">
                                            <small class="form-text text-muted">Solo si presenta discapacidad</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Procedimientos -->
                        <div class="tab-pane fade" id="procedure" role="tabpanel">
                            <div class="mt-3">
                                <h6 class="text-primary"><i class="fas fa-procedures"></i> Procedimientos y Diagnósticos</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="codigo_cups">{{ __('Código CUPS') }}</label>
                                            <input type="text" class="form-control" name="codigo_cups" id="codigo_cups">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cie10">{{ __('Código CIE-10') }}</label>
                                            <input type="text" class="form-control" name="cie10" id="cie10">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descripcion_procedimiento">{{ __('Descripción del Procedimiento') }}</label>
                                            <textarea class="form-control" name="descripcion_procedimiento" id="descripcion_procedimiento" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descripcion_diagnostico">{{ __('Descripción del Diagnóstico') }}</label>
                                            <textarea class="form-control" name="descripcion_diagnostico" id="descripcion_diagnostico" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary mt-3"><i class="fas fa-user-md"></i> Prestador de Servicios</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="prestador_codigo">{{ __('Código Prestador') }}</label>
                                            <input type="text" class="form-control" name="prestador_codigo" id="prestador_codigo">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="prestador_nombre">{{ __('Nombre Prestador') }}</label>
                                            <input type="text" class="form-control" name="prestador_nombre" id="prestador_nombre">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="profesional_tratante">{{ __('Profesional Tratante') }}</label>
                                            <input type="text" class="form-control" name="profesional_tratante" id="profesional_tratante">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="registro_profesional">{{ __('Registro Profesional') }}</label>
                                            <input type="text" class="form-control" name="registro_profesional" id="registro_profesional">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_atencion">{{ __('Fecha de Atención') }}</label>
                                            <input type="datetime-local" class="form-control" name="fecha_atencion" id="fecha_atencion">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="numero_autorizacion">{{ __('Número de Autorización') }}</label>
                                            <input type="text" class="form-control" name="numero_autorizacion" id="numero_autorizacion">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="modalidad_atencion">{{ __('Modalidad de Atención') }}</label>
                                            <select class="form-control" name="modalidad_atencion" id="modalidad_atencion">
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Intramural</option>
                                                <option value="2">Extramural</option>
                                                <option value="3">Telemedicina</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="finalidad_consulta">{{ __('Finalidad de la Consulta') }}</label>
                                            <select class="form-control" name="finalidad_consulta" id="finalidad_consulta">
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Detección temprana - Protección específica</option>
                                                <option value="2">General</option>
                                                <option value="3">Especializada</option>
                                                <option value="4">Control</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Facturación -->
                        <div class="tab-pane fade" id="billing" role="tabpanel">
                            <div class="mt-3">
                                <h6 class="text-primary"><i class="fas fa-money-bill"></i> Valores del Procedimiento</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="valor_procedimiento">{{ __('Valor del Procedimiento') }}</label>
                                            <input type="number" class="form-control" name="valor_procedimiento" id="valor_procedimiento" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="valor_neto">{{ __('Valor Neto') }}</label>
                                            <input type="number" class="form-control" name="valor_neto" id="valor_neto" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="copago">{{ __('Copago') }}</label>
                                            <input type="number" class="form-control" name="copago" id="copago" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cuota_moderadora">{{ __('Cuota Moderadora') }}</label>
                                            <input type="number" class="form-control" name="cuota_moderadora" id="cuota_moderadora" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary mt-3"><i class="fas fa-percentage"></i> Retenciones</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="retencion_fuente">{{ __('Retención en la Fuente') }}</label>
                                            <input type="number" class="form-control" name="retencion_fuente" id="retencion_fuente" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="retencion_ica">{{ __('Retención ICA') }}</label>
                                            <input type="number" class="form-control" name="retencion_ica" id="retencion_ica" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="retencion_cree">{{ __('Retención CREE') }}</label>
                                            <input type="number" class="form-control" name="retencion_cree" id="retencion_cree" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary mt-3"><i class="fas fa-clipboard-list"></i> Información Adicional</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="origen_dato">{{ __('Origen del Dato') }}</label>
                                            <select class="form-control" name="origen_dato" id="origen_dato">
                                                <option value="manual">Manual</option>
                                                <option value="excel">Importación Excel</option>
                                                <option value="api">API Externa</option>
                                                <option value="migracion">Migración</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                                                <label class="form-check-label" for="activo">
                                                    {{ __('Usuario Activo') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">{{ __('Observaciones') }}</label>
                                            <textarea class="form-control" name="observaciones" id="observaciones" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> {{ __('Cancelar') }}
                    </button>
                    <button type="button" class="btn btn-info" onclick="calculateAge()">
                        <i class="fas fa-calculator"></i> {{ __('Calcular Edad') }}
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

@section('scripts')
<script>
let currentUserId = null;

$(document).ready(function() {
    loadHealthUsers();
});

function loadHealthUsers() {
    $('#health-users-table tbody').html('<tr><td colspan="6" class="text-center">Cargando...</td></tr>');
    
    fetch('{{ route("tenant.health-users.data") }}')
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos:', data);
            let rows = '';
            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(user => {
                    rows += `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.documento}</td>
                            <td>${user.nombre_completo || 'Sin nombre'}</td>
                            <td>${user.email || '-'}</td>
                            <td>${user.telefono || '-'}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editUser(${user.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                rows = '<tr><td colspan="6" class="text-center">No hay usuarios registrados</td></tr>';
            }
            $('#health-users-table tbody').html(rows);
        })
        .catch(error => {
            console.error('Error:', error);
            $('#health-users-table tbody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar datos</td></tr>');
        });
}

function saveUser() {
    if (!validateForm()) {
        return;
    }
    
    const form = document.getElementById('health-user-form');
    const formData = new FormData(form);
    
    // Determinar si es creación o edición
    const isEditing = currentUserId !== null;
    const url = isEditing ? `{{ url('/health-users') }}/${currentUserId}` : '{{ route("tenant.health-users.store") }}';
    const method = isEditing ? 'PUT' : 'POST';
    
    // Si es edición con PUT, convertir FormData a datos regulares
    let requestData = formData;
    if (method === 'PUT') {
        requestData = {};
        for (let [key, value] of formData.entries()) {
            requestData[key] = value;
        }
        requestData = JSON.stringify(requestData);
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
        body: requestData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#userModal').modal('hide');
            loadHealthUsers();
            toastr.success(data.message || (isEditing ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente'));
            resetForm();
            currentUserId = null;
        } else {
            if (data.errors) {
                let errorMessages = [];
                Object.values(data.errors).forEach(fieldErrors => {
                    if (Array.isArray(fieldErrors)) {
                        errorMessages.push(...fieldErrors);
                    } else {
                        errorMessages.push(fieldErrors);
                    }
                });
                toastr.error('Errores de validación: ' + errorMessages.join(', '));
            } else {
                toastr.error(data.message || 'Error al guardar usuario');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('Error en la conexión');
    });
}

function editUser(id) {
    fetch(`{{ url('/health-users') }}/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                const user = data.user;
                
                // Datos Personales
                $('#documento').val(user.documento || '');
                $('#tipo_documento').val(user.tipo_documento || 'CC');
                $('#primer_nombre').val(user.primer_nombre || '');
                $('#segundo_nombre').val(user.segundo_nombre || '');
                $('#primer_apellido').val(user.primer_apellido || '');
                $('#segundo_apellido').val(user.segundo_apellido || '');
                $('#nombre_completo').val(user.nombre_completo || '');
                $('#telefono').val(user.telefono || '');
                $('#celular').val(user.celular || '');
                $('#email').val(user.email || '');
                $('#direccion').val(user.direccion || '');
                $('#fecha_nacimiento').val(user.fecha_nacimiento || '');
                $('#edad').val(user.edad || '');
                $('#genero').val(user.genero || '');
                $('#estado_civil').val(user.estado_civil || '');
                $('#departamento').val(user.departamento || '');
                $('#municipio').val(user.municipio || '');
                $('#zona').val(user.zona || '');

                // Información de Salud
                $('#eps_codigo').val(user.eps_codigo || '');
                $('#eps_nombre').val(user.eps_nombre || '');
                $('#tipo_afiliacion').val(user.tipo_afiliacion || '');
                $('#regimen').val(user.regimen || '');
                $('#grupo_poblacional').val(user.grupo_poblacional || '');
                $('#nivel_sisben').val(user.nivel_sisben || '');
                $('#discapacidad').prop('checked', user.discapacidad === 1 || user.discapacidad === true);
                $('#tipo_discapacidad').val(user.tipo_discapacidad || '');

                // Procedimientos
                $('#codigo_cups').val(user.codigo_cups || '');
                $('#descripcion_procedimiento').val(user.descripcion_procedimiento || '');
                $('#cie10').val(user.cie10 || '');
                $('#descripcion_diagnostico').val(user.descripcion_diagnostico || '');

                // Información Financiera
                $('#valor_procedimiento').val(user.valor_procedimiento || '');
                $('#copago').val(user.copago || '');
                $('#cuota_moderadora').val(user.cuota_moderadora || '');
                $('#valor_neto').val(user.valor_neto || '');
                $('#retencion_fuente').val(user.retencion_fuente || '');
                $('#retencion_ica').val(user.retencion_ica || '');
                $('#retencion_cree').val(user.retencion_cree || '');

                // Información del Prestador
                $('#prestador_codigo').val(user.prestador_codigo || '');
                $('#prestador_nombre').val(user.prestador_nombre || '');
                $('#profesional_tratante').val(user.profesional_tratante || '');
                $('#registro_profesional').val(user.registro_profesional || '');
                $('#fecha_atencion').val(user.fecha_atencion || '');
                $('#modalidad_atencion').val(user.modalidad_atencion || '');
                $('#finalidad_consulta').val(user.finalidad_consulta || '');
                $('#numero_autorizacion').val(user.numero_autorizacion || '');

                // Información Administrativa
                $('#activo').prop('checked', user.activo === 1 || user.activo === true);
                $('#observaciones').val(user.observaciones || '');
                $('#origen_dato').val(user.origen_dato || 'manual');

                // Configurar modal para edición
                currentUserId = user.id;
                $('#userModalLabel').text('Editar Usuario del Sector Salud');
                $('#userModal').modal('show');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Error al cargar el usuario para edición');
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

function calculateAge() {
    const fechaNacimiento = $('#fecha_nacimiento').val();
    if (fechaNacimiento) {
        const birthDate = new Date(fechaNacimiento);
        const today = new Date();
        
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        $('#edad').val(age);
        toastr.success(`Edad calculada: ${age} años`);
    } else {
        toastr.error('Primero debe ingresar la fecha de nacimiento');
    }
}

function validateFormData() {
    const requiredFields = ['documento', 'primer_nombre', 'primer_apellido'];
    let isValid = true;
    let missingFields = [];

    requiredFields.forEach(field => {
        const value = $(`#${field}`).val();
        if (!value || value.trim() === '') {
            isValid = false;
            missingFields.push($(`label[for="${field}"]`).text().replace(' *', ''));
            $(`#${field}`).addClass('is-invalid');
        } else {
            $(`#${field}`).removeClass('is-invalid');
        }
    });

    if (!isValid) {
        toastr.error(`Los siguientes campos son obligatorios: ${missingFields.join(', ')}`);
        // Ir al primer tab si hay errores en datos personales
        $('#personal-tab').tab('show');
    }

    return isValid;
}

function resetForm() {
    $('#health-user-form')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    // Volver al primer tab
    $('#personal-tab').tab('show');
    // Marcar usuario como activo por defecto
    $('#activo').prop('checked', true);
    // Establecer origen como manual por defecto
    $('#origen_dato').val('manual');
    // Limpiar el ID del usuario actual
    currentUserId = null;
    // Cambiar el título del modal a crear
    $('#userModalLabel').text('Crear Usuario del Sector Salud');
}

// Event listeners adicionales para el formulario completo
$(document).ready(function() {
    // Auto-calcular edad cuando cambie la fecha de nacimiento
    $('#fecha_nacimiento').on('change', function() {
        if ($(this).val()) {
            calculateAge();
        }
    });

    // Habilitar/deshabilitar tipo de discapacidad según checkbox
    $('#discapacidad').on('change', function() {
        if ($(this).is(':checked')) {
            $('#tipo_discapacidad').prop('disabled', false);
        } else {
            $('#tipo_discapacidad').prop('disabled', true).val('');
        }
    });

    // Auto-completar nombre completo cuando cambien los nombres
    $('#primer_nombre, #segundo_nombre, #primer_apellido, #segundo_apellido').on('blur', function() {
        const primerNombre = $('#primer_nombre').val() || '';
        const segundoNombre = $('#segundo_nombre').val() || '';
        const primerApellido = $('#primer_apellido').val() || '';
        const segundoApellido = $('#segundo_apellido').val() || '';
        
        const nombreCompleto = [primerNombre, segundoNombre, primerApellido, segundoApellido]
            .filter(name => name.trim() !== '')
            .join(' ');
        
        // Actualizar campo oculto si existe
        if ($('#nombre_completo').length) {
            $('#nombre_completo').val(nombreCompleto);
        }
    });

    // Funciones auxiliares para cálculos automáticos
    function calculateAge() {
        const fechaNacimiento = $('#fecha_nacimiento').val();
        if (fechaNacimiento) {
            const birth = new Date(fechaNacimiento);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            $('#edad').val(age >= 0 ? age : '');
        }
    }

    function calculateValorNeto() {
        const valorProcedimiento = parseFloat($('#valor_procedimiento').val()) || 0;
        const copago = parseFloat($('#copago').val()) || 0;
        const cuotaModerada = parseFloat($('#cuota_moderadora').val()) || 0;
        const retencionFuente = parseFloat($('#retencion_fuente').val()) || 0;
        const retencionIca = parseFloat($('#retencion_ica').val()) || 0;
        const retencionCree = parseFloat($('#retencion_cree').val()) || 0;

        const valorNeto = valorProcedimiento - copago - cuotaModerada - retencionFuente - retencionIca - retencionCree;
        $('#valor_neto').val(Math.max(0, valorNeto).toFixed(2));
    }

    function formatCurrency(input) {
        let value = $(input).val().replace(/[^\d.-]/g, '');
        if (value) {
            value = parseFloat(value);
            if (!isNaN(value)) {
                $(input).val(value.toFixed(2));
            }
        }
    }

    // Auto-calcular valor neto cuando cambien campos financieros
    $(document).on('input', '#valor_procedimiento, #copago, #cuota_moderadora, #retencion_fuente, #retencion_ica, #retencion_cree', function() {
        formatCurrency(this);
        calculateValorNeto();
    });

    console.log('🏥 Health Users - Formulario Completo con Tabs Inicializado');
    console.log('✅ Campos disponibles: Datos personales, información de salud, procedimientos y facturación');
    console.log('⚡ Funcionalidades: Cálculo de edad, validación, auto-completado');
});
</script>
@endsection
