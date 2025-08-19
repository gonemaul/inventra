import { ref, onMounted } from "vue";

export function useDarkMode() {
    const isDark = ref(false);

    // Load dari localStorage saat mount
    onMounted(() => {
        const saved = localStorage.getItem("theme");
        if (saved) {
            isDark.value = saved === "dark";
            document.documentElement.classList.toggle("dark", isDark.value);
        }
    });

    const toggleDark = () => {
        isDark.value = !isDark.value;
        localStorage.setItem("theme", isDark.value ? "dark" : "light");
        document.documentElement.classList.toggle("dark", isDark.value);
    };

    return { isDark, toggleDark };
}
