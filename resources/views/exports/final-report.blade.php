<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<style>
		@page {
			margin: 22mm 16mm 20mm 16mm;
		}

		body {
			font-family: DejaVu Sans, Arial, sans-serif;
			font-size: 11px;
			color: #1f2937;
			line-height: 1.45;
		}

		.header {
			border-bottom: 2px solid #0f172a;
			padding-bottom: 10px;
			margin-bottom: 14px;
		}

		.header-table {
			width: 100%;
			border-collapse: collapse;
		}

		.brand {
			width: 72%;
			vertical-align: top;
		}

		.docmeta {
			width: 28%;
			vertical-align: top;
			text-align: right;
		}

		.brand-title {
			font-size: 19px;
			font-weight: 700;
			color: #0f172a;
			letter-spacing: 0.5px;
			margin-bottom: 2px;
		}

		.brand-subtitle {
			font-size: 10px;
			color: #475569;
		}

		.report-title {
			font-size: 16px;
			font-weight: 700;
			margin-top: 14px;
			color: #0f172a;
			text-transform: uppercase;
			letter-spacing: 0.3px;
		}

		.report-subtitle {
			font-size: 10px;
			color: #475569;
			margin-top: 4px;
		}

		.meta-box {
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			padding: 8px 10px;
			margin-bottom: 12px;
		}

		.meta-grid {
			width: 100%;
			border-collapse: collapse;
		}

		.meta-grid td {
			padding: 2px 0;
			vertical-align: top;
		}

		.label {
			color: #64748b;
			font-size: 10px;
		}

		.value {
			color: #0f172a;
			font-weight: 600;
		}

		.section {
			margin-bottom: 12px;
			page-break-inside: avoid;
		}

		.section-title {
			background: #0f172a;
			color: #ffffff;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: 0.5px;
			padding: 6px 8px;
			text-transform: uppercase;
			border-radius: 4px 4px 0 0;
		}

		.section-body {
			border: 1px solid #cbd5e1;
			border-top: none;
			padding: 10px;
			border-radius: 0 0 4px 4px;
		}

		.two-col {
			width: 100%;
			border-collapse: collapse;
		}

		.two-col td {
			width: 50%;
			vertical-align: top;
			padding-right: 10px;
		}

		.info-table {
			width: 100%;
			border-collapse: collapse;
		}

		.info-table th,
		.info-table td {
			border: 1px solid #cbd5e1;
			padding: 6px 7px;
			vertical-align: top;
		}

		.info-table th {
			width: 28%;
			background: #f8fafc;
			font-weight: 700;
			text-align: left;
			color: #334155;
		}

		.small {
			font-size: 9px;
			color: #64748b;
		}

		.badge {
			display: inline-block;
			padding: 2px 7px;
			border-radius: 999px;
			font-size: 9px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.2px;
			color: #ffffff;
			background: #475569;
		}

		.badge-red { background: #b91c1c; }
		.badge-orange { background: #c2410c; }
		.badge-yellow { background: #a16207; }
		.badge-green { background: #15803d; }
		.badge-blue { background: #1d4ed8; }
		.badge-slate { background: #334155; }

		.narrative {
			white-space: pre-line;
		}

		.timeline {
			width: 100%;
			border-collapse: collapse;
			font-size: 9.5px;
		}

		.timeline th,
		.timeline td {
			border: 1px solid #cbd5e1;
			padding: 5px 6px;
			vertical-align: top;
		}

		.timeline th {
			background: #f8fafc;
			text-align: left;
			color: #334155;
			font-weight: 700;
		}

		.muted {
			color: #64748b;
		}

		.signature-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 8px;
		}

		.signature-table td {
			width: 25%;
			vertical-align: top;
			padding-right: 8px;
		}

		.sig-box {
			border: 1px solid #cbd5e1;
			min-height: 78px;
			padding: 8px;
			border-radius: 4px;
		}

		.sig-role {
			font-size: 9px;
			color: #64748b;
			margin-bottom: 18px;
		}

		.sig-line {
			border-top: 1px solid #94a3b8;
			margin-top: 24px;
			padding-top: 4px;
			font-size: 9px;
			color: #475569;
		}

		.footer {
			position: fixed;
			left: 0;
			right: 0;
			bottom: -8mm;
			font-size: 9px;
			color: #64748b;
			border-top: 1px solid #cbd5e1;
			padding-top: 4px;
		}

		.page-note {
			float: right;
		}
	</style>
</head>
<body>
	<div class="header">
		<table class="header-table">
			<tr>
				<td class="brand">
					<div class="brand-title">FINAL INCIDENT AUDIT REPORT</div>
					<div class="brand-subtitle">Evaluation document for incident handling, corrective actions, verification, and final closure</div>
					<div class="report-title">{{ $incident['incident_code'] }} - {{ $incident['title'] }}</div>
					<div class="report-subtitle">Prepared for internal audit purposes and final incident archiving</div>
				</td>
				<td class="docmeta">
					<div><span class="label">Document No.</span><br><span class="value">{{ $reportNumber }}</span></div>
					<div style="margin-top: 8px;"><span class="label">Print Date</span><br><span class="value">{{ $generatedAt->format('d M Y, H:i') }}</span></div>
					<div style="margin-top: 8px;"><span class="label">Prepared By</span><br><span class="value">{{ $preparedBy->name ?? '-' }}</span></div>
				</td>
			</tr>
		</table>
	</div>

	<div class="meta-box">
		<table class="meta-grid">
			<tr>
				<td><span class="label">Incident ID</span><br><span class="value">{{ $incident['incident_id'] }}</span></td>
				<td><span class="label">Status</span><br><span class="badge badge-slate">{{ $incident['status'] }}</span></td>
				<td><span class="label">Severity</span><br><span class="badge badge-red">{{ $incident['severity'] }}</span></td>
			</tr>
		</table>
	</div>

	<div class="section">
		<div class="section-title">1. Incident Identity</div>
		<div class="section-body">
			<table class="info-table">
				<tr>
					<th>Incident Title</th>
					<td>{{ $incident['title'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Asset / Mesin</th>
					<td>{{ $incident['item']['name'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Related Component</th>
					<td>{{ $incident['component_item']['name'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Location</th>
					<td>{{ $incident['location']['name'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Reporter</th>
					<td>{{ $incident['reported_by']['name'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Responsible Person</th>
					<td>{{ $incident['assigned_to']['name'] ?? '-' }}</td>
				</tr>
			</table>
		</div>
	</div>

	<table class="two-col" cellspacing="0" cellpadding="0">
		<tr>
			<td>
				<div class="section">
					<div class="section-title">2. Main Timeline</div>
					<div class="section-body">
						<table class="info-table">
							<tr><th>Detected At</th><td>{{ $incident['detected_at'] ?? '-' }}</td></tr>
							<tr><th>Investigation Started</th><td>{{ $incident['investigating_started_at'] ?? '-' }}</td></tr>
							<tr><th>Repair Started</th><td>{{ $incident['repair_started_at'] ?? '-' }}</td></tr>
							<tr><th>Hypothesis Approved</th><td>{{ $incident['hypothesis_approved_at'] ?? '-' }}</td></tr>
							<tr><th>Resolved At</th><td>{{ $incident['resolved_at'] ?? '-' }}</td></tr>
							<tr><th>Verified At</th><td>{{ $incident['verified_at'] ?? '-' }}</td></tr>
							<tr><th>Closed At</th><td>{{ $incident['closed_at'] ?? '-' }}</td></tr>
						</table>
					</div>
				</div>
			</td>
			<td>
				<div class="section">
					<div class="section-title">3. Phase Duration Summary</div>
					<div class="section-body">
						<table class="info-table">
							@forelse ($phases as $label => $duration)
								<tr>
									<th>{{ $label }}</th>
									<td>{{ $duration }}</td>
								</tr>
							@empty
								<tr>
									<td colspan="2" class="muted">No phase duration calculations are available yet.</td>
								</tr>
							@endforelse
						</table>
					</div>
				</div>
			</td>
		</tr>
	</table>

	<div class="section">
		<div class="section-title">4. Executive Summary</div>
		<div class="section-body">
			<table class="info-table">
				<tr>
					<th>Initial Description</th>
					<td class="narrative">{{ $incident['description'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Investigation Hypothesis</th>
					<td class="narrative">{{ $incident['root_cause_hypothesis'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Investigation Notes</th>
					<td class="narrative">{{ $incident['investigation_notes'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Supervisor Review Notes</th>
					<td class="narrative">{{ $incident['hypothesis_review_notes'] ?? '-' }}</td>
				</tr>
			</table>
		</div>
	</div>

	<div class="section">
		<div class="section-title">5. Corrective Actions and Verification</div>
		<div class="section-body">
			<table class="info-table">
				<tr>
					<th>Hypothesis Approved</th>
					<td>{{ $incident['hypothesis_approved'] ? 'Yes' : 'No' }}</td>
				</tr>
				<tr>
					<th>Corrective Actions</th>
					<td class="narrative">{{ $incident['corrective_actions'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Replaced Parts / Components</th>
					<td class="narrative">{{ $incident['parts_replaced'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Verification Notes</th>
					<td class="narrative">{{ $incident['verification_notes'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Closing Requested</th>
					<td>{{ $incident['closing_requested'] ? 'Yes' : 'No' }}</td>
				</tr>
				<tr>
					<th>Closing Notes</th>
					<td class="narrative">{{ $incident['closing_notes'] ?? '-' }}</td>
				</tr>
			</table>
		</div>
	</div>

	<div class="section">
		<div class="section-title">6. Activity Audit Trail</div>
		<div class="section-body">
			<table class="timeline">
				<thead>
					<tr>
						<th style="width: 16%">Time</th>
						<th style="width: 16%">Activity</th>
						<th style="width: 16%">Actor</th>
						<th style="width: 36%">Details</th>
						<th style="width: 16%">IP</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($timeline as $entry)
						<tr>
							<td>{{ $entry['time'] ?? '-' }}</td>
							<td>
								<strong>{{ $entry['action_label'] ?? '-' }}</strong><br>
								<span class="small">{{ $entry['role'] ?? '-' }}</span>
							</td>
							<td>{{ $entry['performed_by'] ?? '-' }}</td>
							<td class="narrative">
								{{ $entry['details'] ?? '-' }}
								@if(!empty($entry['old_value']) || !empty($entry['new_value']))
									<div class="small" style="margin-top: 4px;">
										This log contains data changes.
									</div>
								@endif
							</td>
							<td>{{ $entry['ip_address'] ?? '-' }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="5" class="muted">No audit trail is available.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>

	<div class="section">
		<div class="section-title">7. Final Disposition</div>
		<div class="section-body">
			<table class="info-table">
				<tr>
					<th>Supervisor Review</th>
					<td>{{ $incident['approved_by']['name'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Verifier</th>
					<td>{{ $incident['verified_by']['name'] ?? '-' }}</td>
				</tr>
				<tr>
					<th>Incident Closer</th>
					<td>{{ $incident['closed_by']['name'] ?? '-' }}</td>
				</tr>
			</table>
		</div>
	</div>

	

	<div class="footer">
		<span>Internal document - {{ $incident['incident_code'] }}</span>
		<span class="page-note">Generated on {{ $generatedAt->format('d M Y, H:i') }}</span>
	</div>
</body>
</html>
