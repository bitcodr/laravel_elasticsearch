<?php   namespace Database\Factories;

use App\Models\products;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class productsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = products::class;

    /**
     * Define the model's default state.
     *
     * @return array
     * @throws Exception
     */
    public function definition()
    {
        return [
            'title' => Str::random(20),
            'size' => $this->faker->word,
            'price' => $this->faker->randomFloat(1,111,999),
            'designer' => Str::random(10),
            'summary' => $this->faker->sentence(8),
            'tags' => implode(",", $this->faker->words(6)),
            'thumbnail' => $this->faker->imageUrl(),
            'product_id' => $this->faker->biasedNumberBetween(111111,999999)
        ];
    }
}
