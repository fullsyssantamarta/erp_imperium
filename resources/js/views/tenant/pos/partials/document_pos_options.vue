<template>
    <div>
        <el-dialog :title="titleDialog" :visible="showDialog" @open="create"
                :close-on-click-modal="false"
                :close-on-press-escape="false"
                :show-close="false">

            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12  ">
                    <el-tabs v-model="activeName"  >
                       <el-tab-pane label="Imprimir A4" name="first">
                            <embed :src="form.print_a4" type="application/pdf" width="100%" height="400px"/>
                        </el-tab-pane>
                        <el-tab-pane label="Imprimir A5" name="second">
                            <embed :src="form.print_a5" type="application/pdf" width="100%" height="400px"/>
                        </el-tab-pane>
                        <el-tab-pane label="Imprimir Ticket" name="third">
                            <embed :src="form.print_ticket" type="application/pdf" width="100%" height="400px"/>
                        </el-tab-pane>

                    </el-tabs>
                </div>


            </div>
            <span slot="footer" class="dialog-footer row">
                <div class="col-md-4">
                    <el-input v-model="form.customer_email">
                        <el-button slot="append" icon="el-icon-message" @click="clickSendEmail" :loading="loading">Enviar</el-button>
                    </el-input>
                </div>
                <div class="col-md-4">
                    <el-input v-model="form.whatsapp_number" placeholder="Número WhatsApp">
                        <el-button slot="append" icon="el-icon-chat-dot-round" @click="clickSendWhatsapp" :loading="loadingWhatsapp">Enviar</el-button>
                    </el-input>
                </div>
                <div class="col-md-4">
                    <template v-if="originPos">
                        <el-button  type="primary"  class="float-right" @click="clickNewSale">Nueva venta</el-button>
                    </template>
                    <template v-else>
                        <template v-if="showClose">
                            <el-button @click="clickClose">Cerrar</el-button>
                        </template>
                        <template v-else>
                            <el-button @click="clickFinalize">Ir al listado</el-button>
                            <el-button type="primary" @click="clickNewSaleNote">Nueva venta</el-button>
                        </template>
                    </template>
                </div>
            </span>
        </el-dialog>

    </div>
</template>

