<template>
    <el-dialog 
        :title="titleDialog" 
        :visible="showDialog" 
        @open="create" 
        @close="clickClose" 
        width="80%" 
        :close-on-click-modal="false" 
        :show-close="false" 
        append-to-body
    >
        <div class="row">
            <!-- Identificación del paciente -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-id-card"></i> Información de Identificación
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-4">
                <div class="form-group" :class="{'has-danger': errors.documento}">
                    <label class="control-label">Número de Documento <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <el-input 
                            v-model="form.documento" 
                            :maxlength="20"
                            @blur="searchUserInDatabase"
                        ></el-input>
                        <div class="input-group-append">
                            <el-button 
                                type="primary" 
                                icon="el-icon-search" 
                                @click="searchUserInDatabase"
                                :disabled="!form.documento"
                                title="Buscar usuario en base de datos"
                                size="small">
                            </el-button>
                        </div>
                    </div>
                    <small class="form-control-feedback" v-if="errors.documento" v-text="errors.documento[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4">
                <div class="form-group" :class="{'has-danger': errors.tipo_documento}">
                    <label class="control-label">Tipo de Documento <span class="text-danger">*</span></label>
                    <el-select v-model="form.tipo_documento" filterable placeholder="Seleccionar">
                        <el-option label="Cédula de Ciudadanía" value="CC"></el-option>
                        <el-option label="Tarjeta de Identidad" value="TI"></el-option>
                        <el-option label="Cédula de Extranjería" value="CE"></el-option>
                        <el-option label="Pasaporte" value="PA"></el-option>
                        <el-option label="Registro Civil" value="RC"></el-option>
                        <el-option label="NIT" value="NIT"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.tipo_documento" v-text="errors.tipo_documento[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Datos personales básicos -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-user"></i> Datos Personales
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.primer_nombre}">
                    <label class="control-label">Primer Nombre <span class="text-danger">*</span></label>
                    <el-input v-model="form.primer_nombre" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.primer_nombre" v-text="errors.primer_nombre[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.segundo_nombre}">
                    <label class="control-label">Segundo Nombre</label>
                    <el-input v-model="form.segundo_nombre" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.segundo_nombre" v-text="errors.segundo_nombre[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.primer_apellido}">
                    <label class="control-label">Primer Apellido <span class="text-danger">*</span></label>
                    <el-input v-model="form.primer_apellido" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.primer_apellido" v-text="errors.primer_apellido[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.segundo_apellido}">
                    <label class="control-label">Segundo Apellido</label>
                    <el-input v-model="form.segundo_apellido" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.segundo_apellido" v-text="errors.segundo_apellido[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información demográfica -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-birthday-cake"></i> Información Demográfica
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.fecha_nacimiento}">
                    <label class="control-label">Fecha de Nacimiento</label>
                    <el-date-picker
                        v-model="form.fecha_nacimiento"
                        type="date"
                        placeholder="Seleccionar fecha"
                        format="dd/MM/yyyy"
                        value-format="yyyy-MM-dd"
                        @change="calculateAge"
                        style="width: 100%">
                    </el-date-picker>
                    <small class="form-control-feedback" v-if="errors.fecha_nacimiento" v-text="errors.fecha_nacimiento[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-3">
                <div class="form-group" :class="{'has-danger': errors.edad}">
                    <label class="control-label">Edad</label>
                    <el-input-number v-model="form.edad" :min="0" :max="120" style="width: 100%"></el-input-number>
                    <small class="form-control-feedback" v-if="errors.edad" v-text="errors.edad[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-3">
                <div class="form-group" :class="{'has-danger': errors.genero}">
                    <label class="control-label">Género</label>
                    <el-select v-model="form.genero" placeholder="Seleccionar">
                        <el-option label="Masculino" value="M"></el-option>
                        <el-option label="Femenino" value="F"></el-option>
                        <el-option label="Otro" value="O"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.genero" v-text="errors.genero[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.estado_civil}">
                    <label class="control-label">Estado Civil</label>
                    <el-select v-model="form.estado_civil" placeholder="Seleccionar">
                        <el-option label="Soltero(a)" value="SOLTERO"></el-option>
                        <el-option label="Casado(a)" value="CASADO"></el-option>
                        <el-option label="Unión Libre" value="UNION_LIBRE"></el-option>
                        <el-option label="Divorciado(a)" value="DIVORCIADO"></el-option>
                        <el-option label="Viudo(a)" value="VIUDO"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.estado_civil" v-text="errors.estado_civil[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información de contacto -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-phone"></i> Información de Contacto
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.telefono}">
                    <label class="control-label">Teléfono</label>
                    <el-input v-model="form.telefono" :maxlength="20"></el-input>
                    <small class="form-control-feedback" v-if="errors.telefono" v-text="errors.telefono[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.celular}">
                    <label class="control-label">Celular</label>
                    <el-input v-model="form.celular" :maxlength="20"></el-input>
                    <small class="form-control-feedback" v-if="errors.celular" v-text="errors.celular[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-12">
                <div class="form-group" :class="{'has-danger': errors.email}">
                    <label class="control-label">Email</label>
                    <el-input v-model="form.email" :maxlength="150"></el-input>
                    <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group" :class="{'has-danger': errors.direccion}">
                    <label class="control-label">Dirección</label>
                    <el-input 
                        v-model="form.direccion" 
                        type="textarea" 
                        :rows="2"
                        placeholder="Dirección completa"
                    ></el-input>
                    <small class="form-control-feedback" v-if="errors.direccion" v-text="errors.direccion[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información de ubicación -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-map-marker-alt"></i> Información de Ubicación
                </h5>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.departamento}">
                    <label class="control-label">Departamento</label>
                    <el-input v-model="form.departamento" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.departamento" v-text="errors.departamento[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.municipio}">
                    <label class="control-label">Municipio</label>
                    <el-input v-model="form.municipio" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.municipio" v-text="errors.municipio[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.zona}">
                    <label class="control-label">Zona</label>
                    <el-select v-model="form.zona" placeholder="Seleccionar">
                        <el-option label="Urbana" value="URBANA"></el-option>
                        <el-option label="Rural" value="RURAL"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.zona" v-text="errors.zona[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información de salud/EPS -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-hospital"></i> Información de EPS y Salud
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.eps_codigo}">
                    <label class="control-label">Código EPS</label>
                    <el-input v-model="form.eps_codigo" :maxlength="10"></el-input>
                    <small class="form-control-feedback" v-if="errors.eps_codigo" v-text="errors.eps_codigo[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.eps_nombre}">
                    <label class="control-label">Nombre EPS</label>
                    <el-input v-model="form.eps_nombre" :maxlength="200"></el-input>
                    <small class="form-control-feedback" v-if="errors.eps_nombre" v-text="errors.eps_nombre[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.tipo_afiliacion}">
                    <label class="control-label">Tipo de Afiliación</label>
                    <el-select v-model="form.tipo_afiliacion" placeholder="Seleccionar">
                        <el-option label="Contributivo" value="CONTRIBUTIVO"></el-option>
                        <el-option label="Subsidiado" value="SUBSIDIADO"></el-option>
                        <el-option label="Especial" value="ESPECIAL"></el-option>
                        <el-option label="Particular" value="PARTICULAR"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.tipo_afiliacion" v-text="errors.tipo_afiliacion[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.regimen}">
                    <label class="control-label">Régimen</label>
                    <el-input v-model="form.regimen" :maxlength="50"></el-input>
                    <small class="form-control-feedback" v-if="errors.regimen" v-text="errors.regimen[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.grupo_poblacional}">
                    <label class="control-label">Grupo Poblacional</label>
                    <el-input v-model="form.grupo_poblacional" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.grupo_poblacional" v-text="errors.grupo_poblacional[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.nivel_sisben}">
                    <label class="control-label">Nivel SISBEN</label>
                    <el-select v-model="form.nivel_sisben" placeholder="Seleccionar">
                        <el-option label="Nivel 1" value="1"></el-option>
                        <el-option label="Nivel 2" value="2"></el-option>
                        <el-option label="Nivel 3" value="3"></el-option>
                        <el-option label="No aplica" value="NA"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.nivel_sisben" v-text="errors.nivel_sisben[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.discapacidad}">
                    <label class="control-label">¿Tiene discapacidad?</label>
                    <div style="margin-top: 8px;">
                        <el-switch v-model="form.discapacidad"></el-switch>
                    </div>
                    <small class="form-control-feedback" v-if="errors.discapacidad" v-text="errors.discapacidad[0]"></small>
                </div>
            </div>
        </div>

        <div class="row" v-if="form.discapacidad">
            <div class="col-lg-6 col-md-12">
                <div class="form-group" :class="{'has-danger': errors.tipo_discapacidad}">
                    <label class="control-label">Tipo de Discapacidad</label>
                    <el-input v-model="form.tipo_discapacidad" :maxlength="100"></el-input>
                    <small class="form-control-feedback" v-if="errors.tipo_discapacidad" v-text="errors.tipo_discapacidad[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información para facturación -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-file-invoice-dollar"></i> Información de Facturación
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.codigo_cups}">
                    <label class="control-label">Código CUPS</label>
                    <el-input v-model="form.codigo_cups" :maxlength="20"></el-input>
                    <small class="form-control-feedback" v-if="errors.codigo_cups" v-text="errors.codigo_cups[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.descripcion_procedimiento}">
                    <label class="control-label">Descripción del Procedimiento</label>
                    <el-input v-model="form.descripcion_procedimiento" :maxlength="500"></el-input>
                    <small class="form-control-feedback" v-if="errors.descripcion_procedimiento" v-text="errors.descripcion_procedimiento[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.cie10}">
                    <label class="control-label">Código CIE10</label>
                    <el-input v-model="form.cie10" :maxlength="20"></el-input>
                    <small class="form-control-feedback" v-if="errors.cie10" v-text="errors.cie10[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group" :class="{'has-danger': errors.descripcion_diagnostico}">
                    <label class="control-label">Descripción del Diagnóstico</label>
                    <el-input 
                        v-model="form.descripcion_diagnostico" 
                        type="textarea" 
                        :rows="2"
                        :maxlength="500"
                    ></el-input>
                    <small class="form-control-feedback" v-if="errors.descripcion_diagnostico" v-text="errors.descripcion_diagnostico[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información financiera -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-calculator"></i> Información Financiera
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.valor_procedimiento}">
                    <label class="control-label">Valor del Procedimiento</label>
                    <el-input-number 
                        v-model="form.valor_procedimiento" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                        @change="calculateNetValue"
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.valor_procedimiento" v-text="errors.valor_procedimiento[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.copago}">
                    <label class="control-label">Copago</label>
                    <el-input-number 
                        v-model="form.copago" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                        @change="calculateNetValue"
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.copago" v-text="errors.copago[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.cuota_moderadora}">
                    <label class="control-label">Cuota Moderadora</label>
                    <el-input-number 
                        v-model="form.cuota_moderadora" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                        @change="calculateNetValue"
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.cuota_moderadora" v-text="errors.cuota_moderadora[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.valor_neto}">
                    <label class="control-label">Valor Neto</label>
                    <el-input-number 
                        v-model="form.valor_neto" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                        disabled
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.valor_neto" v-text="errors.valor_neto[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Retenciones -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-percentage"></i> Retenciones e Impuestos
                </h5>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.retencion_fuente}">
                    <label class="control-label">Retención en la Fuente</label>
                    <el-input-number 
                        v-model="form.retencion_fuente" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.retencion_fuente" v-text="errors.retencion_fuente[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.retencion_ica}">
                    <label class="control-label">Retención ICA</label>
                    <el-input-number 
                        v-model="form.retencion_ica" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.retencion_ica" v-text="errors.retencion_ica[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.retencion_cree}">
                    <label class="control-label">Retención CREE</label>
                    <el-input-number 
                        v-model="form.retencion_cree" 
                        :precision="2" 
                        :min="0" 
                        style="width: 100%"
                    ></el-input-number>
                    <small class="form-control-feedback" v-if="errors.retencion_cree" v-text="errors.retencion_cree[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información del prestador -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-user-md"></i> Información del Prestador
                </h5>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.prestador_codigo}">
                    <label class="control-label">Código del Prestador</label>
                    <el-input v-model="form.prestador_codigo" :maxlength="20"></el-input>
                    <small class="form-control-feedback" v-if="errors.prestador_codigo" v-text="errors.prestador_codigo[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.prestador_nombre}">
                    <label class="control-label">Nombre del Prestador</label>
                    <el-input v-model="form.prestador_nombre" :maxlength="200"></el-input>
                    <small class="form-control-feedback" v-if="errors.prestador_nombre" v-text="errors.prestador_nombre[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.numero_autorizacion}">
                    <label class="control-label">N° Autorización</label>
                    <el-input-number v-model="form.numero_autorizacion" :min="0" style="width: 100%"></el-input-number>
                    <small class="form-control-feedback" v-if="errors.numero_autorizacion" v-text="errors.numero_autorizacion[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.profesional_tratante}">
                    <label class="control-label">Profesional Tratante</label>
                    <el-input v-model="form.profesional_tratante" :maxlength="200"></el-input>
                    <small class="form-control-feedback" v-if="errors.profesional_tratante" v-text="errors.profesional_tratante[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.registro_profesional}">
                    <label class="control-label">Registro Profesional</label>
                    <el-input v-model="form.registro_profesional" :maxlength="50"></el-input>
                    <small class="form-control-feedback" v-if="errors.registro_profesional" v-text="errors.registro_profesional[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información de la consulta -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-calendar-alt"></i> Información de la Consulta/Procedimiento
                </h5>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.fecha_atencion}">
                    <label class="control-label">Fecha y Hora de Atención</label>
                    <el-date-picker
                        v-model="form.fecha_atencion"
                        type="datetime"
                        placeholder="Seleccionar fecha y hora"
                        format="dd/MM/yyyy HH:mm"
                        value-format="yyyy-MM-dd HH:mm:ss"
                        style="width: 100%">
                    </el-date-picker>
                    <small class="form-control-feedback" v-if="errors.fecha_atencion" v-text="errors.fecha_atencion[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.modalidad_atencion}">
                    <label class="control-label">Modalidad de Atención</label>
                    <el-select v-model="form.modalidad_atencion" placeholder="Seleccionar">
                        <el-option label="Consulta Externa" value="CONSULTA_EXTERNA"></el-option>
                        <el-option label="Urgencias" value="URGENCIAS"></el-option>
                        <el-option label="Hospitalización" value="HOSPITALIZACION"></el-option>
                        <el-option label="Cirugía Ambulatoria" value="CIRUGIA_AMBULATORIA"></el-option>
                        <el-option label="Telemedicina" value="TELEMEDICINA"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.modalidad_atencion" v-text="errors.modalidad_atencion[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.finalidad_consulta}">
                    <label class="control-label">Finalidad de la Consulta</label>
                    <el-select v-model="form.finalidad_consulta" placeholder="Seleccionar">
                        <el-option label="Detección temprana" value="DETECCION_TEMPRANA"></el-option>
                        <el-option label="Protección específica" value="PROTECCION_ESPECIFICA"></el-option>
                        <el-option label="Diagnóstico" value="DIAGNOSTICO"></el-option>
                        <el-option label="Tratamiento" value="TRATAMIENTO"></el-option>
                        <el-option label="Rehabilitación" value="REHABILITACION"></el-option>
                        <el-option label="Paliación" value="PALIACION"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.finalidad_consulta" v-text="errors.finalidad_consulta[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Observaciones y control -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2">
                    <i class="fas fa-notes-medical"></i> Observaciones y Control
                </h5>
            </div>
            
            <div class="col-12">
                <div class="form-group" :class="{'has-danger': errors.observaciones}">
                    <label class="control-label">Observaciones</label>
                    <el-input 
                        v-model="form.observaciones" 
                        type="textarea" 
                        :rows="3"
                        placeholder="Observaciones adicionales del usuario"
                    ></el-input>
                    <small class="form-control-feedback" v-if="errors.observaciones" v-text="errors.observaciones[0]"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.activo}">
                    <label class="control-label">Usuario Activo</label>
                    <div style="margin-top: 8px;">
                        <el-switch v-model="form.activo"></el-switch>
                    </div>
                    <small class="form-control-feedback" v-if="errors.activo" v-text="errors.activo[0]"></small>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6">
                <div class="form-group" :class="{'has-danger': errors.origen_dato}">
                    <label class="control-label">Origen del Dato</label>
                    <el-select v-model="form.origen_dato" placeholder="Seleccionar">
                        <el-option label="Registro Manual" value="MANUAL"></el-option>
                        <el-option label="Importación Excel" value="EXCEL_IMPORT"></el-option>
                        <el-option label="API Externa" value="API_EXTERNA"></el-option>
                        <el-option label="Migración" value="MIGRACION"></el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.origen_dato" v-text="errors.origen_dato[0]"></small>
                </div>
            </div>
        </div>

        <span slot="footer" class="dialog-footer">
            <el-button @click.prevent="clickClose()">Cerrar</el-button>
            <el-button class="add" type="primary" @click="clickSave">{{titleAction}}</el-button>
        </span>
    </el-dialog>
