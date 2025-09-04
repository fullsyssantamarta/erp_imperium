<template>
    <div>
        <!-- Filtros estilo DataTable -->
        <div class="row mb-2" v-if="applyFilter">
            <div class="col-lg-4 col-md-4 col-sm-12 pb-2">
                <div class="d-flex">
                    <div style="width:100px">
                        Filtrar por:
                    </div>
                    <el-select v-model="search.column" placeholder="Seleccione campo" @change="changeClearInput">
                        <el-option label="Código interno" value="internal_code"></el-option>
                        <el-option label="Nombre" value="full_name"></el-option>
                        <el-option label="N° documento" value="document_number"></el-option>
                        <el-option label="Teléfono" value="phone"></el-option>
                        <el-option label="Estado" value="status"></el-option>
                        <el-option label="Tipo de comisión" value="commission_type"></el-option>
                    </el-select>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                <el-input 
                    placeholder="Buscar"
                    v-model="search.value"
                    style="width: 100%;"
                    prefix-icon="el-icon-search"
                    @input="loadRecords(1)">
                </el-input>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo Doc.</th>
                        <th>Número Doc.</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Tipo comisión</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in records" :key="row.id">
                        <td>{{ index + 1 }}</td>
                        <td>{{ row.internal_code }}</td>
                        <td>{{ row.full_name }}</td>
                        <td>{{ row.type_document_identification_name }}</td>
                        <td>{{ row.document_number }}</td>
                        <td>{{ row.email }}</td>
                        <td>{{ row.phone }}</td>
                        <td>
                            <span v-if="row.commission_type === 'total'">Sobre venta total</span>
                            <span v-else-if="row.commission_type === 'utilidad'">Sobre utilidad</span>
                            <span v-else-if="row.commission_type === 'producto'">Por producto</span>
                            <span v-else>-</span>
                        </td>
                        <td>
                            <el-switch
                                v-model="row.status"
                                active-value="Activo"
                                inactive-value="Inactivo"
                                @change="changeStatus(row)"
                                active-color="#13ce66"
                                inactive-color="#ff4949">
                            </el-switch>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-custom btn-sm mr-2" @click="$emit('edit', row.id)">
                                Editar
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" @click="$emit('delete', row.id)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        records: Array,
        applyFilter: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            search: {
                column: 'internal_code',
                value: ''
            }
        }
    },
    methods: {
        changeClearInput() {
            this.search.value = ''
            this.loadRecords(1)
        },
        loadRecords(page = 1) {
            const params = {
                page,
                [this.search.column]: this.search.value
            }
            this.$http.get('/co-sellers/records', { params }).then(resp => {
                this.$emit('update:records', resp.data.data)
            })
        },
        changeStatus(row) {
            this.$http.put(`/co-sellers/${row.id}/change-status`, { status: row.status })
                .then(() => {
                    this.$message.success('Estado actualizado')
                })
                .catch(() => {
                    this.$message.error('No se pudo actualizar el estado')
                })
        }
    }
}
</script>