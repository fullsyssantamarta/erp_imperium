<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Vendedores</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="clickCreate()">
                    <i class="fa fa-plus-circle"></i> Nuevo
                </button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de vendedores</h3>
            </div>
            <div class="card-body">
                <seller-table
                    :records="records"
                    @edit="clickEdit"
                    @delete="clickDelete"
                ></seller-table>
            </div>
            <seller-form :showDialog.sync="showDialog" :recordId="recordId"></seller-form>
        </div>
    </div>
</template>

<script>
import SellerForm from './form.vue'
import SellerTable from './SellerTable.vue'
import { deletable } from '@mixins/deletable'

export default {
    mixins: [deletable],
    components: { SellerForm, SellerTable },
    data() {
        return {
            showDialog: false,
            recordId: null,
            records: []
        }
    },
    mounted() {
        this.loadRecords()
        this.$eventHub.$on('reloadData', this.loadRecords)
    },
    methods: {
        loadRecords() {
            this.$http.get('/co-sellers/records').then(resp => {
                this.records = resp.data.data
            })
        },
        clickCreate() {
            this.recordId = null
            this.showDialog = true
        },
        clickEdit(id) {
            this.recordId = id
            this.showDialog = true
        },
        clickDelete(id) {
            this.destroy(`/co-sellers/${id}`)
                .then(() => {
                    this.$message.success('Vendedor eliminado correctamente')
                    this.loadRecords()
                })
                .catch(error => {
                    if (error.response && error.response.status === 404) {
                        this.$message.warning('El vendedor ya no existe o ya fue eliminado')
                        this.loadRecords()
                    } else {
                        this.$message.error('No se pudo eliminar el vendedor')
                    }
                })
        }
    }
}
</script>