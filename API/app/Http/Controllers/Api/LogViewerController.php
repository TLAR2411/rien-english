<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    public function list(Request $request)
    {
        try {
            $logPath = storage_path('logs/laravel.log');

            if (! File::exists($logPath)) {
                // Return an empty but correctly structured response
                return response()->json([
                    'status' => true,
                    'data' => [
                        'data' => [],
                        'links' => [
                            'first' => $request->url().'?page=1',
                            'last' => $request->url().'?page=1',
                            'prev' => null,
                            'next' => null,
                        ],
                        'meta' => [
                            'current_page' => 1,
                            'from' => null,
                            'last_page' => 1,
                            'links' => [
                                [
                                    'url' => null,
                                    'label' => '&laquo; Previous',
                                    'page' => null,
                                    'active' => false,
                                ],
                                [
                                    'url' => $request->url().'?page=1',
                                    'label' => '1',
                                    'page' => 1,
                                    'active' => true,
                                ],
                                [
                                    'url' => null,
                                    'label' => 'Next &raquo;',
                                    'page' => null,
                                    'active' => false,
                                ],
                            ],
                            'path' => $request->url(),
                            'per_page' => 15,
                            'to' => null,
                            'total' => 0,
                        ],
                    ],
                ]);
            }

            // 1. Parse logs into an array
            $logContent = File::get($logPath);
            $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*(?:\n(?!\[\d{4}-\d{2}-\d{2}).*)*)/m';
            preg_match_all($pattern, $logContent, $matches, PREG_SET_ORDER);

            $logs = [];
            $idCounter = 1;
            foreach ($matches as $match) {
                $logs[] = [
                    'id' => $idCounter++,
                    'timestamp' => $match[1],
                    'env' => $match[2],
                    'level' => $match[3],
                    'message' => htmlspecialchars(trim($match[4])),
                ];
            }
            $logs = array_reverse($logs);

            // 2. Dynamic Searching on the array
            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $logs = array_filter($logs, function ($log) use ($searchValue) {
                    return stripos($log['timestamp'], $searchValue) !== false ||
                        stripos($log['env'], $searchValue) !== false ||
                        stripos($log['level'], $searchValue) !== false ||
                        stripos($log['message'], $searchValue) !== false;
                });
                $logs = array_values($logs); // Re-index array
            }

            // 3. Dynamic Sorting on the array
            if ($request->has('order')) {
                $columnIndex = $request->input('order.0.column');
                $columnName = $request->input("columns.{$columnIndex}.data");
                $sortDir = $request->input('order.0.dir', 'asc');

                if ($columnName && isset($logs[0][$columnName])) {
                    usort($logs, function ($a, $b) use ($columnName, $sortDir) {
                        return $sortDir === 'asc' ? ($a[$columnName] <=> $b[$columnName]) : ($b[$columnName] <=> $a[$columnName]);
                    });
                }
            }

            // 4. Manually paginate
            $perPage = (int) $request->input('limit', 15);
            $currentPage = (int) $request->input('page', 1);
            $total = count($logs);
            $lastPage = (int) ceil($total / $perPage);
            $currentPage = min($currentPage, max(1, $lastPage)); // Ensure current page is valid

            // Calculate from and to
            $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : null;
            $to = $total > 0 ? min($currentPage * $perPage, $total) : null;

            // Slice the array to get the items for the current page
            $currentPageItems = array_slice($logs, ($currentPage - 1) * $perPage, $perPage);

            // Build pagination links
            $links = [];

            // Previous link
            $links[] = [
                'url' => $currentPage > 1 ? $request->url().'?page='.($currentPage - 1) : null,
                'label' => '&laquo; Previous',
                'page' => $currentPage > 1 ? $currentPage - 1 : null,
                'active' => false,
            ];

            // Page number links (show up to 10 pages)
            $startPage = max(1, $currentPage - 5);
            $endPage = min($lastPage, $currentPage + 4);

            for ($i = $startPage; $i <= $endPage; $i++) {
                $links[] = [
                    'url' => $request->url().'?page='.$i,
                    'label' => (string) $i,
                    'page' => $i,
                    'active' => $i === $currentPage,
                ];
            }

            // Next link
            $links[] = [
                'url' => $currentPage < $lastPage ? $request->url().'?page='.($currentPage + 1) : null,
                'label' => 'Next &raquo;',
                'page' => $currentPage < $lastPage ? $currentPage + 1 : null,
                'active' => false,
            ];

            // 5. Build response structure matching the user list format
            $response = [
                'status' => true,
                'data' => [
                    'data' => $currentPageItems,
                    'links' => [
                        'first' => $request->url().'?page=1',
                        'last' => $request->url().'?page='.$lastPage,
                        'prev' => $currentPage > 1 ? $request->url().'?page='.($currentPage - 1) : null,
                        'next' => $currentPage < $lastPage ? $request->url().'?page='.($currentPage + 1) : null,
                    ],
                    'meta' => [
                        'current_page' => $currentPage,
                        'from' => $from,
                        'last_page' => $lastPage,
                        'links' => $links,
                        'path' => $request->url(),
                        'per_page' => $perPage,
                        'to' => $to,
                        'total' => $total,
                    ],
                ],
            ];

            return response()->json($response);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
