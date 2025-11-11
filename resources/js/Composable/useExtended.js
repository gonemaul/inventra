import { ref } from "vue";

const isExtended = ref(false);
export function useExtended() {
    const toggleExtended = () => {
        isExtended.value = !isExtended.value;
        // console.log(isExtended);
        // console.log("Sidebar open?", isExtended.value);
    };

    const closeExtended = () => {
        isExtended.value = true;
    };

    return { isExtended, toggleExtended, closeExtended };
}
