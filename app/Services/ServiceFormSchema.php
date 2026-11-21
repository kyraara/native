<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Reads a service's `form_fields` definition and turns it into validation rules,
 * cleaned answers, and a "mulai dari" price estimate.
 *
 * Field shape:
 * [
 *   'key' => 'peringkat_sinta', 'label' => 'Peringkat SINTA',
 *   'type' => text|textarea|number|select|radio|checkbox,
 *   'required' => bool, 'help' => ?string, 'placeholder' => ?string,
 *   'pricing' => none|base|add|per_unit,
 *   'options' => [['value' => 's2', 'label' => 'SINTA 2', 'price' => 4000000|null], ...],
 *   'unit_price' => int, 'unit_label' => 'slide', 'min' => int, 'max' => int,   // number fields
 *   'show_if' => ['field' => 'indeksasi', 'values' => ['sinta']],              // optional
 * ]
 *
 * Pricing:
 * - base:     selected option replaces the base price (last visible answered base field wins;
 *             an option with price null does not set the base, e.g. "SINTA" waits for its rank).
 * - add:      selected option price(s) are added.
 * - per_unit: quantity × unit_price is added.
 * Estimate = base (fallback: service base_price) + adds + per-unit amounts.
 */
class ServiceFormSchema
{
    public const TYPES = ['text', 'textarea', 'number', 'select', 'radio', 'checkbox'];
    public const CHOICE_TYPES = ['select', 'radio', 'checkbox'];
    public const PRICING = ['none', 'base', 'add', 'per_unit'];

    /** Upper bound for the cheapest-combination search, to keep admin saves fast. */
    private const MAX_COMBINATIONS = 5000;

    /** Fill in defaults so the rest of the code can rely on every key existing. */
    public function normalize(?array $fields): array
    {
        return array_values(array_map(function (array $field): array {
            $type = in_array($field['type'] ?? null, self::TYPES, true) ? $field['type'] : 'text';
            $pricing = in_array($field['pricing'] ?? null, self::PRICING, true) ? $field['pricing'] : 'none';

            return [
                'key' => (string) ($field['key'] ?? ''),
                'label' => (string) ($field['label'] ?? ''),
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'help' => isset($field['help']) && $field['help'] !== '' ? (string) $field['help'] : null,
                'placeholder' => isset($field['placeholder']) && $field['placeholder'] !== '' ? (string) $field['placeholder'] : null,
                'pricing' => $pricing,
                'options' => in_array($type, self::CHOICE_TYPES, true)
                    ? array_values(array_map(fn (array $option): array => [
                        'value' => (string) ($option['value'] ?? ''),
                        'label' => (string) ($option['label'] ?? ''),
                        'price' => isset($option['price']) && $option['price'] !== '' ? (int) $option['price'] : null,
                    ], $field['options'] ?? []))
                    : [],
                'unit_price' => $type === 'number' ? (int) ($field['unit_price'] ?? 0) : 0,
                'unit_label' => $type === 'number' ? (string) ($field['unit_label'] ?? '') : '',
                'min' => $type === 'number' && isset($field['min']) && $field['min'] !== '' ? (int) $field['min'] : null,
                'max' => $type === 'number' && isset($field['max']) && $field['max'] !== '' ? (int) $field['max'] : null,
                'show_if' => ! empty($field['show_if']['field']) && ! empty($field['show_if']['values'])
                    ? ['field' => (string) $field['show_if']['field'], 'values' => array_values(array_map('strval', (array) $field['show_if']['values']))]
                    : null,
            ];
        }, $fields ?? []));
    }

    /**
     * Fields visible for the given answers. show_if may only reference an earlier field,
     * so a single forward pass resolves nested conditions (and rules out cycles).
     */
    public function visibleFields(array $fields, array $details): array
    {
        $visible = [];

        foreach ($this->normalize($fields) as $field) {
            $condition = $field['show_if'];

            if ($condition !== null) {
                if (! isset($visible[$condition['field']])) {
                    continue;
                }

                $answer = $details[$condition['field']] ?? null;
                $selected = is_array($answer) ? array_map('strval', $answer) : [(string) $answer];

                if (array_intersect($selected, $condition['values']) === []) {
                    continue;
                }
            }

            $visible[$field['key']] = $field;
        }

        return $visible;
    }

    /** Laravel validation rules for the visible fields, keyed under $prefix (e.g. details.indeksasi). */
    public function rules(array $fields, array $details, string $prefix = 'details'): array
    {
        $rules = [];

        foreach ($this->visibleFields($fields, $details) as $key => $field) {
            $name = "{$prefix}.{$key}";
            $presence = $field['required'] ? 'required' : 'nullable';
            $values = array_column($field['options'], 'value');

            $rules[$name] = match ($field['type']) {
                'text' => [$presence, 'string', 'max:255'],
                'textarea' => [$presence, 'string', 'max:2000'],
                'number' => array_values(array_filter([
                    $presence, 'integer',
                    $field['min'] !== null ? 'min:'.$field['min'] : 'min:0',
                    $field['max'] !== null ? 'max:'.$field['max'] : 'max:100000',
                ])),
                'select', 'radio' => [$presence, 'string', Rule::in($values)],
                'checkbox' => array_values(array_filter([$presence, 'array', $field['required'] ? 'min:1' : null])),
            };

            if ($field['type'] === 'checkbox') {
                $rules["{$name}.*"] = ['string', 'distinct', Rule::in($values)];
            }
        }

        return $rules;
    }

