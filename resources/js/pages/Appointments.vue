<script setup>
import { ref, onMounted, computed, watch } from "vue";

import SelectButton from "primevue/selectbutton";
import Dialog from "primevue/dialog";
import Column from "primevue/column";
import TieredMenu from "primevue/tieredmenu";
import Skeleton from "primevue/skeleton";
import { Form, FormField } from "@primevue/forms";
import FloatLabel from "primevue/floatlabel";
import DatePicker from "primevue/datepicker";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import AutoComplete from "primevue/autocomplete";

import DataTableContainer from "@/components/datatable/Container.vue";
import PaginatedDatatable from "@/components/datatable/PaginatedDatatable.vue";

import { formatDate } from "@/utils/date";
import { useAppToast } from "@/utils/toast";
import { usePaginatedData } from "@/utils/datatable";

const toast = useAppToast();

const appointmentTypes = ref("upcoming");

const tabItems = [
    { label: "Upcoming", value: "upcoming" },
    { label: "Today", value: "today" },
    { label: "History", value: "history" },
];

const {
    data: appointments,
    total,
    rows,
    currentPage,
    loading,
    onPage,
    fetchData,
} = usePaginatedData("/appointments", 20);

const menu = ref();

const menuItems = ref([
    { label: "Edit", icon: "pi pi-pen-to-square" },
    { label: "Delete", icon: "pi pi-trash" },
]);

const toggleMenu = (event, i) => {
    menu.value[i].toggle(event);
};

const addAppointmentModal = ref(false);

const teleconsultation = ref(false);
const selectedAppointmentType = ref(null);
const selectedHmo = ref({ label: "None", value: 0 });
const hmoNumber = ref(null);
const insuranceNumber = ref(null);
const date = ref(null);
const timeSlots = ref([]);
const selectedTimeSlot = ref(null);

async function fetchAvailableTimeSlots() {
    if (!date.value || !selectedAppointmentType.value) {
        timeSlots.value = [];
        selectedTimeSlot.value = null;
        return;
    }

    try {
        const response = await axios.get("/available-time-slots", {
            params: {
                date: date.value.toISOString(),
                service_id: selectedAppointmentType.value.value,
                hmo_id: selectedHmo.value?.value || 0,
            },
        });

        timeSlots.value = response.data || [];
        selectedTimeSlot.value = null;
    } catch (err) {
        console.error("Error fetching available times:", err);
        timeSlots.value = [];
        selectedTimeSlot.value = null;
    }
}

watch([date, selectedAppointmentType, selectedHmo], fetchAvailableTimeSlots);

const yesNoOptions = [
    { label: "No", value: false },
    { label: "Yes", value: true },
];

const categories = ref([]);

async function fetchServices() {
    try {
        const response = await axios.get("/api/services");
        categories.value = response.data.data;
    } catch (err) {
        console.log(err);
    }
}

onMounted(fetchServices);

const appointmentTypesOptions = computed(() => {
    return categories.value.map((cat) => ({
        groupLabel: cat.name,
        items: cat.services.map((service) => ({
            label: service.name,
            value: service.id,
        })),
    }));
});

const filteredAppointmentTypes = computed(() => {
    if (teleconsultation.value === true) {
        return appointmentTypesOptions.value.filter((opt) =>
            ["General", "Therapy"].includes(opt.groupLabel)
        );
    }
    return appointmentTypesOptions.value;
});

const hmoOptions = ref([]);

async function fetchHmos() {
    try {
        const response = await axios.get("/api/hmos");

        const hmos = response.data.data.map((hmo) => ({
            label: hmo.name,
            value: Number(hmo.id), // <-- force number
        }));

        hmoOptions.value = [{ label: "None", value: 0 }, ...hmos];
    } catch (err) {
        console.log(err);
    }
}


onMounted(fetchHmos);

const haveInsurance = ref(false);

const insuranceOptions = ref([]);
const filteredInsuranceOptions = ref([]);
const selectedInsurance = ref(null);

async function fetchInsurances() {
    try {
        const res = await axios.get("/api/insurances", {
            params: { status: "accepted" },
        });

        insuranceOptions.value = res.data.data.map((i) => i.name);
    } catch (err) {
        console.log(err);
    }
}

onMounted(fetchInsurances);

function onInsuranceComplete(event) {
    const query = event.query.toLowerCase();
    filteredInsuranceOptions.value = insuranceOptions.value.filter((i) =>
        i.toLowerCase().includes(query)
    );
}

