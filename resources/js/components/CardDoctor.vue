<script setup>
import { ref, onMounted, computed } from "vue";
import { storeToRefs } from "pinia";

import { useDoctorStore } from "@/stores/doctor";

const props = defineProps({
    id: {
        type: Number,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    specialty: {
        type: String,
        required: true,
    },
    profile_picture: { 
        type: String, 
        required: false, 
        default: '' 
    },
    hmos: {
        type: Array,
        default: () => []
    },
});

const doctorStore = useDoctorStore();
const { availabilities } = storeToRefs(doctorStore);
const { fetchAvailability } = doctorStore;

onMounted(() => fetchAvailability(props.id));

const availabilityByDay = computed(() => {
  const grouped = {};
  (availabilities.value[props.id] || []).forEach((slot) => {
    slot.days.forEach((day) => {
      if (!grouped[day]) grouped[day] = [];
      grouped[day].push({ start: slot.start_time, end: slot.end_time });
    });
  });
  return grouped;
});

</script>

<template>
    <div
        class="flex flex-col border border-color shadow-md rounded-md gap-6 p-4 max-w-sm"

    >
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <div class="flex flex-col">
                <div
                    class="max-w-50 sm:size-34 border border-color rounded-md overflow-hidden"
                >
                    <img 
                    :src="profile_picture || 'https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_1280.png'" 
                    alt="Doctor Picture" 
                    class="h-full w-full object-cover" 
                    />
                </div>
            </div>
            <div
                class="flex flex-col gap-4 text-center sm:text-left items-center sm:items-start"
            >
                <div class="flex flex-col gap-1">
                    <h6>{{ name }}</h6>
                    <p>{{ specialty }}</p>
                    <p v-if="hmos.length" class="text-sm text-muted">
                        HMOs: {{ hmos.join(', ') }}
                    </p>

                </div>
                <Button
                    class="justify-start!"
                    label="Message"
                    icon="pi pi-comment"
                    severity="secondary"
                    size="small"
                />
            </div>
        </div>
        <div class="flex flex-col p-4 justify-between h-full gap-6">
            <div v-if="Object.keys(availabilityByDay).length" class="flex flex-col gap-2">
                <div v-for="(slots, day) in availabilityByDay" :key="day" class="flex flex-col gap-1">
                <p class="font-semibold">{{ day }}</p>
                <p v-for="(slot, index) in slots" :key="index">
                    {{ slot.start.toUpperCase() }} - {{ slot.end.toUpperCase() }}
                </p>
                </div>
            </div>
            <div v-else>
                <p>No available schedule.</p>
            </div>
            <div class="flex flex-col gap-1">
                <Button label="Book Appointment" icon="pi pi-calendar" fluid />
                <Button
                    class="text-color small"
                    label="View More Details"
                    variant="link"
                    fluid
                />
            </div>
        </div>
    </div>
</template>
