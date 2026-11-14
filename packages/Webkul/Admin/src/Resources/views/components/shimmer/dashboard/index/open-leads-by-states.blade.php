<div class="ct-card">
    <div class="ct-card-head">
        <div class="flex items-center gap-2.5">
            <div class="shimmer h-8 w-8 !rounded-[10px]"></div>

            <div class="shimmer h-4 w-32"></div>
        </div>
    </div>

    <div class="ct-bars">
        @foreach ([90, 70, 50, 35, 20] as $width)
            <div class="ct-bar-row">
                <div class="flex justify-between">
                    <div class="shimmer h-3.5 w-24"></div>

                    <div class="shimmer h-3.5 w-12"></div>
                </div>

                <div
                    class="shimmer h-2 !rounded-full"
                    style="width: {{ $width }}%"
                ></div>
            </div>
        @endforeach
    </div>
</div>
