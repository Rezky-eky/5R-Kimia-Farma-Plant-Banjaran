<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\GoAction;
use App\Models\GoBoost;
use App\Models\GoCare;
use App\Models\GoCheck;
use App\Models\GoOffer;
use App\Models\GoSale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FiveRMonthlyReportService
{
    private const REWARD_POINTS_PER_ACTIVITY = 10;

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function monthRange(?string $month): array
    {
        $month = $month ?: now()->format('Y-m');
        [$year, $m] = array_pad(explode('-', (string) $month), 2, null);
        $year = (int) $year;
        $m = (int) $m;

        if ($year < 2000 || $m < 1 || $m > 12) {
            $month = now()->format('Y-m');
            [$year, $m] = array_map('intval', explode('-', $month));
        }

        $start = Carbon::create($year, $m, 1, 0, 0, 0);
        $end = $start->copy()->endOfMonth();

        return [$start, $end, sprintf('%04d-%02d', $year, $m)];
    }

    public function exportGoAction(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'ID', 'Tanggal', 'Nama Karyawan', 'NPP', 'Bagian', 'Nama Ruangan', 'Kode Ruangan',
            'Penjelasan Aksi', 'Status Audit', 'Skor Audit', 'Auditor', 'Latitude', 'Longitude',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->goActionQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++,
                $item->id,
                $this->formatDateTime($item->created_at),
                $item->nama_karyawan,
                $item->npp_karyawan,
                $item->bagian,
                $item->nama_ruangan,
                $item->kode_ruangan,
                $item->penjelasan_aksi,
                $item->audit ? 'Audited' : 'Belum',
                $item->audit?->score,
                $item->audit?->auditor?->name,
                $item->latitude,
                $item->longitude,
            ];
        }

        return $this->downloadSingleSheet('GO ACTION', $headers, $rows, "laporan-go-action-{$label}.xlsx");
    }

    public function exportGoBoost(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'ID', 'Tanggal', 'Nama Karyawan', 'NPP', 'Bagian', 'Area Temuan', 'Ruangan Temuan',
            'Penjelasan Temuan', 'PIC Terkait', 'Status', 'Status Perbaikan', 'Tanggal Perbaikan',
            'Solver (Nama)', 'Solver (NPP)', 'Approval Status', 'Tanggal Approval',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->goBoostQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++,
                $item->id,
                $this->formatDateTime($item->created_at),
                $item->nama_karyawan,
                $item->npp_karyawan,
                $item->bagian,
                $item->area_temuan,
                $item->ruangan_temuan,
                $item->penjelasan_temuan,
                $item->pic_terkait,
                $item->status,
                $item->status_perbaikan,
                $this->formatDateTime($item->tanggal_perbaikan),
                $item->mentionedUser?->name,
                $item->mentionedUser?->npp,
                $item->approval_status,
                $this->formatDateTime($item->approved_at),
            ];
        }

        return $this->downloadSingleSheet('GO BOOST', $headers, $rows, "laporan-go-boost-{$label}.xlsx");
    }

    public function exportGoCare(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'ID', 'Tanggal', 'Nama Karyawan', 'NPP', 'Bagian', 'Bagian Temuan', 'Area Temuan',
            'Penjelasan Temuan', 'Penjelasan Capa', 'Approval Status', 'Tanggal Approval',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->goCareQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++,
                $item->id,
                $this->formatDateTime($item->created_at),
                $item->nama_karyawan ?: $item->user?->name,
                $item->npp_karyawan ?: $item->user?->npp,
                $item->bagian,
                $item->bagian_temuan,
                $item->area_temuan,
                $item->penjelasan_temuan,
                $item->penjelasan_capa,
                $item->approval_status,
                $this->formatDateTime($item->approved_at),
            ];
        }

        return $this->downloadSingleSheet('GO CARE', $headers, $rows, "laporan-go-care-{$label}.xlsx");
    }

    public function exportGoCheck(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'ID', 'Tanggal', 'Bagian', 'Area Temuan', 'Ruangan Temuan', 'Penjelasan Temuan',
            'PIC Terkait', 'Finder (Nama)', 'Finder (NPP)', 'Solver (Nama)', 'Solver (NPP)',
            'Status', 'Status Perbaikan', 'Tanggal Perbaikan', 'Approval Status',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->goCheckQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++,
                $item->id,
                $this->formatDateTime($item->created_at),
                $item->bagian,
                $item->area_temuan,
                $item->ruangan_temuan,
                $item->penjelasan_temuan,
                $item->pic_terkait,
                $item->finder?->name,
                $item->finder?->npp,
                $item->solver?->name,
                $item->solver?->npp,
                $item->status,
                $item->status_perbaikan,
                $this->formatDateTime($item->tanggal_perbaikan),
                $item->approval_status,
            ];
        }

        return $this->downloadSingleSheet('GO CHECK', $headers, $rows, "laporan-go-check-{$label}.xlsx");
    }

    public function exportDbr(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'Go Action ID', 'Tanggal', 'Pelapor', 'Bagian', 'Nama Ruangan', 'Nama Barang',
            'Jumlah', 'Satuan', 'Jenis Distribusi', 'No Aktiva SAP', 'Kondisi Barang', 'Status TPS', 'Tindakan Barang',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->dbrGoActionsQuery($start, $end)->get() as $goAction) {
            $list = $goAction->list_barang_ringkas;
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $barang) {
                $rows[] = [
                    $no++,
                    $goAction->id,
                    $this->formatDateTime($goAction->created_at),
                    $goAction->user?->name,
                    $goAction->bagian,
                    $goAction->nama_ruangan,
                    $barang['nama_barang'] ?? '',
                    $barang['jumlah'] ?? '',
                    $barang['satuan'] ?? '',
                    $barang['distribution_type'] ?? '',
                    $barang['no_aktiva_sap'] ?? '',
                    $barang['kondisi_barang'] ?? '',
                    $barang['status_tps'] ?? '',
                    $barang['tindakan_barang'] ?? '',
                ];
            }
        }

        return $this->downloadSingleSheet('Barang Ringkas', $headers, $rows, "laporan-barang-ringkas-{$label}.xlsx");
    }

    public function exportGoOffer(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'ID', 'Tanggal', 'Go Action ID', 'Nama Barang', 'Jumlah', 'Satuan',
            'Penawar (Nama)', 'Penawar (Bagian)', 'Target Bagian', 'Pemohon (Nama)', 'Status', 'Tanggal Diterima',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->goOfferQuery($start, $end)->get() as $item) {
            $snapshot = $item->dbr_snapshot ?? [];
            $rows[] = [
                $no++,
                $item->id,
                $this->formatDateTime($item->created_at),
                $item->go_action_id,
                $snapshot['nama_barang'] ?? '',
                $snapshot['jumlah'] ?? '',
                $snapshot['satuan'] ?? '',
                $item->offeredByUser?->name,
                $item->offered_by_bagian,
                $item->target_bagian,
                $item->requestedByUser?->name,
                $item->status,
                $this->formatDateTime($item->accepted_at),
            ];
        }

        return $this->downloadSingleSheet('GO OFFER', $headers, $rows, "laporan-go-offer-{$label}.xlsx");
    }

    public function exportGoSale(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $headers = [
            'No', 'ID', 'Tanggal', 'Go Action ID', 'Nama Barang', 'Jumlah', 'Satuan',
            'Penjual (Nama)', 'Penjual (Bagian)', 'Pembeli (Nama)', 'Pembeli (Bagian)',
            'Harga Kesepakatan', 'Status', 'Tanggal Selesai',
        ];

        $rows = [];
        $no = 1;
        foreach ($this->goSaleQuery($start, $end)->get() as $item) {
            $snapshot = $item->dbr_snapshot ?? [];
            $rows[] = [
                $no++,
                $item->id,
                $this->formatDateTime($item->created_at),
                $item->go_action_id,
                $snapshot['nama_barang'] ?? '',
                $snapshot['jumlah'] ?? '',
                $snapshot['satuan'] ?? '',
                $item->sellerUser?->name,
                $item->seller_bagian,
                $item->buyerUser?->name,
                $item->buyer_bagian,
                $item->agreed_price,
                $item->status,
                $this->formatDateTime($item->completed_at),
            ];
        }

        return $this->downloadSingleSheet('GO SALE', $headers, $rows, "laporan-go-sale-{$label}.xlsx");
    }

    public function exportGoReward(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Ringkasan Bulan');

        $summaryHeaders = ['Kategori', 'Jumlah Bulan '.$label];
        $summaryRows = [
            ['GO ACTION', $this->goActionQuery($start, $end)->count()],
            ['GO BOOST', $this->goBoostQuery($start, $end)->count()],
            ['GO CARE', $this->goCareQuery($start, $end)->count()],
            ['GO CHECK', $this->goCheckQuery($start, $end)->count()],
            ['GO OFFER', $this->goOfferQuery($start, $end)->count()],
            ['GO SALE', $this->goSaleQuery($start, $end)->count()],
            ['Audit GO ACTION', Audit::whereBetween('created_at', [$start, $end])->count()],
        ];
        $this->fillSheet($spreadsheet->getActiveSheet(), $summaryHeaders, $summaryRows);

        $this->addDataSheetFromExport($spreadsheet, 'Bagian Teraktif', $this->buildGoRewardBagianTeraktifRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Poin Tertinggi', $this->buildGoRewardPoinTertinggiRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Go Boost Temuan', $this->buildGoRewardGoBoostTemuanRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Go Boost Solver', $this->buildGoRewardGoBoostSolverRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Go Check Finder', $this->buildGoRewardGoCheckFinderRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Go Check Closer', $this->buildGoRewardGoCheckCloserRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Go Care', $this->buildGoRewardGoCareRows($start, $end));

        return $this->streamDownload($spreadsheet, "laporan-go-reward-{$label}.xlsx");
    }

    public function exportOverall(?string $month): StreamedResponse
    {
        [$start, $end, $label] = $this->monthRange($month);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Ringkasan');

        $summaryHeaders = ['Modul', 'Jumlah Data Bulan Ini'];
        $summaryRows = [
            ['GO ACTION', $this->goActionQuery($start, $end)->count()],
            ['GO BOOST', $this->goBoostQuery($start, $end)->count()],
            ['GO CARE', $this->goCareQuery($start, $end)->count()],
            ['GO CHECK', $this->goCheckQuery($start, $end)->count()],
            ['Barang Ringkas (item)', $this->countDbrItems($start, $end)],
            ['GO OFFER', $this->goOfferQuery($start, $end)->count()],
            ['GO SALE', $this->goSaleQuery($start, $end)->count()],
        ];
        $this->fillSheet($spreadsheet->getActiveSheet(), $summaryHeaders, $summaryRows);

        $this->addDataSheetFromExport($spreadsheet, 'GO ACTION', $this->buildGoActionRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'GO BOOST', $this->buildGoBoostRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'GO CARE', $this->buildGoCareRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'GO CHECK', $this->buildGoCheckRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'Barang Ringkas', $this->buildDbrRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'GO OFFER', $this->buildGoOfferRows($start, $end));
        $this->addDataSheetFromExport($spreadsheet, 'GO SALE', $this->buildGoSaleRows($start, $end));

        return $this->streamDownload($spreadsheet, "laporan-5r-keseluruhan-{$label}.xlsx");
    }

    private function goActionQuery(Carbon $start, Carbon $end): Builder
    {
        return GoAction::with(['user', 'audit.auditor'])
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function goBoostQuery(Carbon $start, Carbon $end): Builder
    {
        return GoBoost::with(['user', 'mentionedUser'])
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function goCareQuery(Carbon $start, Carbon $end): Builder
    {
        return GoCare::with('user')
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function goCheckQuery(Carbon $start, Carbon $end): Builder
    {
        return GoCheck::with(['finder', 'solver'])
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function dbrGoActionsQuery(Carbon $start, Carbon $end): Builder
    {
        return GoAction::with('user')
            ->whereNotNull('list_barang_ringkas')
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function goOfferQuery(Carbon $start, Carbon $end): Builder
    {
        return GoOffer::with(['offeredByUser', 'requestedByUser', 'goAction'])
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function goSaleQuery(Carbon $start, Carbon $end): Builder
    {
        return GoSale::with(['sellerUser', 'buyerUser', 'goAction'])
            ->whereBetween('created_at', [$start, $end])
            ->latest();
    }

    private function countDbrItems(Carbon $start, Carbon $end): int
    {
        $count = 0;
        foreach ($this->dbrGoActionsQuery($start, $end)->get(['list_barang_ringkas']) as $goAction) {
            if (is_array($goAction->list_barang_ringkas)) {
                $count += count($goAction->list_barang_ringkas);
            }
        }

        return $count;
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoActionRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'ID', 'Tanggal', 'Nama Karyawan', 'NPP', 'Bagian', 'Ruangan', 'Penjelasan', 'Status Audit', 'Skor'];
        $rows = [];
        $no = 1;
        foreach ($this->goActionQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++, $item->id, $this->formatDateTime($item->created_at),
                $item->nama_karyawan, $item->npp_karyawan, $item->bagian, $item->nama_ruangan,
                $item->penjelasan_aksi, $item->audit ? 'Audited' : 'Belum', $item->audit?->score,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoBoostRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'ID', 'Tanggal', 'Nama', 'NPP', 'Bagian', 'Area', 'Ruangan', 'Penjelasan', 'Status', 'Approval'];
        $rows = [];
        $no = 1;
        foreach ($this->goBoostQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++, $item->id, $this->formatDateTime($item->created_at),
                $item->nama_karyawan, $item->npp_karyawan, $item->bagian,
                $item->area_temuan, $item->ruangan_temuan, $item->penjelasan_temuan,
                $item->status, $item->approval_status,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoCareRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'ID', 'Tanggal', 'Nama', 'NPP', 'Bagian', 'Area', 'Penjelasan Temuan', 'Penjelasan Capa', 'Approval'];
        $rows = [];
        $no = 1;
        foreach ($this->goCareQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++, $item->id, $this->formatDateTime($item->created_at),
                $item->nama_karyawan ?: $item->user?->name,
                $item->npp_karyawan ?: $item->user?->npp,
                $item->bagian, $item->area_temuan, $item->penjelasan_temuan,
                $item->penjelasan_capa, $item->approval_status,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoCheckRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'ID', 'Tanggal', 'Bagian', 'Area', 'Ruangan', 'Finder', 'Solver', 'Status', 'Approval'];
        $rows = [];
        $no = 1;
        foreach ($this->goCheckQuery($start, $end)->get() as $item) {
            $rows[] = [
                $no++, $item->id, $this->formatDateTime($item->created_at),
                $item->bagian, $item->area_temuan, $item->ruangan_temuan,
                $item->finder?->name, $item->solver?->name,
                $item->status, $item->approval_status,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildDbrRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'Go Action ID', 'Tanggal', 'Pelapor', 'Bagian', 'Nama Barang', 'Jumlah', 'Satuan', 'Distribusi', 'Status TPS'];
        $rows = [];
        $no = 1;
        foreach ($this->dbrGoActionsQuery($start, $end)->get() as $goAction) {
            $list = $goAction->list_barang_ringkas;
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $barang) {
                $rows[] = [
                    $no++, $goAction->id, $this->formatDateTime($goAction->created_at),
                    $goAction->user?->name, $goAction->bagian,
                    $barang['nama_barang'] ?? '', $barang['jumlah'] ?? '', $barang['satuan'] ?? '',
                    $barang['distribution_type'] ?? '', $barang['status_tps'] ?? '',
                ];
            }
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoOfferRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'ID', 'Tanggal', 'Barang', 'Penawar', 'Target Bagian', 'Pemohon', 'Status'];
        $rows = [];
        $no = 1;
        foreach ($this->goOfferQuery($start, $end)->get() as $item) {
            $snapshot = $item->dbr_snapshot ?? [];
            $rows[] = [
                $no++, $item->id, $this->formatDateTime($item->created_at),
                $snapshot['nama_barang'] ?? '', $item->offeredByUser?->name,
                $item->target_bagian, $item->requestedByUser?->name, $item->status,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoSaleRows(Carbon $start, Carbon $end): array
    {
        $headers = ['No', 'ID', 'Tanggal', 'Barang', 'Penjual', 'Pembeli', 'Harga', 'Status'];
        $rows = [];
        $no = 1;
        foreach ($this->goSaleQuery($start, $end)->get() as $item) {
            $snapshot = $item->dbr_snapshot ?? [];
            $rows[] = [
                $no++, $item->id, $this->formatDateTime($item->created_at),
                $snapshot['nama_barang'] ?? '', $item->sellerUser?->name,
                $item->buyerUser?->name, $item->agreed_price, $item->status,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardBagianTeraktifRows(Carbon $start, Carbon $end): array
    {
        $headers = ['Peringkat', 'Nama Bagian', 'Go Action', 'Go Boost', 'Go Care', 'Go Check', 'Total Aktivitas'];

        $bagianGoAction = GoAction::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');
        $bagianGoBoost = GoBoost::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');
        $bagianGoCare = GoCare::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian_temuan', DB::raw('count(*) as total'))->groupBy('bagian_temuan')->pluck('total', 'bagian_temuan');
        $bagianGoCheck = GoCheck::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');

        $allBagian = $bagianGoAction->keys()
            ->merge($bagianGoBoost->keys())
            ->merge($bagianGoCare->keys())
            ->merge($bagianGoCheck->keys())
            ->unique()
            ->filter();

        $stats = $allBagian->map(function ($bagian) use ($bagianGoAction, $bagianGoBoost, $bagianGoCare, $bagianGoCheck) {
            $goAction = (int) ($bagianGoAction[$bagian] ?? 0);
            $goBoost = (int) ($bagianGoBoost[$bagian] ?? 0);
            $goCare = (int) ($bagianGoCare[$bagian] ?? 0);
            $goCheck = (int) ($bagianGoCheck[$bagian] ?? 0);

            return [
                'bagian' => $bagian,
                'go_action' => $goAction,
                'go_boost' => $goBoost,
                'go_care' => $goCare,
                'go_check' => $goCheck,
                'total' => $goAction + $goBoost + $goCare + $goCheck,
            ];
        })->sortByDesc('total')->values();

        $rows = [];
        $rank = 1;
        foreach ($stats as $stat) {
            $rows[] = [
                $rank++,
                $stat['bagian'],
                $stat['go_action'],
                $stat['go_boost'],
                $stat['go_care'],
                $stat['go_check'],
                $stat['total'],
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardPoinTertinggiRows(Carbon $start, Carbon $end): array
    {
        $headers = [
            'Peringkat', 'NPP', 'Nama Pegawai', 'Bagian',
            'Poin Go Boost (Temuan)', 'Poin Go Boost (Solver)',
            'Poin Go Check (Finder)', 'Poin Go Check (Closer)',
            'Poin Go Care', 'Total Poin',
        ];

        $scores = [];

        $this->accumulateRewardPoints(
            $scores,
            $this->approvedGoBoostQuery($start, $end)
                ->select('user_id', DB::raw('count(*) as total'))
                ->groupBy('user_id')
                ->pluck('total', 'user_id'),
            'boost_finder'
        );

        $this->accumulateRewardPoints(
            $scores,
            $this->approvedGoBoostQuery($start, $end)
                ->whereNotNull('mentioned_user_id')
                ->where('status_perbaikan', 'selesai')
                ->select('mentioned_user_id', DB::raw('count(*) as total'))
                ->groupBy('mentioned_user_id')
                ->pluck('total', 'mentioned_user_id'),
            'boost_solver'
        );

        $this->accumulateRewardPoints(
            $scores,
            $this->approvedGoCheckQuery($start, $end)
                ->select('finder_user_id', DB::raw('count(*) as total'))
                ->groupBy('finder_user_id')
                ->pluck('total', 'finder_user_id'),
            'check_finder'
        );

        $this->accumulateRewardPoints(
            $scores,
            $this->approvedGoCheckQuery($start, $end)
                ->whereNotNull('solver_user_id')
                ->where('status_perbaikan', 'selesai')
                ->select('solver_user_id', DB::raw('count(*) as total'))
                ->groupBy('solver_user_id')
                ->pluck('total', 'solver_user_id'),
            'check_closer'
        );

        $this->accumulateRewardPoints(
            $scores,
            $this->approvedGoCareQuery($start, $end)
                ->select('user_id', DB::raw('count(*) as total'))
                ->groupBy('user_id')
                ->pluck('total', 'user_id'),
            'go_care'
        );

        if ($scores === []) {
            return [$headers, []];
        }

        $users = User::whereIn('id', array_keys($scores))
            ->get(['id', 'name', 'npp', 'bagian'])
            ->keyBy('id');

        $sorted = collect($scores)->map(function ($data, $userId) use ($users) {
            $user = $users->get($userId);
            $boostFinder = $data['boost_finder'] * self::REWARD_POINTS_PER_ACTIVITY;
            $boostSolver = $data['boost_solver'] * self::REWARD_POINTS_PER_ACTIVITY;
            $checkFinder = $data['check_finder'] * self::REWARD_POINTS_PER_ACTIVITY;
            $checkCloser = $data['check_closer'] * self::REWARD_POINTS_PER_ACTIVITY;
            $goCare = $data['go_care'] * GoCare::POINTS_PER_APPROVAL;

            return [
                'npp' => $user?->npp,
                'name' => $user?->name,
                'bagian' => $user?->bagian,
                'boost_finder' => $boostFinder,
                'boost_solver' => $boostSolver,
                'check_finder' => $checkFinder,
                'check_closer' => $checkCloser,
                'go_care' => $goCare,
                'total' => $boostFinder + $boostSolver + $checkFinder + $checkCloser + $goCare,
            ];
        })->sortByDesc('total')->values();

        $rows = [];
        $rank = 1;
        foreach ($sorted as $item) {
            $rows[] = [
                $rank++,
                $item['npp'],
                $item['name'],
                $item['bagian'],
                $item['boost_finder'],
                $item['boost_solver'],
                $item['check_finder'],
                $item['check_closer'],
                $item['go_care'],
                $item['total'],
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardGoBoostTemuanRows(Carbon $start, Carbon $end): array
    {
        return $this->buildGoRewardEmployeeCountRows(
            $this->approvedGoBoostQuery($start, $end)
                ->select('user_id', DB::raw('count(*) as total'))
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->with('user:id,name,npp,bagian')
                ->get(),
            'user',
            ['Peringkat', 'NPP', 'Nama Pegawai', 'Bagian', 'Jumlah Temuan', 'Poin Diperoleh'],
            self::REWARD_POINTS_PER_ACTIVITY
        );
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardGoBoostSolverRows(Carbon $start, Carbon $end): array
    {
        $queryRows = $this->approvedGoBoostQuery($start, $end)
            ->whereNotNull('mentioned_user_id')
            ->where('status_perbaikan', 'selesai')
            ->select('mentioned_user_id', DB::raw('count(*) as total'))
            ->groupBy('mentioned_user_id')
            ->orderByDesc('total')
            ->get();

        $users = User::whereIn('id', $queryRows->pluck('mentioned_user_id'))
            ->get(['id', 'name', 'npp', 'bagian'])
            ->keyBy('id');

        $headers = ['Peringkat', 'NPP', 'Nama Pegawai', 'Bagian', 'Jumlah Perbaikan', 'Poin Diperoleh'];
        $rows = [];
        $rank = 1;
        foreach ($queryRows as $row) {
            $user = $users->get($row->mentioned_user_id);
            $count = (int) $row->total;
            $rows[] = [
                $rank++,
                $user?->npp,
                $user?->name,
                $user?->bagian,
                $count,
                $count * self::REWARD_POINTS_PER_ACTIVITY,
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardGoCheckFinderRows(Carbon $start, Carbon $end): array
    {
        return $this->buildGoRewardEmployeeCountRows(
            $this->approvedGoCheckQuery($start, $end)
                ->select('finder_user_id', DB::raw('count(*) as total'))
                ->groupBy('finder_user_id')
                ->orderByDesc('total')
                ->with('finder:id,name,npp,bagian')
                ->get(),
            'finder',
            ['Peringkat', 'NPP', 'Nama Pegawai', 'Bagian', 'Jumlah Temuan Audit', 'Poin Diperoleh'],
            self::REWARD_POINTS_PER_ACTIVITY
        );
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardGoCheckCloserRows(Carbon $start, Carbon $end): array
    {
        return $this->buildGoRewardEmployeeCountRows(
            $this->approvedGoCheckQuery($start, $end)
                ->whereNotNull('solver_user_id')
                ->where('status_perbaikan', 'selesai')
                ->select('solver_user_id', DB::raw('count(*) as total'))
                ->groupBy('solver_user_id')
                ->orderByDesc('total')
                ->with('solver:id,name,npp,bagian')
                ->get(),
            'solver',
            ['Peringkat', 'NPP', 'Nama Pegawai', 'Bagian', 'Jumlah Penyelesaian', 'Poin Diperoleh'],
            self::REWARD_POINTS_PER_ACTIVITY
        );
    }

    /**
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardGoCareRows(Carbon $start, Carbon $end): array
    {
        return $this->buildGoRewardEmployeeCountRows(
            $this->approvedGoCareQuery($start, $end)
                ->select('user_id', DB::raw('count(*) as total'))
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->with('user:id,name,npp,bagian')
                ->get(),
            'user',
            ['Peringkat', 'NPP', 'Nama Pegawai', 'Bagian', 'Jumlah Laporan Disetujui', 'Poin Diperoleh'],
            GoCare::POINTS_PER_APPROVAL
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function buildGoRewardEmployeeCountRows(
        $rows,
        string $relation,
        array $headers,
        int $pointsPerItem
    ): array {
        $data = [];
        $rank = 1;
        foreach ($rows as $row) {
            $user = $row->{$relation};
            $count = (int) $row->total;
            $data[] = [
                $rank++,
                $user?->npp,
                $user?->name,
                $user?->bagian,
                $count,
                $count * $pointsPerItem,
            ];
        }

        return [$headers, $data];
    }

    /**
     * @param  array<int, array<string, int>>  $scores
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $counts
     */
    private function accumulateRewardPoints(array &$scores, $counts, string $key): void
    {
        foreach ($counts as $userId => $count) {
            if (! $userId) {
                continue;
            }
            if (! isset($scores[$userId])) {
                $scores[$userId] = [
                    'boost_finder' => 0,
                    'boost_solver' => 0,
                    'check_finder' => 0,
                    'check_closer' => 0,
                    'go_care' => 0,
                ];
            }
            $scores[$userId][$key] += (int) $count;
        }
    }

    private function approvedGoBoostQuery(Carbon $start, Carbon $end): Builder
    {
        $query = GoBoost::query();
        if (GoBoost::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED')
                ->whereBetween('approved_at', [$start, $end]);
        } else {
            $query->whereBetween('created_at', [$start, $end]);
        }

        return $query;
    }

    private function approvedGoCheckQuery(Carbon $start, Carbon $end): Builder
    {
        $query = GoCheck::query();
        if (GoCheck::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED')
                ->whereBetween('approved_at', [$start, $end]);
        } else {
            $query->whereBetween('created_at', [$start, $end]);
        }

        return $query;
    }

    private function approvedGoCareQuery(Carbon $start, Carbon $end): Builder
    {
        $query = GoCare::query();
        if (GoCare::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED')
                ->whereBetween('approved_at', [$start, $end]);
        } else {
            $query->whereRaw('0 = 1');
        }

        return $query;
    }

    /**
     * @param  list<array{rank: int, name: ?string, npp: ?string, total: int}>  $items
     */
    private function addRankingSheet(Spreadsheet $spreadsheet, string $title, array $items): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($this->sanitizeSheetTitle($title));
        $headers = ['Peringkat', 'Nama', 'NPP', 'Jumlah'];
        $rows = array_map(fn ($item) => [
            $item['rank'], $item['name'], $item['npp'], $item['total'],
        ], $items);
        $this->fillSheet($sheet, $headers, $rows);
    }

    /**
     * @param  array{0: list<string>, 1: list<list<mixed>>}  $data
     */
    private function addDataSheetFromExport(Spreadsheet $spreadsheet, string $title, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($this->sanitizeSheetTitle($title));
        $this->fillSheet($sheet, $data[0], $data[1]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function downloadSingleSheet(string $title, array $headers, array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->sanitizeSheetTitle($title));
        $this->fillSheet($sheet, $headers, $rows);

        return $this->streamDownload($spreadsheet, $filename);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function fillSheet(Worksheet $sheet, array $headers, array $rows): void
    {
        $sheet->fromArray([$headers], null, 'A1');
        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([$row], null, 'A'.$rowIndex);
            $rowIndex++;
        }

        $lastCol = $this->columnLetter(count($headers));
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function columnLetter(int $count): string
    {
        $letter = '';
        while ($count > 0) {
            $count--;
            $letter = chr(65 + ($count % 26)).$letter;
            $count = intdiv($count, 26);
        }

        return $letter ?: 'A';
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $title) ?? $title;

        return mb_substr(trim($title), 0, 31);
    }

    private function formatDateTime(mixed $value, string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) || is_numeric($value)) {
            try {
                return Carbon::parse((string) $value)->format($format);
            } catch (\Throwable) {
                return is_string($value) ? $value : null;
            }
        }

        if (is_object($value) && $value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        return null;
    }

    private function streamDownload(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function topGoBoostCreators(Carbon $start, Carbon $end): array
    {
        $query = GoBoost::query()->whereBetween('created_at', [$start, $end]);
        if (GoBoost::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED');
        }

        return $this->mapRanking(
            $query->select('user_id', DB::raw('count(*) as total'))
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->limit(15)
                ->with('user:id,name,npp')
                ->get(),
            'user'
        );
    }

    /**
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function topGoBoostSolvers(Carbon $start, Carbon $end): array
    {
        $query = GoBoost::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('mentioned_user_id')
            ->where('status_perbaikan', 'selesai');
        if (GoBoost::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED');
        }

        $rows = $query->select('mentioned_user_id', DB::raw('count(*) as total'))
            ->groupBy('mentioned_user_id')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $users = User::whereIn('id', $rows->pluck('mentioned_user_id'))->get()->keyBy('id');

        return $rows->values()->map(function ($row, $index) use ($users) {
            $user = $users->get($row->mentioned_user_id);

            return [
                'rank' => $index + 1,
                'name' => $user?->name,
                'npp' => $user?->npp,
                'total' => (int) $row->total,
            ];
        })->all();
    }

    /**
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function topGoCares(Carbon $start, Carbon $end): array
    {
        $query = GoCare::query();

        if (GoCare::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED')
                ->whereBetween('approved_at', [$start, $end]);
        } else {
            $query->whereRaw('0 = 1');
        }

        return $this->mapRanking(
            $query->select('user_id', DB::raw('count(*) * '.GoCare::POINTS_PER_APPROVAL.' as total'))
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->limit(15)
                ->with('user:id,name,npp')
                ->get(),
            'user'
        );
    }

    /**
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function topGoCheckFinders(Carbon $start, Carbon $end): array
    {
        $query = GoCheck::query()->whereBetween('created_at', [$start, $end]);
        if (GoCheck::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED');
        }

        return $this->mapRanking(
            $query->select('finder_user_id', DB::raw('count(*) as total'))
                ->groupBy('finder_user_id')
                ->orderByDesc('total')
                ->limit(15)
                ->with('finder:id,name,npp')
                ->get(),
            'finder'
        );
    }

    /**
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function topGoCheckSolvers(Carbon $start, Carbon $end): array
    {
        $query = GoCheck::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('solver_user_id')
            ->where('status_perbaikan', 'selesai');
        if (GoCheck::hasApprovalWorkflow()) {
            $query->where('approval_status', 'APPROVED');
        }

        return $this->mapRanking(
            $query->select('solver_user_id', DB::raw('count(*) as total'))
                ->groupBy('solver_user_id')
                ->orderByDesc('total')
                ->limit(15)
                ->with('solver:id,name,npp')
                ->get(),
            'solver'
        );
    }

    /**
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function topBagianStats(Carbon $start, Carbon $end): array
    {
        $bagianGoAction = GoAction::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');
        $bagianGoBoost = GoBoost::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');
        $bagianGoCare = GoCare::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian_temuan', DB::raw('count(*) as total'))->groupBy('bagian_temuan')->pluck('total', 'bagian_temuan');
        $bagianGoCheck = GoCheck::query()->whereBetween('created_at', [$start, $end])
            ->select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');

        $allBagian = $bagianGoAction->keys()
            ->merge($bagianGoBoost->keys())
            ->merge($bagianGoCare->keys())
            ->merge($bagianGoCheck->keys())
            ->unique()
            ->filter();

        return $allBagian->map(function ($bagian) use ($bagianGoAction, $bagianGoBoost, $bagianGoCare, $bagianGoCheck) {
            return [
                'name' => $bagian,
                'npp' => null,
                'total' => ($bagianGoAction[$bagian] ?? 0)
                    + ($bagianGoBoost[$bagian] ?? 0)
                    + ($bagianGoCare[$bagian] ?? 0)
                    + ($bagianGoCheck[$bagian] ?? 0),
            ];
        })
            ->sortByDesc('total')
            ->take(15)
            ->values()
            ->map(fn ($item, $index) => [
                'rank' => $index + 1,
                'name' => $item['name'],
                'npp' => null,
                'total' => $item['total'],
            ])
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return list<array{rank: int, name: ?string, npp: ?string, total: int}>
     */
    private function mapRanking($rows, string $relation): array
    {
        return $rows->values()->map(function ($row, $index) use ($relation) {
            $user = $row->{$relation};

            return [
                'rank' => $index + 1,
                'name' => $user?->name,
                'npp' => $user?->npp,
                'total' => (int) $row->total,
            ];
        })->all();
    }
}
