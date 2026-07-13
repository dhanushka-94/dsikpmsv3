<?php

namespace App\Services;

use Illuminate\Support\Str;

class TemporaryPasswordService
{
    public function generate(int $length = 12): string
    {
        $upper = Str::upper(Str::random(2));
        $lower = Str::lower(Str::random(4));
        $digits = (string) random_int(1000, 9999);
        $special = collect(['!', '@', '#', '$', '%'])->random();

        return str_shuffle($upper.$lower.$digits.$special.Str::random(max(0, $length - 11)));
    }
}
