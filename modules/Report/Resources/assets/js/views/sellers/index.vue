<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Reporte de Vendedores</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 col-md-3 mb-2 mb-md-0">
                    <label>Vendedor</label>
                    <el-select v-model="form.seller_id" filterable remote reserve-keyword placeholder="Buscar vendedor"
                        :remote-method="searchSellers" :loading="loading_sellers" @visible-change="onSellerDropdown"
                        :clearable="true" style="width: 100%;">
                        <el-option v-for="seller in sellers" :key="seller.id" :label="seller.full_name"
                            :value="seller.id"></el-option>
                    </el-select>
                </div>
                <div class="col-12 col-md-3 mb-2 mb-md-0">
                    <label>Tipo de documento</label>
                    <el-select v-model="form.document_type_id" clearable placeholder="Todos" style="width: 100%;">
                        <el-option :value="null" label="Todos"></el-option>
                        <el-option v-for="type in document_types" :key="type.id" :label="type.description"
                            :value="type.id"></el-option>
                    </el-select>
                </div>
                <div class="col-12 col-md-3 mb-2 mb-md-0">
                    <label>Filtrar por</label>
                    <el-select v-model="form.date_filter_type" placeholder="Tipo de filtro" style="width: 100%;">
                        <el-option label="Mes" value="month"></el-option>
                        <el-option label="Día específico" value="day"></el-option>
                        <el-option label="Rango de fechas" value="range"></el-option>
                    </el-select>
                </div>
                <div class="col-12 col-md-3 mb-2 mb-md-0" v-if="form.date_filter_type === 'month'">
                    <label>Mes</label>
                    <el-date-picker v-model="form.month" type="month" placeholder="Seleccionar mes" format="yyyy-MM"
                        value-format="yyyy-MM" clearable style="width: 100%;"
                        :key="'month-' + form.date_filter_type"></el-date-picker>
                </div>
                <div class="col-12 col-md-3 mb-2 mb-md-0" v-if="form.date_filter_type === 'day'">
                    <label>Día</label>
                    <el-date-picker v-model="form.day" type="date" placeholder="Seleccionar día" format="yyyy-MM-dd"
                        value-format="yyyy-MM-dd" clearable style="width: 100%;"
                        :key="'day-' + form.date_filter_type"></el-date-picker>
                </div>
                <div class="col-12 col-md-6 mb-2 mb-md-0" v-if="form.date_filter_type === 'range'">
                    <label>Rango de fechas</label>
                    <el-date-picker v-model="form.date_range" type="daterange" range-separator="a"
                        start-placeholder="Inicio" end-placeholder="Fin" format="yyyy-MM-dd" value-format="yyyy-MM-dd"
                        clearable style="width: 100%;" :key="'range-' + form.date_filter_type"></el-date-picker>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <br></br>
                    <el-button type="primary" icon="el-icon-search" @click="getRecords" :loading="loading_records"
                        style="width: 100%;">
                        Buscar
                    </el-button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <el-button class="ml-0" type="success" icon="el-icon-download" @click="exportExcel"
                        :disabled="records.length === 0">
                        Exportar Excel
                    </el-button>
                    <el-button class="ml-2" type="danger" icon="el-icon-printer" @click="exportPdf"
                        :disabled="records.length === 0">
                        Exportar PDF
                    </el-button>
                </div>
            </div>
            <div v-if="monthlyProgress.goal > 0" class="mb-3">
                <label>Progreso mensual: {{ monthlyProgress.total }} / {{ monthlyProgress.goal }}</label>
                <el-progress
                    :percentage="Math.min(100, Math.round((monthlyProgress.total / monthlyProgress.goal) * 100))"></el-progress>
                <div class="mt-2 font-weight-bold" :style="{ color: motivatorColor }">
                    {{ motivatorMessage }}
                </div>
            </div>
            <el-table :data="records" v-loading="loading_records" style="width: 100%;" stripe
                @sort-change="handleSortChange" :default-sort="{ prop: sort.prop, order: sort.order }">
                <el-table-column prop="date_of_issue" label="Fecha" sortable="custom" />
                <el-table-column prop="type" label="Tipo" sortable="custom" />
                <el-table-column prop="number_full" label="Número" sortable="custom" />
                <el-table-column prop="customer_name" label="Cliente" sortable="custom" />
                <el-table-column prop="total" label="Total" sortable="custom">
                    <template slot-scope="scope">
                        {{ scope.row.total | numberFormat }}
                    </template>
                </el-table-column>
                <el-table-column prop="commission" label="Comisión" sortable="custom">
                    <template slot-scope="scope">
                        {{ scope.row.commission | numberFormat }}
                    </template>
                </el-table-column>
            </el-table>
            <div v-if="records.length" class="mt-2 text-right font-weight-bold">
                Total general: {{ totals.total_sum | numberFormat }} &nbsp; | &nbsp; Comisión total: {{
                totals.commission_sum | numberFormat }}
            </div>
            <el-pagination v-if="pagination.total > pagination.per_page" class="mt-3" background
                layout="total, prev, pager, next" :total="pagination.total" :page-size="pagination.per_page"
                :current-page.sync="pagination.current_page" @current-change="getRecords"></el-pagination>
        </div>
    </div>
