<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Ingresos y egresos por métodos de pago</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading">
                            <th class="">#</th>
                            <th class=""><strong>Método de pago / Total pagos</strong></th>
                            <th class="text-center"><strong>Factura Electrónica</strong></th>
                            <th class="text-center"><strong>Remisión</strong></th>
                            <th class="text-center"><strong>Documento POS</strong></th>
                            <!-- <th class="text-center"><strong>N. Venta</strong></th> -->
                            <th class="text-center"><strong>Cotización</strong></th>
                            <!-- <th class="text-center"><strong>Contrato</strong></th> -->
                            <th class="text-center"><strong>Ingresos</strong></th>
                            <th class="text-center"><strong>Total Ingresos</strong></th>
                            <th class="text-center"><strong>Compras</strong></th>
                            <th class="text-center"><strong>Gastos</strong></th>
                            <!-- <th class="text-center"> <strong>Saldo</strong></th> -->
                            <th class="text-center"><strong>Total Egresos</strong></th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td> 
                            <td>{{row.description}}</td>
                            <td class="text-center">{{ row.document_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.remission_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.document_pos_payment | numberFormat }}</td>
                            <!-- <td class="text-center">{{ (row.sale_note_payment != '-') ? ''+row.sale_note_payment : row.sale_note_payment}}</td> -->
                            <td class="text-center">{{ row.quotation_payment | numberFormat }}</td>
                            <!-- <td class="text-center">{{ (row.contract_payment != '-') ? ''+row.contract_payment : row.contract_payment}}</td> -->
                            <td class="text-center">{{ row.income_payment | numberFormat }}</td>
                            <td class="text-center">{{ calculateTotalIncome(row) | numberFormat }}</td>
                            <td class="text-center">{{ row.purchase_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.expense_payment | numberFormat }}</td>
                            <!-- <td class="text-center">{{row.balance}}</td>  -->
                            <td class="text-center">{{ calculateTotalExpense(row) | numberFormat }}</td>
                        </tr>
                    </data-table>
                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableWithoutPaging.vue'

    export default {
        components: {DataTable},
        props: ['configuration'],
        data() {
            return {
                resource: 'finances/payment-method-types',
                form: {},

            }
        },
        async created() {
        },
        methods: {
            calculateTotalIncome(row) {
                const doc = row.document_payment !== '-' ? parseFloat(row.document_payment) : 0;
                const rem = row.remission_payment !== '-' ? parseFloat(row.remission_payment) : 0;
                const pos = row.document_pos_payment !== '-' ? parseFloat(row.document_pos_payment) : 0;
                const inc = row.income_payment !== '-' ? parseFloat(row.income_payment) : 0;
                return (doc + rem + pos + inc).toFixed(2);
            },
            calculateTotalExpense(row) {
                const purchase = row.purchase_payment !== '-' ? parseFloat(row.purchase_payment) : 0;
                const expense = row.expense_payment !== '-' ? parseFloat(row.expense_payment) : 0;
                return (purchase + expense).toFixed(2);
            },
        }
    }
</script>
