<?php

namespace App\Http\Controllers;

use Elasticquent\ElasticquentTrait;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    use ElasticquentTrait;

    const RECORDS_PER_PAGE = 10;
    const HTTP_HEADER_PARAMS = [
        "headers" => [
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ]
    ];
    const ELASTICSEARCH_CONTAINER_URL = "http://localhost:9200";

    /**
     * Makes API call to our ElasticSearch Docker container
     * and returns search results. You may do a dump and die
     * "dd()" just to see the format ElasticSearch uses for search
     * results. We'll still need to pass the result to a
     * function (formatElasticSearchResult) which returns
     * exactly what we need.
     *
     * @param Request $request
     * @return object
     */
    public function queryElasticSearch(Request $request)
    {
        $httpClient = new Client();
        $pagination = $this->getPagination($request->page);

        // Prepare request body
        $body = $this->prepareBody($pagination, $request->search_phrase);


        // Make API call
        $response = $httpClient->get(
            $this->getIndexUrl()->customerIndex,
            ["header" => self::HTTP_HEADER_PARAMS, "json" => $body]
        );

        // Get response body
        $searchResult = json_decode($response->getBody()->getContents());

        // Format result
        $searchResult = $this->formatElasticSearchResult($searchResult);
        // Send API response
        return $this->apiResponse($searchResult, $pagination);
    }

    /**
     * Calculates and returns pagination parameters.
     *
     * @param int $requestPage
     * @return array
     */
    private function getPagination($requestPage)
    {
        // If no page is supplied in request, default to page 1
        $page = $requestPage ? $requestPage : 1;
        // If page is 1, then starting point for search result
        // equals 0. Thus, the search result returned will be from
        // index 0 to 9 based on 10 RECORDS_PER_PAGE
        $startFrom = self::RECORDS_PER_PAGE * ($page - 1);

        // Return pagination array
        return [
            "page" => $page,
            "startFrom" => $startFrom,
            "recordsPerPage" => self::RECORDS_PER_PAGE
        ];
    }

    /**
     * Prepares body for a Guzzle-implemented API call based on
     * ElasticSearch Query DSL.
     *
     * @param array $pagination
     * @param string $searchPhrase
     * @return array
     */
    private function prepareBody($pagination, $searchPhrase)
    {
        return [
            "from" => $pagination['startFrom'],
            "size" => $pagination['recordsPerPage'],
            "query" => [
                "multi_match" => [
                    "query" => $searchPhrase
                ]
            ]
        ];
    }

    /**
     * Method contains an array of urls for all indices
     * available in our ElasticSearch application. When you
     * have other indices such as "order_index", just add it
     * to the array.
     *
     * @return object
     */
    private function getIndexUrl()
    {
        return (object)[
            "all" => self::ELASTICSEARCH_CONTAINER_URL . "/_all/_search",
            "customerIndex" => self::ELASTICSEARCH_CONTAINER_URL . "/customer_index"
        ];
    }

    /**
     * Formats ElasticSearch result to fit into our specification.
     *
     * @param $searchResult
     * @return array
     */
    private function formatElasticSearchResult($searchResult)
    {
        if (isset($searchResult->hits)) {
            $searchResultArray = $searchResult->hits->hits;
            $searchResultCount = $searchResult->hits->total->value;

            $formattedSearchResult = [];

            // Loop through search result array
            foreach ($searchResultArray as $bundle) {
                array_push($formattedSearchResult, $bundle->_source);
            }

            return $formattedSearchResult;
        }

        return  $searchResult;
    }

    /**
     * Creates an API response format for
     * search results, including pagination details
     *
     * @param array $formattedSearchResult
     * @param array $pagination
     * @param int $searchResultCount
     * @return JsonResponse
     */
    private function apiResponse($formattedSearchResult, $pagination)
    {
        return response()->json([
            "status" => Response::HTTP_OK,
            "message" => "Search was successful",
            "page" => $pagination['page'],
            "numerOfRecordsPerPage" => $pagination['recordsPerPage'],
            "startFrom" => $pagination['startFrom'],
//            "total_pages" => ceil(count($formattedSearchResult) / $pagination['recordsPerPage']),
//            "total_results" => count($formattedSearchResult),
            "data" => $formattedSearchResult
        ]);
    }
}
