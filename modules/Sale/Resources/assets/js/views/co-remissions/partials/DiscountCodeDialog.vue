<template>
  <el-dialog
    title="Validar código de descuento"
    :visible.sync="visible"
    width="30%"
    :close-on-click-modal="false"
    @open="fetchDiscountCode"
  >
    <div>
      <el-input v-model="inputCode" placeholder="Ingrese el código de descuento"></el-input>
      <div v-if="error" style="color:red; margin-top:8px;">{{ error }}</div>
    </div>
    <span slot="footer" class="dialog-footer">
      <el-button @click="close">Cancelar</el-button>
      <el-button type="primary" @click="validate">Validar</el-button>
    </span>
  </el-dialog>
</template>

<script>
export default {
  props: {
    visible: { type: Boolean, required: true },
  },
  data() {
    return {
      inputCode: '',
      error: '',
      discountCode: '',
    }
  },
  methods: {
    async fetchDiscountCode() {
      this.inputCode = '';
      this.error = '';
      // Solo carga el código cuando se abre el modal
      try {
        const response = await this.$http.get('/co-advanced-configuration/record');
        this.discountCode = response.data.data.discount_code;
      } catch (e) {
        this.error = 'No se pudo obtener el código de descuento';
      }
    },
    async validate() {
      if (this.inputCode === this.discountCode) {
          this.$emit('validated', true);
          this.close();
          // Generar nuevo código en el backend
          try {
              await this.$http.post('/co-advanced-configuration/generate-discount-code');
          } catch (e) {
              // Puedes mostrar un mensaje si falla, pero no es obligatorio
          }
      } else {
          this.error = 'El código ingresado no es correcto.';
          this.$emit('validated', false);
      }
    },
    close() {
      this.$emit('update:visible', false);
      this.inputCode = '';
      this.error = '';
    }
  }
}
</script>