<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Reporte de Vendedores</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Vendedor</label>
                    <el-select
                        v-model="form.seller_id"
                        filterable
                        remote
                        reserve-keyword
                        placeholder="Buscar vendedor"
                        :remote-method="searchSellers"
                        :loading="loading_sellers"
                        @visible-change="onSellerDropdown"
                        :clearable="true"
                    >
                        <el-option
                            v-for="seller in sellers"
                            :key="seller.id"
                            :label="seller.full_name"
                            :value="seller.id"
                        ></el-option>
                    </el-select>
                </div>
                <div class="col-md-4">
                    <label>Tipo de documento</label>
                    <el-select v-model="form.document_type_id" clearable placeholder="Todos">
                        <el-option :value="null" label="Todos"></el-option>
                        <el-option
                            v-for="type in document_types"
                            :key="type.id"
                            :label="type.description"
                            :value="type.id"
                        ></el-option>
                    </el-select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <el-button type="primary" icon="el-icon-search" @click="getRecords" :loading="loading_records">
                        Buscar
                    </el-button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <el-button class="ml-0" type="success" icon="el-icon-download" @click="exportExcel" :disabled="records.length === 0">
                        Exportar Excel
                    </el-button>
                    <el-button class="ml-2" type="danger" icon="el-icon-printer" @click="exportPdf" :disabled="records.length === 0">
                        Exportar PDF
                    </el-button>
                </div>
            </div>
            <el-table :data="records" v-loading="loading_records" style="width: 100%;" stripe>
                <el-table-column prop="date_of_issue" label="Fecha" />
                <el-table-column prop="type" label="Tipo" />
                <el-table-column prop="number_full" label="Número" />
                <el-table-column prop="customer_name" label="Cliente" />
                <el-table-column prop="total" label="Total" />
                <el-table-column prop="commission" label="Comisión" />
                <!-- Agrega más columnas según tus necesidades -->
            </el-table>
            <el-pagination
                v-if="pagination.total > pagination.per_page"
                class="mt-3"
                background
                layout="total, prev, pager, next"
                :total="pagination.total"
                :page-size="pagination.per_page"
                :current-page.sync="pagination.current_page"
                @current-change="getRecords"
            ></el-pagination>
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
        }
    },
    created() {
        this.fetchDocumentTypes()
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
                    page: page
                }
            }).then(resp => {
                this.records = resp.data.data
                this.pagination = resp.data.meta || { total: resp.data.data.length, per_page: 20, current_page: page }
                this.loading_records = false
            }).catch(() => {
                this.loading_records = false
            })
        },
        exportExcel() {
            this.$message.info('Funcionalidad de exportar a Excel pendiente')
        },
        exportPdf() {
            this.$message.info('Funcionalidad de exportar a PDF pendiente')
        }
    }
}
</script>