<?php namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\SearchRequest;
use App\Models\products;
use ElasticScoutDriverPlus\SearchResult;

class ProductsController extends Controller
{
    public function insert(ProductRequest $request)
    {
        $product = new products();

        $product->title = $request->input("title");
        $product->size = $request->input("size");
        $product->price = $request->input("price");
        $product->designer = $request->input("designer");
        $product->summary = $request->input("summary");
        $product->thumbnail = $request->input("thumbnail");
        $product->product_id = $request->input("product_id");
        $product->tags = $request->input("tags");

        $product->save();
    }

    //TODO create an elastic helper and improve queries, one query at once
    public function search(SearchRequest $request)
    {
        if (!empty($request->query("field"))) {
            return $this->exactOnSpecificField($request);
        }

        $searchResult = $this->exactMatch($request);

        if ($searchResult->total() < 1) {

            $searchResult = $this->prefixMatch($request);
            $array = $searchResult->models()->pluck("title")->all();
            $fuzzyProducts = $this->fuzzyProductsMatch($request);

            if ($searchResult->total() < 1) {

                $designerSuggest = $this->suggestionMatch('designer', $request);
                $designers = $designerSuggest->models()->pluck("designer")->all();

                $titleSuggest = $this->suggestionMatch('title', $request);
                $titles = $titleSuggest->models()->pluck("title")->all();

                return $this->makeResponse($request, "did_you_mean", [], $designers, $titles, ...$fuzzyProducts);
            }

            return $this->makeResponse($request, "suggestion", $array, [], [], ...$fuzzyProducts);
        }

        return $this->makeResponse($request, "products", [], [], [], ...$searchResult);
    }


    private function exactOnSpecificField(SearchRequest $request)
    {
        switch ($request->query("field")) {
            case "designer":
                $fieldName = "designer";
                break;
            case "name":
                $fieldName = "title";
                break;
            default:
                return response()->json(["message" => "field must be one of the designer or name"]);
        }

        $searchResult = products::boolSearch()
            ->must('match', [$fieldName => $request->query("q")])
            ->execute();
        return $this->makeResponse($request, "products", [], [], [], ...$searchResult);
    }

    private function exactMatch(SearchRequest $request): SearchResult
    {
        return products::boolSearch()
            ->must('match', ['title' => $request->query("q")])
            ->execute();
    }

    private function prefixMatch(SearchRequest $request): SearchResult
    {
        return products::boolSearch()
            ->must('prefix', ['title' => $request->query("q")])
            ->execute();
    }

    private function suggestionMatch(string $field, SearchRequest $request): SearchResult
    {
        return products::boolSearch()
            ->mustRaw(['match', [$field => [
                'query' => $request->query("q"),
                'fuzziness' => 5
            ]]])
            ->execute();
    }

    private function fuzzyProductsMatch(SearchRequest $request): SearchResult
    {
        return products::boolSearch()
            ->shouldRaw([[
                'multi_match' => [
                    "query" => $request->query("q"),
                    "type" => "phrase",
                    "fields" => [
                        "title",
                        "designer",
                        "summary",
                        "tags"
                    ],
                    "boost" => 10
                ]
            ],
                [
                    'multi_match' => [
                        "query" => $request->query("q"),
                        "type" => "most_fields",
                        "fields" => [
                            "title",
                            "designer",
                            "summary",
                            "tags"
                        ],
                        "fuzziness" => 5
                    ]
                ]])->execute();
    }

    //TODO use laravel resources and collections
    private function makeResponse(SearchRequest $request,
                                  string $reference,
                                  array $suggestion,
                                  array $designers,
                                  array $names,
                                  products ...$products)
    {
        return resposne()->json([
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
