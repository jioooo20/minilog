export interface UserSummary {
  id: number;
  name: string;
}

export interface ItemSummary {
  item_id: number;
  name?: string;
}

export interface IncidentContract {
  incident_id: number;
  incident_code?: string;
  title: string;
  description?: string;
  status?: string;
  severity?: string;
  priority?: string | null;
  item?: ItemSummary | null;
  location?: { location_id: number; name?: string } | null;
  reported_by?: UserSummary | null;
  assigned_to?: UserSummary | null;
  created_at?: string | null;
  detected_at?: string | null;
  resolved_at?: string | null;
  closed_at?: string | null;
  attachments?: AttachmentContract[];
  audit_logs?: any[];
  can?: { [capability: string]: boolean };
}

import type { AttachmentContract } from './attachments';

export interface IncidentListResponse {
  data: IncidentContract[];
  meta?: Record<string, unknown>;
}