const errors = ref({});
// const insurances
async function onSubmit() {
    errors.value = {};
    try {
        const payload = {
            appointment_type: teleconsultation.value,
            service_id: selectedAppointmentType.value.value,
            hmo_id: selectedHmo.value,
            hmo_number: hmoNumber.value,
            insurance: selectedInsurance.value,
            insurance_number: insuranceNumber.value,
            date: date.value.toISOString(),
            time_start: selectedTimeSlot.value,
        };

        const res = await axios.post("/appointments", payload);
        toast.success("Appointment created successfully.");
        addAppointmentModal.value = false;
        fetchAvailableTimeSlots();
        fetchData(currentPage.value, rows.value);
    } catch (err) {
        console.log(err);
        console.error(err.response?.data || err);
    }
}
</script>

<style>
.select-time-btn {
    .p-togglebutton:first-child {
        border-inline-start-width: 0 !important;
        border-start-start-radius: 0 !important;
        border-end-start-radius: 0 !important;
    }

    .p-togglebutton:last-child {
        border-start-end-radius: 0 !important;
        border-end-end-radius: 0 !important;
    }
}

@media (min-width: 640px) {
    .appointment-tab.p-selectbutton-fluid {
        width: auto;
        .p-togglebutton {
            flex: 0 0 auto;
        }
    }
}
</style>

