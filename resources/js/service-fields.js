// Client-side mirror of App\Services\ServiceFormSchema. Used for live estimates and
// friendly validation only — the server always recomputes and re-validates.

const rupiahFormat = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

export const rupiah = (value) => rupiahFormat.format(Number(value) || 0);

const isEmpty = (answer) => answer === undefined || answer === null || answer === '' || (Array.isArray(answer) && answer.length === 0);

const asList = (answer) => (Array.isArray(answer) ? answer : [answer]).map(String);

/** Fields visible for the given answers; show_if may only reference an earlier field. */
export function visibleFields(fields, details) {
    const visible = new Map();
    for (const field of fields || []) {
        const condition = field.show_if;
        if (condition) {
            if (!visible.has(condition.field)) continue;
            const selected = asList(details[condition.field] ?? '');
            if (!selected.some((value) => condition.values.includes(value))) continue;
        }
        visible.set(field.key, field);
    }
    return [...visible.values()];
}

export function selectedOptions(field, answer) {
    const selected = asList(answer ?? '');
    return (field.options || []).filter((option) => selected.includes(String(option.value)));
}

/** Same rules as the server: base (last wins, null = no change) + add + per_unit. */
export function estimate(fields, details, basePrice) {
    let baseLine = { label: 'Harga dasar layanan', detail: '', amount: Math.round(Number(basePrice) || 0) };
    const extra = [];

    for (const field of visibleFields(fields, details)) {
        const answer = details[field.key];
        if (isEmpty(answer) || field.pricing === 'none') continue;

        if (field.pricing === 'per_unit' && field.type === 'number') {
            const quantity = Math.max(0, parseInt(answer, 10) || 0);
            extra.push({ label: field.label, detail: `${quantity} ${field.unit_label || ''}`.trim() + ` × ${rupiah(field.unit_price)}`, amount: quantity * field.unit_price });
            continue;
        }

        const selected = selectedOptions(field, answer);
        if (field.pricing === 'base') {
            const option = selected[selected.length - 1];
            if (option && option.price !== null && option.price !== undefined) {
                baseLine = { label: field.label, detail: option.label, amount: option.price };
            }
        } else if (field.pricing === 'add') {
            for (const option of selected) {
                if (Number(option.price)) extra.push({ label: field.label, detail: option.label, amount: Number(option.price) });
            }
        }
    }

    const lines = [baseLine, ...extra];
    return { lines, total: lines.reduce((sum, line) => sum + line.amount, 0) };
}

/** Price hint shown next to an option, e.g. "Rp 4.000.000" or "+Rp 250.000". */
export function optionPriceLabel(field, option) {
    if (option.price === null || option.price === undefined) return '';
    // A base option of 0 (e.g. "Per bab") is priced by later choices, so no "Rp 0" hint.
    if (field.pricing === 'base') return Number(option.price) > 0 ? rupiah(option.price) : '';
    if (field.pricing === 'add' && Number(option.price) > 0) return '+' + rupiah(option.price);
    return '';
}

export function unitPriceLabel(field) {
    return field.pricing === 'per_unit' && field.unit_price ? `${rupiah(field.unit_price)} / ${field.unit_label || 'unit'}` : '';
}

/** Human-readable answers for review screens. */
export function answerList(fields, details) {
    return visibleFields(fields, details)
        .filter((field) => !isEmpty(details[field.key]))
        .map((field) => {
            const answer = details[field.key];
            let display = String(answer);
            if (['select', 'radio', 'checkbox'].includes(field.type)) display = selectedOptions(field, answer).map((o) => o.label).join(', ');
            else if (field.type === 'number') display = `${answer} ${field.unit_label || ''}`.trim();
            return { key: field.key, label: field.label, display };
        });
}

/** Returns { 'details.key': message } for visible fields. */
export function validateDetails(fields, details) {
    const errors = {};
    for (const field of visibleFields(fields, details)) {
        const answer = details[field.key];
        const name = `details.${field.key}`;
        if (isEmpty(answer)) {
            if (field.required) errors[name] = ["select", "radio", "checkbox"].includes(field.type) ? `${field.label} wajib dipilih.` : `${field.label} wajib diisi.`;
            continue;
        }
        if (field.type === 'number') {
            const value = Number(answer);
            if (!Number.isInteger(value)) errors[name] = `${field.label} harus berupa angka bulat.`;
            else if (field.min !== null && field.min !== undefined && value < field.min) errors[name] = `${field.label} minimal ${field.min}.`;
            else if (field.max !== null && field.max !== undefined && value > field.max) errors[name] = `${field.label} maksimal ${field.max}.`;
        } else if (field.type === 'text' && String(answer).length > 255) {
            errors[name] = `${field.label} maksimal 255 karakter.`;
        } else if (field.type === 'textarea' && String(answer).length > 2000) {
            errors[name] = `${field.label} maksimal 2.000 karakter.`;
        }
    }
    return errors;
}

/** Empty answer containers so x-model has the right shape (arrays for checkboxes). */
export function blankDetails(fields, previous = {}) {
    const details = {};
    for (const field of fields || []) {
        const old = previous[field.key];
        if (field.type === 'checkbox') details[field.key] = Array.isArray(old) ? old.map(String) : [];
        else details[field.key] = old === undefined || old === null || Array.isArray(old) ? '' : String(old);
    }
    return details;
}

/** Laravel error keys like details.bab.0 → details.bab */
export function normalizeErrorKey(key) {
    const match = key.match(/^details\.([^.]+)/);
    return match ? `details.${match[1]}` : key;
}

/** Cheapest valid combination — mirror of ServiceFormSchema::startingPrice(). */
export function startingPrice(fields, basePrice) {
    const list = fields || [];
    const fallback = Math.round(Number(basePrice) || 0);
    if (!list.length) return fallback;
    let budget = 5000;
    let cheapest = null;
    const walk = (index, details) => {
        if (budget <= 0) return;
        if (index >= list.length) {
            budget -= 1;
            const total = estimate(list, details, basePrice).total;
            cheapest = cheapest === null ? total : Math.min(cheapest, total);
            return;
        }
        const field = list[index];
        const visible = visibleFields(list.slice(0, index + 1), details).some((item) => item.key === field.key);
        if (!visible) { walk(index + 1, details); return; }
        if (['select', 'radio', 'checkbox'].includes(field.type)) {
            if (!field.required || !(field.options || []).length) walk(index + 1, details);
            for (const option of field.options || []) {
                walk(index + 1, { ...details, [field.key]: field.type === 'checkbox' ? [option.value] : option.value });
            }
            return;
        }
        if (field.type === 'number' && field.required) {
            walk(index + 1, { ...details, [field.key]: Math.max(field.min ?? 1, 0) });
            return;
        }
        walk(index + 1, details);
    };
    walk(0, {});
    return cheapest ?? fallback;
}
