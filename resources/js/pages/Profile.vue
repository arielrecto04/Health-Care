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
            schedules.value = [
            {
                selectedDays: [],
                timeRanges: [{ start_time: "", end_time: "" }],
            },
            ];
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
    const map = {};

    raw.forEach((item) => {
        const day = item.day_of_week;

        if (!map[day]) map[day] = [];
        map[day].push({
            start_time: formatTo24Hr(item.start_time),
            end_time: formatTo24Hr(item.end_time),
        });
    });

    const grouped = [];
    const used = new Set();
    const entries = Object.entries(map);

    for (let i = 0; i < entries.length; i++) {
        if (used.has(i)) continue;

        const [day, ranges] = entries[i];
        const days = [day];

        for (let j = i + 1; j < entries.length; j++) {
            if (used.has(j)) continue;
            const [, compareRanges] = entries[j];

            if (
                ranges.length === compareRanges.length &&
                ranges.every((r, idx) =>
                    r.start_time === compareRanges[idx].start_time &&
                    r.end_time === compareRanges[idx].end_time
                )
            ) {
                days.push(entries[j][0]);
                used.add(j);
            }
        }

        grouped.push({
            selectedDays: days.map(d => ({ name: d.charAt(0).toUpperCase() + d.slice(1) })),
            timeRanges: [...ranges], // do NOT add empty slot here
        });
    }

    return grouped;
}


const editScheduleModal = ref(false);

