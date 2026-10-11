<script setup>
const props = defineProps({
    modelValue: {
        type: Number,
        default: 1,
    },
    min: {
        type: Number,
        default: 1,
    },
    max: {
        type: Number,
        default: 99,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const decrement = () => {
    if (props.disabled || props.modelValue <= props.min) return;
    const newVal = props.modelValue - 1;
    emit('update:modelValue', newVal);
    emit('change', newVal);
};

const increment = () => {
    if (props.disabled || props.modelValue >= props.max) return;
    const newVal = props.modelValue + 1;
    emit('update:modelValue', newVal);
    emit('change', newVal);
};

const handleInput = (event) => {
    const val = parseInt(event.target.value, 10);
    if (isNaN(val)) return;
    const clamped = Math.max(props.min, Math.min(props.max, val));
    emit('update:modelValue', clamped);
    emit('change', clamped);
};
</script>

<template>
    <div
        class="inline-flex items-center border border-brand-border bg-brand-surface rounded-sm select-none"
        :class="[
            disabled ? 'opacity-60 pointer-events-none' : '',
            size === 'sm' ? 'h-8' : size === 'lg' ? 'h-12' : 'h-10',
        ]"
    >
        <!-- Decrement Button -->
        <button
            type="button"
            :disabled="disabled || modelValue <= min"
            class="flex items-center justify-center px-3 h-full text-brand-charcoal hover:text-brand-gold disabled:text-brand-muted/40 transition-colors focus:outline-none"
            aria-label="Decrease quantity"
            @click="decrement"
        >
            <svg
                class="w-3.5 h-3.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19.5 12h-15"
                />
            </svg>
        </button>

        <!-- Quantity Input/Display -->
        <input
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            :value="modelValue"
            :disabled="disabled"
            class="w-10 md:w-12 text-center text-xs md:text-sm font-medium font-sans text-brand-charcoal bg-transparent border-0 p-0 focus:ring-0 focus:outline-none"
            aria-label="Quantity"
            @change="handleInput"
        >

        <!-- Increment Button -->
        <button
            type="button"
            :disabled="disabled || modelValue >= max"
            class="flex items-center justify-center px-3 h-full text-brand-charcoal hover:text-brand-gold disabled:text-brand-muted/40 transition-colors focus:outline-none"
            aria-label="Increase quantity"
            @click="increment"
        >
            <svg
                class="w-3.5 h-3.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 4.5v15m7.5-7.5h-15"
                />
            </svg>
        </button>
    </div>
</template>
