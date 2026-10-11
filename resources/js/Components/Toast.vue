<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '../Composables/useToast';

const page = usePage();
const { toasts, remove, success, error, info } = useToast();

// Watch for flash messages coming from Inertia server responses
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) {
            success(flash.success);
        }
        if (flash.error) {
            error(flash.error);
        }
        if (flash.info) {
            info(flash.info);
        }
    },
    { deep: true },
);
</script>

<template>
    <Teleport to="body">
        <div
            aria-live="assertive"
            class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none p-2 sm:p-0"
        >
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-3 p-4 rounded-sm border shadow-xl bg-brand-surface text-brand-charcoal overflow-hidden transition-all"
                    :class="[
                        toast.type === 'success' ? 'border-brand-gold/60' : '',
                        toast.type === 'error' ? 'border-rose-400 bg-rose-50/90' : '',
                        toast.type === 'info' ? 'border-brand-border' : '',
                    ]"
                >
                    <!-- Icon -->
                    <div class="shrink-0 mt-0.5">
                        <!-- Success Check -->
                        <svg
                            v-if="toast.type === 'success'"
                            class="w-5 h-5 text-brand-gold"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                        <!-- Error Alert -->
                        <svg
                            v-else-if="toast.type === 'error'"
                            class="w-5 h-5 text-rose-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>

                        <!-- Info -->
                        <svg
                            v-else
                            class="w-5 h-5 text-brand-muted"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>

                    <!-- Message -->
                    <div class="flex-1 text-xs md:text-sm font-sans font-medium text-brand-charcoal leading-snug">
                        {{ toast.message }}
                    </div>

                    <!-- Close button -->
                    <button
                        type="button"
                        class="shrink-0 text-brand-muted hover:text-brand-charcoal transition-colors p-0.5 rounded-sm"
                        aria-label="Dismiss toast"
                        @click="remove(toast.id)"
                    >
                        <svg
                            class="w-4 h-4"
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
            </TransitionGroup>
        </div>
    </Teleport>
</template>
