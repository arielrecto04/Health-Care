<script setup>
import { computed, ref, watch, onMounted } from "vue";
import { storeToRefs } from "pinia";

import Dialog from "primevue/dialog";
import { Form, FormField } from "@primevue/forms";
import FloatLabel from "primevue/floatlabel";
import Message from "primevue/message";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import SelectButton from "primevue/selectbutton";
import AutoComplete from "primevue/autocomplete";

import { useAuthStore } from "@/stores/auth";
import { useDoctorStore } from "@/stores/doctor";
import { useAppToast } from "@/utils/toast";

import Section from "@/components/Section.vue";

const toast = useAppToast();
const loading = ref(false);
const error = ref(null);

const doctorStore = useDoctorStore();
const { doctorSpecialties } = storeToRefs(doctorStore);
const { fetchDoctorSpecialties } = doctorStore;

const auth = useAuthStore();
const { user, role } = storeToRefs(auth);

const isEditingPersonal = ref(false);

const formDoctorPersonal = ref({
    first_name: "",
    middle_name: "",
    last_name: "",
    license_number: "",
    specialty: "",
    room_number: "",
    email: "",
    clinic_phone_number: "",
    doctor_note: "",
    profile_picture: null,
    profile_picture_preview: null,
});

async function editPersonal() {
    const profile = user?.value;
    const doctor = user.value?.doctor;

    formDoctorPersonal.value = {
        first_name: profile?.first_name,
        middle_name: profile?.middle_name,
        last_name: profile?.last_name,
        email: profile?.contact_email,
        license_number: doctor?.license_number,
        doctor_specialty_id: doctor?.specialty?.id,
        room_number: doctor?.room_number,
        clinic_phone_number: doctor?.clinic_phone_number,
        doctor_note: doctor?.doctor_note,
        profile_picture: null,
        profile_picture_preview: profile?.profile_picture_url || null,
    };
    isEditingPersonal.value = true;
}

async function cancelEditPersonal() {
    isEditingPersonal.value = false;
}

async function onUpdateDoctorPersonal() {
    try {
        const payload = formDoctorPersonal.value;
        const fd = new FormData();

        [
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'doctor_specialty_id',
            'license_number',
            'room_number',
            'clinic_phone_number',
            'doctor_note',
        ].forEach((key) => {
            if (payload[key] !== undefined && payload[key] !== null) {
                fd.append(key, payload[key]);
            }
        });

        if (payload.profile_picture instanceof File) {
            fd.append('profile_picture', payload.profile_picture);
        }

        // spoof method so Laravel accepts it as PUT
        fd.append('_method', 'PUT');

        await axios.post('/doctor/profile', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        await auth.fetchUser();
        toast.success('Profile updated successfully.');
        isEditingPersonal.value = false;

        // revoke preview URL if any
        if (payload.profile_picture_preview && payload.profile_picture instanceof File) {
            URL.revokeObjectURL(payload.profile_picture_preview);
        }
    } catch (err) {
        console.error(err);
        const serverMsg = err.response?.data?.message || err.response?.data?.error || null;

        if (err.response?.status === 422) {
            const first = Object.values(err.response.data.errors || {})[0]?.[0];
            toast.error(first || 'Validation failed.');
        } else {
            toast.error(serverMsg || 'Failed to update profile.');
        }
    }
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    // cleanup previous preview
    if (formDoctorPersonal.value.profile_picture_preview && formDoctorPersonal.value.profile_picture instanceof File) {
        URL.revokeObjectURL(formDoctorPersonal.value.profile_picture_preview);
    }
    formDoctorPersonal.value.profile_picture = file;
    formDoctorPersonal.value.profile_picture_preview = URL.createObjectURL(file);
}

const mySchedule = ref([]);

async function fetchSchedule() {
    if (auth.role !== "doctor") return;

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get("/doctor/schedule");
        mySchedule.value = response.data.data;

        if (mySchedule.value.length > 0) {
            schedules.value = transformSchedule(mySchedule.value);
        } else {
            schedules.value = [{ selectedDays: [], timeSlots: [""] }];
        }

        schedules.value.forEach((s) => watchSchedule(s));
    } catch (err) {
        error.value = "Failed to load schedule.";
        console.error(err);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (auth.role === "doctor") {
        fetchDoctorSpecialties();
    }
    fetchSchedule();
});

function getAvailability(day) {
    return mySchedule.value.filter((a) => a.day_of_week === day);
}

