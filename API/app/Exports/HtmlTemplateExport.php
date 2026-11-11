<?php
// app/Exports/HtmlTemplateExport.php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class HtmlTemplateExport implements FromView
{
    protected $html;
    protected $title;

    public function __construct($html, $title = 'Report')
    {
        $this->html = $html;
        $this->title = $title;
    }

    public function view(): View
    {
        return view('exports.template', [
            'html' => $this->html,
            'title' => $this->title
        ]);
    }
}
