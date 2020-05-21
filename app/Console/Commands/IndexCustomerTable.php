<?php

namespace App\Console\Commands;

use App\Traits\ElasticSearchIndexable;
use Illuminate\Console\Command;

class IndexCustomerTable extends Command
{
    use ElasticSearchIndexable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:customer-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert Customer table into an Elasticsearch index';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('ElasticSearch Indexer has started..');
        $status = $this->index();

        if ($status == 'success') {
            $this->info('Customer table was successfully indexed');
        } else {
            $this->error($status);
        }
    }
}
