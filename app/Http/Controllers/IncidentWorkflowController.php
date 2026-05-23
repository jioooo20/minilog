<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentApproveRequest;
use App\Http\Requests\IncidentAssignRequest;
use App\Http\Requests\IncidentCloseRequest;
use App\Http\Requests\IncidentCompleteRepairRequest;
use App\Http\Requests\IncidentRepairDraftRequest;
use App\Http\Requests\IncidentInvestigationDraftRequest;
use App\Http\Requests\IncidentProposeRequest;
use App\Http\Requests\IncidentRejectRequest;
use App\Http\Requests\IncidentVerifyRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncidentWorkflowController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:engineer')->only([
            'assign',
            'propose',
            'completeRepair',
            'requestClosing',
        ]);

        $this->middleware('role:supervisor')->only([
            'approve',
            'reject',
            'closePage',
            'close',
        ]);

        $this->middleware('role:operator')->only(['verify']);
    }

    public function assign(IncidentAssignRequest $request, Incident $incident, IncidentService $service)
    {
        if ($incident->handled_by && $incident->handled_by !== $request->user()->id) {
            abort(403, 'Incident already assigned to another engineer.');
        }

        $incident = $service->assignSelf($incident, $request->user(), $request->ip());

        return response()->json($incident);
    }

    public function investigate(Request $request, Incident $incident)
    {
        $this->assertHandler($incident, $request->user()->id);

        if ($incident->status !== 'investigating') {
            abort(403, 'Incident is not ready for investigation.');
        }

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'auditLogs.user',
        ]);

        return Inertia::render('Incidents/InvestigateStart', [
            'incident' => (new IncidentResource($incident))->response()->getData(true),
        ]);
    }

    public function saveInvestigationDraft(
        IncidentInvestigationDraftRequest $request,
        Incident $incident,
        IncidentService $service
    ) {
        $this->assertHandler($incident, $request->user()->id);

        $incident = $service->saveInvestigationDraft(
            $incident,
            $request->user(),
            $request->validated(),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function review(Request $request, Incident $incident)
    {
        if ($incident->status !== 'awaiting_approval') {
            abort(403, 'Incident is not ready for supervisor review.');
        }

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'approver',
            'auditLogs.user',
        ]);

        return Inertia::render('Incidents/InvestigateReview', [
            'incident' => (new IncidentResource($incident))->response()->getData(true),
        ]);
    }

    public function repair(Request $request, Incident $incident)
    {
        $this->assertHandler($incident, $request->user()->id);

        if ($incident->status !== 'repairing') {
            abort(403, 'Incident is not ready for repair.');
        }

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'approver',
            'auditLogs.user',
        ]);

        return Inertia::render('Incidents/InvestigateRepair', [
            'incident' => (new IncidentResource($incident))->response()->getData(true),
        ]);
    }

    public function saveRepairDraft(
        IncidentRepairDraftRequest $request,
        Incident $incident,
        IncidentService $service
    ) {
        $this->assertHandler($incident, $request->user()->id);

        $incident = $service->saveRepairDraft(
            $incident,
            $request->user(),
            $request->validated(),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function propose(IncidentProposeRequest $request, Incident $incident, IncidentService $service)
    {
        $this->assertHandler($incident, $request->user()->id);

        $incident = $service->proposeHypothesis(
            $incident,
            $request->user(),
            $request->validated(),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function approve(IncidentApproveRequest $request, Incident $incident, IncidentService $service)
    {
        $incident = $service->approveHypothesis(
            $incident,
            $request->user(),
            $request->input('hypothesis_review_notes'),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function reject(IncidentRejectRequest $request, Incident $incident, IncidentService $service)
    {
        $incident = $service->rejectHypothesis(
            $incident,
            $request->user(),
            $request->input('hypothesis_review_notes'),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function completeRepair(
        IncidentCompleteRepairRequest $request,
        Incident $incident,
        IncidentService $service
    ) {
        $this->assertHandler($incident, $request->user()->id);

        $incident = $service->completeRepair(
            $incident,
            $request->user(),
            $request->validated(),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function verify(IncidentVerifyRequest $request, Incident $incident, IncidentService $service)
    {
        if ($incident->reported_by !== $request->user()->id) {
            abort(403, 'Only the reporting operator can verify this incident.');
        }

        if ($incident->auditLogs()->where('action', 'request_closing')->exists()) {
            abort(409, 'Closing has already been requested for this incident.');
        }

        $incident = $service->verifyIncident(
            $incident,
            $request->user(),
            $request->boolean('passed'),
            $request->input('verification_notes'),
            $request->ip()
        );

        return response()->json($incident);
    }

    public function verifyPage(Request $request, Incident $incident)
    {
        if ($incident->reported_by !== $request->user()->id) {
            abort(403, 'Only the reporting operator can verify this incident.');
        }

        if ($incident->auditLogs()->where('action', 'request_closing')->exists()) {
            abort(409, 'Closing has already been requested for this incident.');
        }

        if ($incident->status !== 'verifying') {
            abort(403, 'Incident is not ready for verification.');
        }

        $incident->loadCount([
            'auditLogs as closing_requests_count' => function ($query) {
                $query->where('action', 'request_closing');
            },
        ]);

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'approver',
            'verifier',
            'auditLogs.user',
        ]);

        return Inertia::render('Incidents/InvestigateVerifying', [
            'incident' => (new IncidentResource($incident))->response()->getData(true),
        ]);
    }

    public function requestClosingPage(Request $request, Incident $incident)
    {
        $this->assertHandler($incident, $request->user()->id);

        if ($incident->status !== 'verifying') {
            abort(403, 'Incident is not ready for closing request.');
        }

        if (!$incident->verified_at) {
            abort(403, 'Incident must be verified before requesting closing.');
        }

        $incident->loadCount([
            'auditLogs as closing_requests_count' => function ($query) {
                $query->where('action', 'request_closing');
            },
        ]);

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'verifier',
            'auditLogs.user',
        ]);

        return Inertia::render('Incidents/InvestigateClosing', [
            'mode' => 'engineer',
            'incident' => (new IncidentResource($incident))->response()->getData(true),
        ]);
    }

    public function closePage(Request $request, Incident $incident)
    {
        if ($incident->status !== 'verifying') {
            abort(403, 'Incident is not ready for closing.');
        }

        if (!$incident->auditLogs()->where('action', 'request_closing')->exists()) {
            abort(403, 'Closing has not been requested yet.');
        }

        $incident->loadCount([
            'auditLogs as closing_requests_count' => function ($query) {
                $query->where('action', 'request_closing');
            },
        ]);

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'verifier',
            'approver',
            'auditLogs.user',
        ]);

        return Inertia::render('Incidents/InvestigateClosing', [
            'mode' => 'supervisor',
            'incident' => (new IncidentResource($incident))->response()->getData(true),
        ]);
    }

    public function requestClosing(Request $request, Incident $incident, IncidentService $service)
    {
        $this->assertHandler($incident, $request->user()->id);

        if (!$incident->verified_at) {
            abort(403, 'Incident must be verified before requesting closing.');
        }

        $incident = $service->requestClosing($incident, $request->user(), $request->ip());

        return response()->json($incident);
    }

    public function close(IncidentCloseRequest $request, Incident $incident, IncidentService $service)
    {
        if ($incident->status !== 'verifying') {
            abort(403, 'Incident is not ready for closing.');
        }

        if (!$incident->auditLogs()->where('action', 'request_closing')->exists()) {
            abort(403, 'Closing has not been requested yet.');
        }

        $incident = $service->closeIncident(
            $incident,
            $request->user(),
            $request->input('closing_notes'),
            $request->ip()
        );

        return response()->json($incident);
    }

    private function assertHandler(Incident $incident, int $userId): void
    {
        if ($incident->handled_by !== $userId) {
            abort(403, 'Incident is not assigned to this engineer.');
        }
    }
}
