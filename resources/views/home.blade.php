<x-layout title="Home">
    <x-section title="Trusted by Thousands">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
            <div class="flex flex-col justify-center items-center text-center">
                <p class="h1 font-bold accent-color">50+</p>
                <h6>Certified Specialists</h6>
            </div>
            <div class="flex flex-col justify-center items-center text-center">
                <p class="h1 font-bold accent-color">25+</p>
                <h6>Certified Specialists</h6>
            </div>
            <div class="flex flex-col justify-center items-center text-center">
                <p class="h1 font-bold accent-color">20+</p>
                <h6>Certified Specialists</h6>
            </div>
            <div class="flex flex-col justify-center items-center text-center">
                <p class="h1 font-bold accent-color">24/7</p>
                <h6>Certified Specialists</h6>
            </div>
        </div>
    </x-section>
    <x-section title="Quality Care, Tailored for You">
        <div class="flex flex-col gap-20 items-center">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">
                @foreach ($service_categories as $category)
                    <div>
                        <x-card.service
                            :title="$category->name"
                            :description="$category->description"
                            icon="pi pi-building">
                        </x-card.service>
                    </div>
                @endforeach
            </div>
            <div data-vue="Button" data-label="Explore Our Services"></div>
        </div>
    </x-section>
    <x-section title="Meet Our Expert Doctors">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 lg:px-18 xl:px-38 gap-10">
            @foreach ($doctors as $doctor)
                <div class="flex flex-col justify-center items-center">
                    <x-card.doctor :doctor="$doctor" />
                </div>
            @endforeach
        </div>
    </x-section>
    <x-section>
        <div class="text-center flex flex-col gap-10 max-w-150 self-center">
            <div class="flex flex-col gap-6">
                <h4 class="font-medium">Find the Right Doctor for Your Needs</h4>
                <p>Browse through our team of experienced specialists and choose the right healthcare expert to
                    guide
                    your
                    journey to better health.</p>
            </div>
            <div data-vue="Button" data-label="See Available Doctors" data-icon="pi pi-arrow-right"
                data-icon-pos="right"></div>
        </div>
    </x-section>
    <x-section>
        <div class="flex flex-col lg:flex-row gap-10">
            <div class="flex flex-col flex-1 gap-8">
                <div class="flex flex-col gap-3">
                    <h3 class="font-bold">Why Choose Us</h3>
                    <p>Lorem ipsum dolor sit amet consectetur. Lobortis odio et odio nulla urna risus aliquam. Massa
                        egestas nulla congue a et
                        pellentesque. Viverra convallis diam eget iaculis. Sed ornare tellus semper tortor pretium nunc
                        duis curabitur.</p>
                </div>
                <div class="w-full h-full rounded-md overflow-hidden">
                    <img src="https://kanto.ph/wp-content/uploads/2023/10/JRSP-ARUP-HERO-01.png" alt=""
                        class="w-full h-full object-cover ">
                </div>
            </div>
            <div class="flex flex-col flex-1 gap-8">
                <x-card.feature title="State of the Art Facilities"
                    description="Lorem ipsum dolor sit amet consectetur. Egestas eget tincidunt habitasse et. Proin tincidunt pellentesque etiam velit et auctor mi aliquam suscipit.">
                </x-card.feature>
                <x-card.feature title="Expert Specialists"
                    description="Lorem ipsum dolor sit amet consectetur. Egestas eget tincidunt habitasse et. Proin tincidunt pellentesque etiam velit et auctor mi aliquam suscipit.">
                </x-card.feature>
                <x-card.feature title="Compassion Patient Care"
                    description="Lorem ipsum dolor sit amet consectetur. Egestas eget tincidunt habitasse et. Proin tincidunt pellentesque etiam velit et auctor mi aliquam suscipit.">
                </x-card.feature>
            </div>
        </div>
    </x-section>
    <x-section>
        <div class="flex flex-col lg:flex-row gap-10">
            <div class="flex-1 flex flex-col gap-6">
                <h3 class="font-bold">Your Journey to Better Health Starts Here</h3>
                <p>Lorem ipsum dolor sit amet consectetur. Ultrices id potenti neque aliquet. Pulvinar id bibendum a
                    mauris aenean sed.
                    Sapien pretium nunc ultrices hac dictum adipiscing id neque. Orci etiam turpis adipiscing nullam id
                    egestas morbi
                    egestas.</p>
                <div data-vue="Button" data-label="Reach Out to Us"></div>
            </div>
            <div class="flex-1">
                <div class="w-full h-full rounded-md overflow-hidden">
                    <img src="{{ asset('img/doctor.jpg') }}" alt="" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </x-section>
</x-layout>