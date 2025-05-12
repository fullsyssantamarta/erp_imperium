<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close" top="7vh" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="col-md-12">
                    <div class="form-group" :class="{'has-danger': errors.tax_id}">
                        <label class="control-label">Retención</label>
                        <el-select v-model="form.tax_id"  filterable @change="calculateRetention">
                            <el-option v-for="option in retentiontaxes" :key="option.id" :value="option.id" :label="`${option.name} - ${option.rate}%`"></el-option>
                        </el-select>
                        <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="form-group">
                        <label class="control-label">Base para la retención</label>
                        <el-select v-model="form.base_type" @change="calculateRetention">
                            <el-option label="Base AIU Total" value="total"></el-option>
                            <el-option label="Administración" value="administration"></el-option>
                            <el-option label="Imprevisto" value="sudden"></el-option>
                            <el-option label="Utilidad" value="utility"></el-option>
                        </el-select>
                    </div>
                </div>
                <div class="col-md-12" v-if="form.tax_id">
                    <div class="alert alert-info">
                        <div>Base seleccionada: {{ getSelectedBaseAmount }}</div>
                        <div>Porcentaje: {{ selectedTaxRate }}%</div>
                        <div>Valor Retención: {{ calculatedRetention }}</div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button class="add" type="primary" native-type="submit" v-if="form.tax_id">{{titleAction}}</el-button>
            </div>
        </form>
    </el-dialog>
</template>
<style>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}
</style>

<script>

    export default {
        props: {
            showDialog: Boolean,
            totalAiu: {
                type: Number,
                required: true
            },
            detailAiu: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                titleAction: '',
                titleDialog: '',
                resource: 'co-documents',
                errors: {},
                form: {
                    tax_id: null,
                    base_type: 'total'
                },
                taxes:[],
                selectedTaxRate: 0,
                calculatedRetention: 0,
                baseAiu: 0
            }
        },
        computed: {
            retentiontaxes() {
                return this.taxes.filter(tax => tax.is_retention);
            },
            getSelectedBaseAmount() {
                const valor = this.form.base_type === 'administration' ? this.detailAiu.value_administartion :
                             this.form.base_type === 'sudden' ? this.detailAiu.value_sudden :
                             this.form.base_type === 'utility' ? this.detailAiu.value_utility :
                             this.totalAiu;
                             
                return Number(valor || 0).toFixed(2);
            }
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/table/taxes`).then(response => {
                this.taxes = response.data;
            })

        },
        methods: {
            initForm() {

                this.errors = {};

                this.form = {
                    tax_id: null,
                    base_type: 'total'
                };

            },
            async create() {

                this.titleDialog = 'Agregar retención';
                this.titleAction = 'Agregar';

            },
            close() {
                this.initForm()
                this.$emit('update:showDialog', false)
            },
            async changeItem() {


            },
            calculateRetention() {
                const tax = this.taxes.find(t => t.id === this.form.tax_id);
                if(tax) {
                    this.selectedTaxRate = Number(tax.rate);
                    this.baseAiu = Number(this.getSelectedBaseAmount);
                    this.calculatedRetention = (this.baseAiu * (tax.rate / (tax.conversion || 100))).toFixed(2);
                }
            },
            async clickAddItem() {
                const tax = this.taxes.find(t => t.id === this.form.tax_id);
                if (!tax) return;
                
                const formData = {
                    tax_id: this.form.tax_id,
                    calculatedRetention: this.calculatedRetention,
                    baseAiu: this.baseAiu,
                    rate: this.selectedTaxRate,
                    conversion: tax.conversion,
                    name: tax.name,
                    type_tax_id: tax.type_tax_id,
                    is_fixed_value: tax.is_fixed_value,
                    is_retention: true,
                    in_base: tax.in_base,
                    in_tax: tax.in_tax,
                    id: tax.id,
                    base_type: this.form.base_type
                };
                
                this.$emit('add', formData);
                this.close();
            },
        }
    }

</script>
