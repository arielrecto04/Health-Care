<x-layout title="Services">
    <x-section title="Our Services"
        description="Explore our comprehensive healthcare services designed to support your well-being. From routine consultations and preventive vaccinations to advanced diagnostics and personalized therapy, we provide care that’s reliable, compassionate, and tailored to your needs.">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">
            @foreach ($categories as $category)
                <div>
                    <x-card.service-2 
                        :title="$category->name"
                        :description="$category->description"
                        :services="$category->services">
                    </x-card.service-2>
                </div>
            @endforeach
        </div>
    </x-section>
</x-layout>