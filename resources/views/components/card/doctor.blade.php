@props(['doctor'])
<div class="flex flex-col border border-color items-center bg-accent-color rounded-md overflow-hidden w-full max-w-80">
    <div class=" w-full h-64 shrink-0">
        <img src="https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_1280.png" alt="Doctor Picture"
            class="w-full h-full object-cover">
    </div>
    <div class="flex flex-col text-center text-white px-4 py-8 break-all gap-4">
        <h5 class="font-bold"> {{ $doctor->profile->first_name ?? '' }} {{ $doctor->profile->last_name ?? '' }}</h5>
        <p class="font-medium">{{ $doctor->doctor?->specialty?->name }}</p>
        <div class="flex flex-row items-center justify-between">
            <i class="pi pi-star-fill"></i>
            <i class="pi pi-star-fill"></i>
            <i class="pi pi-star-fill"></i>
            <i class="pi pi-star-fill"></i>
            <i class="pi pi-star-fill"></i>
        </div>
    </div>
</div>