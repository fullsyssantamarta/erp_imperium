<!-- Formulario completo del usuario del sector salud -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="identificacion-tab" data-toggle="pill" href="#identificacion" role="tab">
                            <i class="fas fa-id-card"></i> Identificación
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="personal-tab" data-toggle="pill" href="#personal" role="tab">
                            <i class="fas fa-user"></i> Datos Personales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contacto-tab" data-toggle="pill" href="#contacto" role="tab">
                            <i class="fas fa-phone"></i> Contacto
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ubicacion-tab" data-toggle="pill" href="#ubicacion" role="tab">
                            <i class="fas fa-map-marker-alt"></i> Ubicación
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="eps-tab" data-toggle="pill" href="#eps" role="tab">
                            <i class="fas fa-hospital"></i> EPS/Salud
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="financiero-tab" data-toggle="pill" href="#financiero" role="tab">
                            <i class="fas fa-money-bill"></i> Financiero
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="custom-tabs-three-tabContent">
                    
                    <!-- Pestaña Identificación -->
                    <div class="tab-pane fade show active" id="identificacion" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_documento">Tipo de Documento <span class="text-danger">*</span></label>
                                    <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                        <option value="TI">Tarjeta de Identidad</option>
                                        <option value="RC">Registro Civil</option>
                                        <option value="PA">Pasaporte</option>
                                        <option value="PEP">Permiso Especial de Permanencia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="documento">Número de Documento <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="documento" name="documento" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" onclick="searchUserByDocument()">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primer_nombre">Primer Nombre <span class="text-danger">*</span></label>
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
                                    <label for="primer_apellido">Primer Apellido <span class="text-danger">*</span></label>
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
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="nombre_completo">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Datos Personales -->
                    <div class="tab-pane fade" id="personal" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edad">Edad</label>
                                    <input type="number" class="form-control" id="edad" name="edad" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="genero">Género</label>
                                    <select class="form-control" id="genero" name="genero">
                                        <option value="">Seleccionar...</option>
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
                                    <label for="estado_civil">Estado Civil</label>
                                    <select class="form-control" id="estado_civil" name="estado_civil">
                                        <option value="">Seleccionar...</option>
                                        <option value="SOLTERO">Soltero(a)</option>
                                        <option value="CASADO">Casado(a)</option>
                                        <option value="UNION_LIBRE">Unión Libre</option>
                                        <option value="DIVORCIADO">Divorciado(a)</option>
                                        <option value="VIUDO">Viudo(a)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ocupacion">Ocupación</label>
                                    <input type="text" class="form-control" id="ocupacion" name="ocupacion">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="etnia">Etnia</label>
                                    <select class="form-control" id="etnia" name="etnia">
                                        <option value="">Seleccionar...</option>
                                        <option value="MESTIZO">Mestizo</option>
                                        <option value="INDIGENA">Indígena</option>
                                        <option value="AFRODESCENDIENTE">Afrodescendiente</option>
                                        <option value="BLANCO">Blanco</option>
                                        <option value="OTRO">Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="escolaridad">Escolaridad</label>
                                    <select class="form-control" id="escolaridad" name="escolaridad">
                                        <option value="">Seleccionar...</option>
                                        <option value="SIN_ESTUDIOS">Sin Estudios</option>
                                        <option value="PRIMARIA">Primaria</option>
                                        <option value="SECUNDARIA">Secundaria</option>
                                        <option value="TECNICO">Técnico</option>
                                        <option value="TECNOLOGO">Tecnólogo</option>
                                        <option value="UNIVERSITARIO">Universitario</option>
                                        <option value="POSGRADO">Posgrado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Contacto -->
                    <div class="tab-pane fade" id="contacto" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefono">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefono_secundario">Teléfono Secundario</label>
                                    <input type="text" class="form-control" id="telefono_secundario" name="telefono_secundario">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="direccion">Dirección</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Ubicación -->
                    <div class="tab-pane fade" id="ubicacion" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="departamento">Departamento</label>
                                    <select class="form-control" id="departamento" name="departamento">
                                        <option value="">Seleccionar...</option>
                                        <option value="BOYACA">Boyacá</option>
                                        <option value="CUNDINAMARCA">Cundinamarca</option>
                                        <option value="BOGOTA">Bogotá D.C.</option>
                                        <option value="ANTIOQUIA">Antioquia</option>
                                        <option value="VALLE">Valle del Cauca</option>
                                        <option value="SANTANDER">Santander</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="municipio">Municipio</label>
                                    <input type="text" class="form-control" id="municipio" name="municipio">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zona">Zona</label>
                                    <select class="form-control" id="zona" name="zona">
                                        <option value="">Seleccionar...</option>
                                        <option value="URBANA">Urbana</option>
                                        <option value="RURAL">Rural</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="codigo_postal">Código Postal</label>
                                    <input type="text" class="form-control" id="codigo_postal" name="codigo_postal">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña EPS/Salud -->
                    <div class="tab-pane fade" id="eps" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eps_codigo">Código EPS</label>
                                    <input type="text" class="form-control" id="eps_codigo" name="eps_codigo">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eps_nombre">Nombre EPS <span class="text-danger">*</span></label>
                                    <select class="form-control" id="eps_nombre" name="eps_nombre" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="SURA">SURA EPS</option>
                                        <option value="SANITAS">Sanitas EPS</option>
                                        <option value="FAMISANAR">Famisanar EPS</option>
                                        <option value="COMPENSAR">Compensar EPS</option>
                                        <option value="NUEVA EPS">Nueva EPS</option>
                                        <option value="SALUD TOTAL">Salud Total EPS</option>
                                        <option value="COOMEVA">Coomeva EPS</option>
                                        <option value="ALIANSALUD">Aliansalud EPS</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="regimen">Régimen</label>
                                    <select class="form-control" id="regimen" name="regimen">
                                        <option value="">Seleccionar...</option>
                                        <option value="CONTRIBUTIVO">Contributivo</option>
                                        <option value="SUBSIDIADO">Subsidiado</option>
                                        <option value="ESPECIAL">Especial</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nivel">Nivel</label>
                                    <select class="form-control" id="nivel" name="nivel">
                                        <option value="">Seleccionar...</option>
                                        <option value="1">Nivel 1</option>
                                        <option value="2">Nivel 2</option>
                                        <option value="3">Nivel 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="categoria">Categoría</label>
                                    <select class="form-control" id="categoria" name="categoria">
                                        <option value="">Seleccionar...</option>
                                        <option value="A">Categoría A</option>
                                        <option value="B">Categoría B</option>
                                        <option value="C">Categoría C</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="observaciones_salud">Observaciones de Salud</label>
                                    <textarea class="form-control" id="observaciones_salud" name="observaciones_salud" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Financiero -->
                    <div class="tab-pane fade" id="financiero" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="valor_consulta">Valor Consulta</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="valor_consulta" name="valor_consulta" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="copago">Copago</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="copago" name="copago" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cuota_moderadora">Cuota Moderadora</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="cuota_moderadora" name="cuota_moderadora" step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="codigo_factura">Código de Factura</label>
                                    <input type="text" class="form-control" id="codigo_factura" name="codigo_factura">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="consecutivo">Consecutivo</label>
                                    <input type="text" class="form-control" id="consecutivo" name="consecutivo">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fecha_consulta">Fecha de Consulta</label>
                                    <input type="datetime-local" class="form-control" id="fecha_consulta" name="fecha_consulta">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="medico_tratante">Médico Tratante</label>
                                    <input type="text" class="form-control" id="medico_tratante" name="medico_tratante">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="diagnostico">Diagnóstico</label>
                                    <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Construir nombre completo automáticamente
