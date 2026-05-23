export interface AttachmentContract {
  attachment_id: number;
  file_name: string;
  file_path: string;
  file_size?: number | null;
  mime_type?: string | null;
  description?: string | null;
  uploaded_at?: string | null;
  uploaded_by?: { id: number; name: string } | null;
}