<template>
    <Toast />
    <section class="flex flex-col gap-8 h-full" id="appointments-section">
        <div class="flex flex-col sm:flex-row justify-between gap-6">
            <SelectButton
                v-model="appointmentTypes"
                :allowEmpty="false"
                :options="tabItems"
                optionLabel="label"
                optionValue="value"
                class="appointment-tab"
                fluid
            />
            <Button
                icon="pi pi-plus"
                label="Add Appointment"
                @click="addAppointmentModal = true"
            />
        </div>
        <DataTableContainer>
            <PaginatedDatatable
                :value="appointments"
                :totalRecords="total"
                :rows="rows"
                :page="onPage"
            >
                <Column field="dateTime" header="Date and Time">
                    <template #body="{ data }">
                        {{ data.start_date }}
                    </template>
                </Column>
                <Column field="doctor" header="Doctor">
                    <template #body="{ data }">
                        <template v-if="data.doctor && data.doctor.id">
                            Dr.
                            {{ data.doctor.first_name }}
                            {{ data.doctor.last_name }}
                        </template>
                        <template v-else>N/A</template>
                    </template>
                </Column>
                <Column field="appointmentType" header="Appointment Type">
                    <template #body="{ data }">
                        {{ data.service.name }}
                    </template>
                </Column>
                <Column field="room" header="Room">
                    <template #body="{ data }">
                        <p v-if="data.appointment_type === 'online'">Online</p>
                        <p v-else-if="data.appointment_type === 'walk-in'">
                            {{ data.doctor?.room_number || "Laboratory" }}
                        </p>
                        <p v-else>---</p>
                    </template>
                </Column>
                <Column class="w-30">
                    <template #header>
                        <div
                            class="flex-1 text-center p-datatable-column-title"
                        >
                            Action
                        </div>
                    </template>
                    <template #body="{ data, index }">
                        <div class="text-center">
                            <Button
                                icon="pi pi-ellipsis-v"
                                aria-label="More"
                                severity="secondary"
                                variant="text"
                                aria-haspopup="true"
                                aria-controls="more"
                                @click="(e) => toggleMenu(e, index)"
                            />
                            <TieredMenu
                                v-for="(appointment, i) in appointments"
                                :key="i"
                                ref="menu"
                                id="more"
                                :model="menuItems"
                                popup
                                appendTo="#appointments-section"
                            />
                        </div>
                    </template>
                </Column>
            </PaginatedDatatable>
        </DataTableContainer>
    </section>
    <Dialog
        v-model:visible="addAppointmentModal"
        modal
        header="Schedule Appointment"
        appendTo="self"
        :dismissableMask="true"
        class="w-[96%] md:w-[80%] xl:w-[50%]"
    >
        <p class="mb-8 small">
            Prefer a specific doctor?
            <span>
                <RouterLink :to="{ name: 'doctor-appointment' }">
                    <Button
                        label="Book here"
                        as="a"
                        variant="link"
                        type="button"
                        class="p-0!"
                        style="font-size: var(--small)"
                    />
                </RouterLink>
            </span>
        </p>
        <Form ref="addAppointmentForm" @submit="onSubmit">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-6">
                    <FormField>
                        <FloatLabel variant="on">
                            <Select
                                inputId="teleconsultation"
                                v-model="teleconsultation"
                                optionLabel="label"
                                optionValue="value"
                                :options="yesNoOptions"
                                fluid
                            />
                            <label for="teleconsultation"
                                >Teleconsultation? (Consult with doctor
                                online.)</label
                            >
                        </FloatLabel>
                    </FormField>
                    <FormField>
                        <FloatLabel variant="on">
                            <Select
                                inputId="appointmentType"
                                v-model="selectedAppointmentType"
                                :options="filteredAppointmentTypes"
                                optionLabel="label"
                                optionGroupLabel="groupLabel"
                                optionGroupChildren="items"
                                :invalid="errors.appointmentType"
                                name="service_id"
                                fluid
                            >
                                <template #optiongroup="slotProps">
                                    <div>
                                        <h6 class="small">
                                            {{ slotProps.option.groupLabel }}
                                        </h6>
                                    </div>
                                </template>
                            </Select>
                            <label for="appointmentType"
                                >Appointment Type</label
                            >
                        </FloatLabel>
                        <!-- <Message
                            v-if="errors.last_name"
                            severity="error"
                            size="small"
                            variant="simple"
                            >{{ errors.last_name[0] }}</Message
                        > -->
                    </FormField>
                    <FormField>
                        <FloatLabel variant="on">
                            <Select
                                inputId="hmo"
                                v-model="selectedHmo"
                                :options="hmoOptions"
                                optionLabel="label"
                                name="patient_hmo_id"
                                fluid
                                appendTo="body"
                            />
                            <label for="hmo">HMO</label>
                        </FloatLabel>

                    </FormField>
                    <FormField v-if="selectedHmo != 0">
                        <FloatLabel variant="on">
                            <InputText
                                id="hmoInputNumber"
                                v-model="hmoNumber"
                                fluid
                            />
                            <label for="hmoInputNumber">HMO Number</label>
                        </FloatLabel>
                    </FormField>
                    <FormField>
                        <FloatLabel variant="on">
                            <Select
                                inputId="haveInsurance"
                                v-model="haveInsurance"
                                :options="yesNoOptions"
                                optionLabel="label"
                                optionValue="value"
                                fluid
                            />
                            <label for="haveInsurance"
                                >Do you have insurance?</label
                            >
                        </FloatLabel>
                    </FormField>
                    <template v-if="haveInsurance">
                        <FormField>
                            <FloatLabel variant="on">
                                <AutoComplete
                                    v-model="selectedInsurance"
                                    inputId="insurance"
                                    dropdown
                                    :suggestions="filteredInsuranceOptions"
                                    @complete="onInsuranceComplete"
                                    fluid
                                />
                                <label for="insurance"
                                    >Insurance Company Name</label
                                >
                            </FloatLabel>
                        </FormField>
                        <FormField>
                            <FloatLabel variant="on">
                                <InputText
                                    id="insuranceNumber"
                                    v-model="insuranceNumber"
                                    fluid
                                />
                                <label for="insuranceNumber"
                                    >Insurance Policy/ID Number</label
                                >
                            </FloatLabel>
                        </FormField>
                    </template>
                </div>
                <div class="flex flex-col gap-4">
                    <h6 class="text-base!">Date Selection</h6>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <DatePicker
                                v-model="date"
                                inline
                                :minDate="new Date()"
                                class="w-full"
                            />
                        </div>
                        <div class="flex flex-col flex-1 gap-4">
                            <FormField>
                                <label for="date">Date</label>
                                <InputText
                                    id="date"
                                    :value="formatDate(date)"
                                    disabled
                                    fluid
                                />
                            </FormField>
                            <div class="flex flex-col gap-2">
                                <p>Available Time</p>
                                <SelectButton
                                    v-if="timeSlots.length > 0"
                                    v-model="selectedTimeSlot"
                                    :options="timeSlots"
                                    class="select-time-btn grid! grid-cols-2 md:grid-cols-3 overflow-hidden p-2"
                                    name="time_slot"
                                />
                                <p v-else class="text-gray-500">
                                    No available slots
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Form>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    @click="addAppointmentModal = false"
                />
                <Button
                    type="button"
                    label="Book Appointment"
                    @click="$refs.addAppointmentForm.submit()"
                />
            </div>
        </template>
    </Dialog>
</template>
