<?php
// app/Http/Controllers/ExportController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\HtmlTemplateExport; // Your custom export class

class ExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $request->validate([
            'html' => 'required|string',
            'filename' => 'sometimes|string'
        ]);

        $html = $request->input('html');
        $filename = $request->input('filename', 'export_' . date('Y-m-d') . '.xlsx');

        return Excel::download(
            new HtmlTemplateExport($html, 'Custom Report'),
            $filename
        );
    }
}
