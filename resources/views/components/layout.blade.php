<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/mpa/app.js'])
</head>

<body>
    @if ($showHero)
    <x-header />
    <div class="flex md:h-[calc(100vh-90px)] mb-22">
        <section
            style="background-image: linear-gradient(rgba(6, 39, 35, 0.5),rgba(20, 184, 166, 0.5)), url('{{ asset('img/hero-section_bg.jpg') }}')"
            class="flex flex-col lg:flex-row flex-grow text-white p-12 md:p-24 bg-no-repeat items-center bg-position-[20%] bg-cover xl:bg-size-[150%] md:bg-left lg:bg-size-[140%]">
            <div
                class="h-full flex flex-col flex-1 gap-14 text-center md:text-left items-center md:items-start md:justify-center">
                <h1 class="font-bold">Smarter Healthcare <br> Starts Here</h1>
                <h6>Our healthcare management system helps clinics and hospitals streamline appointments, patient
                    records,
                    and billing in
                    one secure platform. By simplifying operations, we give providers more time to focus on what
                    truly
                    matters—delivering
                    quality care.
                </h6>
                <div class="flex flex-row gap-4">
                    <div class="flex flex-col justify-center">
                        <i class="pi pi-phone" style="font-size: var(--h4)"></i>
                    </div>
                    <div class="flex flex-col">
                        <h6 class="text-zinc-300">Emergency Line</h6>
                        <p class="h5">(+63) 123 456 789</p>
                    </div>
                </div>
                <div data-vue="Button" data-label="Book an Appointment" data-icon="pi pi-calendar"></div>
            </div>
            <div class="hidden flex-col md:flex md:w-[30%] lg:w-[15%] xl:w-[40%] 2xl:flex-1">
            </div>
        </section>
    </div>
    @else
    <x-header />
    @endif
    <main class="flex flex-col gap-40 p-2 sm:px-12 sm:py-8 lg:px-24 lg:py-12 2xl:px-48">
        {{ $slot }}
    </main>
    <x-footer />
</body>

</html>