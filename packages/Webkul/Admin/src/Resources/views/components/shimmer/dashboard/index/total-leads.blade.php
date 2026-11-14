<div class="ct-card">
    <div class="ct-card-head">
        <div class="flex items-center gap-2.5">
            <div class="shimmer h-8 w-8 !rounded-[10px]"></div>

            <div class="shimmer h-4 w-32"></div>
        </div>
    </div>

    <div class="flex h-[270px] items-end gap-3 px-2">
        @foreach ([45, 70, 35, 85, 55, 65, 40, 75, 50, 60, 30, 80] as $height)
            <div
                class="shimmer flex-1 !rounded-full"
                style="height: {{ $height }}%"
            ></div>
        @endforeach
    </div>
</div>
