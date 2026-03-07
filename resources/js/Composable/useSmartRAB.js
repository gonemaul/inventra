import { ref } from 'vue';
import { usePremiumAlert } from './usePremiumAlert';

// Global state for the Smart RAB Modal
const isRabModalOpen = ref(false);
const rabModalData = ref(null);

export function useSmartRAB() {
    const toast = usePremiumAlert();
    const DRAFT_KEY = 'inventra_create_draft';
    const SUPPLIER_KEY = 'inventra_draft_supplier_id';

    /**
     * Get current draft from localStorage
     */
    const getRabDraft = () => {
        const draftStr = localStorage.getItem(DRAFT_KEY);
        if (!draftStr) return [];
        try {
            return JSON.parse(draftStr);
        } catch (e) {
            console.error('Error parsing RAB draft', e);
            return [];
        }
    };

    /**
     * Check if a product is already in the draft
     * @param {string|number} productId
     * @returns {object|null} The item from the draft if found, null otherwise
     */
    const checkInRab = (productId) => {
        const draft = getRabDraft();
        return draft.find(item => item.product_id == productId) || null;
    };

    /**
     * Open the Smart RAB Modal
     * @param {object} itemData - The product data to add
     * @param {string|number} supplierId - Optional supplier ID (e.g., from PurchaseDetail)
     * @param {number} defaultQty - Suggested default quantity (e.g., from previous PO)
     */
    const openRabModal = (itemData, supplierId = null, defaultQty = 1) => {
        // Find existing item in RAB to get current quantity
        const existingItem = checkInRab(itemData.product_snapshot ? itemData.product_snapshot.id : (itemData.product_id || itemData.id));

        rabModalData.value = {
            product: itemData,
            supplier_id: supplierId,
            default_qty: defaultQty,
            existing_qty: existingItem ? existingItem.quantity : 0,
        };
        isRabModalOpen.value = true;
    };

    /**
     * Close the Smart RAB Modal
     */
    const closeRabModal = () => {
        isRabModalOpen.value = false;
        setTimeout(() => {
            rabModalData.value = null;
        }, 300); // Wait for transition
    };

    /**
     * Final action: Save or Update item to LocalStorage draft
     */
    const saveToRab = (productData, quantity, supplierId = null, existingQty = 0) => {
        const draft = getRabDraft();

        // Normalize product data format to match usePurchaseCart expectations
        // Handle cases where the data comes from PurchaseItem (realisasi table) vs Catalog (product table)
        const isFromPO = !!productData.product_snapshot;
        const productId = isFromPO ? productData.product_id : productData.id;

        const normalizedItem = {
            id: isFromPO ? productData.product_snapshot.id : productData.id,
            product_id: productId,
            name: isFromPO ? productData.product_snapshot.name : productData.name,
            code: isFromPO ? productData.product_snapshot.code : productData.code,
            brand: isFromPO ? productData.product_snapshot.brand : (productData.brand?.name || productData.brand || "-"),
            category: isFromPO ? productData.product_snapshot.category : (productData.category?.name || productData.category || "-"),
            unit: isFromPO ? productData.product_snapshot.unit : (productData.unit?.name || productData.unit || "-"),
            size: isFromPO ? productData.product_snapshot.size : (productData.size?.name || productData.size || "-"),
            current_stock: isFromPO ? productData.product_snapshot.stock : (productData.stock || productData.current_stock || 0),
            min_stock: isFromPO ? productData.product_snapshot.min_stock : (productData.min_stock || 0),
            image_url: isFromPO ? productData.product_snapshot.image_url : (productData.image_url || productData.image_path || ""),
            image_path: isFromPO ? productData.product_snapshot.image_path : (productData.image_path || ""),
            quantity: quantity,
            purchase_price: isFromPO ? productData.purchase_price : (productData.purchase_price || 0),
            subtotal: quantity * (isFromPO ? productData.purchase_price : (productData.purchase_price || 0))
        };

        const index = draft.findIndex(item => item.product_id == productId);

        if (index !== -1) {
            // Update existung item
            // If they are adding "more" from the modal, we add to existing qty since the modal shows 'existing qty' context
            // actually, if the modal handles the final quantity value they want, we should just set it or add it.
            // Let's assume the modal passes the "total desired quantity" or we just overwrite with the new chosen quantity from the modal.
            // If the user picked 5, we set it to 5.
            if (existingQty > 0) {
                // It means the modal handled "add X to current" or "set to X".
                // In this implementation, the modal will calculate the final total quantity and pass it here.
                draft[index].quantity = quantity;
                draft[index].subtotal = quantity * draft[index].purchase_price;
            } else {
                draft[index].quantity += quantity;
                draft[index].subtotal = draft[index].quantity * draft[index].purchase_price;
            }
        } else {
            // New item
            draft.push(normalizedItem);
        }

        // Save back to localStorage
        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));

        // Save supplier if provided and not already set
        if (supplierId) {
            localStorage.setItem(SUPPLIER_KEY, supplierId.toString());
        }

        // Show Toast
        const addedQty = existingQty > 0 ? (quantity - existingQty) : quantity;
        const msgQty = addedQty > 0 ? `+${addedQty}` : `${addedQty}`;

        if (addedQty === 0) {
            toast.info(`${normalizedItem.name} sudah ada di RAB.`);
        } else {
            toast.custom(
                'success',
                'Berhasil Masuk RAB',
                `<span class="font-black text-lime-600 dark:text-lime-400">${msgQty}</span> ${normalizedItem.name}`
            );
        }

        closeRabModal();
    };

    return {
        isRabModalOpen,
        rabModalData,
        getRabDraft,
        checkInRab,
        openRabModal,
        closeRabModal,
        saveToRab
    };
}
