<template>
    <div class="form-group"> 
        <label class="control-label">
            Tipo
        </label>
        <el-select v-model="document_type_id" filterable :clearable="isClearable" @change="changeDocumentType">
            <el-option key="all" value="all" label="Todos"></el-option>
            <el-option key="documents" value="documents" label="Factura electrónica"></el-option>
            <el-option key="documents_pos" value="documents_pos" label="Documento POS"></el-option>
            <el-option key="remissions" value="remissions" label="Remisiones"></el-option>
            <el-option key="credit_notes" value="credit_notes" label="Notas de crédito"></el-option>
            <el-option key="debit_notes" value="debit_notes" label="Notas de débito"></el-option>
            <el-option key="pos_credit_notes" value="pos_credit_notes" label="Notas de crédito POS"></el-option>
        </el-select>

        <small class="text-danger" v-if="errors_document_type_id" v-text="errors_document_type_id[0]"></small>
    </div>
</template> 

<script>

    export default {
        props: {
            errors_document_type_id: Array,
            isClearable: {
                type: Boolean,
                default: false
            }
        },
        data () {
            return {
                document_type_id: null,
            }
        },
        created() 
        {
        },
        methods: {
            setValue(id)
            {
                this.document_type_id = id
            },
            cleanValue()
            {
                this.document_type_id = null
            },
            changeDocumentType()
            {
                this.$emit('changeDocumentType', this.document_type_id)
            },
        }
    }
</script>