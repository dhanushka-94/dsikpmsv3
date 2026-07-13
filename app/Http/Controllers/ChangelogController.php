<?php

namespace App\Http\Controllers;

use App\Services\SoftwareVersion;
use Illuminate\View\View;

class ChangelogController extends Controller
{
    public function __invoke(SoftwareVersion $softwareVersion): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('changelog.index', [
            'version' => $softwareVersion->current(),
            'releasedAt' => $softwareVersion->data()['released_at'],
            'releases' => $softwareVersion->releases(),
        ]);
    }
}
