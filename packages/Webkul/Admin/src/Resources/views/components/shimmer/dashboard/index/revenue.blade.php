<div class="ct-card">
    <div class="ct-card-head">
        <div class="flex items-center gap-2.5">
            <div class="shimmer h-8 w-8 !rounded-[10px]"></div>

            <div class="shimmer h-4 w-32"></div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
        @for ($i = 0; $i < 2; $i++)
            <div class="ct-tile">
                <div class="shimmer h-3.5 w-24"></div>

                <div class="shimmer h-7 w-40"></div>

                <div class="shimmer h-5 w-16 !rounded-full"></div>
            </div>
        @endfor
    </div>

    <div class="shimmer h-3 w-full !rounded-full"></div>
</div>
