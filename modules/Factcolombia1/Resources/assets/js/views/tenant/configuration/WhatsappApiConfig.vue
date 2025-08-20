<template>
    <form autocomplete="off">
        <!-- <div class="card-header bg-info">
            <h3 class="my-0">Configuración API WhatsApp</h3>
        </div> -->
        <div class="row">
            <div class="col-md-6 mt-4">
                <div class="form-group">
                    <label class="control-label">URL Base API</label>
                    <el-input 
                        v-model="apiConfig.api_url"
                        placeholder="Ej: https://api.whatsapp.com/v1">
                    </el-input>
                </div>
            </div>
            <div class="col-md-6 mt-4">
                <div class="form-group">
                    <label class="control-label">Token API</label>
                    <el-input 
                        v-model="apiConfig.api_token"
                        type="password"
                        show-password
                        placeholder="Ingrese el token de la API">
                    </el-input>
                </div>
            </div>
        </div>
        <div class="form-actions text-right mt-4">
            <el-button
                type="primary"
                :loading="loadingApiConfig"
                @click="saveApiConfig">
                Guardar Configuración API
            </el-button>
        </div>
    </form>
</template>

<script>
export default {
    data: () => ({
        apiConfig: {
            api_url: '',
            api_token: ''
        },
        loadingApiConfig: false,
    }),
    mounted() {
        this.getWhatsappConfig();
    },
    methods: {
        async getWhatsappConfig() {
            try {
                const response = await this.$http.get('/pos/whatsapp/config');
                if (response.data.success && response.data.data) {
                    this.apiConfig = {
                        api_url: response.data.data.api_url || '',
                        api_token: response.data.data.api_token || ''
                    };
                }
            } catch (error) {
                this.$message.error('Error al cargar la configuración de WhatsApp');
            }
        },
        async saveApiConfig() {
            if (!this.apiConfig.api_url || !this.apiConfig.api_token) {
                this.$message.error('Debe ingresar URL y Token del API');
                return;
            }
            try {
                this.loadingApiConfig = true;
                const response = await this.$http.post('/pos/whatsapp/config', {
                    api_url: this.apiConfig.api_url,
                    api_token: this.apiConfig.api_token
                });
                if (response.data.success) {
                    this.$message.success(response.data.message);
                } else {
                    throw new Error(response.data.message || 'Error desconocido');
                }
            } catch (error) {
                this.$message.error(error.response?.data?.message || error.message || 'Error al guardar la configuración');
            } finally {
                this.loadingApiConfig = false;
            }
        }
    }
}
</script>