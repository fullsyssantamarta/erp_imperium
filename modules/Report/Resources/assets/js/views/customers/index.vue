<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Consulta de documentos por cliente</h3>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <th class="">#</th>
                        <th class="">Fecha</th>
                        <th class="">Tipo Documento</th>
                        <th class="">Prefijo</th>
                        <th class="">Número</th>
                        <th class="">Monto</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{row.date_of_issue}}</td>
                        <td>{{row.document_type_description}}</td>
                        <td>{{row.series}}</td>
                        <td>{{row.alone_number}}</td>
                        <td>{{ formatTotal(row) }}</td>
                    </tr>
                </data-table>
            </div>
        </div>
    </div>
</template>

<script>
    import DataTable from '../../components/DataTableCustomers.vue'

    export default {
        components: {DataTable},

        data() {
            return {
                resource: 'reports/customers',
                form: {},

            }
        },

        async created() {
        },

        methods: {
            formatNumber(number) {
                return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",").replace(/\.(\d{2})/, ".$1");
            },
            formatTotal(row) {
                let total;
                
                if (row.document_type_id == '07') {
                    total = (row.total == 0) ? '0.00' : '-' + row.total;
                } else if (row.document_type_id != '07' && (row.state_type_id == '11' || row.state_type_id == '09')) {
                    total = '0.00';
                } else {
                    total = row.total;
                }
                
                return this.formatNumber(total);
            },
        }
    }
</script>
