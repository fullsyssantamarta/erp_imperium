<template>
    <div>
        <div class="table-resposive">
            <table class="table table-bordered table-striped">
                <thead v-if="title">
                    <tr>
                        <th :colspan="columns.length" class="text-uppercase">
                            <strong>{{ title }}</strong>
                        </th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <th v-for="column in columns" :key="column.field">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in data" :key="row.code">
                        <td v-for="column in columns"
                            :key="column.field"
                            :class="{ 'text-right': column.field === 'saldo' }">
                            {{ row[column.field] }}
                        </td>
                    </tr>
                    <tr>
                        <td :colspan="columns.length - 1" class="text-right">
                            <strong>Total</strong>
                        </td>
                        <td class="text-right">{{ total }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: { type: String, default: '' },
        data: { type: Array, required: true },
        columns: { type: Array, required: true },
        total: { type: Number, default: 0 },
    },
};
</script>