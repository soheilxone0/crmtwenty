<div class="ct-card">
    <div class="ct-card-head">
        <div class="flex items-center gap-2.5">
            <div class="shimmer h-8 w-8 !rounded-[10px]"></div>

            <div class="shimmer h-4 w-32"></div>
        </div>

        <div class="shimmer h-6 w-24 !rounded-full"></div>
    </div>

    <div class="ct-funnel">
        @foreach ([100, 78, 58, 40, 26] as $width)
            <div class="ct-funnel-row">
                <div class="shimmer h-3.5 w-24"></div>

                <div class="ct-funnel-lane">
                    <div
                        class="shimmer mx-auto h-10 !rounded-[10px]"
                        style="width: {{ $width }}%"
                    ></div>
                </div>

                <div class="shimmer h-3.5 w-16 justify-self-end"></div>
            </div>
        @endforeach
    </div>
</div>
