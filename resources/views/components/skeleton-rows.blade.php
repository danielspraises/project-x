@props(['rows' => 5, 'columns' => 4])

<div class="space-y-3">
    @for ($i = 0; $i < $rows; $i++)
        <div class="flex gap-4">
            @for ($j = 0; $j < $columns; $j++)
                <div class="skeleton h-4 flex-1"></div>
            @endfor
        </div>
    @endfor
</div>
