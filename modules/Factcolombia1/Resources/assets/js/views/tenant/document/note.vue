<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            {{ note ? `Nueva Nota (${note.prefix}-${note.number})` : 'Nota Contable Sin Referencia a Factura' }}
        </div>
        <div class="card-body" v-if="loading_form">
            <div class="invoice">
                <form autocomplete="off" @submit.prevent="submit">
                    <div class="form-body">
                        <div class="row">
                        </div>
                        <!-- Datos de salud (solo lectura) cuando la nota referencia una factura con campos de salud -->
                        <div class="row mt-2" v-if="note && healthInfo && healthInfo.health_fields">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <strong>Datos sector salud (referencia)</strong>
                                    <div class="mt-2">
                                        <div><b>Factura:</b> {{ healthInfo.prefix }}-{{ healthInfo.number }} | <b>Fecha:</b> {{ healthInfo.issue_date }}</div>
                                        <div><b>Periodo:</b> {{ healthInfo.health_fields.invoice_period_start_date }} a {{ healthInfo.health_fields.invoice_period_end_date }}</div>
                                        <div v-if="healthInfo.health_fields.users_info">
                                            <b>Usuarios en factura:</b> {{ healthInfo.health_fields.users_info.length }}
                                        </div>
                                    </div>
                                    <div class="small text-muted mt-1">Esta información se copiará automáticamente a la nota y no puede editarse.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de usuarios del sector salud (referencia) -->
                        <div class="row" v-if="note && healthInfo && healthInfo.health_fields && healthInfo.health_fields.users_info && healthInfo.health_fields.users_info.length">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">Usuarios sector salud (referencia)</div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Identificación</th>
                                                        <th>Nombre</th>
                                                        <th>Tipo usuario</th>
                                                        <th>Cobertura</th>
                                                        <th>Método contrato</th>
                                                        <th>Autorización</th>
                                                        <th>MIPRES</th>
                                                        <th>Copago</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(u, idx) in healthInfo.health_fields.users_info" :key="idx">
                                                        <td>
                                                            <div>{{ labelDocIdType(u.health_type_document_identification_id) }} {{ u.identification_number }}</div>
                                                        </td>
                                                        <td>
                                                            {{ [u.first_name, u.middle_name, u.surname, u.second_surname].filter(Boolean).join(' ') }}
                                                        </td>
                                                        <td>{{ labelUserType(u.health_type_user_id) }}</td>
                                                        <td>{{ labelCoverage(u.health_coverage_id) }}</td>
                                                        <td>{{ labelContractMethod(u.health_contracting_payment_method_id) }}</td>
                                                        <td>{{ u.autorization_numbers || '-' }}</td>
                                                        <td>{{ u.mipres || '-' }}</td>
                                                        <td>{{ ratePrefix() }} {{ Number(u.co_payment || 0).toFixed(2) | numberFormat }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-4 col-lg-4 pb-2">
                                <div class="form-group" :class="{'has-danger': errors.type_document_id}">
                                    <label class="control-label">Tipo de nota</label>
                                    <el-select v-model="form.type_document_id" filterable @change="changeDocumentType" popper-class="el-select-document_type" dusk="type_document_id" class="border-left rounded-left border-info">
                                        <el-option v-for="option in type_documents" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.type_document_id" v-text="errors.type_document_id[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-4 pb-2">
                                <div class="form-group" :class="{'has-danger': errors.note_concept_id}">
                                    <label class="control-label">Concepto</label>
                                    <el-select v-model="form.note_concept_id" filterable  popper-class="el-select-document_type" dusk="note_concept_id" class="border-left rounded-left border-info">
                                        <el-option v-for="option in note_concepts" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.note_concept_id" v-text="errors.note_concept_id[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-2 col-lg-2">
                                <div class="form-group" :class="{'has-danger': errors.date_issue}">
                                    <label class="control-label">Fec. Emisión</label>
                                    <el-date-picker v-model="form.date_issue" type="date" value-format="yyyy-MM-dd" :clearable="false" @change="changeDateOfIssue" :picker-options="datEmision"></el-date-picker>
                                    <small class="form-control-feedback" v-if="errors.date_issue" v-text="errors.date_issue[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-2 col-lg-2">
                                <div class="form-group" :class="{'has-danger': errors.currency_id}">
                                    <label class="control-label">Moneda</label>
                                    <el-select v-model="form.currency_id" disabled @change="changeCurrencyType" filterable>
                                        <el-option v-for="option in currencies" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.currency_id" v-text="errors.currency_id[0]"></small>
                                </div>
                            </div>
                        </div>

                        <!-- Numeración manual -->
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <el-checkbox v-model="manualNumberEnabled" @change="onToggleManualNumber">Asignar número manualmente</el-checkbox>
                            </div>
                        </div>
                        <div class="row" v-if="manualNumberEnabled">
                            <div class="col-md-2 col-lg-2 pb-2">
                                <div class="form-group">
                                    <label class="control-label">Prefijo</label>
                                    <el-input v-model="manualPrefix" disabled></el-input>
                                </div>
                            </div>
                            <div class="col-md-3 col-lg-3 pb-2">
                                <div class="form-group">
                                    <label class="control-label">Número</label>
                                    <el-input v-model.number="manualNumber" @blur="validateManualNumber" placeholder="Ingrese el número"/>
                                </div>
                                <div v-if="numberValidationMessage" :class="{'text-success': numberAvailable, 'text-danger': !numberAvailable}">
                                    {{ numberValidationMessage }}
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6 col-lg-6 pb-2">
                                <div class="form-group" :class="{'has-danger': errors.customer_id}">
                                    <label class="control-label">Cliente</label>
                                    <el-select v-model="form.customer_id" :disabled="note !== null" filterable @change="changeCustomer" popper-class="el-select-document_type" dusk="customer_id" class="border-left rounded-left border-info">
                                        <el-option v-for="option in customers" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.customer_id" v-text="errors.customer_id[0]"></small>
                                </div>
                            </div>

                            <div v-if="note == null" class="col-md-2 col-lg-2 pb-2">
                                <div class="form-group" :class="{'has-danger': errors.start_invoice_period}">
                                    <label class="control-label">Ini. Periodo Facturación</label>
                                    <el-date-picker v-model="form.start_invoice_period" type="date" value-format="yyyy-MM-dd" :clearable="false" ></el-date-picker>
                                    <small class="form-control-feedback" v-if="errors.start_invoice_period" v-text="errors.start_invoice_period[0]"></small>
                                </div>
                            </div>

                            <div v-if="note == null" class="col-md-2 col-lg-2 pb-2">
                                <div class="form-group" :class="{'has-danger': errors.end_invoice_period}">
                                    <label class="control-label">Fin. Periodo Facturación</label>
                                    <el-date-picker v-model="form.end_invoice_period" type="date" value-format="yyyy-MM-dd" :clearable="false" ></el-date-picker>
                                    <small class="form-control-feedback" v-if="errors.end_invoice_period" v-text="errors.end_invoice_period[0]"></small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Observaciones</label>
                                    <el-input
                                            type="textarea"
                                            autosize
                                            :rows="1"
                                            v-model="form.observation"
                                            maxlength="250"
                                            show-word-limit>
                                    </el-input>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <el-switch
                                    v-model="health_sector"
                                    :disabled="!!note"
                                    active-text="Sector salud"
                                    inactive-text="Sector salud"
                                    @change="toggleHealthSector"
                                ></el-switch>
                                <small class="text-muted d-block" v-if="!note">
                                    Activa esta opción si la nota pertenece al sector salud y requiere usuarios asociados.
                                </small>
                            </div>
                        </div>

                        <div class="row mt-3" v-if="health_sector && !note">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <strong>Datos sector salud</strong>
                                            <div class="small text-muted" v-if="form.health_fields && (form.health_fields.invoice_period_start_date || form.health_fields.invoice_period_end_date)">
                                                Periodo: {{ form.health_fields.invoice_period_start_date || '-' }}
                                                &nbsp;al&nbsp;
                                                {{ form.health_fields.invoice_period_end_date || '-' }}
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap">
                                            <el-button type="primary" size="mini" class="mr-2 mb-2" @click="clickAddHealthData">Periodo facturación</el-button>
                                            <el-button type="primary" size="mini" class="mb-2" @click="clickAddHealthUser">Agregar usuario</el-button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div v-if="!form.health_users || form.health_users.length === 0" class="p-3 text-center text-muted">
                                            Sin usuarios registrados. Agrega al menos un usuario del servicio de salud.
                                        </div>
                                        <div class="table-responsive" v-else>
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Identificación</th>
                                                        <th>Nombre</th>
                                                        <th>Tipo usuario</th>
                                                        <th>Cobertura</th>
                                                        <th>Método contrato</th>
                                                        <th>Autorización</th>
                                                        <th>MIPRES</th>
                                                        <th>Copago</th>
                                                        <th class="text-right">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(user, idx) in form.health_users" :key="idx">
                                                        <td>{{ labelDocIdType(user.health_type_document_identification_id) }} {{ user.identification_number }}</td>
                                                        <td>{{ [user.first_name, user.middle_name, user.surname, user.second_surname].filter(Boolean).join(' ') }}</td>
                                                        <td>{{ labelUserType(user.health_type_user_id) }}</td>
                                                        <td>{{ labelCoverage(user.health_coverage_id) }}</td>
                                                        <td>{{ labelContractMethod(user.health_contracting_payment_method_id) }}</td>
                                                        <td>{{ user.autorization_numbers || '-' }}</td>
                                                        <td>{{ user.mipres || '-' }}</td>
                                                        <td>{{ ratePrefix() }} {{ Number(user.co_payment || 0).toFixed(2) | numberFormat }}</td>
                                                        <td class="text-right">
                                                            <el-button type="text" size="mini" @click="clickEditHealthUser(user, idx)">Editar</el-button>
                                                            <el-button type="text" size="mini" class="text-danger" @click="clickRemoveHealthUser(idx)">Eliminar</el-button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th class="font-weight-bold">Descripción</th>
                                                <th class="text-center font-weight-bold">Unidad</th>
                                                <th class="text-right font-weight-bold">Cantidad</th>
                                                <th class="text-right font-weight-bold">Precio Unitario</th>
                                                <th class="text-right font-weight-bold">Subtotal</th>
                                                <th class="text-right font-weight-bold">Descuento</th>
                                                <th class="text-right font-weight-bold">Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody v-if="form.items.length > 0">
                                            <tr v-for="(row, index) in form.items" :key="index">
                                                <td>{{index + 1}}</td>
                                                <td>{{row.item.name}}
                                                    {{row.item.presentation.hasOwnProperty('description') ? row.item.presentation.description : ''}}
                                                    <br/>
                                                    <small>{{row.tax ? row.tax.name : 'EXCLUIDO'}}</small>
                                                </td>
                                                <td class="text-center">{{row.item.unit_type.name}}</td>
                                                <!-- <td class="text-center">{{(row.item.hasOwnProperty('unit_type') ) ? row.item.unit_type.name : row.item.item.unit_type.name}}</td> -->


                                                <td class="text-right">{{row.quantity}}</td>
                                                <!--<td class="text-right" v-else ><el-input-number :min="0.01" v-model="row.quantity"></el-input-number> </td> -->

                                                <td class="text-right">{{ratePrefix()}} {{row.price | numberFormat}}</td>
                                                <!--<td class="text-right" v-else ><el-input-number :min="0.01" v-model="row.unit_price"></el-input-number> </td> -->


                                                <td class="text-right">{{ratePrefix()}} {{row.subtotal | numberFormat}}</td>
                                                <td class="text-right">{{ratePrefix()}} {{row.discount | numberFormat}}</td>
                                                <td class="text-right">{{ratePrefix()}} {{row.total | numberFormat}}</td>
                                                <td class="text-right">
                                                    <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickRemoveItem(index)">x</button>
                                                    <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click="ediItem(row, index)" ><span style='font-size:10px;'>&#9998;</span> </button>

                                                </td>
                                            </tr>
                                            <tr><td colspan="9"></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-6 d-flex align-items-end">
                                <div class="form-group">
                                    <button type="button" class="btn waves-effect waves-light btn-primary" @click.prevent="clickAddItemInvoice">+ Agregar Producto</button>
<!--                                    <button type="button" class="ml-3 btn waves-effect waves-light btn-primary" @click.prevent="clickAddRetention">+ Agregar Retención</button> -->
                                </div>
                            </div>

                            <div class="col-md-12" style="display: flex; flex-direction: column; align-items: flex-end;" v-if="form.items.length > 0">
                                <table>

                                    <tr>
                                        <td>TOTAL VENTA</td>
                                        <td>:</td>
                                        <td class="text-right">{{ratePrefix()}} {{ form.sale | numberFormat }}</td>
                                    </tr>
                                    <tr >
                                        <td>TOTAL DESCUENTO (-)</td>
                                        <td>:</td>
                                        <td class="text-right">{{ratePrefix()}} {{ form.total_discount | numberFormat }}</td>
                                    </tr>
                                    <template v-for="(tax, index) in form.taxes">
                                        <tr v-if="((tax.total > 0) && (!tax.is_retention))" :key="index">
                                            <td >
                                                {{tax.name}}(+)
                                            </td>
                                            <td>:</td>
                                            <td class="text-right">{{ratePrefix()}} {{ Number(tax.total).toFixed(2) | numberFormat }}</td>
                                        </tr>
                                    </template>
                                    <tr>
                                        <td>SUBTOTAL</td>
                                        <td>:</td>
                                        <td class="text-right">{{ratePrefix()}} {{ form.subtotal | numberFormat }}</td>
                                    </tr>

                                    <template v-for="(tax, index) in form.taxes">
                                        <tr v-if="((tax.is_retention) && (tax.apply))" :key="index">

                                            <td>{{tax.name}}(-)</td>
                                            <td>:</td>
                                            <!-- <td class="text-right">
                                                {{ratePrefix()}} {{Number(tax.retention).toFixed(2)}}
                                            </td> -->
                                            <td class="text-right" width=35%>
                                                <el-input v-model="tax.retention" readonly >
                                                    <span slot="prefix" class="c-m-top">{{ ratePrefix() }}</span>
                                                    <i slot="suffix" class="el-input__icon el-icon-delete pointer"  @click="clickRemoveRetention(index)"></i>
                                                    <!-- <el-button slot="suffix" icon="el-icon-delete" @click="clickRemoveRetention(index)"></el-button> -->
                                                </el-input>
                                            </td>
                                        </tr>
                                    </template>

                                </table>

                                <template>
                                    <h3 class="text-right"><b>TOTAL: </b>{{ratePrefix()}} {{ form.total | numberFormat }}</h3>
                                </template>
                            </div>

                        </div>

                    </div>


                    <div class="form-actions text-right mt-4">
                        <el-button @click.prevent="close()">Cancelar</el-button>
                        <el-button class="submit" type="primary" native-type="submit" :loading="loading_submit" v-if="form.items.length > 0">Generar</el-button>
                    </div>
                </form>
            </div>

        <document-form-item :showDialog.sync="showDialogAddItem"
                           :recordItem="recordItem"
                           :isEditItemNote="false"
                           :operation-type-id="form.operation_type_id"
                           :currency-type-id-active="form.currency_id"
                           :currency-type-symbol-active="ratePrefix()"
                           :exchange-rate-sale="form.exchange_rate_sale"
                           :typeUser="typeUser"
                           @add="addRow"></document-form-item>

        <person-form :showDialog.sync="showDialogNewPerson"
                       type="customers"
                       :external="true"
                       :input_person="input_person"
                       :type_document_id = form.type_document_id></person-form>

        <document-options :showDialog.sync="showDialogOptions"
                            :recordId="documentNewId"
                            :showDownload="true"
                            :showClose="false"></document-options>

        <document-form-retention :showDialog.sync="showDialogAddRetention"
                           @add="addRowRetention"></document-form-retention>

        <document-health-data
            :showDialog.sync="showDialogHealthData"
            :health_fields="form.health_fields"
            @addHealthData="addHealthData"
        ></document-health-data>

        <document-health-user
            :showDialog.sync="showDialogAddHealthUser"
            :recordItemHealthUser="recordItemHealthUser"
            @add="addRowHealthUser"
        ></document-health-user>

        <!-- Modal conflicto de numeración -->
        <el-dialog title="Número en uso" :visible.sync="showNumberConflictModal" width="30%">
            <p>El número {{ manualPrefix }}-{{ manualNumber }} ya existe. ¿Desea usar el sugerido {{ manualPrefix }}-{{ suggestedNumber }}?</p>
            <span slot="footer" class="dialog-footer">
                <el-button @click="showNumberConflictModal=false">Cancelar</el-button>
                <el-button type="primary" @click="acceptSuggestedNumber">Usar sugerido</el-button>
            </span>
        </el-dialog>


    </div>
    </div>
</template>

<style>

.c-m-top{
    margin-top: 4.5px !important;
}

.pointer{
    cursor: pointer;
}

.input-custom{
    width: 50% !important;
}

.el-textarea__inner {
    height: 65px !important;
    min-height: 65px !important;
}

</style>
<script>
    import DocumentFormItem from './partials/item.vue'
    import DocumentFormRetention from './partials/retention.vue'
    import PersonForm from '@views/persons/form.vue'
    // import DocumentOptions from '../documents/partials/options.vue'
    // import {functions, exchangeRate} from '@mixins/functions'
    // import {calculateRowItem} from '../../../helpers/functions'
    // import Helper from "../../../mixins/Helper";
    import DocumentOptions from './partials/options.vue'
    import DocumentHealthData from './partials/health_fields.vue'
    import DocumentHealthUser from './partials/health_users.vue'

    export default {
        props: ['typeUser', 'note', 'invoice'],
        components: {PersonForm, DocumentFormItem, DocumentFormRetention, DocumentOptions, DocumentHealthData, DocumentHealthUser},
        // mixins: [Helper],
        data() {
            return {
                datEmision: {
                  disabledDate(time) {
                    return time.getTime() > moment();
                  }
                },
                input_person:{},
                company:{},
                health_sector: false,
                is_client:false,
                recordItem: null,
                recordItemHealthUser: null,
                resource: 'co-documents',
                showDialogAddItem: false,
                showDialogAddRetention: false,
                showDialogNewPerson: false,
                showDialogOptions: false,
                showDialogHealthData: false,
                showDialogAddHealthUser: false,
                loading_submit: false,
                loading_form: false,
                errors: {},
                form: {},
                healthInfo: null,
                // Tablas de referencia para etiquetas de usuarios de salud
                health_type_document_identifications: [],
                health_type_users: [],
                health_contracting_payment_methods: [],
                health_coverages: [],
                note_concepts: [],
                type_invoices: [],
                currencies: [],
                all_customers: [],
                payment_methods: [],
                payment_forms: [],
                form_payment: {},
                customers: [],
                all_series: [],
                series: [],
                currency_type: {},
                documentNewId: null,
                total_global_discount:0,
                loading_search:false,
                taxes: [],
                type_documents: [],
                all_type_documents: [],
                noteService: {},
                // Manual numbering state
                manualNumberEnabled: false,
                manualPrefix: '',
                manualNumber: null,
                numberAvailable: true,
                numberValidationMessage: '',
                showNumberConflictModal: false,
                suggestedNumber: null,
            }
        },

        async created() {
            await this.initForm()
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.all_customers = response.data.customers;
                    this.taxes = response.data.taxes
                    // console.log(this.taxes)
//                    this.all_type_documents = response.data.type_documents.filter(doc => doc.code === 4 || doc.code === 5);
                    this.all_type_documents = response.data.type_documents;
                    this.currencies = response.data.currencies
                    this.payment_methods = response.data.payment_methods
                    this.payment_forms = response.data.payment_forms
                    this.filterCustomers();
                    this.typeNoteDocuments()
                    this.load_invoice();
                })
            if(this.note)
                await this.getRecordCustomer()
            else
                this.customers = this.all_customers
            this.fetchHealthTables();
            this.loading_form = true
            if(this.note){
                this.$eventHub.$on('reloadDataPersons', (customer_id) => {
                    this.reloadDataCustomers(customer_id)
                })
                // Cargar datos de salud de la factura referenciada
                this.fetchHealthInfo();
            }
            this.$eventHub.$on('initInputPerson', () => {
                this.initInputPerson()
            })
        },

        methods: {
            getRecordCustomer(){
                this.$http.get(`/${this.resource}/search/customer/${this.form.customer_id}`).then((response) => {
                    this.customers = response.data.customers
                    // this.form.customer_id = this.document.customer_id
                })
            },

            typeNoteDocuments() {
                console.log(this.all_type_documents)
                this.type_documents = this.all_type_documents.filter(row => row.code === "4" || row.code === "5");
            },

            onToggleManualNumber() {
                if (this.manualNumberEnabled) {
                    // Prefijo desde el tipo de documento seleccionado
                    const typeDocument = this.type_documents.find(x => x.id == this.form.type_document_id);
                    this.manualPrefix = typeDocument ? typeDocument.prefix : '';
                    // Exponer en form para backend
                    this.form.manual_prefix = this.manualPrefix;
                } else {
                    this.manualPrefix = '';
                    this.manualNumber = null;
                    this.form.manual_prefix = null;
                    this.form.manual_number = null;
                    this.numberValidationMessage = '';
                }
            },

            ratePrefix(tax = null) {
                if ((tax != null) && (!tax.is_fixed_value)) return null;
                return (this.company.currency != null) ? this.company.currency.symbol : '$';
            },

            keyupCustomer(){

                if(this.input_person.number){

                    if(!isNaN(parseInt(this.input_person.number))){

                        switch (this.input_person.number.length) {
                            case 8:
                                this.input_person.identity_type_document_id = '1'
                                this.showDialogNewPerson = true
                                break;

                            case 11:
                                this.input_person.identity_type_document_id = '6'
                                this.showDialogNewPerson = true
                                break;
                            default:
                                this.input_person.identity_type_document_id = '6'
                                this.showDialogNewPerson = true
                                break;
                        }
                    }
                }
            },
            clickAddItemInvoice(){
                this.recordItem = null
                this.showDialogAddItem = true
            },
            clickAddRetention(){
                this.showDialogAddRetention = true
            },

            getFormatUnitPriceRow(unit_price){
                return _.round(unit_price, 6)
                // return unit_price.toFixed(6)
            },


            ediItem(row, index)
            {
                row.indexi = index
                this.recordItem = row
                this.showDialogAddItem = true

            },
            searchRemoteCustomers(input) {

                if (input.length > 0) {

                    this.loading_search = true
                    let parameters = `input=${input}&type_document_id=${this.form.type_document_id}&operation_type_id=${this.form.operation_type_id}`

                    this.$http.get(`/${this.resource}/search/customers?${parameters}`)
                            .then(response => {
                                this.customers = response.data.customers
                                this.loading_search = false
                                this.input_person.number = null

                                if(this.customers.length == 0){
                                    this.filterCustomers()
                                    this.input_person.number = input
                                }
                            })
                } else {
                    this.filterCustomers()
                    this.input_person.number = null
                }

            },

            load_invoice(){
                if (typeof this.invoice !== 'undefined'){
                    this.form.items = this.invoice ? this.prepareItems(this.invoice.items) : [];
                    this.calculateTotal();
                }
            },

            prepareItems(items){
                return items.map(row => {
                    row.item = this.prepareIndividualItem(row)
                    row.price = row.unit_price
                    row.id = row.item.id
                    return row
                })
            },

            prepareIndividualItem(row){
                const new_item = row.item
                new_item.presentation = (row.presentation && !_.isEmpty(row.presentation)) ? row.presentation : {}
                return new_item
            },

            initForm() {
//                console.log(this.note)
                this.form = {
                    customer_id: this.note ? this.note.customer_id : null,
                    type_document_id: null,
                    note_concept_id: null,
                    currency_id: this.note ? this.note.currency_id : 170,
                    date_issue: moment().format('YYYY-MM-DD'),
                    start_invoice_period: moment().format('YYYY-MM-DD'),
                    end_invoice_period: moment().format('YYYY-MM-DD'),
                    date_expiration: null,
                    type_invoice_id: null,
                    resolution_number: this.note && this.note.resolution_number ? this.note.resolution_number : null,
                    prefix: this.note && this.note.prefix ? this.note.prefix : null,
                    manual_prefix: null,
                    total_discount: 0,
                    total_tax: 0,
                    watch: false,
                    subtotal: 0,
                    items: [],
                    taxes: [],
                    total: 0,
                    sale: 0,
                    observation: null,
                    time_days_credit: 0,
                    id: this.note ? this.note.id : null,
                    reference_id: this.note ? this.note.id : null,
                    payment_form_id: this.note ? this.note.payment_form_id : 1,
                    payment_method_id: this.note ? this.note.payment_method_id : 1,
                    correlative_api: this.note ? this.note.correlative_api : null,
                    response_api_cufe: this.note ? this.note.response_api_cufe : null,
                    note_service: {},
                    health_fields: {},
                    health_users: [],
                    health_type_operation_id: 1
                }

                this.noteService.customer = {
                    identification_number: this.note ? this.note.customer.number : null,
                    name: this.note ? this.note.customer.name : null,
                    phone: this.note ? this.note.customer.telephone : null,
                    address: this.note ? this.note.customer.address : null,
                    email: this.note ? this.note.customer.email : null,
                    merchant_registration: "0000-00",
                    type_document_identification_id: this.note ? this.note.customer.identity_document_type_id : null,
                    type_organization_id: this.note ? this.note.customer.type_person_id : null,
                    municipality_id_fact: this.note ? this.note.customer.city_id : null,
                    type_regime_id: this.note ? this.note.customer.type_regime_id : null
                }

                if(this.note){
                    if (this.note.customer.type_person_id == 1) {
                        this.noteService.customer.dv = this.note.customer.dv;
                    }
                    this.applyHealthSnapshot(this.note.health_fields || {});
                }
                else
                    this.noteService.customer.dv = null;

                if(!this.note){
                    this.updateHealthSectorState();
                }

                this.errors = {}
                this.$eventHub.$emit('eventInitForm')

                this.initInputPerson()

            },
            async fetchHealthInfo(){
                try {
                    const { data } = await this.$http.get(`/co-documents/health/invoice-info`, { params: { id: this.form.reference_id }})
                    if(data && data.success){
                        this.healthInfo = data.data
                    }
                } catch (e) {
                    // Silencioso: si no hay datos de salud o error, no bloqueamos
                    this.healthInfo = null
                }
            },
            async fetchHealthTables(){
                try {
                    const { data } = await this.$http.get(`/${this.resource}/health/tables`)
                    this.health_type_document_identifications = data.health_type_document_identifications || []
                    this.health_type_users = data.health_type_users || []
                    this.health_contracting_payment_methods = data.health_contracting_payment_methods || []
                    this.health_coverages = data.health_coverages || []
                } catch (e) {
                    // no-op
                }
            },
            decodeHealthFields(raw) {
                if (!raw) return {};
                let parsed = raw;
                if (typeof parsed === 'string') {
                    try {
                        parsed = JSON.parse(parsed);
                    } catch (error) {
                        return {};
                    }
                }
                if (parsed && typeof parsed === 'object' && parsed.health_fields && !parsed.invoice_period_start_date) {
                    parsed = parsed.health_fields;
                }

                return (parsed && typeof parsed === 'object') ? JSON.parse(JSON.stringify(parsed)) : {};
            },
            sanitizeHealthUsers(users) {
                if (!Array.isArray(users)) return [];
                return users.map(user => JSON.parse(JSON.stringify(user || {})));
            },
            applyHealthSnapshot(healthFields) {
                if (!this.form) {
                    return;
                }

                const normalizedFields = this.decodeHealthFields(healthFields);

                if (!normalizedFields || Object.keys(normalizedFields).length === 0) {
                    this.form.health_fields = {};
                    this.form.health_users = [];
                    this.form.health_type_operation_id = this.form.health_type_operation_id || 1;
                    this.updateHealthSectorState();
                    return;
                }

                const start = normalizedFields.invoice_period_start_date
                    ? moment(normalizedFields.invoice_period_start_date).format('YYYY-MM-DD')
                    : (this.form.start_invoice_period || null);
                const end = normalizedFields.invoice_period_end_date
                    ? moment(normalizedFields.invoice_period_end_date).format('YYYY-MM-DD')
                    : (this.form.end_invoice_period || null);
                const operation = normalizedFields.health_type_operation_id || this.form.health_type_operation_id || 1;
                const users = this.sanitizeHealthUsers(normalizedFields.users_info || normalizedFields.users || []);

                if (start) this.form.start_invoice_period = start;
                if (end) this.form.end_invoice_period = end;

                this.form.health_fields = {
                    invoice_period_start_date: start,
                    invoice_period_end_date: end,
                    health_type_operation_id: operation,
                    users_info: users,
                };
                this.form.health_users = users;
                this.form.health_type_operation_id = operation;
                this.updateHealthSectorState();
                this.refreshHealthState();
            },
            updateHealthSectorState(forceValue = null) {
                if (forceValue !== null) {
                    this.health_sector = !!forceValue;
                    return;
                }
                const currentForm = this.form || {};
                const hasUsers = Array.isArray(currentForm.health_users) && currentForm.health_users.length > 0;
                const hasFields = currentForm.health_fields && Object.keys(currentForm.health_fields).length > 0;
                if (hasUsers || hasFields) {
                    this.health_sector = true;
                } else if (!this.note) {
                    this.health_sector = false;
                }
            },
            buildHealthPayload() {
                const fields = this.form.health_fields || {};
                const users = Array.isArray(this.form.health_users) ? this.form.health_users : [];
                const start = fields.invoice_period_start_date || this.form.start_invoice_period;
                const end = fields.invoice_period_end_date || this.form.end_invoice_period;

                if (!start || !end || users.length === 0) {
                    return null;
                }

                const operation = fields.health_type_operation_id || this.form.health_type_operation_id || 1;
                const normalizedUsers = users.map(user => JSON.parse(JSON.stringify(user || {})));

                return {
                    invoice_period_start_date: moment(start).format('YYYY-MM-DD'),
                    invoice_period_end_date: moment(end).format('YYYY-MM-DD'),
                    health_type_operation_id: Number(operation) || 1,
                    users_info: normalizedUsers,
                };
            },
            labelFrom(list, id, key='name'){
                const row = list.find(x => String(x.id) === String(id))
                return row ? row[key] : id
            },
            labelDocIdType(id){ return this.labelFrom(this.health_type_document_identifications, id, 'code') },
            labelUserType(id){ return this.labelFrom(this.health_type_users, id) },
            labelContractMethod(id){ return this.labelFrom(this.health_contracting_payment_methods, id) },
            labelCoverage(id){ return this.labelFrom(this.health_coverages, id) },
            toggleHealthSector(value) {
                if (this.note) {
                    return;
                }
                if (value) {
                    if (!this.form.health_fields || Object.keys(this.form.health_fields).length === 0) {
                        this.form.health_fields = {
                            invoice_period_start_date: this.form.start_invoice_period || null,
                            invoice_period_end_date: this.form.end_invoice_period || null,
                            health_type_operation_id: this.form.health_type_operation_id || 1,
                            users_info: Array.isArray(this.form.health_users) ? this.form.health_users : [],
                        };
                    }
                    this.updateHealthSectorState(true);
                } else {
                    this.form.health_fields = {};
                    this.form.health_users = [];
                    this.form.health_type_operation_id = 1;
                    this.updateHealthSectorState(false);
                }
                this.refreshHealthState();
            },
            refreshHealthState() {
                if (!this.form) {
                    return;
                }
                if (!this.health_sector) {
                    delete this.noteService.health_fields;
                    return;
                }
                if (!this.form.health_fields) {
                    this.form.health_fields = {};
                }
                const operation = this.form.health_fields.health_type_operation_id || this.form.health_type_operation_id || 1;
                this.form.health_type_operation_id = operation;
                this.form.health_fields.health_type_operation_id = operation;
                const users = Array.isArray(this.form.health_users) ? this.form.health_users : [];
                const normalizedUsers = users.map(user => JSON.parse(JSON.stringify(user || {})));
                this.form.health_users = normalizedUsers;
                this.form.health_fields.users_info = normalizedUsers;
                const payload = this.buildHealthPayload();
                if (payload) {
                    this.noteService.health_fields = JSON.parse(JSON.stringify(payload));
                } else {
                    delete this.noteService.health_fields;
                }
            },
            clickAddHealthData() {
                if (!this.health_sector && !this.note) {
                    this.updateHealthSectorState(true);
                }
                this.showDialogHealthData = true;
            },
            clickAddHealthUser() {
                if (!this.health_sector && !this.note) {
                    this.updateHealthSectorState(true);
                }
                this.recordItemHealthUser = null;
                this.showDialogAddHealthUser = true;
            },
            clickEditHealthUser(user, index) {
                if (this.note) {
                    return;
                }
                const record = JSON.parse(JSON.stringify(user || {}));
                record.indexi = index;
                this.recordItemHealthUser = record;
                this.showDialogAddHealthUser = true;
            },
            clickRemoveHealthUser(index) {
                if (this.note) {
                    return;
                }
                if (index < 0 || index >= this.form.health_users.length) {
                    return;
                }
                this.form.health_users.splice(index, 1);
                this.refreshHealthState();
            },
            addHealthData(health_fields) {
                const start = health_fields.invoice_period_start_date ? moment(health_fields.invoice_period_start_date).format('YYYY-MM-DD') : null;
                const end = health_fields.invoice_period_end_date ? moment(health_fields.invoice_period_end_date).format('YYYY-MM-DD') : null;
                const currentFields = this.form.health_fields || {};
                const currentOperation = currentFields.health_type_operation_id || this.form.health_type_operation_id || 1;
                this.form.health_fields = Object.assign({}, currentFields, {
                    invoice_period_start_date: start,
                    invoice_period_end_date: end,
                    health_type_operation_id: currentOperation,
                });
                if (!this.note) {
                    if (start) this.form.start_invoice_period = start;
                    if (end) this.form.end_invoice_period = end;
                }
                this.updateHealthSectorState(true);
                this.refreshHealthState();
            },
            addRowHealthUser(row) {
                const record = JSON.parse(JSON.stringify(row || {}));
                if (this.recordItemHealthUser && typeof this.recordItemHealthUser.indexi === 'number') {
                    this.$set(this.form.health_users, this.recordItemHealthUser.indexi, record);
                } else {
                    this.form.health_users.push(record);
                }
                this.recordItemHealthUser = null;
                this.updateHealthSectorState(true);
                this.refreshHealthState();
            },
            initInputPerson(){
                this.input_person = {
                    number:null,
                    identity_type_document_id:null
                }
            },
            resetForm() {
                this.activePanel = 0
                this.initForm()
            },
            async changeOperationType() {
                await this.filterCustomers();
                await this.setDataDetraction();
            },
            changeEstablishment() {
                this.establishment = _.find(this.establishments, {'id': this.form.establishment_id})
                this.filterSeries()
            },

            changeDocumentType() {
                this.conceptss()
                const typeDocument = this.type_documents.find(x => x.id == this.form.type_document_id);
                this.syncResolutionMetadata(typeDocument);
                if (this.manualNumberEnabled) {
                    this.manualPrefix = typeDocument && typeDocument.prefix ? typeDocument.prefix : '';
                    this.form.manual_prefix = this.manualPrefix || null;
                }
            },

            syncResolutionMetadata(typeDocument = null) {
                const target = typeDocument || this.type_documents.find(x => x.id == this.form.type_document_id);
                const resolutionNumber = target && target.resolution_number ? target.resolution_number : null;
                const prefix = target && target.prefix ? target.prefix : null;

                this.form.resolution_number = resolutionNumber;
                this.form.prefix = prefix;

                if (!this.manualNumberEnabled) {
                    this.form.manual_prefix = null;
                }

                if (!this.noteService) {
                    this.noteService = {};
                }

                if (resolutionNumber !== null && resolutionNumber !== undefined) {
                    this.noteService.resolution_number = resolutionNumber;
                } else {
                    this.noteService.resolution_number = '';
                }

                if (prefix !== null && prefix !== undefined) {
                    this.noteService.prefix = prefix;
                } else {
                    this.noteService.prefix = '';
                }
            },

            conceptss() {
                this.form.note_concept_id = null;
                if (this.form.type_document_id != null)
                    this.getConcepts(this.form.type_document_id).then(
                        rows => (this.note_concepts = rows)
                    );
            },

            getConcepts(val) {
                return axios.post(`/concepts/${val}`).then(response => {
                                if(!this.note)
                                    if(val == 3)
                                        response.data.splice(1, 1);
                                return response.data;
                            })
                            .catch(error => {
                                console.log(error)
                            });
            },

            cleanCustomer(){
                this.form.customer_id = null
                // this.customers = []
            },

            changeDateOfIssue() {
                this.form.date_expiration = this.form.date_of_issue
                this.searchExchangeRateByDate(this.form.date_of_issue).then(response => {
                    this.form.exchange_rate_sale = response
                })
            },

            assignmentDateOfPayment(){
                this.form.payments.forEach((payment)=>{
                    payment.date_of_payment = this.form.date_of_issue
                })
            },

            filterSeries() {
                this.form.series_id = null
                this.series = _.filter(this.all_series, {'establishment_id': this.form.establishment_id,
                                                         'type_document_id': this.form.type_document_id,
                                                         'contingency': this.is_contingency});
                this.form.series_id = (this.series.length > 0)?this.series[0].id:null
            },
            filterCustomers() {
                this.customers = this.all_customers
            },
            addRow(row) {
                if(this.recordItem)
                {
                    //this.form.items.$set(this.recordItem.indexi, row)
                    this.form.items[this.recordItem.indexi] = row
                    this.recordItem = null
                }
                else{
                    this.form.items.push(JSON.parse(JSON.stringify(row)));
                }
                // console.log(this.form)
                this.calculateTotal();
            },
            async addRowRetention(row){

                await this.taxes.forEach(tax => {
                    if(tax.id == row.tax_id){
                        tax.apply = true
                    }
                });

                await this.calculateTotal()

            },
            cleanTaxesRetention(tax_id){

                this.taxes.forEach(tax => {
                    if(tax.id == tax_id){
                        tax.apply = false
                        tax.retention = 0
                    }
                })

            },
            async clickRemoveRetention(index){
                // console.log(index, "w")
                this.form.taxes[index].apply = false
                this.form.taxes[index].retention = 0
                await this.cleanTaxesRetention(this.form.taxes[index].id)
                await this.calculateTotal()

            },
            clickRemoveItem(index) {
                this.form.items.splice(index, 1)
                this.calculateTotal()
            },
            changeCurrencyType() {
                // this.currency_type = _.find(this.currencies, {'id': this.form.currency_id})
                // let items = []
                // this.form.items.forEach((row) => {
                //     items.push(calculateRowItem(row, this.form.currency_id, this.form.exchange_rate_sale))
                // });
                // this.form.items = items
                // this.calculateTotal()
            },
            calculateTotal() {

                this.setDataTotals()

            },
            setDataTotals() {

                // console.log(val)
                let val = this.form
                val.taxes = JSON.parse(JSON.stringify(this.taxes));

                val.items.forEach(item => {
                    item.tax = this.taxes.find(tax => tax.id == item.tax_id);

                    if (
                        item.discount == null ||
                        item.discount == "" ||
                        item.discount > item.price * item.quantity
                    )
                        this.$set(item, "discount", 0);

                    item.total_tax = 0;

                    if (item.tax != null) {
                        let tax = val.taxes.find(tax => tax.id == item.tax.id);

                        if (item.tax.is_fixed_value)

                            item.total_tax = (
                                item.tax.rate * item.quantity -
                                (item.discount < item.price * item.quantity ? item.discount : 0)
                            ).toFixed(2);

                        if (item.tax.is_percentage)

                            item.total_tax = (
                                (item.price * item.quantity -
                                (item.discount < item.price * item.quantity
                                    ? item.discount
                                    : 0)) *
                                (item.tax.rate / item.tax.conversion)
                            ).toFixed(2);

                        if (!tax.hasOwnProperty("total"))
                            tax.total = Number(0).toFixed(2);

                        tax.total = (Number(tax.total) + Number(item.total_tax)).toFixed(2);
                    }

                    item.subtotal = (
                        Number(item.price * item.quantity) + Number(item.total_tax)
                    ).toFixed(2);

                    this.$set(
                        item,
                        "total",
                        (Number(item.subtotal) - Number(item.discount)).toFixed(2)
                    );

                });

                val.subtotal = val.items
                    .reduce(
                        (p, c) => Number(p) + (Number(c.subtotal) - Number(c.discount)),
                        0
                    )
                    .toFixed(2);
                    val.sale = val.items
                    .reduce(
                        (p, c) =>
                        Number(p) + Number(c.price * c.quantity) - Number(c.discount),
                        0
                    )
                    .toFixed(2);
                    val.total_discount = val.items
                    .reduce((p, c) => Number(p) + Number(c.discount), 0)
                    .toFixed(2);
                    val.total_tax = val.items
                    .reduce((p, c) => Number(p) + Number(c.total_tax), 0)
                    .toFixed(2);

                let total = val.items
                    .reduce((p, c) => Number(p) + Number(c.total), 0)
                    .toFixed(2);

                let totalRetentionBase = Number(0);

                // this.taxes.forEach(tax => {
                val.taxes.forEach(tax => {
                    if (tax.is_retention && tax.in_base && tax.apply) {
                        tax.retention = (
                        Number(val.sale) *
                        (tax.rate / tax.conversion)
                        ).toFixed(2);

                        totalRetentionBase =
                        Number(totalRetentionBase) + Number(tax.retention);

                        if (Number(totalRetentionBase) >= Number(val.sale))
                        this.$set(tax, "retention", Number(0).toFixed(2));

                        total -= Number(tax.retention).toFixed(2);
                    }

                    if (
                        tax.is_retention &&
                        !tax.in_base &&
                        tax.in_tax != null &&
                        tax.apply
                    ) {
                        let row = val.taxes.find(row => row.id == tax.in_tax);

                        tax.retention = Number(
                        Number(row.total) * (tax.rate / tax.conversion)
                        ).toFixed(2);

                        if (Number(tax.retention) > Number(row.total))
                        this.$set(tax, "retention", Number(0).toFixed(2));

                        row.retention = Number(tax.retention).toFixed(2);
                        total -= Number(tax.retention).toFixed(2);
                    }
                });

                val.total = Number(total).toFixed(2)

            },
            close() {
                location.href = (this.is_contingency) ? `/contingencies` : `/${this.resource}`
            },
            reloadDataCustomers(customer_id) {
                // this.$http.get(`/${this.resource}/table/customers`).then((response) => {
                //     this.customers = response.data
                //     this.form.customer_id = customer_id
                // })
                this.$http.get(`/${this.resource}/search/customer/${customer_id}`).then((response) => {
                    this.customers = response.data.customers
                    this.form.customer_id = customer_id
                })
            },

            changeCustomer() {
            },

            async submit() {
                if(!this.form.customer_id){
                    return this.$message.error('Debe seleccionar un cliente')
                }
                if(!this.form.note_concept_id){
                    return this.$message.error('Debe seleccionar un concepto')
                }
                if (this.manualNumberEnabled) {
                    if (!this.manualPrefix || !this.manualNumber) {
                        return this.$message.error('Ingrese prefijo y número manual.');
                    }
                    // Enviar al backend
                    this.form.manual_prefix = this.manualPrefix;
                    this.form.manual_number = this.manualNumber;
                }
                if (this.health_sector) {
                    const healthFields = this.form.health_fields || {};
                    if (!healthFields.invoice_period_start_date || !healthFields.invoice_period_end_date) {
                        return this.$message.error('Debe registrar el periodo de facturación del sector salud.');
                    }
                    if (!Array.isArray(this.form.health_users) || this.form.health_users.length === 0) {
                        return this.$message.error('Debe agregar al menos un usuario del sector salud.');
                    }
                }
                const serviceReady = await this.generateNoteService();
                if (!serviceReady) {
                    return;
                }
                this.form.note_service = this.noteService;
                // return
                this.loading_submit = true
               console.log(this.form)
                this.$http.post(`/${this.resource}/note`, this.form).then(response => {
                    if (response.data.success) {
                        this.resetForm();
                        this.documentNewId = response.data.data.id;
                        // this.$message.success(response.data.message);
                        this.showDialogOptions = true;
                    }
                    else {
                        if (response.data && response.data.conflict) {
                            // conflicto por número existente
                            this.suggestedNumber = response.data.suggested || null;
                            this.numberAvailable = false;
                            this.numberValidationMessage = 'Número en uso';
                            this.showNumberConflictModal = true;
                        } else {
                            this.$message.error(response.data.message);
                        }
                    }
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data;
                    }
                    else {
                        this.$message.error(error.response.data.message);
                    }
                }).then(() => {
                    this.loading_submit = false;
                });
            },

            async validateManualNumber() {
                if (!this.manualNumberEnabled || !this.manualPrefix || !this.manualNumber) return;
                try {
                    const { data } = await this.$http.get(`/${this.resource}/validate-number`, { params: { prefix: this.manualPrefix, number: this.manualNumber } });
                    if (data.success) {
                        if (data.available) {
                            this.numberAvailable = true;
                            this.numberValidationMessage = 'Número disponible';
                        } else {
                            this.numberAvailable = false;
                            this.numberValidationMessage = 'Número en uso';
                            this.suggestedNumber = data.suggested;
                            this.showNumberConflictModal = true;
                        }
                    }
                } catch (e) {
                    // ignore
                }
            },

            acceptSuggestedNumber() {
                if (this.suggestedNumber) {
                    this.manualNumber = this.suggestedNumber;
                    this.form.manual_number = this.suggestedNumber;
                    this.numberAvailable = true;
                    this.numberValidationMessage = 'Número sugerido seleccionado';
                }
                this.showNumberConflictModal = false;
            },

            getTypeDocumentService()
            {
                let id = this.form.type_document_id
                let id_service = 0

                if(id == 2)
                {
                    id_service = 5
                }
                else if(id == 3)
                {
                    id_service = 4
                }

                return id_service
            },

            async generateNoteService() {
                // let contex = this
                this.noteService.number = 0;
                this.noteService.type_document_id = await this.getTypeDocumentService();
                this.noteService.date = "";
                this.noteService.time = "";

                // Obtener el tipo de documento para obtener el prefijo
                const typeDocument = this.type_documents.find(x => x.id == this.form.type_document_id);
                this.syncResolutionMetadata(typeDocument);

                if (!this.noteService.resolution_number) {
                    this.$message.error('No se encontró una resolución activa para este tipo de nota. Configure la resolución en DIAN antes de continuar.');
                    return false;
                }

                if(!this.note){
                    if (this.form.type_document_id == 2) {
                        this.noteService.type_operation_id = "5";
                    }
                    else if (this.form.type_document_id == 3) {
                        this.noteService.type_operation_id = "8";
                    }
                    this.noteService.invoice_period = {
                        start_date: moment(this.form.start_invoice_period).format('YYYY-MM-DD'),
                        end_date: moment(this.form.end_invoice_period).format('YYYY-MM-DD')
                    };
                }
//                console.log(this.noteService)
                if(this.note){
                    this.noteService.billing_reference = {
                        number: this.formatBillingReferenceNumber(this.note),
                        uuid: this.note.response_api_cufe,
                        issue_date: moment(this.note.date_issue).format('YYYY-MM-DD')
                    };
                }
                if(!this.note)
                    this.noteService.customer =  this.getCustomer();
                this.noteService.tax_totals = await this.getTaxTotal();
                this.noteService.with_holding_tax_total = await this.getWithHolding();

                if(this.noteService.type_document_id == 4)
                {
                    this.noteService.legal_monetary_totals = await this.getLegacyMonetaryTotal();
                    this.noteService.credit_note_lines = await this.getCreditNoteLines();
                    this.noteService.allowance_charges = await this.createAllowanceCharge(
                        this.noteService.legal_monetary_totals.allowance_total_amount, 
                        this.noteService.legal_monetary_totals.line_extension_amount
                    );
                }
                else if(this.noteService.type_document_id == 5){
                    this.noteService.requested_monetary_totals = await this.getLegacyMonetaryTotal();
                    this.noteService.debit_note_lines = await this.getCreditNoteLines();
                    /*this.noteService.allowance_charges = await this.createAllowanceCharge(
                        this.noteService.requested_monetary_totals.allowance_total_amount, this.noteService.requested_monetary_totals.line_extension_amount
                    );*/
                }
                const healthPayload = this.buildHealthPayload();
                if (healthPayload) {
                    this.noteService.health_fields = JSON.parse(JSON.stringify(healthPayload));
                } else {
                    delete this.noteService.health_fields;
                }

                this.form.resolution_number = this.noteService.resolution_number;
                this.form.prefix = this.noteService.prefix;
                if (this.manualNumberEnabled) {
                    this.form.manual_prefix = this.noteService.prefix;
                }
                return true;
            },

            getCustomer() {
                let customer = this.customers.find(x => x.id == this.form.customer_id);
                console.log(customer)
                let obj = {
                    identification_number: customer.number,
                    name: customer.name,
                    phone: customer.phone,
                    address: customer.address,
                    email: customer.email,
                    merchant_registration: "000000",
                    municipality_id_fact: customer.city_id,
                };
                this.form.customer_id = customer.id
                if (customer.type_person_id == 2) {
                    obj.dv = customer.dv;
                }
                return obj;
            },

            formatBillingReferenceNumber(reference) {
                if (!reference) return '';
                const prefix = reference.prefix ? String(reference.prefix).trim() : '';
                const baseNumber = reference.number ?? reference.correlative_api ?? '';
                const numberStr = baseNumber !== null && baseNumber !== undefined ? String(baseNumber) : '';
                if (prefix) {
                    return `${prefix}${numberStr}`;
                }
                return numberStr;
            },

            getTaxTotal() {
                let tax = [];
                this.form.items.forEach(element => {
                    let find = tax.find(x => element.tax !== undefined && x.tax_id == element.tax.type_tax_id && x.percent == element.tax.rate);
                    if(find)
                    {
                        let indexobj = tax.findIndex(x => x.tax_id == element.tax.type_tax_id && x.percent == element.tax.rate);
                        tax.splice(indexobj, 1);
                        tax.push({
                            tax_id: find.tax_id,
                            tax_amount: this.cadenaDecimales(Number(find.tax_amount) + Number(element.total_tax)),
                            percent: this.cadenaDecimales(find.percent),
                            taxable_amount: this.cadenaDecimales(Number(find.taxable_amount) + Number(element.price) * Number(element.quantity)) - Number(element.discount)
                        });
                    }
                    else {
                        if(element.tax !== undefined){
                            tax.push({
                                tax_id: element.tax.type_tax_id,
                                tax_amount: this.cadenaDecimales(Number(element.total_tax)),
                                percent: this.cadenaDecimales(Number(element.tax.rate)),
                                taxable_amount: this.cadenaDecimales((Number(element.price) * Number(element.quantity)) - Number(element.discount))
                            });
                        }
                    }
                });
                this.tax_amount_calculate = tax;
                return tax;
            },

            getLegacyMonetaryTotal() {
                let line_ext_am = 0;
                let tax_incl_am = 0;
                let allowance_total_amount = 0;
                this.form.items.forEach(element => {
                    line_ext_am += (Number(element.price) * Number(element.quantity)) - Number(element.discount) ;
//                    allowance_total_amount += Number(element.discount);
                });

                let total_tax_amount = 0;
                this.tax_amount_calculate.forEach(element => {
                    total_tax_amount += Number(element.tax_amount);
                });

                let tax_excl_am = 0;
                this.tax_amount_calculate.forEach(element => {
                    tax_excl_am += Number(element.taxable_amount);
                });
                tax_incl_am = line_ext_am + total_tax_amount;

                return {
                    line_extension_amount: this.cadenaDecimales(line_ext_am),
                    tax_exclusive_amount: this.cadenaDecimales(tax_excl_am),
                    tax_inclusive_amount: this.cadenaDecimales(tax_incl_am),
                    allowance_total_amount: this.cadenaDecimales(allowance_total_amount),
                    charge_total_amount: "0.00",
                    payable_amount: this.cadenaDecimales(tax_incl_am)
//                    payable_amount: this.cadenaDecimales(tax_incl_am - allowance_total_amount)
                };
            },

            createAllowanceCharge(amount, base) {
                return [
                    {
                        discount_id: 1,
                        charge_indicator: false,
                        allowance_charge_reason: "DESCUENTO GENERAL",
                        amount: this.cadenaDecimales(amount),
                        base_amount: this.cadenaDecimales(base)
                    }
                ]
            },

            getCreditNoteLines() {
                let data = this.form.items.map(x => {
                    if(x.tax !== undefined){
                        return {
                            unit_measure_id: x.item.unit_type.code, //codigo api dian de unidad
                            invoiced_quantity: x.quantity,
                            line_extension_amount: this.cadenaDecimales((Number(x.price) * Number(x.quantity)) - x.discount),
                            free_of_charge_indicator: false,
                            allowance_charges: [
                                {
                                    charge_indicator: false,
                                    allowance_charge_reason: "DESCUENTO GENERAL",
                                    amount: this.cadenaDecimales(x.discount),
                                    base_amount: this.cadenaDecimales(Number(x.price) * Number(x.quantity))
                                }
                            ],
                            tax_totals: [
                                {
                                    tax_id: x.tax.type_tax_id,
                                    tax_amount: this.cadenaDecimales(x.total_tax),
                                    taxable_amount: this.cadenaDecimales((Number(x.price) * Number(x.quantity)) - x.discount),
                                    percent: this.cadenaDecimales(x.tax.rate)
                                }
                            ],
                            description: x.item.name,
                            code: x.item.internal_id,
                            type_item_identification_id: 4,
                            price_amount: this.cadenaDecimales(Number(x.price) + (Number(x.total_tax) / Number(x.quantity))),
                            base_quantity: x.quantity
                        };
                    }
                    else
                    {
                        return {
                            unit_measure_id: x.item.unit_type.code, //codigo api dian de unidad
                            invoiced_quantity: x.quantity,
                            line_extension_amount: this.cadenaDecimales((Number(x.price) * Number(x.quantity)) - x.discount),
                            free_of_charge_indicator: false,
                            allowance_charges: [
                                {
                                    charge_indicator: false,
                                    allowance_charge_reason: "DESCUENTO GENERAL",
                                    amount: this.cadenaDecimales(x.discount),
                                    base_amount: this.cadenaDecimales(Number(x.price) * Number(x.quantity))
                                }
                            ],
                            description: x.item.name,
                            code: x.item.internal_id,
                            type_item_identification_id: 4,
                            price_amount: this.cadenaDecimales(Number(x.price) + (Number(x.total_tax) / Number(x.quantity))),
                            base_quantity: x.quantity
                        };
                    }
                });
                return data;
            },

            getWithHolding() {

                let total = this.form.total
                let list = this.form.taxes.filter(function(x) {
                    return x.is_retention && x.apply;
                });

                return list.map(x => {
                    return {
                        tax_id: x.type_tax_id,
                        tax_amount: this.cadenaDecimales(x.retention),
                        percent: this.cadenaDecimales(x.rate),
                        taxable_amount: this.cadenaDecimales(total),
                    };
                });

            },

            cadenaDecimales(amount){
                if(amount.toString().indexOf(".") != -1)
                    return amount.toString();
                else
                    return amount.toString()+".00";
                },
            },
    }
</script>