function buildFullName() {
    let names = [
        $('#primer_nombre').val(),
        $('#segundo_nombre').val(),
        $('#primer_apellido').val(),
        $('#segundo_apellido').val()
    ].filter(name => name && name.trim() !== '');
    
    $('#nombre_completo').val(names.join(' '));
}

// Calcular edad automáticamente
function calculateAge() {
    let birthDate = $('#fecha_nacimiento').val();
    if (birthDate) {
        let today = new Date();
        let birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        let monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        $('#edad').val(age);
    }
}

// Buscar usuario por documento
function searchUserByDocument() {
    let documento = $('#documento').val();
    if (documento && documento.length >= 5) {
        $.ajax({
            url: `/health-users/find-user-by-document?document=${documento}`,
            method: 'GET',
            success: function(user) {
                if (user && user.id) {
                    // Poblar formulario con datos encontrados
                    populateFormWithUser(user);
                    showAlert('Usuario encontrado y cargado', 'success');
                } else {
                    showAlert('Usuario no encontrado', 'info');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showAlert('Usuario no encontrado', 'info');
                } else {
                    showAlert('Error buscando usuario', 'error');
                }
            }
        });
    }
}

// Poblar formulario con datos del usuario
function populateFormWithUser(user) {
    Object.keys(user).forEach(function(key) {
        let field = $(`#${key}`);
        if(field.length > 0) {
            if (field.is('select')) {
                field.val(user[key]).trigger('change');
            } else {
                field.val(user[key]);
            }
        }
    });
    
    // Construir nombre completo si es necesario
    buildFullName();
    
    // Calcular edad si hay fecha
    if (user.fecha_nacimiento) {
        calculateAge();
    }
}

// Event listeners
$(document).ready(function() {
    // Construir nombre completo
    $('#primer_nombre, #segundo_nombre, #primer_apellido, #segundo_apellido').on('keyup change', buildFullName);
    
    // Calcular edad
    $('#fecha_nacimiento').on('change', calculateAge);
    
    // Buscar por documento al presionar Enter
    $('#documento').on('keypress', function(e) {
        if (e.which === 13) {
            searchUserByDocument();
        }
    });
});
</script>