function transformSchedule(raw) {
    // Step 1: Build a map of day -> timeSlots
    const daySlotsMap = {};

    raw.forEach((item) => {
        const day = item.day_of_week;
        if (!daySlotsMap[day]) daySlotsMap[day] = [];
        if (!daySlotsMap[day].includes(item.start_time)) {
            daySlotsMap[day].push(item.start_time);
        }
    });

    // Step 2: Convert map to array of { day, slots } and sort slots
    const daysWithSlots = Object.entries(daySlotsMap).map(([day, slots]) => ({
        day,
        slots: slots.sort((a, b) => timeToMinutes(a) - timeToMinutes(b)),
    }));

    // Step 3: Group days with identical slots
    const grouped = [];
    const used = new Set();

    for (let i = 0; i < daysWithSlots.length; i++) {
        if (used.has(i)) continue;

        const current = daysWithSlots[i];
        const groupDays = [current.day];

        for (let j = i + 1; j < daysWithSlots.length; j++) {
            if (used.has(j)) continue;

            const compare = daysWithSlots[j];
            if (
                current.slots.length === compare.slots.length &&
                current.slots.every((slot, idx) => slot === compare.slots[idx])
            ) {
                groupDays.push(compare.day);
                used.add(j);
            }
        }

        grouped.push({
            selectedDays: groupDays.map((d) => ({
                name: d.charAt(0).toUpperCase() + d.slice(1),
            })),
            timeSlots: [...current.slots, ""], // extra empty slot for adding new time
        });
    }
    return grouped;
}

const editScheduleModal = ref(false);

const schedules = ref([
    {
        selectedDays: [],
        timeSlots: [""],
    },
]);

const days = ref([
    { name: "Monday" },
    { name: "Tuesday" },
    { name: "Wednesday" },
    { name: "Thursday" },
    { name: "Friday" },
    { name: "Saturday" },
    { name: "Sunday" },
]);

const slots = [
    "6:00 am",
    "6:30 am",
    "7:00 am",
    "7:30 am",
    "8:00 am",
    "8:30 am",
    "9:00 am",
    "9:30 am",
    "10:00 am",
    "10:30 am",
    "11:00 am",
    "11:30 am",
    "12:00 pm",
    "12:30 pm",
    "1:00 pm",
    "1:30 pm",
    "2:00 pm",
    "2:30 pm",
    "3:00 pm",
    "3:30 pm",
    "4:00 pm",
    "4:30 pm",
    "5:00 pm",
    "5:30 pm",
    "6:00 pm",
    "6:30 pm",
    "7:00 pm",
    "7:30 pm",
    "8:00 pm",
    "8:30 pm",
    "9:00 pm",
    "9:30 pm",
    "10:00 pm",
    "10:30 pm",
    "11:00 pm",
    "11:30 pm",
    "12:00 am",
    "12:30 am",
    "1:00 am",
    "1:30 am",
    "2:00 am",
    "2:30 am",
    "3:00 am",
    "3:30 am",
    "4:00 am",
    "4:30 am",
    "5:00 am",
    "5:30 am",
];

const filteredSlots = ref([]);

function searchSlots(event, currentSchedule, slotIndex) {
    const query = event.query.toLowerCase();

    const selectedSlots = schedules.value.flatMap((s, idx) =>
        s.timeSlots.filter(
            (slot, sidx) =>
                slot && (s !== currentSchedule || sidx !== slotIndex)
        )
    );

    filteredSlots.value = slots.filter(
        (s) => s.toLowerCase().includes(query) && !selectedSlots.includes(s)
    );
}

function timeToMinutes(timeStr) {
    const [time, modifier] = timeStr.split(" "); // "6:30", "AM"
    let [hours, minutes] = time.split(":").map(Number);

    if (modifier === "PM" && hours !== 12) hours += 12;
    if (modifier === "AM" && hours === 12) hours = 0;

    return hours * 60 + minutes;
}

function availableDays(currentSchedule) {
    const selectedDays = schedules.value
        .filter((s) => s !== currentSchedule)
        .flatMap((s) => s.selectedDays)
        .map((d) => d.name); // flatten and get names

    return days.value.filter((day) => !selectedDays.includes(day.name));
}

function sortSlots(schedule) {
    const nonEmptySlots = schedule.timeSlots.filter((slot) => slot);
    nonEmptySlots.sort((a, b) => timeToMinutes(a) - timeToMinutes(b));
    schedule.timeSlots = [...nonEmptySlots, ""];
}

