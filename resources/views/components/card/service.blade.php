@props(['title', 'description', 'icon'])
<div class="flex flex-col shadow-md border border-color rounded-md px-4 py-8 items-center gap-6 min-h-90">
    <div
        class="flex flex-col shrink-0 items-center justify-center border border-color rounded-full p-4 size-20 bg-accent-color">
        <i class="{{ $icon }} text-4xl! text-white"></i>
    </div>
    <div class="flex flex-col gap-6 text-center">
        <h4>{{ $title }}</h4>
        <p class="text-center">{{ $description }}</p>
    </div>
</div>