import { defineStore } from "pinia";
import { usePosState } from "./usePosState";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";
import axios from "axios";

export const usePosService = defineStore("posService", () => {
    const posState = usePosState();
    const toast = usePremiumAlert();

    const searchVehicle = async (query) => {
        if (!query) return null;
        
        try {
            const res = await axios.get(route('api.vehicles.info'), { 
                params: { plate_number: query } 
            });
            
            if (res.data.status === 'success') {
                return res.data.data.vehicle;
            } else {
                toast.warning("Kendaraan tidak ditemukan.");
                return null;
            }
        } catch (error) {
            console.error(error);
            toast.error("Gagal mencari data kendaraan");
            return null;
        }
    };

    const attachVehicleToDraft = (vehicle) => {
        if (posState.activeDraft) {
             posState.activeDraft.serviceData.vehicle = vehicle;
             posState.activeDraft.serviceData.current_km = null;
             toast.success(`Kendaraan ${vehicle.plate_number} berhasil dilampirkan.`);
        }
    };

    const detachVehicle = () => {
        if (posState.activeDraft) {
            posState.activeDraft.serviceData.vehicle = null;
            posState.activeDraft.serviceData.current_km = null;
        }
    };

    return {
        searchVehicle,
        attachVehicleToDraft,
        detachVehicle
    };
});
