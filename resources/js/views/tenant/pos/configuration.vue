<template>
    <div>
        <div class="card mb-0 pt-2 pt-md-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Configuración POS</h3>
            </div>
            <div class="card-body">
                <div>
                    <el-table
                    :data="records"
                    style="width: 100%">
                        <el-table-column
                            prop="prefix"
                            label="Prefijo"
                            width="120">
                        </el-table-column>
                        <el-table-column
                            prop="resolution_number"
                            label="Número">
                        </el-table-column>
                        <el-table-column
                            prop="date_from"
                            label="Fecha Desde">
                        </el-table-column>
                        <el-table-column
                            prop="date_end"
                            label="Fecha Hasta">
                        </el-table-column>
                        <el-table-column
                            prop="from"
                            label="Desde">
                        </el-table-column>
                        <el-table-column
                            prop="to"
                            label="Hasta">
                        </el-table-column>
                        <el-table-column
                            prop="electronic"
                            label="POS Electronico">
                            <template slot-scope="scope">
                                <el-checkbox
                                    v-model="scope.row.electronic"
                                    :disabled="true"
                                ></el-checkbox>
                            </template>
                        </el-table-column>
                        <el-table-column
                            prop="plate_number"
                            label="Serial Caja">
                        </el-table-column>
                        <el-table-column
                            prop="cash_type"
                            label="Tipo Caja">
                        </el-table-column>
                        <el-table-column
                            fixed="right"
                            label="Operaciones"
                            width="120">
                            <template slot-scope="scope">
                                <el-button
                                icon="el-icon-check"
                                @click.native.prevent="selection(scope.row)"
                                size="mini">
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                <div class="resolution">
                    <form autocomplete="off">
                        <div class="form-body">
                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="control-label">Tipo de Documento</label>
                                        <el-input
                                            :value="'POS'"
                                            :disabled="true">
                                        </el-input>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.prefix}">
                                        <label class="control-label">Prefijo *</label>
                                        <el-input
                                            v-model="resolution.prefix"
                                            placeholder="Digite el prefijo de la resolucion"
                                            maxlength="4"
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.prefix" v-text="errors.prefix[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.resolution_number}">
                                        <label class="control-label">Nro Resolucion *</label>
                                        <el-input
                                            v-model="resolution.resolution_number"
                                            placeholder="Digite el numero de resolucion."
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.resolution_number" v-text="errors.resolution_number[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.resolution_date}">
                                        <label class="control-label">Fecha Resolucion</label>
                                        <el-date-picker
                                            v-model="resolution.resolution_date"
                                            type="date"
                                            value-format="yyyy-MM-dd"
                                            placeholder="Seleccione la fecha de emision de la resolucion."
                                            :clearable="false">
                                        </el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.resolution_date" v-text="errors.resolution_date[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.date_from}">
                                        <label class="control-label">Fecha Desde</label>
                                        <el-date-picker
                                            v-model="resolution.date_from"
                                            type="date"
                                            value-format="yyyy-MM-dd"
                                            placeholder="Seleccione la fecha inicial de validez de la resolucion."
                                            :clearable="false">
                                        </el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.date_from" v-text="errors.date_from[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.date_end}">
                                        <label class="control-label">Fecha Hasta</label>
                                        <el-date-picker
                                            v-model="resolution.date_end"
                                            type="date"
                                            value-format="yyyy-MM-dd"
                                            placeholder="Seleccione la fecha final de validez de la resolucion."
                                            :clearable="false">
                                        </el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.date_end" v-text="errors.date_end[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.from}">
                                        <label class="control-label">Desde *</label>
                                        <el-input
                                            v-model="resolution.from"
                                            placeholder="Introduzca el numero inicial de la resolucion."
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.from" v-text="errors.from[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.to}">
                                        <label class="control-label">Hasta *</label>
                                        <el-input
                                            v-model="resolution.to"
                                            placeholder="Digite el numero final de la resolucion."
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.to" v-text="errors.to[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-2">
                                    <div class="form-group" :class="{'has-danger': errors.generated}">
                                        <label class="control-label">Generadas *</label>
                                        <el-input
                                            v-model="resolution.generated"
                                            placeholder="Documentos generados."
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.generated" v-text="errors.generated[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-1">
                                    <div class="form-group" :class="{'has-danger': errors.electronic}">
                                        <label class="control-label">POS Electronico</label><br>
                                        <el-checkbox  v-model="resolution.electronic"></el-checkbox>
                                        <small class="form-control-feedback" v-if="errors.electronic" v-text="errors.electronic[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4" v-if="resolution.electronic">
                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.plate_number}">
                                        <label class="control-label">Serial Caja</label>
                                        <el-input
                                            v-model="resolution.plate_number"
                                            placeholder="Introduzca el numero serial de la caja."
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.plate_number" v-text="errors.plate_number[0]"></small>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group" :class="{'has-danger': errors.cash_type}">
                                        <label class="control-label">Tipo Caja</label>
                                        <el-input
                                            v-model="resolution.cash_type"
                                            placeholder="Digite el tipo de caja."
                                            :disabled="false">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.cash_type" v-text="errors.cash_type[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4 align-items-end">
                                <div class="col-lg-6">
                                    <div class="form-group mb-0" :class="{'has-danger': errors.show_in_establishments}">
                                        <label class="control-label">¿Dónde mostrar esta resolución?</label>
                                        <el-select v-model="resolution.show_in_establishments" placeholder="Seleccione una opción">
                                            <el-option label="Todos los establecimientos" value="all"></el-option>
                                            <el-option label="Ninguno" value="none"></el-option>
                                            <el-option label="Seleccionar" value="custom"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.show_in_establishments" v-text="errors.show_in_establishments[0]"></small>
                                    </div>
                                </div>
                                <div class="col-lg-6" v-if="resolution.show_in_establishments === 'custom'">
                                    <div class="form-group mb-0" :class="{'has-danger': errors.establishment_ids}">
                                        <label class="control-label">Establecimientos</label>
                                        <el-select
                                            v-model="resolution.establishment_ids"
                                            multiple
                                            filterable
                                            placeholder="Seleccione los establecimientos"
                                            style="width: 100%;">
                                            <el-option
                                                v-for="est in establishments"
                                                :key="est.id"
                                                :label="est.description"
                                                :value="est.id">
                                            </el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.establishment_ids" v-text="errors.establishment_ids[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions text-right mt-4">
                                <el-button type="default " @click="clearFields()">Limpiar campos</el-button>
                                <el-button
                                    type="primary"
                                    :loading="loadingResolution"
                                    @click="validateResolution()">Guardar
                                </el-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Nueva sección configuración API -->
        <div class="card mb-0 pt-2 pt-md-0 mt-4">
            <div class="card-header bg-info">
                <h3 class="my-0">Configuración API WhatsApp</h3>
            </div>
            <div class="card-body">
                <form autocomplete="off">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="control-label">URL Base API</label>
                                <el-input 
                                    v-model="apiConfig.api_url"
                                    placeholder="Ej: https://api.whatsapp.com/v1">
                                </el-input>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="control-label">Token API</label>
                                <el-input 
                                    v-model="apiConfig.api_token"
                                    type="password"
                                    show-password
                                    placeholder="Ingrese el token de la API">
                                </el-input>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions text-right mt-4">
                        <el-button
                            type="primary"
                            :loading="loadingApiConfig"
                            @click="saveApiConfig">
                            Guardar Configuración API
                        </el-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
   // import Helper from "../../../mixins/Helper";
    export default {
       // mixins: [Helper],
        props: ['configuration'],
        data: () => ({
            typeDocuments: [
                { id: 1, name: "Factura de Venta Nacional" },
                //{ id: 2, name: "Factura de Exportación" },
                //{ id: 3, name: "Factura de Contingencia" },
                { id: 4, name: "Nota Crédito" },
                { id: 5, name: "Nota Débito" },
                { id: 6, name: "ZIP" }
            ],

            errors: {
            },

            resolution: {
                prefix : '',
                resolution_number: '',
                resolution_date: '',
                date_from: '',
                date_end: '',
                from: '',
                to: '',
                electronic: true,
                generated: '',
                plate_number: '',
                cash_type: '',
                show_in_establishments: 'all',
                establishment_ids: [],
            },

            loadingResolution: false,
            records: [],
            apiConfig: {
                api_url: '',
                api_token: ''
            },
            loadingApiConfig: false,
            establishments: [],
        }),

        mounted() {
            this.errors = {
            }
            if (window.File && window.FileReader && window.FileList && window.Blob)
                console.log("ok.");
            else
                alert("The File APIs are not fully supported in this browser.");


            /*if(this.configuration)
            {
                this.resolution.prefix = this.configuration.prefix;
                this.resolution.resolution_number = this.configuration.resolution_number;
                this.resolution.resolution_date = this.configuration.resolution_date;
                this.resolution.date_from = this.configuration.date_from;
                this.resolution.date_end = this.configuration.date_end;
                this.resolution.from = this.configuration.from;
                this.resolution.to = this.configuration.to;
            }*/

            this.getRecords()
            this.loadApiConfig()
            this.getWhatsappConfig();
            this.getEstablishments();
        },

        methods: {
            getRecords()
            {
                this.$http.get(`/pos/records`, this.resolution)
                    .then(response => {
                        this.records = response.data.data
//                        console.log(this.records)
                    })
                    .catch(error => {

                    })
                    .then(() => {
                    })
            },

            getEstablishments() {
                this.$http.get('/establishments/records')
                    .then(response => {
                        this.establishments = response.data.data;
                    });
            },

            initForm() {
                this.resolution = {
                    prefix : '',
                    resolution_number: '',
                    resolution_date: '',
                    date_from: '',
                    date_end: '',
                    from: '',
                    to: '',
                    electronic: true,
                    generated: '',
                    plate_number: '',
                    cash_type: '',
                    show_in_establishments: 'all',
                    establishment_ids: [],
                }
            },

            validateResolution() {
                this.loadingResolution = true
                this.$http.post(`/pos/configuration`, this.resolution)
                    .then(response => {
//                        console.log(this.resolution)
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            if(this.resolution.electronic)
                                localStorage.setItem("plate_number", this.resolution.plate_number);
                            this.getRecords()
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data
                        } else {
                            console.log(error)
                        }
                    })
                    .then(() => {
                        this.loadingResolution = false
                        //this.initForm()
                    })
            },

            selection(row) {
                this.resolution = {
                    prefix : row.prefix,
                    resolution_number: row.resolution_number,
                    resolution_date: row.resolution_date,
                    date_from: row.date_from,
                    date_end: row.date_end,
                    from: row.from,
                    to: row.to,
                    electronic: row.electronic,
                    generated: row.generated,
                    plate_number: row.plate_number,
                    cash_type: row.cash_type,
                    show_in_establishments: row.show_in_establishments ?? 'all',
                    establishment_ids: row.establishment_ids ?? [],
                }
            },

            clearFields(){
                this.initForm()
            },

            loadApiConfig() {
            },

            async getWhatsappConfig() {
                try {
                    const response = await this.$http.get('/pos/whatsapp/config');
                    if (response.data.success && response.data.data) {
                        this.apiConfig = {
                            api_url: response.data.data.api_url || '',
                            api_token: response.data.data.api_token || ''
                        };
                    }
                } catch (error) {
                    console.error('Error al cargar configuración:', error);
                    this.$message.error('Error al cargar la configuración de WhatsApp');
                }
            },

            async saveApiConfig() {
                if (!this.apiConfig.api_url || !this.apiConfig.api_token) {
                    this.$message.error('Debe ingresar URL y Token del API');
                    return;
                }

                try {
                    this.loadingApiConfig = true;
                    const response = await this.$http.post('/pos/whatsapp/config', {
                        api_url: this.apiConfig.api_url,
                        api_token: this.apiConfig.api_token
                    });

                    if (response.data.success) {
                        this.$message.success(response.data.message);
                    } else {
                        throw new Error(response.data.message || 'Error desconocido');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.$message.error(error.response?.data?.message || error.message || 'Error al guardar la configuración');
                    
                    if (error.response?.data?.errors) {
                        Object.values(error.response.data.errors).forEach(errorMessages => {
                            errorMessages.forEach(message => {
                                this.$message.error(message);
                            });
                        });
                    }
                } finally {
                    this.loadingApiConfig = false;
                }
            },
            
        }
    };
</script>