function watchSchedule(schedule) {
    watch(
        () => schedule.timeSlots,
        (newVal) => {
            if (schedule.selectedDays.length === 0) return;

            const nonEmpty = schedule.timeSlots.filter((slot) => slot?.trim());
            if (schedule.timeSlots.length !== nonEmpty.length + 1) {
                schedule.timeSlots = [...nonEmpty, ""];
            }
        },
        { deep: true }
    );

    watch(
        () => schedule.selectedDays,
        (newVal) => {
            if (newVal.length === 0) schedule.timeSlots = [];
            else if (schedule.timeSlots.length === 0) schedule.timeSlots = [""];
        },
        { deep: true }
    );
}

schedules.value.forEach((s) => watchSchedule(s));

watch(
    schedules,
    (newVal) => {
        for (let i = newVal.length - 2; i >= 0; i--) {
            const schedule = newVal[i];
            if (
                schedule.selectedDays.length === 0 &&
                schedule.timeSlots.every((slot) => !slot)
            ) {
                newVal.splice(i, 1);
            }
        }

        const lastSchedule = newVal[newVal.length - 1];
        if (
            lastSchedule.selectedDays.length > 0 ||
            lastSchedule.timeSlots.some((slot) => slot)
        ) {
            newVal.push({ selectedDays: [], timeSlots: [""] });
            watchSchedule(newVal[newVal.length - 1]);
        }
    },
    { deep: true }
);

function canSelectDays(scheduleIndex) {
    for (let i = 0; i < scheduleIndex; i++) {
        const prev = schedules.value[i];
        if (
            prev.selectedDays.length > 0 &&
            prev.timeSlots.every((slot) => !slot)
        ) {
            return false;
        }
    }
    return true;
}

async function onSubmitSchedule() {
    const payload = schedules.value
        .map((schedule) => ({
            selectedDays: schedule.selectedDays.map((d) => d.name),
            timeSlots: schedule.timeSlots.filter((slot) => slot?.trim()),
        }))
        .filter((schedule) => schedule.timeSlots.length > 0);

    try {
        const response = await axios.post("/doctor/schedule", {
            schedules: payload,
        });

        await fetchSchedule();

        editScheduleModal.value = false;

        toast.success("Schedule updated successfully.");
    } catch (error) {
        toast.error("Failed to update schedule.");
    }
}
</script>