    /** Human-readable attribute names for validation messages. */
    public function attributes(array $fields, string $prefix = 'details'): array
    {
        $attributes = [];

        foreach ($this->normalize($fields) as $field) {
            $attributes["{$prefix}.{$field['key']}"] = $field['label'];
            $attributes["{$prefix}.{$field['key']}.*"] = $field['label'];
        }

        return $attributes;
    }

    /** Keep only answers to visible fields, cast to their field type. Unknown keys are dropped. */
    public function clean(array $fields, array $details): array
    {
        $clean = [];

        foreach ($this->visibleFields($fields, $details) as $key => $field) {
            $answer = $details[$key] ?? null;

            if ($answer === null || $answer === '' || $answer === []) {
                continue;
            }

            $clean[$key] = match ($field['type']) {
                'number' => (int) $answer,
                'checkbox' => array_values(array_unique(array_map('strval', (array) $answer))),
                default => trim((string) $answer),
            };
        }

        return $clean;
    }

    /**
     * Price estimate with an itemised breakdown.
     *
     * @return array{base: int, total: int, lines: array<int, array{label: string, detail: string, amount: int}>}
     */
    public function estimate(array $fields, array $details, float|int|string|null $basePrice): array
    {
        $base = (int) round((float) $basePrice);
        $baseLine = ['label' => 'Harga dasar layanan', 'detail' => '', 'amount' => $base];
        $extraLines = [];

        foreach ($this->visibleFields($fields, $details) as $key => $field) {
            $answer = $details[$key] ?? null;

            if ($answer === null || $answer === '' || $answer === [] || $field['pricing'] === 'none') {
                continue;
            }

            if ($field['pricing'] === 'per_unit' && $field['type'] === 'number') {
                $quantity = max(0, (int) $answer);
                $amount = $quantity * $field['unit_price'];
                $extraLines[] = [
                    'label' => $field['label'],
                    'detail' => trim("{$quantity} {$field['unit_label']}").' × '.$this->rupiah($field['unit_price']),
                    'amount' => $amount,
                ];
                continue;
            }

            $selected = $this->selectedOptions($field, $answer);

            if ($field['pricing'] === 'base') {
                $option = end($selected);
                if ($option && $option['price'] !== null) {
                    $base = $option['price'];
                    $baseLine = ['label' => $field['label'], 'detail' => $option['label'], 'amount' => $option['price']];
                }
                continue;
            }

            if ($field['pricing'] === 'add') {
                foreach ($selected as $option) {
                    if ((int) $option['price'] !== 0) {
                        $extraLines[] = ['label' => $field['label'], 'detail' => $option['label'], 'amount' => (int) $option['price']];
                    }
                }
            }
        }

        $lines = array_merge([$baseLine], $extraLines);

        return [
            'base' => $base,
            'total' => array_sum(array_column($lines, 'amount')),
            'lines' => $lines,
        ];
    }

    /** Answers with their labels, ready to store on the order. */
    public function answers(array $fields, array $details): array
    {
        $answers = [];

        foreach ($this->visibleFields($fields, $details) as $key => $field) {
            if (! array_key_exists($key, $details)) {
                continue;
            }

            $answer = $details[$key];
            $display = match ($field['type']) {
                'select', 'radio', 'checkbox' => implode(', ', array_column($this->selectedOptions($field, $answer), 'label')),
                'number' => trim($answer.' '.$field['unit_label']),
                default => (string) $answer,
            };

            $answers[] = ['key' => $key, 'label' => $field['label'], 'value' => $answer, 'display' => $display];
        }

        return $answers;
    }

    /** Everything an order stores: cleaned answers + estimate breakdown. */
    public function snapshot(array $fields, array $details, float|int|string|null $basePrice): array
    {
        $clean = $this->clean($fields, $details);

        return [
            'answers' => $this->answers($fields, $clean),
            'estimate' => $this->estimate($fields, $clean, $basePrice),
        ];
    }

