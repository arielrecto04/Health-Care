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
import CardDoctor from '@/components/CardDoctor.vue';

// Form fields
const name = ref('');
const specialties = ref([]);
const selectedSpecialty = ref(null);
const hmos = ref([]);
const selectedHmo = ref(null);
const selectedDays = ref([]);
const selectedTime = ref(null);

// Search results (Vue-style array)
const results = ref([]);

// Fetch specialties and HMOs on mount
const fetchSpecialties = async () => {
  try {
    const specialtiesRes = await axios.get("/doctors/specialties");
    specialties.value = specialtiesRes.data;

    const hmosRes = await axios.get("/doctors/hmos");
    hmos.value = hmosRes.data;
  } catch (err) {
    console.error("Failed to load specialties/HMOs:", err);
  }
};

onMounted(fetchSpecialties);

const search = async () => {
  try {
    const payload = {
      name: (name.value || '').trim() || null,
      specialty: selectedSpecialty.value,
      hmo: selectedHmo.value,
      days: selectedDays.value.map(d => d.toLowerCase()),
      time: selectedTime.value,
    };

    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const headers = tokenMeta ? { 'X-CSRF-TOKEN': tokenMeta.getAttribute('content') } : {};

    // Call JSON endpoint
    const res = await axios.post('/doctors/search-json', payload, { headers });

    // Make sure results.value is always an array
    results.value = res.data.doctors ?? [];

    console.log('Doctors results:', results.value); // <--- debug
  } catch (err) {
    console.error('Search failed:', err?.response?.data || err);
    results.value = [];
  }
};

</script>

<template>
  <Form>
    <div class="flex flex-col gap-8">
      <!-- Name & Specialty -->
      <div class="flex flex-col md:flex-row gap-4">
        <FormField class="flex-1">
          <FloatLabel variant="on">
            <InputText id="name" type="text" v-model="name" fluid />
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

      <!-- HMO -->
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

      <!-- Schedule & Time -->
      <div class="flex flex-col lg:flex-row justify-between gap-8">
        <div class="flex flex-col gap-3">
          <h6 class="text-base">Schedule</h6>
          <CheckboxGroup v-model="selectedDays" class="flex flex-wrap gap-10">
            <div 
              v-for="day in ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']" 
              :key="day" 
              class="flex items-center gap-2"
            >
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

      <!-- Search Button -->
      <div class="flex flex-row justify-end">
        <Button label="Search Doctor" type="button" @click="search" />
      </div>
    </div>
  </Form>

  <!-- Results -->
  <div class="mt-6 flex flex-col gap-4">
    <p v-if="results.length === 0" class="text-center text-muted">No doctors found</p>
      <div v-else class="grid gap-4">
        <CardDoctor
          v-for="doctor in results"
          :key="doctor.id"
          :id="doctor.id"
          :name="doctor.name"
          :specialty="doctor.specialty"
          :profile_picture="doctor.profile_picture"
          :hmos="doctor.hmos ?? []"
        />
      </div>
  </div>

</template>
