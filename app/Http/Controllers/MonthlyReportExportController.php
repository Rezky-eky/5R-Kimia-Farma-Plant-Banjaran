<?php

namespace App\Http\Controllers;

use App\Services\FiveRMonthlyReportService;
use Illuminate\Http\Request;

class MonthlyReportExportController extends Controller
{
    public function __construct(
        private FiveRMonthlyReportService $reportService
    ) {}

    public function goAction(Request $request)
    {
        return $this->reportService->exportGoAction($request->input('month'));
    }

    public function goBoost(Request $request)
    {
        return $this->reportService->exportGoBoost($request->input('month'));
    }

    public function goCare(Request $request)
    {
        return $this->reportService->exportGoCare($request->input('month'));
    }

    public function goCheck(Request $request)
    {
        return $this->reportService->exportGoCheck($request->input('month'));
    }

    public function dbr(Request $request)
    {
        return $this->reportService->exportDbr($request->input('month'));
    }

    public function goOffer(Request $request)
    {
        return $this->reportService->exportGoOffer($request->input('month'));
    }

    public function goSale(Request $request)
    {
        return $this->reportService->exportGoSale($request->input('month'));
    }

    public function goReward(Request $request)
    {
        return $this->reportService->exportGoReward($request->input('month'));
    }

    public function overall(Request $request)
    {
        return $this->reportService->exportOverall($request->input('month'));
    }
}
