// composables/useApiSound.js
import { ref, onMounted, onUnmounted } from 'vue';
import { useSound } from "@vueuse/sound";
import { apiEvents } from '@/utils/api';
import buttonSfx from "@/assets/sounds/notification.mp3";

export function useApiSound() {
    const isEnabled = ref(true);
    const { play, stop } = useSound(buttonSfx);

    const handleApiSuccess = (event) => {
        // Only play sound for status 200 success responses
        if (isEnabled.value && event.detail.response?.data?.status === 200) {
            play();
        }
    };

    const handleApiError = (event) => {
        // No sound for errors - you can add error sound here if needed
        // Example: play a different error sound
        console.log('API Error - no sound played:', event.detail.message);
    };

    const toggleSound = () => {
        isEnabled.value = !isEnabled.value;
    };

    const enableSound = () => {
        isEnabled.value = true;
    };

    const disableSound = () => {
        isEnabled.value = false;
    };

    onMounted(() => {
        // Listen to API events - sound only plays for success (status 200)
        apiEvents.on('api-success', handleApiSuccess);
        apiEvents.on('api-error', handleApiError);
    });

    onUnmounted(() => {
        // Clean up event listeners
        apiEvents.off('api-success', handleApiSuccess);
        apiEvents.off('api-error', handleApiError);
        stop();
    });

    return {
        isEnabled,
        toggleSound,
        enableSound,
        disableSound,
        playSuccess: () => isEnabled.value && play(), // Manual success sound
    };
}