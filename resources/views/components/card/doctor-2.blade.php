@php
    use Carbon\Carbon;
@endphp

@if(isset($doctor))
<div class="flex flex-row shadow-md border border-color rounded-md gap-4 overflow-hidden mb-4 max-w-140">
    <div class="w-32 flex-shrink-0 min-h-60">
        <img src="{{ $doctor->profile->avatar ?? 'https://img.freepik.com/free-photo/beautiful-young-female-doctor-looking-camera-office_1301-7807.jpg?semt=ais_hybrid&w=740&q=80' }}"
            alt="{{ $doctor->profile->first_name ?? 'Doctor' }}" class="w-full h-full object-cover" />
    </div>
    <div class="flex flex-col min-w-64 justify-between w-full p-4">
        <div class="flex flex-col gap-2">
            <h5>{{ ($doctor->profile->first_name ?? '') . ' ' . ($doctor->profile->last_name ?? '') }}</h5>
            <p>{{ $doctor->specialty->name ?? '-' }}</p>
            <div>
                @php
                    // Capitalize day names for display and collect HMOs
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
            <a href="/appointments?doctor_id={{ $doctor->id }}" data-vue="Button" data-label="Book Appointment" class="btn btn-primary" onclick="window.location.href='/appointments?doctor_id={{ $doctor->id }}'">Book Appointment</a> <br>
            <a href="/doctor/{{ $doctor->id }}" data-vue="Button" data-label="View More Details" data-variant="link" class="btn btn-link" onclick="window.location.href='/doctor/{{ $doctor->id }}'">View More Details</a>
        </div>
    </div>
</div>
@endif