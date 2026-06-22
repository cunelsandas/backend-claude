<script setup>
import DatePicker from './DatePicker.vue';

defineProps({
    modelValue: { type: Array, default: () => [] },
    label:      { type: String, default: 'Date range' },
    hint:       { type: String, default: null },
    error:      { type: String, default: null },
    minDate:    { type: [String, Date, null], default: null },
    maxDate:    { type: [String, Date, null], default: null },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <DatePicker
        :model-value="modelValue"
        :label="label"
        :hint="hint"
        :error="error"
        :min-date="minDate"
        :max-date="maxDate"
        mode="range"
        placeholder="Pick a range…"
        alt-format="M j → M j, Y"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>
