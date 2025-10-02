<template>
<div class="rips-dialog-container">
    <!-- Modal para Configuración Avanzada de RIPS -->
    <el-dialog 
        title="Configuración Avanzada RIPS - Sector Salud" 
        :visible.sync="showRipsDialog" 
        width="90%" 
        :close-on-click-modal="false"
        class="rips-dialog">
        
        <div class="rips-configuration">
            <!-- Pestañas de configuración -->
            <el-tabs v-model="activeTab" type="border-card">
                
                <!-- Tab 1: Información de la Entidad -->
                <el-tab-pane label="Entidad Administradora" name="entidad">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Código EAPB *</label>
                                <el-input v-model="ripsData.codigo_eapb" placeholder="Ejemplo: EAPB001"></el-input>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Nombre Entidad Administradora *</label>
                                <el-input v-model="ripsData.nombre_eapb" placeholder="Ejemplo: NUEVA EPS S.A."></el-input>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Número de Contrato</label>
                                <el-input v-model="ripsData.numero_contrato" placeholder="001"></el-input>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Plan de Beneficios</label>
                                <el-select v-model="ripsData.plan_beneficios" placeholder="Seleccionar plan">
                                    <el-option label="Plan Obligatorio de Salud (POS)" value="01"></el-option>
                                    <el-option label="Plan de Atención Complementaria (PAC)" value="02"></el-option>
                                    <el-option label="Plan Adicional de Salud (PAS)" value="03"></el-option>
                                </el-select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Número de Póliza</label>
                                <el-input v-model="ripsData.numero_poliza" placeholder="Opcional"></el-input>
                            </div>
                        </div>
                    </div>
                </el-tab-pane>

                <!-- Tab 2: Información Clínica -->
                <el-tab-pane label="Información Clínica" name="clinica">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Número de Autorización</label>
                                <el-input v-model="ripsData.numero_autorizacion" placeholder="Número de autorización"></el-input>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Causa Externa *</label>
                                <el-select v-model="ripsData.causa_externa" placeholder="Seleccionar causa">
                                    <el-option v-for="causa in causasExternas" :key="causa.value" 
                                               :label="causa.label" :value="causa.value"></el-option>
                                </el-select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Diagnóstico Principal (CIE-10) *</label>
                                <el-input v-model="ripsData.diagnostico_principal" 
                                          placeholder="Ejemplo: Z000" 
                                          @blur="validateDiagnostico('principal')">
                                    <template slot="append">
                                        <el-button @click="showDiagnosticoSearch('principal')">
                                            <i class="fa fa-search"></i>
                                        </el-button>
                                    </template>
                                </el-input>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Modalidad de Atención</label>
                                <el-select v-model="ripsData.modalidad_atencion" placeholder="Seleccionar modalidad">
                                    <el-option v-for="modalidad in modalidadesAtencion" :key="modalidad.value" 
                                               :label="modalidad.label" :value="modalidad.value"></el-option>
                                </el-select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Diagnósticos relacionados -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Diagnóstico Relacionado 1</label>
                                <el-input v-model="ripsData.diagnostico_relacionado1" placeholder="Opcional"></el-input>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Diagnóstico Relacionado 2</label>
                                <el-input v-model="ripsData.diagnostico_relacionado2" placeholder="Opcional"></el-input>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Diagnóstico Relacionado 3</label>
                                <el-input v-model="ripsData.diagnostico_relacionado3" placeholder="Opcional"></el-input>
                            </div>
                        </div>
                    </div>

                    <!-- Cuota moderadora -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Cuota Moderadora</label>
                                <el-input-number v-model="ripsData.cuota_moderadora" :precision="2" :min="0">
                                </el-input-number>
                            </div>
                        </div>
                    </div>
                </el-tab-pane>

                <!-- Tab 3: Validación y Generación -->
                <el-tab-pane label="Validación y Generación" name="validacion">
                    <div class="validation-section">
                        <!-- Estado de validación -->
                        <div class="alert alert-info" v-if="!ripsValidation.isValid">
                            <h5><i class="fa fa-info-circle"></i> Validación de Datos</h5>
                            <p>Complete todos los campos requeridos para generar los archivos RIPS:</p>
                            <ul>
                                <li v-for="error in ripsValidation.errors" :key="error" class="text-danger">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <div class="alert alert-success" v-if="ripsValidation.isValid">
                            <h5><i class="fa fa-check-circle"></i> Datos Válidos</h5>
                            <p>Todos los datos requeridos están completos. Puede proceder con la generación de RIPS.</p>
                        </div>

                        <!-- Opciones de generación -->
                        <div class="generation-options" v-if="ripsValidation.isValid">
                            <h5>Opciones de Generación</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <el-checkbox v-model="ripsOptions.generarTxt">
                                        Generar archivos TXT (Requerido)
                                    </el-checkbox>
                                </div>
                                <div class="col-md-6">
                                    <el-checkbox v-model="ripsOptions.generarExcel">
                                        Generar archivo Excel (Opcional)
                                    </el-checkbox>
                                </div>
                                <div class="col-md-6">
                                    <el-checkbox v-model="ripsOptions.validarFevRips">
                                        Validar con FEV-RIPS automáticamente
                                    </el-checkbox>
                                </div>
                                <div class="col-md-6">
                                    <el-checkbox v-model="ripsOptions.enviarApidian">
                                        Enviar a APIDIAN para procesamiento
                                    </el-checkbox>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen de archivos a generar -->
                        <div class="files-summary" v-if="ripsValidation.isValid">
                            <h5>Archivos RIPS a Generar</h5>
                            <div class="row">
                                <div class="col-md-3" v-for="fileType in expectedFiles" :key="fileType.code">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i :class="fileType.icon" class="fa-2x text-primary"></i>
                                            <h6 class="mt-2">{{ fileType.name }}</h6>
                                            <small class="text-muted">{{ fileType.description }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-tab-pane>

                <!-- Tab 4: Historial -->
                <el-tab-pane label="Historial RIPS" name="historial">
                    <div class="historial-section">
                        <div class="mb-3">
                            <el-button @click="loadRipsHistory" :loading="loadingHistory">
                                <i class="fa fa-refresh"></i> Actualizar Historial
                            </el-button>
                        </div>

                        <el-table :data="ripsHistory" v-loading="loadingHistory">
                            <el-table-column prop="numero_remision" label="Número de Remisión" width="180"></el-table-column>
                            <el-table-column prop="fecha_generacion" label="Fecha" width="120"></el-table-column>
                            <el-table-column prop="estado" label="Estado" width="100">
                                <template slot-scope="scope">
                                    <el-tag :type="getEstadoType(scope.row.estado)" size="mini">
                                        {{ scope.row.estado }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column prop="archivos_generados" label="Archivos"></el-table-column>
                            <el-table-column label="Acciones" width="200">
                                <template slot-scope="scope">
                                    <el-button size="mini" @click="downloadRips(scope.row.id)" 
                                               v-if="scope.row.estado === 'generado'">
                                        <i class="fa fa-download"></i> Descargar
                                    </el-button>
                                    <el-button size="mini" type="warning" @click="validateRips(scope.row.id)"
                                               v-if="scope.row.estado === 'generado'">
                                        <i class="fa fa-check"></i> Validar
                                    </el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>

        <div slot="footer" class="dialog-footer">
            <el-button @click="showRipsDialog = false">Cancelar</el-button>
            <el-button @click="validateRipsData">Validar Datos</el-button>
            <el-button type="primary" @click="generateRipsAdvanced" 
                       :loading="loadingGeneration" 
                       :disabled="!ripsValidation.isValid">
                <i class="fa fa-file-medical" v-if="!loadingGeneration"></i>
                Generar RIPS Completo
            </el-button>
        </div>
    </el-dialog>

    <!-- Modal para búsqueda de diagnósticos -->
    <el-dialog title="Buscar Diagnóstico CIE-10" :visible.sync="showDiagnosticoDialog" width="70%">
        <div class="diagnostico-search">
            <el-input v-model="diagnosticoSearch" placeholder="Buscar por código o descripción" 
                      @input="searchDiagnosticos" class="mb-3">
                <template slot="prepend">CIE-10</template>
            </el-input>
            
            <el-table :data="diagnosticosList" @row-click="selectDiagnostico" height="400">
                <el-table-column prop="codigo" label="Código" width="80"></el-table-column>
                <el-table-column prop="descripcion" label="Descripción"></el-table-column>
            </el-table>
        </div>
        
        <div slot="footer" class="dialog-footer">
            <el-button @click="showDiagnosticoDialog = false">Cancelar</el-button>
        </div>
    </el-dialog>
</div>
</template>

<script>
export default {
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        documentId: {
            type: [String, Number],
            default: null
        }
    },
    
    data() {
        return {
            showRipsDialog: false,
            activeTab: 'entidad',
            loadingGeneration: false,
            loadingHistory: false,
            showDiagnosticoDialog: false,
            diagnosticoSearch: '',
            diagnosticoSearchType: 'principal',
            
            // Datos del formulario RIPS
            ripsData: {
                codigo_eapb: '',
                nombre_eapb: '',
                numero_contrato: '001',
                plan_beneficios: '01',
                numero_poliza: '',
                numero_autorizacion: '',
                causa_externa: '13',
                diagnostico_principal: '',
                diagnostico_relacionado1: '',
                diagnostico_relacionado2: '',
                diagnostico_relacionado3: '',
                cuota_moderadora: 0,
                modalidad_atencion: '01'
            },
            
            // Opciones de generación
            ripsOptions: {
                generarTxt: true,
                generarExcel: true,
                validarFevRips: false,
                enviarApidian: false
            },
            
            // Validación
            ripsValidation: {
                isValid: false,
                errors: []
            },
            
            // Historial
            ripsHistory: [],
            
            // Listas de opciones
            causasExternas: [
                { value: '01', label: '01 - Accidente de trabajo' },
                { value: '02', label: '02 - Accidente de tránsito' },
                { value: '03', label: '03 - Accidente rábico' },
                { value: '04', label: '04 - Accidente ofídico' },
                { value: '05', label: '05 - Otro tipo de accidente' },
                { value: '06', label: '06 - Evento catastrófico' },
                { value: '07', label: '07 - Lesión por agresión' },
                { value: '08', label: '08 - Lesión autoinfligida' },
                { value: '09', label: '09 - Sospecha de maltrato físico' },
                { value: '10', label: '10 - Sospecha de abuso sexual' },
                { value: '11', label: '11 - Sospecha de violencia sexual' },
                { value: '12', label: '12 - Sospecha de maltrato emocional' },
                { value: '13', label: '13 - Enfermedad general' },
                { value: '14', label: '14 - Enfermedad profesional' },
                { value: '15', label: '15 - Otra' }
            ],
            
            modalidadesAtencion: [
                { value: '01', label: '01 - Intramural' },
                { value: '02', label: '02 - Extramural unidad móvil' },
                { value: '03', label: '03 - Extramural domiciliaria' },
                { value: '04', label: '04 - Extramural jornada de salud' },
                { value: '05', label: '05 - Telemedicina' }
            ],
            
            expectedFiles: [
                { code: 'CT', name: 'Control', description: 'Archivo de control', icon: 'fa fa-file-alt' },
                { code: 'AF', name: 'Transacciones', description: 'Datos de facturación', icon: 'fa fa-file-invoice' },
                { code: 'US', name: 'Usuarios', description: 'Datos de usuarios', icon: 'fa fa-users' },
                { code: 'AC', name: 'Consultas', description: 'Consultas médicas', icon: 'fa fa-stethoscope' },
                { code: 'AP', name: 'Procedimientos', description: 'Procedimientos realizados', icon: 'fa fa-tools' },
                { code: 'AU', name: 'Urgencias', description: 'Atenciones de urgencia', icon: 'fa fa-ambulance' }
            ],
            
            diagnosticosList: []
        }
    },
    
    watch: {
        visible(newVal) {
            this.showRipsDialog = newVal;
            if (newVal) {
                this.initializeRipsData();
                this.loadRipsHistory();
            }
        },
        
        showRipsDialog(newVal) {
            this.$emit('update:visible', newVal);
        }
    },
    
    methods: {
        // Inicializar datos
        initializeRipsData() {
            // Cargar datos por defecto o existentes
            this.loadDefaultRipsData();
            this.validateRipsData();
        },
        
        loadDefaultRipsData() {
            // Aquí se pueden cargar datos por defecto desde configuración
            // o datos existentes del documento
        },
        
        // Validación de datos
        validateRipsData() {
            this.ripsValidation.errors = [];
            
            if (!this.ripsData.codigo_eapb) {
                this.ripsValidation.errors.push('Código EAPB es requerido');
            }
            
            if (!this.ripsData.nombre_eapb) {
                this.ripsValidation.errors.push('Nombre de la entidad administradora es requerido');
            }
            
            if (!this.ripsData.diagnostico_principal) {
                this.ripsValidation.errors.push('Diagnóstico principal es requerido');
            }
            
            this.ripsValidation.isValid = this.ripsValidation.errors.length === 0;
        },
        
        // Generación de RIPS
        async generateRipsAdvanced() {
            if (!this.ripsValidation.isValid) {
                this.$message.error('Complete todos los campos requeridos');
                return;
            }
            
            this.loadingGeneration = true;
            
            try {
                const response = await axios.post(`/client/rips/generar/${this.documentId}`, this.ripsData);
                
                if (response.data.success) {
                    this.$message.success('RIPS generado exitosamente');
                    this.loadRipsHistory();
                    this.activeTab = 'historial';
                    
                    // Procesar opciones adicionales
                    if (this.ripsOptions.validarFevRips) {
                        await this.validateWithFevRips(response.data.data.control_id);
                    }
                    
                    if (this.ripsOptions.enviarApidian) {
                        await this.sendToApidian(response.data.data.control_id);
                    }
                } else {
                    this.$message.error('Error al generar RIPS: ' + response.data.message);
                }
            } catch (error) {
                console.error('Error generating RIPS:', error);
                this.$message.error('Error interno al generar RIPS');
            } finally {
                this.loadingGeneration = false;
            }
        },
        
        // Cargar historial
        async loadRipsHistory() {
            if (!this.documentId) return;
            
            this.loadingHistory = true;
            
            try {
                const response = await axios.get(`/client/rips/historial/${this.documentId}`);
                
                if (response.data.success) {
                    this.ripsHistory = response.data.data;
                }
            } catch (error) {
                console.error('Error loading RIPS history:', error);
            } finally {
                this.loadingHistory = false;
            }
        },
        
        // Descargar RIPS
        async downloadRips(controlId) {
            try {
                const response = await axios.get(`/client/rips/descargar/${controlId}`);
                
                if (response.data.success) {
                    // Procesar descarga de archivos
                    const archivos = response.data.data.archivos_txt;
                    
                    archivos.forEach(archivo => {
                        const link = document.createElement('a');
                        link.href = archivo.url;
                        link.download = archivo.nombre;
                        link.click();
                    });
                    
                    this.$message.success('Descarga iniciada');
                }
            } catch (error) {
                console.error('Error downloading RIPS:', error);
                this.$message.error('Error al descargar archivos');
            }
        },
        
        // Validar con FEV-RIPS
        async validateRips(controlId) {
            try {
                const response = await axios.post(`/client/rips/validar/${controlId}`);
                
                if (response.data.success) {
                    this.$message.success('Validación enviada a FEV-RIPS');
                } else {
                    this.$message.error('Error en validación: ' + response.data.message);
                }
            } catch (error) {
                console.error('Error validating RIPS:', error);
                this.$message.error('Error al validar con FEV-RIPS');
            }
        },
        
        async validateWithFevRips(controlId) {
            await this.validateRips(controlId);
        },
        
        async sendToApidian(controlId) {
            // Implementar envío a APIDIAN
            this.$message.info('Función de envío a APIDIAN en desarrollo');
        },
        
        // Búsqueda de diagnósticos
        showDiagnosticoSearch(type) {
            this.diagnosticoSearchType = type;
            this.showDiagnosticoDialog = true;
            this.diagnosticoSearch = '';
            this.diagnosticosList = [];
        },
        
        searchDiagnosticos() {
            // Simular búsqueda de diagnósticos CIE-10
            // En implementación real, consultar API o base de datos
            const mockDiagnosticos = [
                { codigo: 'Z000', descripcion: 'Examen médico general' },
                { codigo: 'K309', descripcion: 'Dispepsia no especificada' },
                { codigo: 'J06', descripcion: 'Infección aguda de las vías respiratorias superiores' },
                { codigo: 'M791', descripcion: 'Mialgia' },
                { codigo: 'R51', descripcion: 'Cefalea' }
            ];
            
            if (this.diagnosticoSearch.length >= 2) {
                this.diagnosticosList = mockDiagnosticos.filter(diag => 
                    diag.codigo.toLowerCase().includes(this.diagnosticoSearch.toLowerCase()) ||
                    diag.descripcion.toLowerCase().includes(this.diagnosticoSearch.toLowerCase())
                );
            }
        },
        
        selectDiagnostico(row) {
            if (this.diagnosticoSearchType === 'principal') {
                this.ripsData.diagnostico_principal = row.codigo;
            }
            this.showDiagnosticoDialog = false;
            this.validateRipsData();
        },
        
        // Validación de diagnóstico
        validateDiagnostico(type) {
            // Implementar validación de código CIE-10
            this.validateRipsData();
        },
        
        // Utilidades
        getEstadoType(estado) {
            const types = {
                'generado': 'success',
                'pendiente': 'warning',
                'rechazado': 'danger',
                'obsoleto': 'info'
            };
            return types[estado] || 'info';
        }
    }
}
</script>

<style scoped>
.rips-dialog .el-dialog__body {
    padding: 10px 20px;
}

.validation-section .alert {
    margin-bottom: 20px;
}

.generation-options, .files-summary {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.files-summary .card {
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.files-summary .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.diagnostico-search {
    max-height: 500px;
}

.el-table .el-table__row {
    cursor: pointer;
}

.el-table .el-table__row:hover {
    background-color: #f5f7fa;
}
</style>
