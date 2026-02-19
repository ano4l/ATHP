<?php

namespace App\Http\Controllers;

use App\Models\CashRequisitionAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController extends Controller
{
    public function __invoke(CashRequisitionAttachment $attachment): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $canAccess = $user->isAdmin() || $attachment->requisition->requester_id === $user->id;
        abort_unless($canAccess, 403);

        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->file_name);
    }
}
