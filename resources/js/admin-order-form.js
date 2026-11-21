// Admin create/edit order form: service-specific fields with a live estimate,
// and (when editing) a comparison against the order's previous estimate.
import { visibleFields, estimate, blankDetails, optionPriceLabel, unitPriceLabel, rupiah } from './service-fields.js';

export function adminOrderForm(config) {
    return {
        services: config.services || [],
        serviceId: String(config.serviceId || ''),
        details: {},
        detailsServiceId: '',
        originalServiceId: String(config.originalServiceId || ''),
        originalDetails: config.details || {},
        oldEstimate: config.oldEstimate ?? null,
        priceFinal: config.priceFinal ?? null,
        errors: config.errors || {},
        status: config.status || 'pending',
        priceInput: config.priceInput ?? '',
        submitting: false,

        init() {
            this.details = blankDetails(this.fields, this.originalDetails);
            this.detailsServiceId = this.serviceId;
        },

        get service() { return this.services.find((item) => String(item.id) === this.serviceId) || null; },
        get fields() { return this.service?.fields || []; },
        get visible() { return this.detailsServiceId === this.serviceId ? visibleFields(this.fields, this.details) : []; },

        /** Same rule as the public form: until required "harga dasar" choices are made, show the starting price. */
        get estimate() {
            if (!this.service) return null;
            const pending = this.visible.some((field) => field.pricing === 'base' && field.required && !this.details[field.key]);
            if (pending) return { total: this.service.price, lines: [], pending: true };
            return estimate(this.fields, this.details, this.service.base_price);
        },
        get estimateChanged() {
            return this.oldEstimate !== null && this.estimate && !this.estimate.pending && Math.round(this.estimate.total) !== Math.round(this.oldEstimate);
        },
        get needsPrice() {
            return ['processing', 'review', 'done'].includes(this.status) && String(this.priceInput).trim() === '';
        },

        changeService() {
            // Returning to the order's own service restores its saved answers.
            const previous = this.serviceId === this.originalServiceId ? this.originalDetails : {};
            this.details = blankDetails(this.fields, previous);
            this.detailsServiceId = this.serviceId;
            Object.keys(this.errors).filter((key) => key.startsWith('details.')).forEach((key) => delete this.errors[key]);
        },
        isChosen(field, option) {
            const answer = this.details[field.key];
            return Array.isArray(answer) ? answer.includes(option.value) : answer === option.value;
        },
        error(field) { return this.errors[`details.${field.key}`]; },
        clearError(field) { delete this.errors[`details.${field.key}`]; },
        optionPrice(field, option) { return optionPriceLabel(field, option); },
        unitPrice(field) { return unitPriceLabel(field); },
        rupiah(value) { return rupiah(value); },
        useEstimate() { if (this.estimate) this.priceInput = String(Math.round(this.estimate.total)); },
    };
}
