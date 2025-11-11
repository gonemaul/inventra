import { ref } from "vue";
import { useExtended } from "./useExtended";

const { isExtended, closeExtended } = useExtended();
const isSidebarOpen = ref(false);
export function useSidebar() {
    const toggleSidebar = () => {
        isSidebarOpen.value = !isSidebarOpen.value;
        closeExtended();
        console.log("Sidebar open?", isExtended.value);
    };

    const closeSidebar = () => {
        isSidebarOpen.value = false;
    };

    return { isSidebarOpen, toggleSidebar, closeSidebar };
}