    /**
     * Cheapest total over every valid combination of choices. Required choice fields branch over
     * their options; optional ones may also stay empty. Number fields use their minimum quantity.
     */
    public function startingPrice(?array $fields, float|int|string|null $basePrice): int
    {
        $fields = $this->normalize($fields);
        $fallback = (int) round((float) $basePrice);

        if ($fields === []) {
            return $fallback;
        }

        $budget = self::MAX_COMBINATIONS;
        $cheapest = null;

        $walk = function (int $index, array $details) use (&$walk, &$cheapest, &$budget, $fields, $basePrice): void {
            if ($budget <= 0) {
                return;
            }

            if ($index >= count($fields)) {
                $budget--;
                $total = $this->estimate($fields, $details, $basePrice)['total'];
                $cheapest = $cheapest === null ? $total : min($cheapest, $total);
                return;
            }

            $field = $fields[$index];

            if (! isset($this->visibleFields(array_slice($fields, 0, $index + 1), $details)[$field['key']])) {
                $walk($index + 1, $details);
                return;
            }

            if (in_array($field['type'], self::CHOICE_TYPES, true)) {
                if (! $field['required'] || $field['options'] === []) {
                    $walk($index + 1, $details);
                }
                foreach ($field['options'] as $option) {
                    $walk($index + 1, $details + [$field['key'] => $field['type'] === 'checkbox' ? [$option['value']] : $option['value']]);
                }
                return;
            }

            if ($field['type'] === 'number' && $field['required']) {
                $walk($index + 1, $details + [$field['key'] => max($field['min'] ?? 1, 0)]);
                return;
            }

            $walk($index + 1, $details);
        };

        $walk(0, []);

        return $cheapest ?? $fallback;
    }

    /**
     * Structural checks for admin-authored schemas. Returns a list of error messages (empty = valid).
     */
    public function validateSchema(array $fields): array
    {
        $errors = [];
        $seen = [];

        foreach ($this->normalize($fields) as $i => $field) {
            $n = $i + 1;
            $name = $field['label'] !== '' ? "\"{$field['label']}\"" : "#{$n}";

            if ($field['label'] === '') {
                $errors[] = "Field #{$n}: label wajib diisi.";
            }
            if (! preg_match('/^[a-z][a-z0-9_]{0,49}$/', $field['key'])) {
                $errors[] = "Field {$name}: key harus huruf kecil, angka, atau garis bawah.";
            } elseif (isset($seen[$field['key']])) {
                $errors[] = "Field {$name}: key \"{$field['key']}\" sudah dipakai field lain.";
            }

            if (in_array($field['type'], self::CHOICE_TYPES, true)) {
                if ($field['options'] === []) {
                    $errors[] = "Field {$name}: tambahkan minimal satu pilihan.";
                }
                $values = array_column($field['options'], 'value');
                if (count($values) !== count(array_unique($values)) || in_array('', $values, true)) {
                    $errors[] = "Field {$name}: setiap pilihan harus punya nilai unik.";
                }
                foreach ($field['options'] as $option) {
                    if ($option['label'] === '') {
                        $errors[] = "Field {$name}: setiap pilihan wajib punya label.";
                    }
                    if ($option['price'] !== null && $option['price'] < 0) {
                        $errors[] = "Field {$name}: harga pilihan tidak boleh negatif.";
                    }
                }
            }

            if ($field['pricing'] === 'base' && ! in_array($field['type'], ['select', 'radio'], true)) {
                $errors[] = "Field {$name}: mode \"harga dasar\" hanya untuk dropdown atau pilihan tunggal.";
            }
            if ($field['pricing'] === 'add' && ! in_array($field['type'], self::CHOICE_TYPES, true)) {
                $errors[] = "Field {$name}: mode \"tambahan\" hanya untuk field pilihan.";
            }
            if ($field['pricing'] === 'per_unit' && $field['type'] !== 'number') {
                $errors[] = "Field {$name}: mode \"per unit\" hanya untuk field angka.";
            }
            if ($field['type'] === 'number') {
                if ($field['unit_price'] < 0) {
                    $errors[] = "Field {$name}: harga per unit tidak boleh negatif.";
                }
                if ($field['min'] !== null && $field['max'] !== null && $field['min'] > $field['max']) {
                    $errors[] = "Field {$name}: nilai minimum lebih besar dari maksimum.";
                }
            }

            if ($field['show_if'] !== null) {
                $source = $seen[$field['show_if']['field']] ?? null;
                if ($source === null) {
                    $errors[] = "Field {$name}: syarat tampil harus merujuk ke field pilihan di atasnya.";
                } elseif (! in_array($source['type'], self::CHOICE_TYPES, true)) {
                    $errors[] = "Field {$name}: syarat tampil hanya bisa memakai field pilihan.";
                } elseif (array_diff($field['show_if']['values'], array_column($source['options'], 'value')) !== []) {
                    $errors[] = "Field {$name}: nilai syarat tampil tidak ada di pilihan \"{$source['label']}\".";
                }
            }

            $seen[$field['key']] = $field;
        }

        return array_values(array_unique($errors));
    }

    /** Stable option value from a label, e.g. "SINTA 2" → "sinta_2". */
    public static function valueFromLabel(string $label): string
    {
        return Str::limit(Str::slug($label, '_'), 50, '') ?: 'opsi';
    }

    private function selectedOptions(array $field, mixed $answer): array
    {
        $selected = array_map('strval', is_array($answer) ? $answer : [$answer]);

        return array_values(array_filter($field['options'], fn (array $option): bool => in_array($option['value'], $selected, true)));
    }

    private function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
