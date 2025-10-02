<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close" top="7vh" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="col-md-12">
                    <div class="form-group" :class="{'has-danger': errors.selected_tax_ids}">
                        <label class="control-label">Retenciones</label>
                        <div class="d-flex align-items-center mb-2">
                            <el-checkbox v-model="selectAllRetentions" @change="handleSelectAllChange">
                                Seleccionar todas
                            </el-checkbox>
                        </div>
                        <el-checkbox-group v-model="form.selected_tax_ids" @change="calculateRetention">
                            <div class="retention-option" v-for="option in retentiontaxes" :key="option.id">
                                <el-checkbox :label="option.id">
                                    {{ option.name }} - {{ option.rate }}%
                                </el-checkbox>
                            </div>
                        </el-checkbox-group>
                        <small class="form-control-feedback" v-if="errors.selected_tax_ids"
                            v-text="errors.selected_tax_ids[0]"></small>
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
                <div class="col-md-12" v-if="retentionSummaries.length">
                    <div class="alert alert-info">
                        <div class="mb-2"><strong>Base seleccionada:</strong> {{ getSelectedBaseAmount }}</div>
                        <div v-for="summary in retentionSummaries" :key="summary.tax_id">
                            <strong>{{ summary.name }}</strong> ({{ summary.rate }}%) → {{ summary.retention }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button class="add" type="primary" native-type="submit" v-if="retentionSummaries.length">{{titleAction}}</el-button>
            </div>
        </form>
    </el-dialog>
</template>
<style>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}
.retention-option {
    margin-bottom: 6px;
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
                    selected_tax_ids: [],
                    base_type: 'total'
                },
                taxes: [],
                selectAllRetentions: false
            }
        },
        computed: {
            retentiontaxes() {
                return this.taxes.filter(tax => tax.is_retention);
            },
            getSelectedBaseAmount() {
                const valor = this.form.base_type === 'administration'
                    ? this.detailAiu.value_administartion
                    : this.form.base_type === 'sudden'
                        ? this.detailAiu.value_sudden
                        : this.form.base_type === 'utility'
                            ? this.detailAiu.value_utility
                            : this.totalAiu;

                return Number(valor || 0).toFixed(2);
            },
            selectedTaxes() {
                return this.retentiontaxes.filter(tax => this.form.selected_tax_ids.includes(tax.id));
            },
            retentionSummaries() {
                const baseAmount = Number(this.getSelectedBaseAmount);
                return this.selectedTaxes.map(tax => {
                    const conversion = Number(tax.conversion || 100);
                    const rate = Number(tax.rate);
                    const retentionValue = (baseAmount * (rate / conversion)).toFixed(2);
                    return {
                        tax_id: tax.id,
                        name: tax.name,
                        rate,
                        retention: retentionValue,
                        conversion,
                        type_tax_id: tax.type_tax_id,
                        is_fixed_value: tax.is_fixed_value,
                        in_base: tax.in_base,
                        in_tax: tax.in_tax,
                        base_type: this.form.base_type
                    };
                });
            }
        },
        watch: {
            'form.selected_tax_ids'(newVal) {
                if (newVal.length === this.retentiontaxes.length && this.retentiontaxes.length > 0) {
                    this.selectAllRetentions = true;
                } else if (this.selectAllRetentions && newVal.length !== this.retentiontaxes.length) {
                    this.selectAllRetentions = false;
                }
                if (this.errors.selected_tax_ids && newVal.length > 0) {
                    this.$delete(this.errors, 'selected_tax_ids');
                }
            },
            retentiontaxes(newVal) {
                if (this.selectAllRetentions) {
                    this.form.selected_tax_ids = newVal.map(tax => tax.id);
                }
            }
        },
        created() {
            this.initForm();
            this.$http.get(`/${this.resource}/table/taxes`).then(response => {
                this.taxes = response.data;
            });
        },
        methods: {
            initForm() {
                this.errors = {};
                this.form = {
                    selected_tax_ids: [],
                    base_type: 'total'
                };
                this.selectAllRetentions = false;
            },
            async create() {
                this.titleDialog = 'Agregar retención';
                this.titleAction = 'Agregar';
            },
            close() {
                this.initForm();
                this.$emit('update:showDialog', false);
            },
            async changeItem() {
            },
            calculateRetention() {
                if (this.form.selected_tax_ids.length === this.retentiontaxes.length && this.retentiontaxes.length > 0) {
                    this.selectAllRetentions = true;
                } else if (this.selectAllRetentions && this.form.selected_tax_ids.length !== this.retentiontaxes.length) {
                    this.selectAllRetentions = false;
                }
            },
            handleSelectAllChange(value) {
                if (value) {
                    this.form.selected_tax_ids = this.retentiontaxes.map(tax => tax.id);
                } else if (this.form.selected_tax_ids.length === this.retentiontaxes.length) {
                    this.form.selected_tax_ids = [];
                }
            },
            async clickAddItem() {
                if (!this.form.selected_tax_ids.length) {
                    this.$set(this.errors, 'selected_tax_ids', ['Debe seleccionar al menos una retención']);
                    return;
                }
                const baseAmount = Number(this.getSelectedBaseAmount);
                const payload = this.retentionSummaries.map(summary => ({
                    tax_id: summary.tax_id,
                    calculatedRetention: summary.retention,
                    baseAiu: baseAmount,
                    rate: summary.rate,
                    conversion: summary.conversion,
                    name: summary.name,
                    type_tax_id: summary.type_tax_id,
                    is_fixed_value: summary.is_fixed_value,
                    in_base: summary.in_base,
                    in_tax: summary.in_tax,
                    base_type: summary.base_type
                }));
                this.$emit('add', payload);
                this.close();
            },
        }
    }

</script>
