<?php namespace Tests\Feature;

use Tests\TestCase;
use App\Models\products;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = products::factory()->createOne()->toArray();
        sleep(1); //necessary for syncing
        Artisan::call('scout:import \'App\\\\Models\\\\products\''); //in case may search index isn't update
    }

    public function testInsert()
    {
        $product = products::factory()->makeOne()->toArray();
        return $this->postJson('/api/product', $product)->assertCreated();
    }

    public function testSearchExactMatchWithFieldTitle()
    {
        $case = [
            "entry" => "?q=" . $this->data["title"] . "&field=title",
            "want" => [
                "query" => $this->data["title"],
                "reference" => "products",
                "suggestion" => [],
                "did_you_mean" => [
                    "designers" => [],
                    "names" => []
                ],
                "products" => [
                    $this->data
                ]
            ]
        ];

        $response = $this->get('/api/search' . $case["entry"]);
        $response->assertJson($case["want"]);
        $response->assertStatus(200);
    }


    public function testSearchExactMatchWithFieldDesigner()
    {
        $case = [
            "name" => "exactMatchWithFieldDesigner",
            "entry" => "?q=" . $this->data["designer"] . "&field=designer",
            "want" => [
                "query" => $this->data["designer"],
                "reference" => "products",
                "suggestion" => [],
                "did_you_mean" => [
                    "designers" => [],
                    "names" => []
                ],
                "products" => [
                    $this->data
                ]
            ]
        ];

        $response = $this->get('/api/search' . $case["entry"]);
        $response->assertJson($case["want"]);
        $response->assertStatus(200);
    }


    public function testSearchExactMatch()
    {
        $case = [
            "name" => "exactMatch",
            "entry" => "?q=" . $this->data["title"],
            "want" => [
                "query" => $this->data["title"],
                "reference" => "products",
                "suggestion" => [],
                "did_you_mean" => [
                    "designers" => [],
                    "names" => []
                ],
                "products" => [
                    $this->data
                ]
            ]
        ];

        $response = $this->get('/api/search' . $case["entry"]);
        $response->assertJson($case["want"]);
        $response->assertStatus(200);
    }

    public function testSearchSuggestion()
    {
        $case = [
            "name" => "suggestion",
            "entry" => "?q=" . substr($this->data["title"], 0, 3),
            "want" => [
                "query" => $this->data["title"],
                "reference" => "suggestion",
                "suggestion" => [
                    $this->data["title"]
                ],
                "did_you_mean" => [
                    "designers" => [],
                    "names" => []
                ],
                "products" => [
                    $this->data
                ]
            ]
        ];

        $response = $this->get('/api/search' . $case["entry"]);
        $response->assertSee($case["want"]["query"])
            ->assertSee($case["want"]["reference"]);
        $response->assertStatus(200);
    }

    public function testSearchDidYouMeanName()
    {
        $case = [
            "name" => "did_you_mean_name",
            "entry" => "?q=" . $this->data["title"] . " " . Str::random(3),
            "want" => [
                "query" => $this->data["title"],
                "reference" => "did_you_mean",
                "suggestion" => [],
                "did_you_mean" => [
                    "designers" => [],
                    "names" => [
                        $this->data["title"]
                    ]
                ],
                "products" => [
                    $this->data
                ]
            ]
        ];

        $response = $this->get('/api/search' . $case["entry"]);
        $response->assertSee($case["want"]["query"])
            ->assertSee($case["want"]["reference"]);
        $response->assertStatus(200);
    }

    public function testSearchDidYouMeanDesigner()
    {
        $case = [
            "name" => "did_you_mean_designer",
            "entry" => "?q=" . $this->data["designer"] . " " . Str::random(3),
            "want" => [
                "query" => $this->data["designer"],
                "reference" => "did_you_mean",
                "suggestion" => [],
                "did_you_mean" => [
                    "designers" => [
                        $this->data["designer"]
                    ],
                    "names" => []
                ],
                "products" => [
                    $this->data
                ]
            ]
        ];

        $response = $this->get('/api/search' . $case["entry"]);
        $response->assertSee($case["want"]["query"])
            ->assertSee($case["want"]["reference"]);
        $response->assertStatus(200);
    }


}
