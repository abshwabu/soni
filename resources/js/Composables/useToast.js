import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

export function useToast() {
    const add = ({ message, type = 'success', duration = 4000 }) => {
        const id = ++toastId;
        const toast = {
            id,
            message,
            type,
            duration,
        };

        toasts.value.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }

        return id;
    };

    const remove = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message, duration = 4000) => {
        return add({ message, type: 'success', duration });
    };

    const error = (message, duration = 5000) => {
        return add({ message, type: 'error', duration });
    };

    const info = (message, duration = 4000) => {
        return add({ message, type: 'info', duration });
    };

    return {
        toasts,
        add,
        remove,
        success,
        error,
        info,
    };
}
