<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachmentStoreRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Upload satu atau multiple attachment ke suatu incident.
     */
    public function store(AttachmentStoreRequest $request, Incident $incident)
    {
        $user = $request->user();

        // Pastikan user punya akses ke incident ini
        if (!$this->userCanAccessIncident($user, $incident)) {
            abort(403, 'Anda tidak memiliki akses ke insiden ini.');
        }

        $validated = $request->validated();
        $files = $validated['files'];
        $descriptions = $validated['descriptions'] ?? [];
        $uploaded = [];

        DB::transaction(function () use ($user, $incident, $files, $descriptions, &$uploaded, $request) {
            foreach ($files as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                $relativePath = "uploads/incidents/{$incident->incident_id}/{$fileName}";

                // Simpan ke storage/app/public/uploads/incidents/{id}/
                $file->storeAs(
                    "uploads/incidents/{$incident->incident_id}",
                    $fileName,
                    'public'
                );

                $description = $descriptions[$index] ?? null;

                $source = $validated['source'] ?? null;

                $attachment = Attachment::create([
                    'incident_id' => $incident->incident_id,
                    'uploaded_by' => $user->id,
                    'file_name' => $originalName,
                    'file_path' => $relativePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'description' => $description,
                    'source' => $source,
                    'uploaded_at' => now(),
                ]);

                // Override audit detail to include source info
                $fileDetail = $originalName . ($source ? " ({$source})" : '');

                $uploaded[] = $attachment;
            }

            // Audit log
            $sourceLabel = $validated['source'] ?? 'unknown';
            $fileNames = implode(', ', array_map(fn($a) => $a->file_name . " ({$sourceLabel})", $uploaded));
            AuditLog::create([
                'incident_id' => $incident->incident_id,
                'user_id' => $user->id,
                'action' => 'upload_attachment',
                'action_details' => "Uploaded file(s) [source: {$sourceLabel}]: {$fileNames}",
                'old_value' => null,
                'new_value' => json_encode([
                    'attachment_ids' => array_map(fn($a) => $a->attachment_id, $uploaded),
                    'file_names' => array_map(fn($a) => $a->file_name, $uploaded),
                ]),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            // Notifikasi ke engineer/supervisor jika uploader adalah operator
            if ($user->role === 'operator') {
                $this->notifyRelevantUsers($incident, $user, $fileNames);
            }
        });

        // Load relasi untuk response
        $incident->load('attachments.uploader');

        return response()->json([
            'message' => count($uploaded) . ' file(s) berhasil diupload.',
            'attachments' => AttachmentResource::collection($incident->attachments),
        ], 201);
    }

    /**
     * Hapus attachment.
     */
    public function destroy(Request $request, Incident $incident, Attachment $attachment)
    {
        $user = $request->user();

        // Pastikan attachment milik incident yang benar
        if ($attachment->incident_id !== $incident->incident_id) {
            abort(404, 'Attachment tidak ditemukan.');
        }

        // Pastikan user punya akses
        if (!$this->userCanAccessIncident($user, $incident)) {
            abort(403, 'Anda tidak memiliki akses ke insiden ini.');
        }

        // Hanya uploader, supervisor, atau assigned engineer yang bisa hapus
        if ($attachment->uploaded_by !== $user->id && $user->role !== 'supervisor' && $incident->handled_by !== $user->id) {
            abort(403, 'Anda tidak berwenang menghapus attachment ini.');
        }

        DB::transaction(function () use ($incident, $attachment, $user, $request) {
            // Hapus file dari storage
            Storage::disk('public')->delete($attachment->file_path);

            // Hapus record dari database
            $attachment->delete();

            // Audit log
            AuditLog::create([
                'incident_id' => $incident->incident_id,
                'user_id' => $user->id,
                'action' => 'delete_attachment',
                'action_details' => "Deleted file: {$attachment->file_name}",
                'old_value' => json_encode([
                    'attachment_id' => $attachment->attachment_id,
                    'file_name' => $attachment->file_name,
                    'file_path' => $attachment->file_path,
                ]),
                'new_value' => null,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'File berhasil dihapus.',
        ]);
    }

    /**
     * Download / view attachment.
     */
    public function download(Request $request, Incident $incident, Attachment $attachment)
    {
        $user = $request->user();

        // Pastikan attachment milik incident yang benar
        if ($attachment->incident_id !== $incident->incident_id) {
            abort(404, 'Attachment tidak ditemukan.');
        }

        // Pastikan user punya akses
        if (!$this->userCanAccessIncident($user, $incident)) {
            abort(403, 'Anda tidak memiliki akses ke insiden ini.');
        }

        // Cek file exist
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Cek apakah user memiliki akses ke incident.
     */
    private function userCanAccessIncident(User $user, Incident $incident): bool
    {
        // Supervisor bisa akses semua
        if ($user->role === 'supervisor') {
            return true;
        }

        // Engineer: hanya incident yang di-handle
        if ($user->role === 'engineer') {
            return $incident->handled_by === $user->id;
        }

        // Operator: hanya incident miliknya (yang dilaporkan)
        if ($user->role === 'operator') {
            return $incident->reported_by === $user->id;
        }

        return false;
    }

    /**
     * Kirim notifikasi ke engineer (yang handle) dan supervisor jika operator upload.
     */
    private function notifyRelevantUsers(Incident $incident, User $uploader, string $fileNames): void
    {
        $now = now()->toDateTimeString();
        $message = "Operator {$uploader->name} uploaded attachment(s): {$fileNames}";

        $targetUserIds = [];

        // Notifikasi ke engineer yang handle
        if ($incident->handled_by) {
            $targetUserIds[] = $incident->handled_by;
        }

        // Notifikasi ke semua supervisor
        $supervisorIds = User::query()
            ->where('role', 'supervisor')
            ->where('is_active', true)
            ->pluck('id')
            ->all();
        $targetUserIds = array_merge($targetUserIds, $supervisorIds);
        $targetUserIds = array_unique($targetUserIds);

        $rows = array_map(static function (int $userId) use ($incident, $message, $now): array {
            return [
                'user_id' => $userId,
                'incident_id' => $incident->incident_id,
                'type' => 'new_incident',
                'message' => $message,
                'is_read' => false,
                'created_at' => $now,
                'read_at' => null,
            ];
        }, $targetUserIds);

        if ($rows !== []) {
            Notification::insert($rows);
        }
    }
}
