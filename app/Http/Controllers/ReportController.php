<?php

namespace App\Http\Controllers;

use App\Enums\ReportTargetType;
use App\Http\Requests\StoreReportRequest;
use App\Models\Post;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

/**
 * A plain JSON endpoint like VoteController: reporting must not trigger a page visit, which would
 * reset any feed or comment list the reader has already scrolled.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected PostRepositoryInterface $postRepository,
        protected CommentRepositoryInterface $commentRepository
    ) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $type = $request->validated('target_type') === 'post' ? ReportTargetType::Post : ReportTargetType::Comment;
        $targetId = $request->validated('target_id');

        $target = match ($type) {
            ReportTargetType::Post => $this->postRepository->find($targetId),
            ReportTargetType::Comment => $this->commentRepository->find($targetId),
        };

        abort_if($target === null || $target->is_deleted, 404);

        $this->authorize('report', $target);

        $reported = $this->reportService->report(
            $request->user()->id,
            $type,
            $target->id,
            $target instanceof Post ? $target->group_id : $target->post->group_id,
            $request->validated('rule_index'),
            $request->boolean('platform_violation'),
            $request->validated('text')
        );

        if (! $reported) {
            return response()->json(['message' => __('reports.already')], 409);
        }

        return response()->json(['reported' => true], 201);
    }
}
