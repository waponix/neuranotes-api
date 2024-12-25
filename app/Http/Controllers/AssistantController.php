<?php
namespace App\Http\Controllers;

use App\Http\Resource\AssistantResource;
use Illuminate\Http\Request;

final class AssistantController extends Controller
{
    public function generalNotesQuery(
        Request $request,
        AssistantResource $assistantResource,
    )
    {
        return $assistantResource
            ->handleGeneralNotesQueryRequest($request);
    }
}