<script>


    export default {

        props: ['showDialog', 'recordId', 'showClose', 'originPos'],
        data() {
            return {
                titleDialog: null,
                loading: false,
                resource: 'document-pos',
                errors: {},
                form: {},
                document:{},
                document_types: [],
                all_series: [],
                series: [],
                loading_submit:false,
                showDialogOptions: false,
                documentNewId: null,
                activeName: 'third',
                enable_qz_tray: false,
                loadingWhatsapp: false,
                apiConfig: {},
                whatsappError: null,
            }
        },
        async created() {
            this.initForm()
            await this.getConfigPrint()
            await this.getQzTrayStatus()
        },
        mounted() {
            this.loadApiConfig()
        },
        methods: {
            async getConfigPrint() {
                console.info('iniciando qztray');
                await this.$http
                    .get(`/certificates-qztray/record`)
                    .then(response => {
                        this.enable_qz_tray = response.data.enable_qz_tray;
                    })
                if(this.enable_qz_tray) {
                    startConnection()
                }
            },
            async getQzTrayStatus() {
                // Consulta el estado del switch QZ Tray
                try {
                    const response = await this.$http.get('/certificates-qztray/record')
                    this.enable_qz_tray = !!response.data.enable_qz_tray
                } catch (e) {
                    this.enable_qz_tray = false
                }
            },
            // async printTicket() {
            //     let html_content = null
            //     await this.$http.get(this.form.print_html)
            //         .then(response => {
            //             html_content = response.data
            //         })
            //         .catch(error => {
            //             console.log(error)
            //         })
            //     if (html_content) {
            //         console.log('imprimir')
            //         const opts = getUpdatedConfig()
            //         const printData = [
            //             {
            //                 type: 'html',
            //                 format: 'plain',
            //                 data: html_content,
            //                 options: opts
            //             }
            //         ]
            //         qz.print(opts, printData)
            //             .then(() => {
            //                 console.log('Impresión en proceso...')
            //                 this.$message.success('Impresión en proceso...')
            //             })
            //             .catch(displayError)
            //     }
            // },
            async printTicket() {
                // Solo imprime con QZ Tray si está habilitado
                if (!this.enable_qz_tray) {
                    // Si quieres, puedes mostrar un mensaje o simplemente no hacer nada
                    // this.$message.info('La impresión directa está desactivada.');
                    return;
                }
                try {
                    // Obtener el PDF del ticket según la pestaña activa
                    const pdfUrl = {
                        first: this.form.print_a4,
                        second: this.form.print_a5,
                        third: this.form.print_ticket
                    }[this.activeName] || this.form.print_ticket;

                    if (!pdfUrl) {
                        this.$message.error('No se encontró el documento para imprimir');
                        return;
                    }

                    // Obtener el PDF como base64
                    const response = await fetch(pdfUrl);
                    const blob = await response.blob();
                    const reader = new FileReader();
                    
                    const base64Data = await new Promise((resolve) => {
                        reader.onloadend = () => resolve(reader.result.split(',')[1]);
                        reader.readAsDataURL(blob);
                    });

                    // Configurar impresora
                    const config = getUpdatedConfig();
                    
                    // Preparar datos para impresión
                    const printData = [{
                        type: 'pdf',
                        format: 'base64',
                        data: base64Data,
                        options: {
                            sizing: 'fit',
                            orientation: 'portrait',
                            units: 'mm'
                        }
                    }];

                    // Imprimir
                    await qz.print(config, printData);
                    this.$message.success('Documento enviado a la impresora');

                } catch (error) {
                    console.error('Error en la impresión:', error);
                    this.$message.error('Error al imprimir: ' + error.message);
                }
            },
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    external_id: null,
                    identifier: null,
                    date_of_issue:null,
                    print_ticket: null,
                    print_a4: null,
                    print_a5: null,
                    print_html: null,
                    series:null,
                    number:null,
                    whatsapp_number: null,
                }
            },
            async getPdfAsBase64(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Error al obtener el PDF');
                    
                    const blob = await response.blob();
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result.split(',')[1]);
                        reader.readAsDataURL(blob);
                    });
                } catch (error) {
                    console.error('Error al convertir PDF a base64:', error);
                    throw error;
                }
            },

            async clickSendWhatsapp() {
                if (!this.form.whatsapp_number) {
                    this.$message.error('Ingrese un número de WhatsApp');
                    return;
                }

                this.loadingWhatsapp = true;
                this.whatsappError = null;
                
                try {
                    const pdfUrl = {
                        first: this.form.print_a4,
                        second: this.form.print_a5,
                        third: this.form.print_ticket
                    }[this.activeName] || this.form.print_ticket;

                    const [pdfBase64] = await Promise.all([
                        this.getPdfAsBase64(pdfUrl)
                    ]);

                    const payload = {
                        number: this.form.whatsapp_number,
                        message: `Documento POS: ${this.form.serie}-${this.form.number}`,
                        pdf_base64: pdfBase64,
                        filename: `documento_${this.form.serie}-${this.form.number}.pdf`
                    };

                    const { data } = await this.$http.post('/pos/whatsapp/send', payload);

                    if (data.success) {
                        this.$message.success('Documento enviado por WhatsApp exitosamente');
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    this.whatsappError = error.response?.data?.message || error.message;
                    this.$message.error(this.whatsappError);
                } finally {
                    this.loadingWhatsapp = false;
                }
            },
            loadApiConfig() {
                const config = localStorage.getItem('pos_api_config')
                if (config) {
                    this.apiConfig = JSON.parse(config)
                }
            },
            create() {
                this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data
                        this.titleDialog = `Documento POS registrado:  ${this.form.serie}-${this.form.number}`
                        this.getQzTrayStatus().then(() => {
                            // Solo imprimir automáticamente si QZ Tray está habilitado
                            if (this.enable_qz_tray) {
                                this.printTicket()
                            }
                            // Si QZ Tray está deshabilitado, solo se muestra el PDF en el <embed>
                        })
                    })
            },
            clickFinalize() {
                location.href = `/${this.resource}`
            },
            clickNewSale(){
                this.initForm()
                this.$eventHub.$emit('cancelSale')

            },
            clickNewSaleNote() {
                this.clickClose()
            },
            clickClose() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            clickDownload(){
                window.open(`/downloads/saleNote/sale_note/${this.form.external_id}`, '_blank');
            },
            clickToPrint(format){
                window.open(`/${this.resource}/print/${this.form.id}/${format}`, '_blank');
            },
            clickSendEmail() {
                this.loading = true
                this.$http.post(`/${this.resource}/email`, {
                    customer_email: this.form.customer_email,
                    id: this.form.id,
                    format: this.activeName === 'first' ? 'a4' : 
                           this.activeName === 'second' ? 'a5' : 'ticket'
                })
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success('El correo fue enviado satisfactoriamente')
                        } else {
                            this.$message.error('Error al enviar el correo')
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data.errors
                        } else {
                            this.$message.error(error.response.data.message)
                        }
                    })
                    .finally(() => {
                        this.loading = false
                    })
            },

        }
    }
</script>
