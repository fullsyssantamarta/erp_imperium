<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Reporte de Movimientos Auxiliares</span>
                </li>
            </ol>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <div class="filter-container">
                            <el-date-picker
                                v-model="dateRange"
                                type="daterange"
                                range-separator="a"
                                start-placeholder="Fecha inicio"
                                end-placeholder="Fecha fin"
                                @change="onDateChange"
                                format="yyyy-MM-dd"
                                value-format="yyyy-MM-dd"
                                class="date-picker"
                            />
                        </div>
                    </div>
                    <div class="col-6 text-right">
                        <el-button type="primary" @click="ReportDownload('pdf')">Pdf</el-button>
                        <el-button type="success" @click="ReportDownload('excel')">Excel</el-button>
                    </div>
                </div>
                <div class="row">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th class="font-weight-bold">Codigo</th>
                                <th class="font-weight-bold">Cuenta</th>
                                <th class="font-weight-bold">Comprobante</th>
                                <th class="font-weight-bold">Fecha</th>
                                <th class="font-weight-bold">Número de documento</th>
                                <th class="font-weight-bold">Nombre del tercero</th>
                                <th class="font-weight-bold">Descripción</th>
                                <th class="font-weight-bold">Débito</th>
                                <th class="font-weight-bold">Crédito</th>
                            </tr>
                            <tbody v-if="accounts.length">
                                <template v-for="group in accounts" >
                                    <tr class="bg-light">
                                        <td colspan="7">
                                            <span class="font-weight-bold">Cuenta contable:</span> {{ group.account_code }} {{ group.account_name }}
                                        </td>
                                        <td>{{ group.total_debit }}</td>
                                        <td>{{ group.total_credit }}</td>
                                    </tr>
                                    <tr v-for="row in group.details" :key="row.id">
                                        <td>{{ row.account_code }}</td>
                                        <td>{{ row.account_name }}</td>
                                        <td>{{ row.document_info.number }}</td>
                                        <td>{{ row.date }}</td>
                                        <td>{{ row.document_info.third_party_number }}</td>
                                        <td>{{ row.document_info.third_party_name }}</td>
                                        <td>{{ row.description }}</td>
                                        <td class="text-right">{{ row.debit }}</td>
                                        <td class="text-right">{{ row.credit }}</td>
                                    </tr>
                                </template>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="9" class="text-center">No se encontraron registros</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DataTable from '../components/DataTableReport.vue';
import queryString from 'query-string';

export default {
    components: { DataTable },
    data() {
        return {
            dateRange: [],
            accounts: [],
        }
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData(params = {}) {
            const response = await this.$http.get('/accounting/auxiliary-movement/records', { params });
            this.accounts = response.data.data;
        },
        onDateChange() {
            let params = {
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
            };
            this.fetchData(params);
        },
        ReportDownload(type = 'pdf') {
            let params = {
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
                format: type,
            };

            window.open(`/accounting/auxiliary-movement/export?${queryString.stringify(params)}`, '_blank');
        }
    }
}
</script>