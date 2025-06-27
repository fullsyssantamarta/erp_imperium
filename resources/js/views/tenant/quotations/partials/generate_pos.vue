<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" width="500px">
        <div class="form-group">
            <label class="control-label font-weight-bold">Seleccione Resolución POS</label>
            <el-select v-model="form.resolution_id" class="w-100">
                <el-option
                    v-for="option in resolutions"
                    :key="option.id"
                    :value="option.id"
                    :label="getResolutionLabel(option)">
                </el-option>
            </el-select>
        </div>

        <span slot="footer" class="dialog-footer">
            <el-button @click="close">Cerrar</el-button>
            <el-button type="primary" :loading="loading" @click="submit">
                Generar POS
            </el-button>
        </span>
    </el-dialog>
</template>

<script>
export default {
    props: ['showDialog', 'recordId'],
    data() {
        return {
            loading: false,
            titleDialog: null,
            resource: 'quotations',
            errors: {},
            form: {
                resolution_id: null
            },
            resolutions: [],
            quotationData: null
        }
    },
    methods: {
        async create() {
            try {
                const [quotationResponse, resolutionsResponse] = await Promise.all([
                    this.$http.get(`/${this.resource}/record/${this.recordId}`),
                    this.$http.get('/configurations/get-pos-resolution')
                ]);

                this.quotationData = quotationResponse.data.data.quotation;
                this.resolutions = resolutionsResponse.data;
                this.titleDialog = 'Seleccionar Resolución POS';
                
            } catch (error) {
                console.error("Error al cargar datos:", error);
                this.$message.error(error.message || 'Error al obtener los datos');
            }
        },
        getResolutionLabel(resolution) {
            return `${resolution.prefix} / ${resolution.resolution_number} / ${resolution.from} - ${resolution.to}`
        },
        async submit() {
            this.loading = true;
            try {
                if (!this.form.resolution_id) {
                    throw new Error('Debe seleccionar una resolución POS');
                }
                // Validar si hay caja abierta para la resolución seleccionada
                const validateResponse = await this.$http.get(`/document-pos/validate-cash/${this.form.resolution_id}`);
                if (!validateResponse.data.success) {
                    this.$message.error(validateResponse.data.message || 'No hay caja abierta para esta resolución POS');
                    this.loading = false;
                    return;
                }

                const resolution = this.resolutions.find(r => r.id === this.form.resolution_id);
                const currentDateTime = this.getCurrentDateTime();

                // Asegurar que el cliente tenga los campos requeridos
                const customer = {
                    ...this.quotationData.customer,
                    municipality_id: this.quotationData.customer.municipality_id || '11001',
                    department_id: this.quotationData.customer.department_id || '11',
                    country_id: this.quotationData.customer.country_id || '169',
                };

                // Transformar los items al formato esperado por DocumentPosItem
                const transformedItems = this.quotationData.items.map(item => ({
                    item_id: item.item_id,
                    item: {
                        ...item.item,
                        is_set: typeof item.item.is_set !== 'undefined' ? item.item.is_set : false,
                        edit_sale_unit_price: item.unit_price,
                        description: item.item.description,
                        name: item.item.name,
                        internal_id: item.item.internal_id,
                        unit_type: {
                            code: item.item.unit_type_id
                        },
                        tax: item.tax || null
                    },
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    total: item.total,
                    total_tax: item.total_tax || 0,
                    subtotal: item.total - (item.total_tax || 0),
                    discount: item.discount || 0,
                    unit_type_id: item.item.unit_type_id,
                    tax_id: item.tax_id || null,
                }));

                // Estructura base del payload
                const payload = {
                    user_id: null,
                    external_id: null,
                    establishment_id: this.quotationData.establishment_id,
                    establishment: this.quotationData.establishment,
                    soap_type_id: '01',
                    state_type_id: '01',
                    prefix: resolution?.prefix,
                    date_of_issue: currentDateTime.date,
                    time_of_issue: currentDateTime.time,
                    customer_id: this.quotationData.customer_id,
                    customer: customer,
                    exchange_rate_sale: 1,
                    total: this.quotationData.total,
                    filename: null,
                    currency_id: this.quotationData.currency_id,
                    sale: this.quotationData.total,
                    taxes: this.quotationData.taxes || [],
                    total_tax: this.quotationData.total_tax,
                    subtotal: this.quotationData.subtotal,
                    total_discount: this.quotationData.total_discount || 0,
                    items: transformedItems,
                    paid: true,
                    payments: [{
                        id: null,
                        document_id: null,
                        sale_note_id: null,
                        date_of_payment: currentDateTime.date,
                        payment_method_type_id: "01",
                        payment_destination_id: "cash",
                        reference: null,
                        payment: this.quotationData.total
                    }],
                    resolution_id: this.form.resolution_id,
                    resolution_number: resolution?.resolution_number,
                    plate_number: resolution?.plate_number,
                    cash_type: resolution?.cash_type || '01'
                };

                console.log("Datos a enviar:", payload);
                const response = await this.$http.post('/document-pos', payload);
                
                if (response.data.success) {
                    await this.$http.post('/cash-document', {
                        document_id: null,
                        document_pos_id: response.data.data.id,
                        sale_note_id: null
                    });
                    this.$message.success(response.data.message);
                    this.$eventHub.$emit('reloadData');
                    this.close();
                } else {
                    throw new Error(response.data.message);
                }
            } catch (error) {
                console.error("Error completo:", error);
                this.$message.error(error.response?.data?.message || error.message || 'Error al generar POS');
            }
            this.loading = false;
        },
        close() {
            this.$emit('update:showDialog', false)
            this.form.resolution_id = null
            this.errors = {}
        },
        getCurrentDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            return {
                date: `${year}-${month}-${day}`,
                time: `${hours}:${minutes}:${seconds}`,
                full: `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
            };
        }
    }
}
</script>

<style scoped>
.form-group {
    margin-bottom: 1rem;
}
.font-weight-bold {
    font-weight: bold;
}
</style>