<template>
  <el-dialog width="65%" :title="titleDialog" :visible="showDialog" :close-on-click-modal="false" @close="close" @open="create" append-to-body top="7vh">
    <form autocomplete="off" @submit.prevent="submit">
      <!-- Datos básicos del vendedor -->
      <div class="mb-3 pb-2 border-bottom">
        <h5 class="font-weight-bold">Datos básicos del vendedor</h5>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.internal_code}">
              <label class="control-label">Código interno</label>
              <el-input v-model="form.internal_code"></el-input>
              <small class="form-control-feedback" v-if="errors.internal_code" v-text="errors.internal_code[0]"></small>
            </div>
          </div>
          <div class="col-md-8">
            <div class="form-group" :class="{'has-danger': errors.full_name}">
              <label class="control-label">Nombre completo</label>
              <el-input v-model="form.full_name"></el-input>
              <small class="form-control-feedback" v-if="errors.full_name" v-text="errors.full_name[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.type_document_identification_id}">
              <label class="control-label">Tipo de documento</label>
              <el-select v-model="form.type_document_identification_id" filterable placeholder="Seleccione">
                <el-option v-for="option in typeDocuments" :key="option.id" :value="option.id" :label="option.name"></el-option>
              </el-select>
              <small class="form-control-feedback" v-if="errors.type_document_identification_id" v-text="errors.type_document_identification_id[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.document_number}">
              <label class="control-label">Número de documento</label>
              <el-input v-model="form.document_number"></el-input>
              <small class="form-control-feedback" v-if="errors.document_number" v-text="errors.document_number[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.birth_date}">
              <label class="control-label">Fecha de nacimiento</label>
              <el-date-picker v-model="form.birth_date" type="date" placeholder="Seleccione"></el-date-picker>
              <small class="form-control-feedback" v-if="errors.birth_date" v-text="errors.birth_date[0]"></small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group" :class="{'has-danger': errors.address}">
              <label class="control-label">Dirección</label>
              <el-input v-model="form.address"></el-input>
              <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group" :class="{'has-danger': errors.phone}">
              <label class="control-label">Teléfono/celular</label>
              <el-input v-model="form.phone"></el-input>
              <small class="form-control-feedback" v-if="errors.phone" v-text="errors.phone[0]"></small>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group" :class="{'has-danger': errors.email}">
              <label class="control-label">Correo electrónico</label>
              <el-input v-model="form.email"></el-input>
              <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
            </div>
          </div>
        </div>
      </div>

      <!-- Datos laborales -->
      <div class="mb-3 pb-2 border-bottom">
        <h5 class="font-weight-bold">Datos laborales</h5>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.start_date}">
              <label class="control-label">Fecha de inicio</label>
              <el-date-picker v-model="form.start_date" type="date" placeholder="Seleccione"></el-date-picker>
              <small class="form-control-feedback" v-if="errors.start_date" v-text="errors.start_date[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.status}">
              <label class="control-label">Estado</label>
              <el-select v-model="form.status" placeholder="Seleccione">
                <el-option label="Activo" value="Activo"></el-option>
                <el-option label="Inactivo" value="Inactivo"></el-option>
              </el-select>
              <small class="form-control-feedback" v-if="errors.status" v-text="errors.status[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.role}">
              <label class="control-label">Cargo o rol</label>
              <el-input v-model="form.role"></el-input>
              <small class="form-control-feedback" v-if="errors.role" v-text="errors.role[0]"></small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group" :class="{'has-danger': errors.area}">
              <label class="control-label">Área/sucursal</label>
              <el-input v-model="form.area"></el-input>
              <small class="form-control-feedback" v-if="errors.area" v-text="errors.area[0]"></small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group" :class="{'has-danger': errors.shift}">
              <label class="control-label">Turno/jornada</label>
              <el-input v-model="form.shift"></el-input>
              <small class="form-control-feedback" v-if="errors.shift" v-text="errors.shift[0]"></small>
            </div>
          </div>
        </div>
      </div>

      <!-- Datos de control de ventas -->
      <div>
        <h5 class="font-weight-bold">Datos de control de ventas</h5>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.monthly_goal}">
              <label class="control-label">Meta mensual</label>
              <el-input v-model="form.monthly_goal" type="number"></el-input>
              <small class="form-control-feedback" v-if="errors.monthly_goal" v-text="errors.monthly_goal[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.commission_percentage}">
              <label class="control-label">Porcentaje comisión</label>
              <el-input v-model="form.commission_percentage" type="number"></el-input>
              <small class="form-control-feedback" v-if="errors.commission_percentage" v-text="errors.commission_percentage[0]"></small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group" :class="{'has-danger': errors.commission_type}">
              <label class="control-label">Tipo de comisión</label>
              <el-select v-model="form.commission_type" placeholder="Seleccione">
                <el-option label="Sobre venta total" value="total"></el-option>
                <el-option label="Sobre utilidad" value="utilidad"></el-option>
                <el-option label="Por producto" value="producto"></el-option>
              </el-select>
              <small class="form-control-feedback" v-if="errors.commission_type" v-text="errors.commission_type[0]"></small>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions text-right pt-2">
        <el-button @click.prevent="close()">Cancelar</el-button>
        <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
      </div>
    </form>
  </el-dialog>
