<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: '2xl',
        validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl'].includes(value),
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
            dialog.value?.showModal();
        } else {
            document.body.style.overflow = '';
            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 250);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        ref="dialog"
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
    >
        <div
            class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-10 flex items-center justify-center min-h-screen"
            scroll-region
        >
            <!-- Backdrop -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 bg-brand-charcoal/70 backdrop-blur-sm transition-opacity"
                    @click="close"
                />
            </Transition>

            <!-- Dialog Modal Panel -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 scale-95 translate-y-3"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 translate-y-3"
            >
                <div
                    v-show="show"
                    class="relative transform overflow-hidden rounded-sm bg-brand-bg text-brand-charcoal border border-brand-border shadow-2xl transition-all w-full my-auto"
                    :class="maxWidthClass"
                    role="dialog"
                    aria-modal="true"
                >
                    <!-- Header -->
                    <div
                        v-if="title || $slots.header || closeable"
                        class="flex items-center justify-between px-6 py-4 border-b border-brand-border bg-brand-surface/80"
                    >
                        <slot name="header">
                            <h3 class="font-serif text-xl sm:text-2xl font-normal text-brand-charcoal tracking-tight">
                                {{ title }}
                            </h3>
                        </slot>

                        <button
                            v-if="closeable"
                            type="button"
                            class="p-1.5 text-brand-muted hover:text-brand-charcoal transition-colors rounded-sm focus:outline-none focus:ring-1 focus:ring-brand-gold"
                            aria-label="Close"
                            @click="close"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <slot v-if="showSlot" />
                    </div>

                    <!-- Footer -->
                    <div
                        v-if="$slots.footer"
                        class="px-6 py-4 border-t border-brand-border bg-brand-surface-warm/50 flex items-center justify-end gap-3"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>
