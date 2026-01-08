@props(['title' => '', 'description' => '', 'services' => []])
<div class="flex flex-col shadow-md border border-color rounded-md px-4 py-8 gap-4 min-h-125">
    <div class="flex justify-center items-center size-16 bg-accent-color rounded-full border border-color">
        <i class="pi pi-shop text-white text-3xl!"></i>
    </div>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
            <h4>{{$title}}</h4>
            <p>{{$description}}</p>
        </div>
        <div class="flex flex-col gap-2">
            <h6 class="text-base">Services:</h6>
            <ul class="list-disc list-inside">
                @forelse($services as $service)
                    <li>{{$service->name}}</li>
                @empty
                    <li>No services available</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>