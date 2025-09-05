export default {
    data() {
        return {
            scale: {
                supported: 'serial' in navigator,
                connecting: false,
                connected: false,
                port: null,
                reader: null,
                readingIndex: null,
                cfg: { baudRate: 9600, dataBits: 8, stopBits: 1, parity: 'none', flowControl: 'none' },
                lineBuffer: '',
            },
        };
    },
    methods: {
        async connectScale() {
            if (!this.scale.supported) {
                this.$message.error('Tu navegador no soporta Web Serial.');
                return;
            }
            try {
                this.scale.connecting = true;
                this.scale.port = await navigator.serial.requestPort();
                await this.scale.port.open(this.scale.cfg);
                const textDecoder = new TextDecoderStream();
                this.scale.port.readable.pipeTo(textDecoder.writable);
                this.scale.reader = textDecoder.readable.getReader();
                this.scale.connected = true;
                this.$message.success('Balanza conectada');
            } catch (err) {
                console.error(err);
                this.$message.error('No se pudo abrir el puerto de la balanza.');
                await this.disconnectScale();
            } finally {
                this.scale.connecting = false;
            }
        },
        async disconnectScale() {
            try {
                if (this.scale.reader) {
                    await this.scale.reader.cancel();
                    this.scale.reader.releaseLock();
                }
            } catch (_) {}
            try {
                if (this.scale.port) await this.scale.port.close();
            } catch (_) {}
            this.scale.reader = null;
            this.scale.port = null;
            this.scale.connected = false;
        },
        async readWeightOnce({ timeoutMs = 4000, stableOnly = false } = {}) {
            if (!this.scale.connected || !this.scale.reader) {
                await this.connectScale();
                if (!this.scale.connected) return null;
            }
            const deadline = Date.now() + timeoutMs;
            let buffer = '';
            while (Date.now() < deadline) {
                const { value, done } = await this.scale.reader.read();
                if (done) break;
                if (!value) continue;
                buffer += value;
                const lines = buffer.split(/\r\n|\n|\r/);
                buffer = lines.pop() || '';
                for (const line of lines) {
                    if (stableOnly && /US|UNSTABLE/i.test(line)) continue;
                    const parsed = this.parseWeightLine(line);
                    if (parsed) return parsed;
                }
            }
            return null;
        },
        parseWeightLine(line) {
            const numM = line.match(/-?\d+(?:[.,]\d+)?/);
            if (!numM) return null;
            let val = parseFloat(numM[0].replace(',', '.'));
            if (isNaN(val)) return null;
            const unitM = line.match(/\b(kg|g|lb|lbs)\b/i);
            let unit = unitM ? unitM[1].toLowerCase() : null;
            if (unit === 'g') {
                val = val / 1000;
                unit = 'kg';
            }
            return { value: val, unit, raw: line };
        },
        async readWeightLoop(item, index) {
            while (this.scale.continuousReading && this.scale.connected && this.scale.reader && this.scale.readingIndex === index) {
                const { value, done } = await this.scale.reader.read();
                if (done || !value) break;
                const lines = value.split(/\r\n|\n|\r/);
                for (const line of lines) {
                    const reading = this.parseWeightLine(line);
                    if (reading && Number.isFinite(reading.value)) {
                        this.scale.lastWeightValue = reading.value.toFixed(3);
                        this.$set(item.item, 'aux_quantity', reading.value.toFixed(3));
                    }
                }
            }
        },
        startContinuousWeight(item, index) {
            if (!this.scale.connected || !this.scale.reader) return;
            this.scale.readingIndex = index;
            this.scale.continuousReading = true;
            this.scale.lastWeightValue = null;
            this.readWeightLoop(item, index);
        },
        stopContinuousWeight(item, index) {
            this.scale.continuousReading = false;
            this.scale.readingIndex = null;
            if (
                this.scale.lastWeightValue !== null &&
                !isNaN(this.scale.lastWeightValue) &&
                Number(this.scale.lastWeightValue) > 0
            ) {
                this.$set(item.item, 'aux_quantity', this.scale.lastWeightValue);
            }
            this.onQuantityInput(item, index);
        },
        async onEnterQuantity(item, index) {
            if (this.scale.connected && this.scale.reader) {
                const reading = await this.readWeightOnce({ timeoutMs: 2000, stableOnly: true });
                if (reading && Number.isFinite(reading.value) && reading.value > 0) {
                    this.$set(item.item, 'aux_quantity', Number(reading.value).toFixed(3));
                    item.quantity = Number(reading.value).toFixed(3);
                }
            }
            if (Number(item.item.aux_quantity) > 0) {
                item.quantity = Number(item.item.aux_quantity);
            }
            this.calculateTotal();
        },
    }
}