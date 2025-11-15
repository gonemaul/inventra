import { ref } from "vue";

// State ini HANYA untuk loading aksi (CRUD)
const isActionLoading = ref(false);

export function useActionLoading() {
    return {
        isActionLoading,
    };
}