</template>

<script>
    export default {
        props: ['showDialog', 'recordItemHealthUserSS'],

        data() {
            return {
                titleDialog: '',
                titleAction: '',
                loading: false,
                resource: 'co-documents',
                errors: {},
                form: {},
                isSearching: false,
            }
        },

        watch: {
            // Watchers para convertir a mayúsculas los campos de texto importantes
            'form.primer_nombre'(newVal) {
                if(this.form.primer_nombre)
                    this.form.primer_nombre = newVal.toUpperCase();
            },

            'form.segundo_nombre'(newVal) {
                if(this.form.segundo_nombre)
                    this.form.segundo_nombre = newVal.toUpperCase();
            },

            'form.primer_apellido'(newVal) {
                if(this.form.primer_apellido)
                    this.form.primer_apellido = newVal.toUpperCase();
            },

            'form.segundo_apellido'(newVal) {
                if(this.form.segundo_apellido)
                    this.form.segundo_apellido = newVal.toUpperCase();
            },

            'form.eps_nombre'(newVal) {
                if(this.form.eps_nombre)
                    this.form.eps_nombre = newVal.toUpperCase();
            },

            'form.prestador_nombre'(newVal) {
                if(this.form.prestador_nombre)
                    this.form.prestador_nombre = newVal.toUpperCase();
            },

            'form.profesional_tratante'(newVal) {
                if(this.form.profesional_tratante)
                    this.form.profesional_tratante = newVal.toUpperCase();
            },

            'form.departamento'(newVal) {
                if(this.form.departamento)
                    this.form.departamento = newVal.toUpperCase();
            },

            'form.municipio'(newVal) {
                if(this.form.municipio)
                    this.form.municipio = newVal.toUpperCase();
            },

            // Actualizar nombre completo automáticamente
            'form.primer_nombre': 'updateFullName',
            'form.segundo_nombre': 'updateFullName',
            'form.primer_apellido': 'updateFullName',
            'form.segundo_apellido': 'updateFullName',
        },

        async created() {
            this.initForm()
        },

        methods: {
            async create() {
                this.titleDialog = (this.recordItemHealthUserSS) ? 'Editar Usuario S.S' : 'Agregar Usuario del Sector Salud';
                this.titleAction = (this.recordItemHealthUserSS) ? 'Actualizar' : 'Guardar';
                
                if (this.recordItemHealthUserSS) {
                    // Cargar datos del usuario para edición
                    Object.keys(this.form).forEach(key => {
                        if (this.recordItemHealthUserSS.hasOwnProperty(key)) {
                            this.form[key] = this.recordItemHealthUserSS[key];
                        }
                    });
                } else {
                    this.initForm()
                }
            },

            initForm() {
                this.errors = {}
                this.form = {
                    // Identificación
                    documento: null,
                    tipo_documento: 'CC',
                    
                    // Datos personales
                    primer_apellido: null,
                    segundo_apellido: null,
                    primer_nombre: null,
                    segundo_nombre: null,
                    nombre_completo: null,
                    
                    // Información demográfica
                    fecha_nacimiento: null,
                    edad: null,
                    genero: null,
                    estado_civil: null,
                    
                    // Información de contacto
                    telefono: null,
                    celular: null,
                    email: null,
                    direccion: null,
                    
                    // Información de ubicación
                    departamento: null,
                    municipio: null,
                    zona: null,
                    
                    // Información de salud/EPS
                    eps_codigo: null,
                    eps_nombre: null,
                    tipo_afiliacion: null,
                    regimen: null,
                    
                    // Información adicional del sector salud
                    grupo_poblacional: null,
                    nivel_sisben: null,
                    discapacidad: false,
                    tipo_discapacidad: null,
                    
                    // Información para facturación
                    codigo_cups: null,
                    descripcion_procedimiento: null,
                    cie10: null,
                    descripcion_diagnostico: null,
                    
                    // Información financiera
                    valor_procedimiento: 0,
                    copago: 0,
                    cuota_moderadora: 0,
                    valor_neto: 0,
                    
                    // Retenciones
                    retencion_fuente: 0,
                    retencion_ica: 0,
                    retencion_cree: 0,
                    
                    // Información del prestador
                    prestador_codigo: null,
                    prestador_nombre: null,
                    profesional_tratante: null,
                    registro_profesional: null,
                    
                    // Información de la consulta
                    fecha_atencion: null,
                    modalidad_atencion: null,
                    finalidad_consulta: null,
                    numero_autorizacion: null,
                    
                    // Campos de control
                    activo: true,
                    observaciones: null,
                    origen_dato: 'MANUAL',
                }
            },

            updateFullName() {
                const nombres = [
                    this.form.primer_nombre,
                    this.form.segundo_nombre,
                    this.form.primer_apellido,
                    this.form.segundo_apellido
                ].filter(nombre => nombre && nombre.trim() !== '').join(' ').trim();
                
                this.form.nombre_completo = nombres || null;
            },

            calculateAge() {
                if (this.form.fecha_nacimiento) {
                    const birthDate = new Date(this.form.fecha_nacimiento);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    
                    this.form.edad = age >= 0 ? age : null;
                }
            },

            calculateNetValue() {
                const valor = this.form.valor_procedimiento || 0;
                const copago = this.form.copago || 0;
                const cuota = this.form.cuota_moderadora || 0;
                
                this.form.valor_neto = parseFloat((valor - copago - cuota).toFixed(2));
            },

            validate() {
                const response = { success: true };
                
                // Validaciones básicas
                if (!this.form.documento) {
                    response.success = false;
                    response.documento = ["El número de documento es obligatorio"];
                }
                
                if (!this.form.tipo_documento) {
                    response.success = false;
                    response.tipo_documento = ["El tipo de documento es obligatorio"];
                }
                
                if (!this.form.primer_nombre) {
                    response.success = false;
                    response.primer_nombre = ["El primer nombre es obligatorio"];
                }
                
                if (!this.form.primer_apellido) {
                    response.success = false;
                    response.primer_apellido = ["El primer apellido es obligatorio"];
                }
                
                // Validación de email si se proporciona
                if (this.form.email) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.form.email)) {
                        response.success = false;
                        response.email = ["El formato del email no es válido"];
                    }
                }
                
                // Validación de documento numérico
                const docRegex = /^[0-9]+$/;
                if (this.form.documento && !docRegex.test(this.form.documento)) {
                    response.success = false;
                    response.documento = ["El documento debe contener solo números"];
                }
                
                return response;
            },

            clickSave() {
                this.updateFullName(); // Asegurar que el nombre completo esté actualizado
                
                let validate = this.validate();
                if (!validate.success) {
                    this.errors = validate;
                } else {
                    this.errors = {};
                    if (this.recordItemHealthUserSS) {
                        this.form.id = this.recordItemHealthUserSS.id;
                    }
                    this.$emit('add', this.form);
                    this.clickClose();
                }
            },

            clickClose() {
                this.initForm();
                this.$emit('update:showDialog', false);
            },

            // Método para buscar usuario por documento en la base de datos de usuarios S.S
            searchUserInDatabase() {
                if (this.isSearching) {
                    this.$message({
                        message: 'Ya hay una búsqueda en progreso. Espere a que termine.',
                        type: 'warning'
                    });
                    return;
                }

                if (!this.form.documento || !this.form.tipo_documento) {
                    return;
                }

                this.isSearching = true;

                const loading = this.$loading({
                    lock: true,
                    text: 'Buscando usuario en base de datos de S.S...',
                    spinner: 'el-icon-loading',
                    background: 'rgba(0, 0, 0, 0.7)'
                });

                const timeoutId = setTimeout(() => {
                    this.isSearching = false;
                    loading.close();
                    this.$message({
                        message: 'La búsqueda está tardando más de lo esperado.',
                        type: 'warning'
                    });
                }, 30000);

                const requestData = {
                    documento: this.form.documento,
                    tipo_documento: this.form.tipo_documento
                };

                // Endpoint para buscar en usuarios S.S (tabla tenancy_health_users)
                this.$http.get('/health-users/find-user-by-document', {
                    params: requestData,
                    timeout: 25000
                }).then(response => {
                    clearTimeout(timeoutId);
                    this.isSearching = false;
                    loading.close();
                    
                    if (response.data.found) {
                        const userData = response.data.user;
                        
                        this.$confirm(
                            `Usuario encontrado: ${userData.nombre_completo}\n¿Desea cargar automáticamente los datos?`, 
                            'Usuario Encontrado en Base de Datos S.S', 
                            {
                                confirmButtonText: 'Sí, cargar datos',
                                cancelButtonText: 'No, mantener vacío',
                                type: 'info'
                            }
                        ).then(() => {
                            // Cargar automáticamente todos los datos disponibles
                            Object.keys(this.form).forEach(key => {
                                if (userData.hasOwnProperty(key) && userData[key] !== null) {
                                    this.form[key] = userData[key];
                                }
                            });
                            
                            this.$message({
                                message: 'Datos del usuario cargados automáticamente desde base de datos S.S',
                                type: 'success'
                            });
                        }).catch(() => {
                            this.$message({
                                type: 'info',
                                message: 'Operación cancelada'
                            });
                        });
                    } else {
                        this.$message({
                            message: 'Usuario no encontrado en la base de datos de usuarios S.S',
                            type: 'warning',
                            duration: 4000
                        });
                    }
                }).catch(error => {
                    clearTimeout(timeoutId);
                    this.isSearching = false;
                    loading.close();
                    
                    let errorMessage = 'Error al buscar el usuario en base de datos S.S';
                    
                    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
                        errorMessage = 'La búsqueda tardó demasiado tiempo. Verifique su conexión.';
                    } else if (error.response) {
                        if (error.response.status === 401) {
                            errorMessage = 'Sesión expirada. Recargue la página.';
                        } else if (error.response.status === 404) {
                            errorMessage = 'Servicio de búsqueda no disponible';
                        } else if (error.response.data && error.response.data.message) {
                            errorMessage = error.response.data.message;
                        }
                    }
                    
                    this.$message({
                        message: errorMessage,
                        type: 'error',
                        duration: 4000
                    });
                });
            }
        }
    }
</script>

<style scoped>
    .border-bottom {
        border-bottom: 2px solid #e9ecef;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .has-danger .el-input__inner {
        border-color: #dc3545;
    }
    
    .has-danger .el-select .el-input__inner {
        border-color: #dc3545;
    }
    
    .form-control-feedback {
        color: #dc3545;
        font-size: 12px;
        margin-top: 4px;
    }
    
    .el-dialog {
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .text-primary {
        color: #007bff !important;
    }
</style>
