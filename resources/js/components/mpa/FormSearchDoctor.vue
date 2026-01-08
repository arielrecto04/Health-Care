<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import Button from "primevue/button";
import { Form, FormField } from "@primevue/forms";
import FloatLabel from "primevue/floatlabel";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Checkbox from "primevue/checkbox";
import CheckboxGroup from "primevue/checkboxgroup";
import RadioButton from "primevue/radiobutton";
import RadioButtonGroup from "primevue/radiobuttongroup";

const specialties = ref([]);
const selectedSpecialty = ref(null);

const hmos = ref([]);
const selectedHmo = ref(null);

const fetchSpecialties = async () => {
    try {
        const specialtiesResponse = await axios.get("/doctor/specialties");
        specialties.value = specialtiesResponse.data;
        
        const hmosResponse = await axios.get("/doctor/hmos");
        hmos.value = hmosResponse.data;
    } catch (err) {
        console.error("Failed to load data:", err);
    }
};

onMounted(() => {
    fetchSpecialties();
});

const selectedDays = ref([]);
const selectedTime = ref(null);

</script>
<template>
    <Form>
        <div class="flex flex-col gap-8">
            <div class="flex flex-col md:flex-row gap-4">
                <FormField class="flex-1">
                    <FloatLabel variant="on">
                        <InputText id="name" type="text" fluid />
                        <label for="name">Doctor's Name</label>
                    </FloatLabel>
                </FormField>
                <FormField class="flex-1">
                    <FloatLabel variant="on">
                        <Select 
                                id="specialty" 
                                :options="specialties" 
                                optionLabel="name" 
                                optionValue="id" 
                                v-model="selectedSpecialty" 
                                fluid 
                            />
                        <label for="specialty">Specialty</label>
                    </FloatLabel>
                </FormField>
            </div>
            <FormField>
                <FloatLabel variant="on">
                            <Select 
                                id="hmo"
                                :options="hmos"
                                optionLabel="name"
                                optionValue="id"
                                v-model="selectedHmo"
                                fluid
                            />
                    <label for="hmo">HMO</label>
                </FloatLabel>
            </FormField>
            <div class="flex flex-col lg:flex-row justify-between gap-8">
                <div class="flex flex-col gap-3">
                    <h6 class="text-base">Schedule</h6>
                    <CheckboxGroup  v-model="selectedDays" class="flex flex-wrap gap-10">
                          <div v-for="day in ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']" 
                            :key="day" class="flex items-center gap-2">
                            <Checkbox :inputId="day.toLowerCase()" :value="day" />
                            <label :for="day.toLowerCase()">{{ day }}</label>
                            </div>
                    </CheckboxGroup>
                </div>
                <div class="flex flex-col gap-3 shrink-0 mr-4">
                    <h6 class="text-base">Time</h6>
                    <RadioButtonGroup v-model="selectedTime" class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-2">
                            <RadioButton inputId="am" value="AM"/>
                            <label for="am">AM</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton inputId="pm" value="PM"/>
                            <label for="pm">PM</label>
                        </div>
                    </RadioButtonGroup>
                </div>
            </div>
            <div class="flex flex-row justify-end">
                <Button label="Search Doctor" />
            </div>
        </div>
    </Form>
</template>