</template>

<script>
export default {
  props: ['showDialog', 'recordId'],
  data() {
    return {
      loading_submit: false,
      titleDialog: null,
      errors: {},
      form: {},
      typeDocuments: [],
    }
  },
  watch: {
    showDialog(val) {
      if (val) {
        this.create()
      }
    }
  },
  methods: {
    create() {
      this.titleDialog = this.recordId ? 'Editar Vendedor' : 'Nuevo Vendedor'
      this.resetForm()
      // Ajusta la URL para tu endpoint real de tipos de documento
     this.$http.get('/co-sellers/type-documents').then(resp => {
        this.typeDocuments = resp.data.data || resp.data
     })
      if (this.recordId) {
        this.$http.get(`/co-sellers/edit/${this.recordId}`).then(resp => {
          this.form = resp.data.data
        })
      }
    },
    resetForm() {
      this.form = {
        id: null,
        internal_code: '',
        full_name: '',
        type_document_identification_id: null,
        document_number: '',
        birth_date: '',
        address: '',
        phone: '',
        email: '',
        start_date: '',
        status: 'Activo',
        role: '',
        area: '',
        shift: '',
        monthly_goal: '',
        commission_percentage: '',
        commission_type: '',
      }
      this.errors = {}
    },
    submit() {
    this.loading_submit = true

    const formatDate = date => {
        if (!date) return null
        if (typeof date === 'string') return date.substr(0, 10)
        if (date instanceof Date) return date.toISOString().substr(0, 10)
        if (typeof date === 'object' && typeof date.toISOString === 'function') return date.toISOString().substr(0, 10)
        return null
    }

    let payload = {
        ...this.form,
        birth_date: formatDate(this.form.birth_date),
        start_date: formatDate(this.form.start_date),
    }

    let method = this.form.id ? 'put' : 'post'
    let url = this.form.id ? `/co-sellers/${this.form.id}` : '/co-sellers'
    this.$http[method](url, payload)
        .then(response => {
        if (response.data.success) {
            this.$message.success('Vendedor guardado correctamente')
            this.$eventHub.$emit('reloadData')
            this.close()
        } else {
            this.$message.error(response.data.message)
        }
        })
        .catch(error => {
        if (error.response && error.response.status === 422) {
            this.errors = error.response.data.errors || error.response.data
        } else {
            console.log(error)
        }
        })
        .then(() => {
        this.loading_submit = false
        })
    },
    close() {
      this.$emit('update:showDialog', false)
      this.resetForm()
    },
  }
}
</script>