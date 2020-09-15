<?php namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\products;
use Elasticsearch\ClientBuilder;

class ProductsController extends Controller
{

    public function search(SearchRequest $request)
    {
        if (!empty($request->query("field"))) {
            return $this->exactOnSpecificField($request);
        }

        return $this->find($request);
    }


    private function exactOnSpecificField(SearchRequest $request)
    {
        $fieldName = $request->query("field");
        $fieldName = ($fieldName == "name") ? "title" : $fieldName;

        $searchResult = products::boolSearch()
            ->must('match', [$fieldName => $request->query("q")])
            ->execute();

        $array = [];
        foreach ($searchResult->documents() as $doc) {
            $array[] = $doc->getContent();
        }

        return $this->makeResponse($request, "products", [], [], [], $array);
    }


    private function find($request)
    {
        $params = [
            'body' => [
                ["index" => "products"],
                ["query" => ["bool" => ["must" => [["match" => ["title" => $request->query("q")]]]]]],
                ["index" => "products"],
                ["query" => ["bool" => ["must" => [["prefix" => ["title" => $request->query("q")]]]]]],
                ["index" => "products"],
                ["query" => ["bool" => ["must" => [["match" => ["title" => ["query" => $request->query("q"), "fuzziness" => 5]]]]]]],
                ["index" => "products"],
                ["query" => ["bool" => ["must" => [["match" => ["designer" => ["query" => $request->query("q"), "fuzziness" => 5]]]]]]],
                ["index" => "products"],
                ["query" => ["bool" => ["should" => [["multi_match" => ["query" => $request->query("q"), "type" => "phrase", "fields" => ["title", "designer", "summary", "tags"], "boost" => 10]], ["multi_match" => ["query" => $request->query("q"), "type" => "most_fields", "fields" => ["title", "designer", "summary", "tags"], "fuzziness" => 5]]]]]]
            ],
        ];

        $client = ClientBuilder::create()->build();
        $result = $client->msearch($params);
        return $this->flattenSearchDocument($request, $result);
    }


    private function flattenSearchDocument(SearchRequest $request, array $result)
    {
        //we performed 5 query and use the indexes to get each query data
        if (isset($result['responses']) && count($result['responses']) !== 5) {
            return response()->json(["message" => "cannot find any result"]);
        }

        $exactMatch = $result['responses'][0]["hits"]["hits"] ?? [];
        $suggestion = $result['responses'][1]["hits"]["hits"] ?? [];
        $title_typo_fix = $result['responses'][2]["hits"]["hits"] ?? [];
        $designer_typo_fix = $result['responses'][3]["hits"]["hits"] ?? [];
        $products = $result['responses'][4]["hits"]["hits"] ?? [];

        $reference = (count($exactMatch) > 0) ? "products" : (
            (count($suggestion) > 0) ? "suggestion" : (
                (count($title_typo_fix) > 0 || count($designer_typo_fix) > 0) ? "did_you_mean" : ""
            )
        );

        return $this->makeResponse($request,
            $reference,
            (count($exactMatch) == 0) ? array_column($suggestion, "_source") : [],
            (count($exactMatch) == 0 && count($suggestion) == 0) ? array_column($designer_typo_fix, "_source") : [],
            (count($exactMatch) == 0 && count($suggestion) == 0) ? array_column($title_typo_fix, "_source") : [],
            (count($exactMatch) > 0) ? array_column($exactMatch, "_source")
                : array_column($products, "_source"));
    }


    //TODO use laravel resources and collections
    private function makeResponse(SearchRequest $request,
                                  string $reference,
                                  array $suggestion,
                                  array $designers,
                                  array $names,
                                  array $products)
    {
        return response()->json([
            "query" => $request->query("q"),
            'reference' => $reference,
            'suggestion' => $suggestion,
            'did_you_mean' => [
                'designers' => $designers,
                'names' => $names
            ],
            'products' => $products,
        ]);
    }

}
