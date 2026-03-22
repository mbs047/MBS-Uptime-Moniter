<?php

namespace App\Http\Controllers\PublicStatus;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\View\View;

class IncidentPageController extends Controller
{
    public function __invoke(Incident $incident): View
    {
        abort_unless($incident->published_at, 404);

        $incident->load([
            'services:id,name,slug',
            'components:id,service_id,display_name',
            'updates' => fn ($query) => $query->whereNotNull('published_at')->orderBy('created_at'),
        ]);

        return view('status.incident', [
            'incident' => $incident,
        ]);
    }
}