const schedules = ref([
  {
    selectedDays: [],
    timeRanges: [
      { start_time: "", end_time: "" }
    ],
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
  "06:00", "06:30", "07:00", "07:30", "08:00", "08:30", "09:00", "09:30",
  "10:00", "10:30", "11:00", "11:30", "12:00", "12:30", "13:00", "13:30",
  "14:00", "14:30", "15:00", "15:30", "16:00", "16:30", "17:00", "17:30",
  "18:00", "18:30", "19:00", "19:30", "20:00", "20:30", "21:00", "21:30",
  "22:00", "22:30", "23:00", "23:30", "00:00", "00:30", "01:00", "01:30",
  "02:00", "02:30", "03:00", "03:30", "04:00", "04:30", "05:00", "05:30"
];

const filteredSlots = ref([]);

function availableDays(currentSchedule) {
    const selectedDays = schedules.value
        .filter((s) => s !== currentSchedule)
        .flatMap((s) => s.selectedDays)
        .map((d) => d.name); // flatten and get names

    return days.value.filter((day) => !selectedDays.includes(day.name));
}

function watchSchedule(schedule) {
    watch(
        () => schedule.timeRanges,
        (ranges) => {
            // remove all fully empty rows except the last one
            for (let i = ranges.length - 2; i >= 0; i--) {
                if (!ranges[i].start_time && !ranges[i].end_time) {
                    ranges.splice(i, 1);
                }
            }

            const last = ranges[ranges.length - 1];
            // always ensure there is at least one empty row at the end
            if (last.start_time || last.end_time) {
                ranges.push({ start_time: "", end_time: "" });
            }
        },
        { deep: true, immediate: true }
    );
}

// Apply watcher to all schedules
schedules.value.forEach(s => watchSchedule(s));


watch(
    schedules,
    (newVal) => {
        for (let i = newVal.length - 2; i >= 0; i--) {
            const schedule = newVal[i];

            const hasRanges = schedule.timeRanges?.some(
                r => r.start_time || r.end_time
            );

            if (schedule.selectedDays.length === 0 && !hasRanges) {
                newVal.splice(i, 1);
            }
        }

        const last = newVal[newVal.length - 1];
        const lastHasRanges = last.timeRanges?.some(
            r => r.start_time || r.end_time
        );

        if (last.selectedDays.length > 0 || lastHasRanges) {
            newVal.push({
                selectedDays: [],
                timeRanges: [{ start_time: "", end_time: "" }],
            });
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
            prev.timeRanges.every(
                r => !r.start_time && !r.end_time
            )
        ) {
            return false;
        }
    }
    return true;
}

function formatTo24Hr(timeStr) {
    if (!timeStr) return "";
    const [time, modifier] = timeStr.split(" ");
    if (!modifier) return time; // already in 24hr (just in case)

    let [hours, minutes] = time.split(":").map(Number);
    if (modifier.toLowerCase() === "pm" && hours < 12) hours += 12;
    if (modifier.toLowerCase() === "am" && hours === 12) hours = 0;

    return `${hours.toString().padStart(2, "0")}:${minutes
        .toString()
        .padStart(2, "0")}`;
}

function isOverlappingWithAll(range, day, schedules) {
    if (!range.start_time || !range.end_time) return false;
    const dayName = day.toLowerCase();

    // collect all ranges for this day (saved + current)
    const allRanges = [];

    // include existing ranges from server
    if (existingRangesMap.value[dayName]) {
        allRanges.push(...existingRangesMap.value[dayName]);
    }

    // include current ranges in form
    schedules.forEach(schedule => {
        if (!schedule.selectedDays.some(d => d.name.toLowerCase() === dayName)) return;
        schedule.timeRanges.forEach(r => {
            if (r !== range && r.start_time && r.end_time) {
                allRanges.push({ start_time: r.start_time, end_time: r.end_time });
            }
        });
    });

    return allRanges.some(r => range.start_time < r.end_time && range.end_time > r.start_time);
}


function hasDuplicateRanges(schedules) {
    for (const s of schedules) {
        const seen = new Set();
        for (const r of s.timeRanges) {
            if (!r.start_time || !r.end_time) continue;
            const key = `${r.start_time}-${r.end_time}`;
            if (seen.has(key)) return true;
            seen.add(key);
        }
    }
    return false;
}

async function onSubmitSchedule() {

    if (hasDuplicateRanges(schedules.value)) {
        toast.error("You have duplicate time ranges in the same schedule.");
        return;
    }
    const cleanedSchedules = schedules.value
        .filter(
            s =>
                s.selectedDays.length &&
                s.timeRanges.some(r => r.start_time && r.end_time)
        )
        .map(s => ({
            selectedDays: s.selectedDays.map(d => ({ name: d.name })),
            timeRanges: s.timeRanges
                .filter(r => r.start_time && r.end_time)
                .map(r => ({
                    start_time: formatTo24Hr(r.start_time),
                    end_time: formatTo24Hr(r.end_time),
                })),
        }));

    try {
        await axios.post("/doctor/schedule", { schedules: cleanedSchedules });
        await fetchSchedule();
        editScheduleModal.value = false;
        toast.success("Schedule updated successfully.");
    } catch (err) {
        console.error(err.response?.data || err);
        const firstError =
            Object.values(err.response?.data?.errors || {})[0]?.[0];
        toast.error(firstError || "Failed to update schedule.");
    }
}

const existingRangesMap = computed(() => {
    const map = {};
    mySchedule.value.forEach(({ day_of_week, start_time, end_time }) => {
        const day = day_of_week.toLowerCase();
        if (!map[day]) map[day] = [];
        map[day].push({
            start_time: formatTo24Hr(start_time),
            end_time: formatTo24Hr(end_time),
        });
    });
    return map;
});

function getStartTimeOptions(range, day) {
    if (!day) return slots.map(s => ({ label: s, value: s }));

    return slots
        .filter(s => {
            if (s === range.start_time) return true;

            const end = range.end_time || slots[slots.indexOf(s) + 1];
            const tempRange = { start_time: s, end_time: end };
            return !isOverlappingWithAll(tempRange, day, schedules.value);
        })
        .map(s => ({ label: s, value: s }));
}

function getEndTimeOptions(range, day) {
    if (!range.start_time || !day) return slots.map(s => ({ label: s, value: s }));

    return slots
        .filter(s => {
            if (s === range.end_time) return true;

            const tempRange = { start_time: range.start_time, end_time: s };
            return s > range.start_time && !isOverlappingWithAll(tempRange, day, schedules.value);
        })
        .map(s => ({ label: s, value: s }));
}

async function onClearSchedule() {
    if (!confirm("This will remove all your schedules. Continue?")) return;

    try {
        await axios.post("/doctor/schedule", {
            schedules: [],
            clear: true,
        });

        // reset UI state
        schedules.value = [
            {
                selectedDays: [],
                timeRanges: [{ start_time: "", end_time: "" }],
            },
        ];

        await fetchSchedule();
        editScheduleModal.value = false;
        toast.success("Schedule cleared successfully.");
    } catch (err) {
        toast.error("Failed to clear schedule.");
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
                        <div class="aspect-square w-full max-w-54 md:mt-2">
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
                                    {{ slot.start_time }} – {{ slot.end_time }}
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
                                    {{ slot.start_time }} – {{ slot.end_time }}
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
                                    {{ slot.start_time }} – {{ slot.end_time }}
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
                                    {{ slot.start_time }} – {{ slot.end_time }}
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
                                    {{ slot.start_time }} – {{ slot.end_time }}
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
                                    {{ slot.start_time }} – {{ slot.end_time }}
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
                                {{ slot.start_time }} – {{ slot.end_time }}
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
                            <div v-for="(range, rIdx) in schedule.timeRanges" :key="rIdx" class="flex gap-2 justify-center items-center">
                                <FormField>
                                    <!-- Start Time -->
                                    <Select
                                        v-model="range.start_time"
                                        :options="getStartTimeOptions(range, schedule.selectedDays[0]?.name)"
                                        placeholder="Start time"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-45"
                                    />
                                </FormField>
                                <FormField>
                                    <!-- End Time -->
                                    <Select
                                        v-model="range.end_time"
                                        :options="getEndTimeOptions(range, schedule.selectedDays[0]?.name)"
                                        placeholder="End time"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-45"
                                    />

                                    <!-- Remove Range Button -->
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        v-if="schedule.timeRanges.length > 1"
                                        @click="schedule.timeRanges.splice(rIdx, 1)"
                                    />
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
                        label="Clear Schedule"
                        severity="danger"
                        outlined
                        @click="onClearSchedule"
                    />
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
