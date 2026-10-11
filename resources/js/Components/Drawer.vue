<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    position: {
        type: String,
        default: 'right',
        validator: (value) => ['left', 'right', 'bottom'].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value),
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});

const widthClass = computed(() => {
    if (props.position === 'bottom') return 'w-full max-h-[85vh]';
    return {
        sm: 'max-w-xs',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        full: 'max-w-full',
    }[props.maxWidth];
});

const transitionClasses = computed(() => {
    if (props.position === 'left') {
        return {
            enterFrom: '-translate-x-full',
            enterTo: 'translate-x-0',
            leaveFrom: 'translate-x-0',
            leaveTo: '-translate-x-full',
        };
    }
    if (props.position === 'bottom') {
        return {
            enterFrom: 'translate-y-full',
            enterTo: 'translate-y-0',
            leaveFrom: 'translate-y-0',
            leaveTo: 'translate-y-full',
        };
    }
    return {
        enterFrom: 'translate-x-full',
        enterTo: 'translate-x-0',
        leaveFrom: 'translate-x-0',
        leaveTo: 'translate-x-full',
    };
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 overflow-hidden"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <Transition
                appear
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-brand-charcoal/60 backdrop-blur-sm transition-opacity"
                    @click="close"
                />
            </Transition>

            <!-- Panel -->
            <div
                class="fixed inset-y-0 flex"
                :class="[
                    position === 'left' ? 'left-0 justify-start' : '',
                    position === 'right' ? 'right-0 justify-end' : '',
                    position === 'bottom' ? 'bottom-0 inset-x-0 items-end justify-center' : '',
                ]"
            >
                <Transition
                    appear
                    enter-active-class="transform transition ease-out duration-300"
                    :enter-from-class="transitionClasses.enterFrom"
                    :enter-to-class="transitionClasses.enterTo"
                    leave-active-class="transform transition ease-in duration-250"
                    :leave-from-class="transitionClasses.leaveFrom"
                    :leave-to-class="transitionClasses.leaveTo"
                >
                    <div
                        class="w-screen bg-brand-bg text-brand-charcoal border-brand-border flex flex-col shadow-2xl relative"
                        :class="[
                            widthClass,
                            position === 'left' ? 'border-r h-full' : '',
                            position === 'right' ? 'border-l h-full' : '',
                            position === 'bottom' ? 'border-t rounded-t-lg' : '',
                        ]"
                    >
                        <!-- Header -->
                        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between bg-brand-surface/70 shrink-0">
                            <slot name="header">
                                <h3 class="font-serif text-2xl font-normal text-brand-charcoal tracking-tight">
                                    {{ title }}
                                </h3>
                            </slot>

                            <button
                                v-if="closeable"
                                type="button"
                                class="p-1.5 text-brand-muted hover:text-brand-charcoal transition-colors rounded-sm focus:outline-none focus:ring-1 focus:ring-brand-gold"
                                aria-label="Close panel"
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

                        <!-- Content Area -->
                        <div class="flex-1 overflow-y-auto px-6 py-6">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div
                            v-if="$slots.footer"
                            class="px-6 py-4 border-t border-brand-border bg-brand-surface/80 shrink-0"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </Teleport>
</template>
