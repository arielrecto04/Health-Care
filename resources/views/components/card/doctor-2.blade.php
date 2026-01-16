@php
use Carbon\Carbon;
@endphp

@if(isset($doctor))
<div class="flex flex-row shadow-md border border-color rounded-md gap-4 overflow-hidden mb-4 max-w-140">
    <div class="w-40 flex-shrink-0 min-h-60">
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
                    // Group availabilities by day
                    $availabilitiesByDay = $doctor->availabilities
                        ->groupBy('day_of_week')
                        ->sortBy(function($group, $day) {
                            $weekOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                            return array_search(strtolower($day), $weekOrder);
                        });

                    $hmos = $doctor->hmos->pluck('name')->unique()->values();
                @endphp

                @if($doctor->availabilities->isNotEmpty())
                    <div class="flex flex-col gap-1">
                        @foreach($availabilitiesByDay as $day => $availabilities)
                            <p class="font-semibold">{{ ucfirst($day) }}</p>
                            @foreach($availabilities as $availability)
                                @php
                                    try {
                                        $start = Carbon::parse($availability->start_time)->format('g:i A');
                                        $end = Carbon::parse($availability->end_time)->format('g:i A');
                                    } catch (Exception $e) {
                                        $start = $availability->start_time;
                                        $end = $availability->end_time;
                                    }
                                @endphp
                                <p>{{ $start }} - {{ $end }}</p>
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <p>No schedule</p>
                @endif

                <p class="text-sm text-muted">
                    Accepted HMOs: {{ $hmos->isNotEmpty() ? $hmos->join(', ') : 'None' }}
                </p>
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
