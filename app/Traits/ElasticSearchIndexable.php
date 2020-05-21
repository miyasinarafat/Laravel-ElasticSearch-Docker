<?php

namespace App\Traits;

use App\Customer;
use Elasticquent\ElasticquentTrait;
use Exception;

trait ElasticSearchIndexable
{
    use ElasticquentTrait;

    public function index()
    {
        $this->info('Deleting customer index..');
        $this->deleteCustomerIndex();
        $this->info('Counting number of rows to be indexed..');

        $number_of_rows = $this->selectCustomerTable()->count();

        // Index progress report
        $this->info('Rows: ' . $number_of_rows);
        $this->info('Indexing customer table..');

        try {
            $this->selectCustomerTable()->chunk(5000, function ($rows) {
                $rows->addToindex();
            });

            // Index progress report
            $this->info("Indexed {$number_of_rows} rows");
            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function deleteCustomerIndex()
    {
        try {
            Customer::deleteIndex();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function selectCustomerTable()
    {
        return Customer::select(['company', 'last_name', 'first_name', 'email_address', 'job_title']);
    }
}
