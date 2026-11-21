// Admin form builder for a service's order fields and prices.
// Edits a working copy (with stable uids), and produces the normalised JSON the server stores.
import { visibleFields, estimate, startingPrice, optionPriceLabel, unitPriceLabel, rupiah } from './service-fields.js';

const CHOICE = ['select', 'radio', 'checkbox'];

export const TYPE_LABELS = {
    radio: 'Pilihan tunggal',
    select: 'Dropdown',
    checkbox: 'Centang banyak',
    text: 'Teks singkat',
    textarea: 'Paragraf',
    number: 'Angka',
};

export const PRICING_LABELS = {
    none: 'Tidak memengaruhi harga',
    base: 'Harga dasar (menggantikan)',
    add: 'Tambahan (+)',
    per_unit: 'Per unit (jumlah × harga)',
};

let uidCounter = 0;
const uid = () => `f${Date.now().toString(36)}${(uidCounter++).toString(36)}`;

/** "Peringkat SINTA" → "peringkat_sinta" (must start with a letter, max 50). */
export function slugKey(label, fallback = 'field') {
    let key = String(label || '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 50);
    if (!key) key = fallback;
    if (!/^[a-z]/.test(key)) key = `${fallback}_${key}`.slice(0, 50);
    return key;
}

const numOrNull = (value) => (value === '' || value === null || value === undefined || Number.isNaN(Number(value)) ? null : Math.round(Number(value)));

function toWorking(fields) {
    const list = (fields || []).map((field) => ({
        uid: uid(),
        open: false,
        keyLocked: true,
        key: field.key,
        label: field.label,
        type: field.type,
        required: !!field.required,
        help: field.help || '',
        placeholder: field.placeholder || '',
        pricing: field.pricing || 'none',
        options: (field.options || []).map((option) => ({ uid: uid(), valueLocked: true, value: option.value, label: option.label, price: option.price ?? '' })),
        unit_price: field.unit_price ?? '',
        unit_label: field.unit_label || '',
        min: field.min ?? '',
        max: field.max ?? '',
        showIfKey: field.show_if?.field || '',
        showIfValues: field.show_if?.values || [],
    }));
    // show_if is tracked by uid so renaming a key never breaks a condition.
    const byKey = Object.fromEntries(list.map((field) => [field.key, field.uid]));
    return list.map(({ showIfKey, showIfValues, ...field }) => ({ ...field, showIf: showIfKey && byKey[showIfKey] ? { uid: byKey[showIfKey], values: [...showIfValues] } : null }));
}

export function serviceFormBuilder(config) {
    return {
        fields: toWorking(config.fields),
        templates: config.templates || [],
        otherServices: config.otherServices || [],
        basePrice: config.basePrice ?? 0,
        preview: {},
        copyFrom: '',
        dirty: false,
        typeLabels: TYPE_LABELS,
        pricingLabels: PRICING_LABELS,

        init() {
            // Only real changes count — expanding/collapsing a card must not trigger the leave-page warning.
            this.$watch('json', () => { this.dirty = true; });
            window.addEventListener('beforeunload', (event) => {
                if (this.dirty && !this.submitting) { event.preventDefault(); event.returnValue = ''; }
            });
        },

        // ---- Output -------------------------------------------------------
        get normalized() {
            const keyOf = Object.fromEntries(this.fields.map((field) => [field.uid, field.key]));
            return this.fields.map((field) => {
                const isChoice = CHOICE.includes(field.type);
                const isNumber = field.type === 'number';
                const source = field.showIf ? this.fields.find((item) => item.uid === field.showIf.uid) : null;
                return {
                    key: field.key,
                    label: field.label.trim(),
                    type: field.type,
                    required: field.required,
                    help: field.help.trim() || null,
                    placeholder: field.placeholder.trim() || null,
                    pricing: field.pricing,
                    options: isChoice ? field.options.map((option) => ({ value: option.value, label: option.label.trim(), price: numOrNull(option.price) })) : [],
                    unit_price: isNumber ? (numOrNull(field.unit_price) ?? 0) : 0,
                    unit_label: isNumber ? field.unit_label.trim() : '',
                    min: isNumber ? numOrNull(field.min) : null,
                    max: isNumber ? numOrNull(field.max) : null,
                    show_if: source && field.showIf.values.length ? { field: keyOf[source.uid], values: [...field.showIf.values] } : null,
                };
            });
        },
        get json() { return JSON.stringify(this.normalized); },

        // ---- Preview ------------------------------------------------------
        get previewFields() { return visibleFields(this.normalized, this.preview); },
        // Same rule as the public form: until required "harga dasar" choices are made, show the catalog price.
        get previewEstimate() {
            const pendingBase = this.previewFields.some((field) => field.pricing === 'base' && field.required && !this.preview[field.key]);
            if (pendingBase) return { total: this.catalogPrice, lines: [{ label: 'Harga katalog (termurah)', detail: '', amount: this.catalogPrice }] };
            return estimate(this.normalized, this.preview, this.basePrice);
        },
        get catalogPrice() { return startingPrice(this.normalized, this.basePrice); },
        syncPreview() {
            for (const field of this.normalized) {
                const current = this.preview[field.key];
                if (field.type === 'checkbox' && !Array.isArray(current)) this.preview[field.key] = [];
                else if (field.type !== 'checkbox' && (current === undefined || Array.isArray(current))) this.preview[field.key] = '';
            }
        },
        resetPreview() { this.preview = {}; this.syncPreview(); },
        optionPrice(field, option) { return optionPriceLabel(field, option); },
        unitPrice(field) { return unitPriceLabel(field); },
        rupiah(value) { return rupiah(value); },

        // ---- Field operations --------------------------------------------
        uniqueKey(base, except = null) {
            const taken = new Set(this.fields.filter((field) => field.uid !== except).map((field) => field.key));
            let key = base;
            let n = 2;
            while (taken.has(key)) { key = `${base}_${n}`.slice(0, 50); n += 1; }
            return key;
        },
        addField(type = 'radio') {
            const field = {
                uid: uid(), open: true, keyLocked: false, key: this.uniqueKey('field_baru'), label: '', type, required: true,
                help: '', placeholder: '', pricing: 'none', options: [], unit_price: '', unit_label: '', min: '', max: '', showIf: null,
            };
            if (CHOICE.includes(type)) { this.addOption(field); this.addOption(field); }
            this.fields.push(field);
            this.$nextTick(() => document.getElementById(`label-${field.uid}`)?.focus());
        },
        labelChanged(field) {
            if (!field.keyLocked) field.key = this.uniqueKey(slugKey(field.label), field.uid);
        },
        keyEdited(field) {
            field.keyLocked = true;
            field.key = slugKey(field.key);
        },
        typeChanged(field) {
            if (CHOICE.includes(field.type) && !field.options.length) { this.addOption(field); this.addOption(field); }
            if (!this.pricingFor(field).includes(field.pricing)) field.pricing = 'none';
            // Fields that depend on this one need a choice field as their source.
            if (!CHOICE.includes(field.type)) this.fields.forEach((item) => { if (item.showIf?.uid === field.uid) item.showIf = null; });
            this.syncPreview();
        },
        pricingFor(field) {
            if (field.type === 'select' || field.type === 'radio') return ['none', 'base', 'add'];
            if (field.type === 'checkbox') return ['none', 'add'];
            if (field.type === 'number') return ['none', 'per_unit'];
            return ['none'];
        },
        move(index, delta) {
            const target = index + delta;
            if (target < 0 || target >= this.fields.length) return;
            const [field] = this.fields.splice(index, 1);
            this.fields.splice(target, 0, field);
        },
        duplicateField(index) {
            const original = this.fields[index];
            const copy = JSON.parse(JSON.stringify(original));
            copy.uid = uid();
            copy.open = true;
            copy.keyLocked = false;
            copy.label = `${original.label} (salinan)`;
            copy.key = this.uniqueKey(slugKey(copy.label), copy.uid);
            copy.options = copy.options.map((option) => ({ ...option, uid: uid() }));
            this.fields.splice(index + 1, 0, copy);
        },
        removeField(index) {
            const field = this.fields[index];
            const dependants = this.fields.filter((item) => item.showIf?.uid === field.uid);
            const label = field.label || 'tanpa nama';
            const warning = dependants.length ? `\n${dependants.length} field lain yang bergantung padanya akan selalu tampil.` : '';
            if (!confirm(`Hapus field "${label}"?${warning}`)) return;
            dependants.forEach((item) => { item.showIf = null; });
            this.fields.splice(index, 1);
        },

        // ---- Options ------------------------------------------------------
        addOption(field) {
            const value = this.uniqueOptionValue(field, 'opsi');
            field.options.push({ uid: uid(), valueLocked: false, value, label: '', price: '' });
        },
        uniqueOptionValue(field, base, except = null) {
            const taken = new Set(field.options.filter((option) => option.uid !== except).map((option) => option.value));
            let value = base;
            let n = 2;
            while (taken.has(value)) { value = `${base}_${n}`.slice(0, 50); n += 1; }
            return value;
        },
        optionLabelChanged(field, option) {
            if (!option.valueLocked) option.value = this.uniqueOptionValue(field, slugKey(option.label, 'opsi'), option.uid);
        },
        moveOption(field, index, delta) {
            const target = index + delta;
            if (target < 0 || target >= field.options.length) return;
            const [option] = field.options.splice(index, 1);
            field.options.splice(target, 0, option);
        },
        removeOption(field, index) {
            const [removed] = field.options.splice(index, 1);
            this.fields.forEach((item) => {
                if (item.showIf?.uid === field.uid) item.showIf.values = item.showIf.values.filter((value) => value !== removed.value);
            });
        },

        // ---- Conditional visibility --------------------------------------
        conditionSources(index) {
            return this.fields.slice(0, index).filter((field) => CHOICE.includes(field.type));
        },
        sourceOf(field) { return field.showIf ? this.fields.find((item) => item.uid === field.showIf.uid) : null; },
        setCondition(field, sourceUid) {
            field.showIf = sourceUid ? { uid: sourceUid, values: [] } : null;
        },
        toggleConditionValue(field, value) {
            const values = field.showIf.values;
            field.showIf.values = values.includes(value) ? values.filter((item) => item !== value) : [...values, value];
        },
        conditionProblem(field, index) {
            if (!field.showIf) return '';
            const sourceIndex = this.fields.findIndex((item) => item.uid === field.showIf.uid);
            if (sourceIndex === -1) return 'Field syarat sudah dihapus.';
            if (sourceIndex >= index) return 'Field syarat harus berada di atas field ini. Pindahkan urutannya.';
            if (!field.showIf.values.length) return 'Pilih minimal satu nilai agar syarat berlaku.';
            return '';
        },

        // ---- Summaries ---------------------------------------------------
        summary(field) {
            const parts = [TYPE_LABELS[field.type]];
            if (field.pricing !== 'none') parts.push(PRICING_LABELS[field.pricing].split(' (')[0]);
            if (CHOICE.includes(field.type)) parts.push(`${field.options.length} pilihan`);
            if (field.showIf) parts.push(`muncul jika ${this.sourceOf(field)?.label || '…'}`);
            return parts.join(' · ');
        },
        priceHint(field) {
            if (field.pricing === 'base') return 'Pilihan ini menjadi harga dasar. Kosongkan harga bila ditentukan field berikutnya (mis. SINTA → peringkat).';
            if (field.pricing === 'add') return 'Harga pilihan ditambahkan ke estimasi. Isi 0 bila tidak ada biaya tambahan.';
            if (field.pricing === 'per_unit') return 'Estimasi bertambah sebesar jumlah × harga per unit.';
            return '';
        },

        // ---- Templates & copying ----------------------------------------
        loadFields(fields, label) {
            if (this.fields.length && !confirm(`Ganti semua field saat ini dengan ${label}?`)) return false;
            this.fields = toWorking(fields);
            this.resetPreview();
            return true;
        },
        useTemplate(template) { this.loadFields(template.fields, `template "${template.name}"`); },
        copyFromService() {
            const service = this.otherServices.find((item) => String(item.id) === String(this.copyFrom));
            if (!service) return;
            if (!service.fields.length) { alert(`${service.name} belum punya field khusus.`); return; }
            if (this.loadFields(service.fields, `field dari "${service.name}"`)) this.copyFrom = '';
        },
        clearFields() {
            if (this.fields.length && confirm('Hapus semua field? Layanan akan memakai form umum saja.')) { this.fields = []; this.resetPreview(); }
        },
        expandAll(open) { this.fields.forEach((field) => { field.open = open; }); },

        submitting: false,
        beforeSubmit() { this.submitting = true; },
    };
}
