<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppealRequest;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\AppealService;
use DomainException;
use Illuminate\Http\JsonResponse;

class AppealController extends Controller
{
    public function __construct(
        protected AppealService $appealService,
        protected ModerationCaseRepositoryInterface $moderationCaseRepository
    ) {}

    // A background JSON post, like a vote: the page shows the appeal as sent without reloading.
    public function store(StoreAppealRequest $request, string $verdictId): JsonResponse
    {
        $verdict = $this->moderationCaseRepository->findVerdict($verdictId);

        abort_if($verdict === null, 404);

        $this->authorize('appeal', $verdict);

        try {
            $appeal = $this->appealService->file($request->user()->id, $verdict, $request->validated('statement'));
        } catch (DomainException) {
            // Another one got in first.
            abort(403);
        }

        return response()->json([
            'status' => $appeal->status->key(),
            'statement' => $appeal->statement,
            'filed_at' => $appeal->created_at,
        ], 201);
    }
}
