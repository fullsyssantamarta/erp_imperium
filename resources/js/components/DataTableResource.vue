<template>
    <div>
        <div class="row ">

            <div class="col-md-12 col-lg-12 col-xl-12 ">
                <div class="row" v-if="applyFilter">
                    <div class="col-lg-4 col-md-4 col-sm-12 pb-2">
                        <div class="d-flex">
                            <div style="width:100px">
                                Filtrar por:
                            </div>
                            <el-select v-model="search.column"  placeholder="Select" @change="changeClearInput">
                                <el-option v-for="(label, key) in columns" :key="key" :value="key" :label="label"></el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                        <template v-if="search.column=='date_of_issue'">
                            <div class="d-flex">
                                <el-select 
                                    v-model="filterType" 
                                    placeholder="Tipo de filtro" 
                                    style="width: 120px; margin-right: 8px;">
                                    <el-option label="Por mes" value="month"></el-option>
                                    <el-option label="Por fecha" value="date"></el-option>
                                </el-select>
                                
                                <template v-if="filterType === 'month'">
                                    <el-date-picker
                                        v-model="search.value"
                                        type="month"
                                        style="width: calc(100% - 130px)"
                                        placeholder="Seleccione mes"
                                        value-format="yyyy-MM"
                                        @change="getRecords">
                                    </el-date-picker>
                                </template>
                                <template v-else>
                                    <el-date-picker
                                        v-model="search.value"
                                        type="date"
                                        style="width: calc(100% - 130px)"
                                        placeholder="Seleccione fecha"
                                        value-format="yyyy-MM-dd"
                                        :clearable="true"
                                        :editable="false"
                                        @change="onDateChange">
                                    </el-date-picker>
                                </template>
                            </div>
                        </template>
                        <template v-else-if="search.column=='date_of_due' || search.column=='date_of_payment' || search.column=='delivery_date'">
                            <el-date-picker
                                v-model="search.value"
                                type="date"
                                style="width: 100%;"
                                placeholder="Buscar"
                                value-format="yyyy-MM-dd"
                                @change="getRecords">
                            </el-date-picker>
                        </template>
                        <template v-else>
                            <el-input placeholder="Buscar"
                                v-model="search.value"
                                style="width: 100%;"
                                prefix-icon="el-icon-search"
                                @input="getRecords">
                            </el-input>
                        </template>
                    </div>
                </div>

            </div>


            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <slot name="heading"></slot>
                        </thead>
                        <tbody>
                        <slot v-for="(row, index) in records" :row="row" :index="customIndex(index)"></slot>
                        </tbody>
                    </table>
                    <div>
                        <el-pagination
                                @current-change="getRecords"
                                layout="total, prev, pager, next"
                                :total="pagination.total"
                                :current-page.sync="pagination.current_page"
                                :page-size="pagination.per_page">
                        </el-pagination>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>


<script>

    import moment from 'moment'
    import queryString from 'query-string'

    export default {
        props: {
            resource: String,
            applyFilter:{
                type: Boolean,
                default: true,
                required: false
            },
            initSearch: {
                type: Object,
                default: null
            }
        },
        data () {
            return {
                filterType: 'month',
                search: {
                    column: null,
                    value: null
                },
                columns: [],
                records: [],
                pagination: {}
            }
        },
        computed: {
        },
        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },
        async mounted () {
            await this.$http.get(`/${this.resource}/columns`).then((response) => {
                this.columns = response.data
                if (this.initSearch) {
                    this.search = this.initSearch;
                } else {
                    this.search.column = _.head(Object.keys(this.columns))
                }
            });
            await this.getRecords()

        },
        methods: {
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },
            getRecords() {
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)
                });
            },
            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.search
                })
            },
            changeClearInput(){
                this.search.value = ''
                this.getRecords()
            },
            onDateChange(date) {
                this.search.value = date;
                this.getRecords();
            },
            getCurrentMonth() {
                const date = new Date();
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                return `${year}-${month}`;
            }
        },
        watch: {
            filterType(newValue) {
                this.search.value = '';
                if (newValue === 'month') {
                    this.search.value = this.getCurrentMonth();
                }
                this.getRecords();
            }
        }
    }
</script>

<style scoped>
.d-flex {
    display: flex;
    align-items: center;
    width: 100%;
}
</style>
