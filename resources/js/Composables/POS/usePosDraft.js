import { defineStore, storeToRefs } from "pinia";
import { watch } from "vue";
import { usePosState } from "./usePosState";

export const usePosDraft = defineStore("posDraft", () => {
    const store = usePosState();
    const { drafts, activeTabIndex } = storeToRefs(store);

    // Initial load from LocalStorage
    const initDrafts = () => {
        const savedDrafts = localStorage.getItem("pos_active_drafts");
        const savedIndex = localStorage.getItem("pos_active_index");

        if (savedDrafts) {
            try {
                const parsed = JSON.parse(savedDrafts);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    drafts.value = parsed;
                }
            } catch (e) {
                console.error("Failed to parse drafts from localstorage", e);
            }
        }

        if (savedIndex !== null) {
            const idx = parseInt(savedIndex);
            if (!isNaN(idx) && idx >= 0 && idx < drafts.value.length) {
                activeTabIndex.value = idx;
            }
        }
    };

    // Watch for changes and save to LocalStorage
    const startSync = () => {
        watch(
            [drafts, activeTabIndex],
            ([newDrafts, newIndex]) => {
                localStorage.setItem("pos_active_drafts", JSON.stringify(newDrafts));
                localStorage.setItem("pos_active_index", newIndex.toString());
            },
            { deep: true }
        );
    };

    return {
        initDrafts,
        startSync,
    };
});
