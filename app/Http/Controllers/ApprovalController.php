<?php

namespace App\Http\Controllers;

use App\Models\ContentPlanVersion;
use App\Models\Post;

class ApprovalController extends Controller
{
    public function index()
    {
        $posts = Post::with(['brand', 'creator', 'attachments'])->where('status', 'pending')->orderBy('publish_date')->get();
        $versions = ContentPlanVersion::with(['plan.brand', 'uploader', 'attachment'])->where('status', 'pending')->latest()->get();
        $clientChanges = Post::with('brand')->where('status', 'client_changes')->orderBy('publish_date')->get();
        $planChanges = ContentPlanVersion::with('plan.brand')->where('status', 'client_changes')->latest()->get();
        $awaitingClient = Post::with('brand')->where('status', 'sent')->orderBy('publish_date')->get();

        return view('approvals.index', compact('posts', 'versions', 'clientChanges', 'planChanges', 'awaitingClient'));
    }
}
