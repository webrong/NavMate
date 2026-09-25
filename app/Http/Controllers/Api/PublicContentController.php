<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\FriendLink;
use Illuminate\Http\JsonResponse;

class PublicContentController extends Controller
{
    public function friendLinks(): JsonResponse
    {
        return response()->json(
            FriendLink::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function ads(): JsonResponse
    {
        return response()->json(
            Ad::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'title', 'image_url', 'link_url', 'position', 'target'])
        );
    }
}
