<?php

namespace App\Http\Controllers;

use App\Services\DashboardThreeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardThreeController extends Controller
{
    public function __construct(private DashboardThreeService $service) {}

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function parseDateRange(Request $request): array
    {
        $today = Carbon::today()->toDateString();
        $start = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $end   = $request->get('end', $today);

        try { $start = Carbon::parse($start)->toDateString(); } catch (\Throwable) { $start = $today; }
        try { $end   = Carbon::parse($end)->toDateString();   } catch (\Throwable) { $end   = $today; }
        if ($start > $end) [$start, $end] = [$end, $start];

        return [
            $start . ' 00:00:00',
            $end   . ' 23:59:59',
            $start,
            $end,
        ];
    }

    // ── Inertia page ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        [, , $start, $end] = $this->parseDateRange($request);

        return Inertia::render('DashboardThree/Index', [
            'dateRange'  => ['start' => $start, 'end' => $end],
            'categories' => $this->service->getCategories(),
            'csStaff'    => $this->service->getCsStaff(),
        ]);
    }

    // ── MAIN section ─────────────────────────────────────────────────────────

    public function mainSummary(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getMainSummary($start, $end));
    }

    public function thaiChart(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getThaiChart($start, $end));
    }

    public function exportChart(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getExportChart($start, $end));
    }

    public function thaiTop10(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getThaiTop10($start, $end));
    }

    public function exportTop10(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getExportTop10($start, $end));
    }

    public function thaiAll(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getThaiAll(
            $start, $end,
            (int) $request->get('page', 1),
            (string) $request->get('search', '')
        ));
    }

    public function exportAll(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getExportAll(
            $start, $end,
            (int) $request->get('page', 1),
            (string) $request->get('search', '')
        ));
    }

    public function sliceDetail(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        $customerId = (int) $request->get('customer_id');
        return response()->json($this->service->getSliceDetail($customerId, $start, $end));
    }

    // ── BEHAVIOR section ──────────────────────────────────────────────────────

    public function behaviorSummary(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getBehaviorSummary($start, $end));
    }

    public function behaviorCharts(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getBehaviorCharts($start, $end));
    }

    public function behaviorTierCustomers(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getBehaviorTierCustomers(
            $start, $end,
            (string) $request->get('tier', '')
        ));
    }

    // ── FINANCIAL section ─────────────────────────────────────────────────────

    public function financialSummary(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getFinancialSummary($start, $end));
    }

    public function financialCharts(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getFinancialCharts($start, $end));
    }

    public function financialTopCustomers(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getFinancialTopCustomers($start, $end));
    }

    public function financialPaymentAnalysis(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getFinancialPaymentAnalysis($start, $end));
    }

    // ── OPERATION section ─────────────────────────────────────────────────────

    public function operationSummary(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getOperationSummary($start, $end));
    }

    public function operationCharts(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getOperationCharts($start, $end));
    }

    public function operationTimeline(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getOperationTimeline($start, $end));
    }

    public function operationFollowups(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getOperationFollowups($start, $end));
    }

    public function operationFollowupStore(Request $request)
    {
        $data = $request->validate([
            'customer_id'    => 'required|integer',
            'tier'           => 'nullable|string',
            'stage'          => 'nullable|integer|min:1|max:6',
            'cs_id'          => 'nullable|integer',
            'follow_up_date' => 'required|date',
            'next_due_date'  => 'nullable|date',
            'follow_up_type' => 'nullable|string',
            'notes'          => 'nullable|string',
            'expected_value' => 'nullable|numeric',
        ]);

        return response()->json($this->service->createFollowup($data));
    }

    public function operationFollowupDone(int $id)
    {
        return response()->json($this->service->markFollowupDone($id));
    }

    public function operationFollowupDelete(int $id)
    {
        return response()->json($this->service->deleteFollowup($id));
    }

    public function operationPipeline(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getOperationCharts($start, $end));
    }

    public function operationPipelineDeals(Request $request)
    {
        $stage = $request->get('stage') ? (int) $request->get('stage') : null;
        return response()->json($this->service->getOperationPipelineDeals($stage));
    }

    public function operationPipelineDealStore(Request $request)
    {
        $data = $request->validate([
            'customer_id'    => 'required|integer',
            'expected_value' => 'required|numeric|min:0',
            'stage'          => 'required|integer|min:1|max:6',
            'probability'    => 'nullable|integer|min:0|max:100',
            'notes'          => 'nullable|string',
        ]);

        return response()->json($this->service->createPipelineDeal($data));
    }

    public function operationPipelineDealUpdate(Request $request, int $id)
    {
        $data = $request->validate([
            'expected_value' => 'nullable|numeric|min:0',
            'stage'          => 'nullable|integer|min:1|max:6',
            'probability'    => 'nullable|integer|min:0|max:100',
            'notes'          => 'nullable|string',
            'status'         => 'nullable|integer|in:0,1,2',
        ]);

        return response()->json($this->service->updatePipelineDeal($id, $data));
    }

    public function operationPipelineDealDelete(int $id)
    {
        return response()->json($this->service->deletePipelineDeal($id));
    }

    // ── PRODUCT section ───────────────────────────────────────────────────────

    public function productSummary(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getProductSummary($start, $end));
    }

    public function productCharts(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getProductCharts($start, $end));
    }

    public function productSlowMovers(Request $request)
    {
        $days = (int) $request->get('days', 60);
        return response()->json($this->service->getProductSlowMovers($days));
    }

    public function productHealthScores()
    {
        return response()->json($this->service->getProductHealthScores());
    }

    public function productCustomerSearch(Request $request)
    {
        return response()->json($this->service->searchCustomers($request->get('q', '')));
    }

    public function productProductSearch(Request $request)
    {
        return response()->json($this->service->searchProducts($request->get('q', '')));
    }

    public function competitiveList(Request $request)
    {
        $customerId = $request->get('customer_id') ? (int) $request->get('customer_id') : null;
        return response()->json($this->service->getCompetitiveRefs($customerId));
    }

    public function competitiveStore(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => 'required|integer',
            'product_name'     => 'required|string',
            'competitor_id'    => 'nullable|integer',
            'competitor_name'  => 'nullable|string',
            'competitor_price' => 'required|numeric|min:0',
            'our_price'        => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
            'recorded_at'      => 'nullable|date',
        ]);

        return response()->json($this->service->createCompetitiveRef($data));
    }

    public function competitiveDelete(int $id)
    {
        return response()->json($this->service->deleteCompetitiveRef($id));
    }

    public function competitiveSummary()
    {
        return response()->json($this->service->getCompetitiveSummary());
    }

    public function feedbackList(Request $request)
    {
        $productId = $request->get('product_id') ? (int) $request->get('product_id') : null;
        return response()->json($this->service->getProductFeedback($productId));
    }

    public function feedbackStore(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|integer',
            'customer_id' => 'nullable|integer',
            'type'        => 'nullable|string',
            'category'    => 'nullable|string',
            'score'       => 'nullable|integer|min:1|max:5',
            'text'        => 'nullable|string',
            'source'      => 'nullable|string',
        ]);

        return response()->json($this->service->createProductFeedback($data));
    }

    public function feedbackDelete(int $id)
    {
        return response()->json($this->service->deleteProductFeedback($id));
    }

    public function feedbackOverview(Request $request)
    {
        [$start, $end] = $this->parseDateRange($request);
        return response()->json($this->service->getFeedbackOverview($start, $end));
    }

    public function competitorList()
    {
        return response()->json($this->service->getCompetitors());
    }

    public function competitorStore(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        return response()->json($this->service->createCompetitor($data));
    }

    public function competitorDelete(int $id)
    {
        return response()->json($this->service->deleteCompetitor($id));
    }
}
