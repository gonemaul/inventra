import { ref } from "vue";

const isSidebarOpen = ref(false);
export function useSidebar() {
    const toggleSidebar = () => {
        isSidebarOpen.value = !isSidebarOpen.value;
        // console.log(isSidebarOpen);
        console.log("Sidebar open?", isSidebarOpen.value);
    };

    const closeSidebar = () => {
        isSidebarOpen.value = false;
    };

    return { isSidebarOpen, toggleSidebar, closeSidebar };
}