</template>

<script>
import debounce from 'lodash/debounce'

export default {
    data() {
        return {
            form: {
                seller_id: null,
                document_type_id: null,
                month: null,
            },
            sellers: [],
            document_types: [],
            records: [],
            loading_sellers: false,
            loading_records: false,
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1,
            },
            seller_search: '',
            sort: {
                prop: 'date_of_issue',
                order: 'descending'
            },
            monthlyProgress: {
                total: 0,
                goal: 0,
            },
            totals: {
                total_sum: 0,
                commission_sum: 0,
            },
        }
    },
    created() {
        this.fetchDocumentTypes()
    },
    computed: {
        motivatorMessage() {
            const { total, goal } = this.monthlyProgress
            if (!goal || goal === 0) return ''
            const percent = (total / goal) * 100
            if (percent === 0) return '¡Vamos, empieza a sumar documentos este mes!'
            if (percent < 50) return '¡Buen inicio, sigue así para alcanzar la meta!'
            if (percent < 80) return '¡Vas muy bien, la meta está cerca!'
            if (percent < 100) return '¡Excelente trabajo, casi logras la meta!'
            return '¡Felicidades, superaste tu meta mensual!'
        },
        motivatorColor() {
            const { total, goal } = this.monthlyProgress
            if (!goal || goal === 0) return '#333'
            const percent = (total / goal) * 100
            if (percent < 50) return '#007bff'
            if (percent < 80) return '#17a2b8'
            if (percent < 100) return '#ffc107'
            return '#28a745'
        }
    },
    methods: {
        fetchDocumentTypes() {
            this.$http.get('/reports/sellers/document-types').then(resp => {
                this.document_types = resp.data
            })
        },
        fetchSellers(query = '') {
            this.loading_sellers = true
            this.$http.get('/reports/sellers/list', {
                params: {
                    search: query,
                    page: 1
                }
            }).then(resp => {
                this.sellers = resp.data.data
                this.loading_sellers = false
            }).catch(() => {
                this.loading_sellers = false
            })
        },
        searchSellers: debounce(function(query) {
            this.fetchSellers(query)
        }, 500),
        onSellerDropdown(visible) {
            if (visible && this.sellers.length === 0) {
                this.fetchSellers()
            }
        },
        getRecords(page = 1) {
            if (!this.form.seller_id) {
                this.$message.warning('Seleccione un vendedor')
                return
            }
            this.loading_records = true
            this.$http.get('/reports/sellers/records', {
                params: {
                    seller_id: this.form.seller_id,
                    document_type_id: this.form.document_type_id,
                    date_filter_type: this.form.date_filter_type,
                    month: this.form.month,
                    day: this.form.day,
                    date_range: this.form.date_range,
                    page: page,
                    sort_by: this.sort.prop,
                    sort_order: this.sort.order === 'ascending' ? 'asc' : 'desc'
                }
            }).then(resp => {
                this.records = resp.data.data
                this.pagination = resp.data.meta || { total: resp.data.data.length, per_page: 20, current_page: page }
                this.monthlyProgress = resp.data.progress || { total: 0, goal: 0 }
                this.totals = resp.data.totals || { total_sum: 0, commission_sum: 0 }
                this.loading_records = false
            }).catch(() => {
                this.loading_records = false
            })
        },
        exportExcel() {
            if (!this.form.seller_id) {
                this.$message.warning('Seleccione un vendedor')
                return
            }
            const paramsObj = {
                seller_id: this.form.seller_id,
                sort_by: this.sort.prop,
                sort_order: this.sort.order === 'ascending' ? 'asc' : 'desc',
                document_type_id: this.form.document_type_id,
                date_filter_type: this.form.date_filter_type,
                month: this.form.month,
                day: this.form.day,
                date_range: this.form.date_range ? JSON.stringify(this.form.date_range) : null,
            }
            Object.keys(paramsObj).forEach(k => (paramsObj[k] == null) && delete paramsObj[k])
            const params = new URLSearchParams(paramsObj).toString()
            window.open(`/reports/sellers/export-excel?${params}`, '_blank')
        },
        exportPdf() {
            if (!this.form.seller_id) {
                this.$message.warning('Seleccione un vendedor')
                return
            }
            const paramsObj = {
                seller_id: this.form.seller_id,
                sort_by: this.sort.prop,
                sort_order: this.sort.order === 'ascending' ? 'asc' : 'desc',
                document_type_id: this.form.document_type_id,
                date_filter_type: this.form.date_filter_type,
                month: this.form.month,
                day: this.form.day,
                date_range: this.form.date_range ? JSON.stringify(this.form.date_range) : null,
            }
            Object.keys(paramsObj).forEach(k => (paramsObj[k] == null) && delete paramsObj[k])
            const params = new URLSearchParams(paramsObj).toString()
            window.open(`/reports/sellers/export-pdf?${params}`, '_blank')
        },
        handleSortChange({ prop, order }) {
            this.sort = { prop, order }
            this.getRecords(1)
        },
    }
}
</script>