@php
    use Carbon\Carbon;
@endphp

@if(isset($doctor))
<div class="flex flex-row shadow-md border border-color rounded-md gap-4 overflow-hidden mb-4 max-w-140">
    <div class="w-32 flex-shrink-0 min-h-60">
        <img 
            src="{{ asset('storage/' . optional($doctor->profile)->profile_picture ?? 'default-doctor.jpg') }}" 
            alt="{{ optional($doctor->profile)->first_name ?? 'Doctor' }}" 
            class="w-full h-full object-cover" 
        />
    </div>
    <div class="flex flex-col min-w-64 justify-between w-full p-4">
        <div class="flex flex-col gap-2">
            <h5>{{ ($doctor->profile->first_name ?? '') . ' ' . ($doctor->profile->last_name ?? '') }}</h5>
            <p>{{ $doctor->specialty->name ?? '-' }}</p>
            <div>
                @php
                    $days = $doctor->availabilities->pluck('day_of_week')->unique()->values()->map(function($d){ return ucfirst($d); });
                    $hmos = $doctor->hmos->pluck('name')->unique()->values();
                    $timeRange = '';
                    if ($doctor->availabilities->isNotEmpty()) {
                        $start = $doctor->availabilities->min('start_time');
                        $end = $doctor->availabilities->max('end_time');
                        try {
                            $timeRange = Carbon::parse($start)->format('g:i A') . ' - ' . Carbon::parse($end)->format('g:i A');
                        } catch (Exception $e) {
                            $timeRange = $start . ' - ' . $end;
                        }
                    }
                @endphp
                <p>{{ $days->join(', ') ?: 'No schedule' }}</p>
                <p>{{ $timeRange ?: 'No schedule' }}</p>
                <p class="text-sm text-muted">Accepted HMOs: {{ $hmos->isNotEmpty() ? $hmos->join(', ') : 'None' }}</p>
            </div>
        </div>
        <div>
            <a
                href="/appointments?doctor_id={{ $doctor->id }}"
                class="p-button p-component w-full flex items-center justify-center gap-2 mt-5"
            >
                <i class="pi pi-calendar"></i>
                <span>Book Appointment</span>
            </a>

            <br>

            <a
                href="/doctors/{{ $doctor->id }}"
                class="p-button p-button-link p-component w-full"
            >
                View More Details
            </a>
        </div>
    </div>
</div>
@endif