<style>
.select-days-btn {
    width: 100%;
    flex-wrap: wrap;
    overflow: hidden;

    button {
        width: 100%;
    }

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

@media screen and (min-width: 640px) {
    .select-days-btn {
        button {
            width: auto;
            flex-grow: 1;
        }
    }
}
</style>

<template>
    <Toast />
    <div v-if="role === 'doctor'">
        <div class="flex flex-col gap-4">
            <Section
                title="Personal Information"
                edit
                :footer="isEditingPersonal"
                @edit-btn="editPersonal"
                @cancel-btn="cancelEditPersonal"
                @save-btn="onUpdateDoctorPersonal"
            >
                <div class="flex flex-col gap-4">
                    <div
                        class="flex flex-col md:flex-row gap-4 md:items-start"
                    >
                        <div class="aspect-square w-full max-w-54 md:mt-6">
                            <img
                                :src="isEditingPersonal ? (formDoctorPersonal.profile_picture_preview || user.profile_picture_url || 'https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_1280.png') : (user.profile_picture_url || 'https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_1280.png')"
                                alt="Profile Picture"
                                class="w-full h-full border rounded-md border-color object-cover"
                            />
                            <div v-if="isEditingPersonal" class="mt-2">
                                <input
                                    id="profilePic"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="onFileChange"
                                />
                                <div v-if="isEditingPersonal" class="mt-2 flex justify-center">
                                    <label
                                        for="profilePic"
                                        class="cursor-pointer text-sm text-primary opacity-50 hover:opacity-100"
                                    >
                                        Change profile picture
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-grow gap-4">
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex flex-col flex-1">
                                    <h6>Name</h6>
                                    <div
                                        v-if="isEditingPersonal"
                                        class="flex flex-col lg:flex-row gap-6 lg:gap-2 mt-2"
                                    >
                                        <FormField class="w-full">
                                            <FloatLabel variant="on">
                                                <InputText
                                                    v-model="
                                                        formDoctorPersonal.first_name
                                                    "
                                                    id="first_name"
                                                    fluid
                                                />
                                                <label for="first_name"
                                                    >First Name</label
                                                >
                                            </FloatLabel>
                                        </FormField>
                                        <FormField class="w-full">
                                            <FloatLabel variant="on">
                                                <InputText
                                                    v-model="
                                                        formDoctorPersonal.middle_name
                                                    "
                                                    id="middle_name"
                                                    fluid
                                                />
                                                <label for="middle_name"
                                                    >Middle Name</label
                                                >
                                            </FloatLabel>
                                        </FormField>
                                        <FormField class="w-full">
                                            <FloatLabel variant="on">
                                                <InputText
                                                    v-model="
                                                        formDoctorPersonal.last_name
                                                    "
                                                    id="last_name"
                                                    fluid
                                                />
                                                <label for="last_name"
                                                    >Last Name</label
                                                >
                                            </FloatLabel>
                                        </FormField>
                                    </div>
                                    <p v-else>
                                        {{ user.first_name }}
                                        {{ user.middle_name }}
                                        {{ user.last_name }}
                                    </p>
                                </div>
                                <div class="flex flex-col flex-1">
                                    <h6>License Number</h6>
                                    <template v-if="isEditingPersonal">
                                        <FormField
                                            class="mt-2 w-full xl:max-w-80"
                                        >
                                            <InputText
                                                fluid
                                                v-model="
                                                    formDoctorPersonal.license_number
                                                "
                                            />
                                        </FormField>
                                    </template>
                                    <p v-else>
                                        {{
                                            user.doctor?.license_number || "N/A"
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex flex-col flex-1">
                                    <h6>Specialty</h6>
                                    <template v-if="isEditingPersonal">
                                        <FormField
                                            class="mt-2 w-full xl:max-w-80"
                                        >
                                            <Select
                                                v-model="
                                                    formDoctorPersonal.doctor_specialty_id
                                                "
                                                :options="doctorSpecialties"
                                                optionLabel="label"
                                                optionValue="value"
                                                fluid
                                            />
                                        </FormField>
                                    </template>
                                    <p v-else>
                                        {{
                                            user.doctor.specialty?.name || "N/A"
                                        }}
                                    </p>
                                </div>
                                <div class="flex flex-col flex-1">
                                    <h6>Room</h6>
                                    <template v-if="isEditingPersonal">
                                        <FormField
                                            class="mt-2 w-full xl:max-w-80"
                                        >
                                            <InputText
                                                v-model="
                                                    formDoctorPersonal.room_number
                                                "
                                                fluid
                                            />
                                        </FormField>
                                    </template>
                                    <p v-else>
                                        {{ user.doctor?.room_number || "N/A" }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex flex-col flex-1">
                                    <h6>Email</h6>
                                    <template v-if="isEditingPersonal">
                                        <FormField
                                            class="mt-2 w-full xl:max-w-80"
                                        >
                                            <InputText
                                                v-model="
                                                    formDoctorPersonal.email
                                                "
                                                fluid
                                            />
                                        </FormField>
                                    </template>
                                    <p v-else>{{ user.email }}</p>
                                </div>
                                <div class="flex flex-col flex-1">
                                    <h6>Clinic Number</h6>
                                    <template v-if="isEditingPersonal">
                                        <FormField
                                            class="mt-2 w-full xl:max-w-80"
                                        >
                                            <InputText
                                                v-model="
                                                    formDoctorPersonal.clinic_phone_number
                                                "
                                                fluid
                                            />
                                        </FormField>
                                    </template>
                                    <p v-else>
                                        {{
                                            user.doctor?.clinic_phone_number ||
                                            "N/A"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-base!">Doctor Notes</h6>
                        <template v-if="isEditingPersonal">
                            <FormField class="mt-2">
                                <Textarea
                                    v-model="formDoctorPersonal.doctor_note"
                                    fluid
                                />
                            </FormField>
                        </template>
                        <p v-else>
                            {{ user.doctor?.doctor_note || "N/A" }}
                        </p>
                    </div>
                </div>
            </Section>
            <Section title="Schedule" edit @edit-btn="editScheduleModal = true">
                <div v-if="loading"><p>Loading schedule...</p></div>
                <div v-else class="flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex flex-col flex-1">
                            <h6>Monday</h6>
                            <template
                                v-if="getAvailability('monday').length > 0"
                            >
                                <p v-for="slot in getAvailability('monday')">
                                    {{ slot.start_time }}
                                </p>
                            </template>
                            <p
                                v-else
                                class="text-gray-500 dark:text-gray-400 small"
                            >
                                No schedule available.
                            </p>
                        </div>
                        <div class="flex flex-col flex-1">
                            <h6>Tuesday</h6>
                            <template
                                v-if="getAvailability('tuesday').length > 0"
                            >
                                <p v-for="slot in getAvailability('tuesday')">
                                    {{ slot.start_time }}
                                </p>
                            </template>
                            <p
                                v-else
                                class="text-gray-500 dark:text-gray-400 small"
                            >
                                No schedule available.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex flex-col flex-1">
                            <h6>Wednesday</h6>
                            <template
                                v-if="getAvailability('wednesday').length > 0"
                            >
                                <p v-for="slot in getAvailability('wednesday')">
                                    {{ slot.start_time }}
                                </p>
                            </template>
                            <p
                                v-else
                                class="text-gray-500 dark:text-gray-400 small"
                            >
                                No schedule available.
                            </p>
                        </div>
                        <div class="flex flex-col flex-1">
                            <h6>Thursday</h6>
                            <template
                                v-if="getAvailability('thursday').length > 0"
                            >
                                <p v-for="slot in getAvailability('thursday')">
                                    {{ slot.start_time }}
                                </p>
                            </template>
                            <p
                                v-else
                                class="text-gray-500 dark:text-gray-400 small"
                            >
                                No schedule available.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex flex-col flex-1">
                            <h6>Friday</h6>
                            <template
                                v-if="getAvailability('friday').length > 0"
                            >
                                <p v-for="slot in getAvailability('friday')">
                                    {{ slot.start_time }}
                                </p>
                            </template>
                            <p
                                v-else
                                class="text-gray-500 dark:text-gray-400 small"
                            >
                                No schedule available.
                            </p>
                        </div>
                        <div class="flex flex-col flex-1">
                            <h6>Saturday</h6>
                            <template
                                v-if="getAvailability('saturday').length > 0"
                            >
                                <p v-for="slot in getAvailability('saturday')">
                                    {{ slot.start_time }}
                                </p>
                            </template>
                            <p
                                v-else
                                class="text-gray-500 dark:text-gray-400 small"
                            >
                                No schedule available.
                            </p>
                        </div>
                    </div>
                    <div>
                        <h6>Sunday</h6>
                        <template v-if="getAvailability('sunday').length > 0">
                            <p v-for="slot in getAvailability('sunday')">
                                {{ slot.start_time }}
                            </p>
                        </template>
                        <p
                            v-else
                            class="text-gray-500 dark:text-gray-400 small"
                        >
                            No schedule available.
                        </p>
                    </div>
                </div>
            </Section>
        </div>

        <Dialog
            v-model:visible="editScheduleModal"
            modal
            header="Update Schedule"
            appendTo="self"
            :dismissableMask="true"
            :style="{ width: '30rem' }"
        >
            <Form
                :initialValues="{ schedules }"
                ref="editScheduleForm"
                @submit="onSubmitSchedule"
            >
                <div class="flex flex-col gap-8">
                    <div
                        v-for="(schedule, idx) in schedules"
                        :key="idx"
                        class="flex flex-col gap-4"
                    >
                        <div class="flex flex-col gap-2">
                            <p
                                v-if="
                                    idx > 0 &&
                                    schedule.selectedDays.length === 0 &&
                                    availableDays(schedule).length > 0
                                "
                                class="xsmall text-center"
                            >
                                Add more time slots by selecting another
                                schedule in an empty day selection
                            </p>
                            <FormField>
                                <SelectButton
                                    multiple
                                    optionLabel="name"
                                    aria-labelledby="multiple"
                                    :options="availableDays(schedule)"
                                    :disabled="!canSelectDays(idx)"
                                    v-model="schedule.selectedDays"
                                    class="select-days-btn rounded-md!"
                                />
                                <Message
                                    severity="error"
                                    size="small"
                                    variant="simple"
                                >
                                </Message>
                            </FormField>
                        </div>
                        <div
                            v-if="schedule.selectedDays.length > 0"
                            class="flex flex-col gap-2"
                        >
                            <div
                                v-for="(slot, sidx) in schedule.timeSlots"
                                :key="sidx"
                            >
                                <FormField>
                                    <AutoComplete
                                        v-model="schedule.timeSlots[sidx]"
                                        dropdown
                                        fluid
                                        :suggestions="filteredSlots"
                                        @complete="
                                            (e) =>
                                                searchSlots(e, schedule, sidx)
                                        "
                                        @blur="sortSlots(schedule)"
                                        placeholder="Select Time Slot"
                                    />
                                    <Message
                                        severity="error"
                                        size="small"
                                        variant="simple"
                                    >
                                    </Message>
                                </FormField>
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
                        @click="editScheduleModal = false"
                    ></Button>
                    <Button
                        type="submit"
                        label="Save"
                        @click="$refs.editScheduleForm.submit()"
                    />
                </div>
            </template>
        </Dialog>
    </div>
</template>
