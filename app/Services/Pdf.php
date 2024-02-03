<?php
namespace App\Services;
use App;
use App\Invoice;
use Knp\Snappy\Pdf as Snappy;
use Illuminate\Support\Facades\View;

class Pdf extends Snappy
{
    /**
     * Initialize a new pdf instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        parent::__construct(config('pdf.binary'), config('pdf.generator'));
    }

    /**
     * Render the PDF preview.
     *
     * @param  \App\Invoice  $invoice
     * @return string
     */
    public function render()
    {
        return $this->getOutputFromHtml(
            View::make('candidate_part')->render()
        );
    }
}

?>