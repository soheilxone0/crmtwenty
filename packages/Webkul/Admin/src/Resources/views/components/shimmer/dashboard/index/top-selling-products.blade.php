<div class="ct-card w-full">
    <div class="ct-card-head">
        <div class="flex items-center gap-2.5">
            <div class="shimmer h-8 w-8 !rounded-[10px]"></div>

            <div class="shimmer h-4 w-32"></div>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        @for ($i = 1; $i <= 5; $i++)
            <div class="flex items-center gap-3">
                <div class="shimmer h-9 w-9 !rounded-full"></div>

                <div class="flex flex-1 flex-col gap-2">
                    <div class="shimmer h-3.5 w-3/5"></div>

                    <div class="shimmer h-3 w-2/5"></div>
                </div>

                <div class="shimmer h-3.5 w-16"></div>
            </div>
        @endfor
    </div>
</div>
