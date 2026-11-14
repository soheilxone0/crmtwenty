<div class="ct-card">
    <div class="ct-card-head">
        <div class="flex items-center gap-2.5">
            <div class="shimmer h-8 w-8 !rounded-[10px]"></div>

            <div class="shimmer h-4 w-32"></div>
        </div>
    </div>

    <div class="mx-auto my-2 h-[180px] w-[180px] animate-pulse rounded-full border-[22px] border-gray-100 dark:border-gray-800"></div>

    <div class="flex flex-col gap-3">
        @for ($i = 0; $i < 3; $i++)
            <div class="flex justify-between">
                <div class="shimmer h-3.5 w-28"></div>

                <div class="shimmer h-3.5 w-16"></div>
            </div>
        @endfor
    </div>
</div>
