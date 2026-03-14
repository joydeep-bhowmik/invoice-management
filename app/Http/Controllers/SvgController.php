<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SvgController extends Controller
{
    public function render(Request $request)
    {
        $encoded = $request->query('svg');

        if (! $encoded) {
            abort(400, 'Missing SVG');
        }

        $svg = base64_decode($encoded, true);

        if ($svg === false) {
            abort(422, 'Invalid base64');
        }

        $svg = ltrim($svg);

        if (
            ! str_contains($svg, '<svg') ||
            ! str_contains($svg, '</svg>')
        ) {
            abort(422, 'Invalid SVG');
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => 'inline; filename="print.svg"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
