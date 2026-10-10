<?php

namespace Database\Seeders;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::all()->keyBy('slug');
        $categories = Category::all()->keyBy('slug');

        $products = [
            // 1. Dior Sauvage
            [
                'brand' => 'dior',
                'category' => 'men',
                'name' => 'Dior Sauvage Eau de Parfum',
                'slug' => 'dior-sauvage-edp',
                'short_description' => 'A radically fresh composition dictated by a name that has the ring of a manifesto.',
                'description' => 'The powerful freshness of Sauvage exudes new sensual and mysterious facets, amply renewing itself with the signature of an ingenious composition. Calabrian bergamot, as juicy and spirited as ever, invites new spicy notes to heighten fullness and sensuality.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Calabrian Bergamot, Spicy Pepper',
                'heart_notes' => 'Sichuan Pepper, Lavender, Star Anise, Nutmeg',
                'base_notes' => 'Ambroxan, Papua New Guinean Vanilla, Cedarwood',
                'scent_family' => ScentFamily::FRESH,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 60, 'price' => 18500, 'compare_at' => 20500, 'stock' => 12],
                    ['size_ml' => 100, 'price' => 24500, 'compare_at' => 27000, 'stock' => 18],
                    ['size_ml' => 200, 'price' => 36500, 'compare_at' => null, 'stock' => 6],
                ],
            ],

            // 2. Dior Homme Intense
            [
                'brand' => 'dior',
                'category' => 'men',
                'name' => 'Dior Homme Intense',
                'slug' => 'dior-homme-intense',
                'short_description' => 'The ultimate noble and powdery masculine fragrance with Tuscan iris.',
                'description' => 'Dior Homme Intense reveals the extreme grandeur and sophistication of a generous and powerful eau de parfum. The olfactory intensity of iris is enhanced by a sensual amber facet and precious wood base.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Lavender, Sage',
                'heart_notes' => 'Tuscan Iris, Ambrette Seed, Pear',
                'base_notes' => 'Virginia Cedar, Haitian Vetiver',
                'scent_family' => ScentFamily::WOODY,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 19000, 'compare_at' => null, 'stock' => 10],
                    ['size_ml' => 100, 'price' => 26000, 'compare_at' => 29000, 'stock' => 14],
                ],
            ],

            // 3. Miss Dior
            [
                'brand' => 'dior',
                'category' => 'women',
                'name' => 'Miss Dior Eau de Parfum',
                'slug' => 'miss-dior-edp',
                'short_description' => 'A velvet floral bouquet blooming with thousands of colorful flowers.',
                'description' => 'Miss Dior Eau de Parfum celebrates the roses of Grasse enveloped in a delicate lace of lily-of-the-valley and peony, warmed by sensual woods and velvety comforting musks.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Lily-of-the-Valley, Peony, Iris',
                'heart_notes' => 'Centifolia Rose, Peach, Apricot',
                'base_notes' => 'Vanilla, Tonka Bean, White Musk, Sandalwood',
                'scent_family' => ScentFamily::FLORAL,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 17500, 'compare_at' => 19500, 'stock' => 8],
                    ['size_ml' => 100, 'price' => 23500, 'compare_at' => 26000, 'stock' => 15],
                ],
            ],

            // 4. Bleu de Chanel Parfum
            [
                'brand' => 'chanel',
                'category' => 'men',
                'name' => 'Bleu de Chanel Parfum',
                'slug' => 'bleu-de-chanel-parfum',
                'short_description' => 'An ode to masculine freedom in an aromatic, intensely woody fragrance.',
                'description' => 'A captivating trail in an enigmatic blue bottle. Bleu de Chanel Parfum accomplishes a pure and profound statement of confidence and elegance with aromatic mint, deep cedar, and New Caledonian sandalwood.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::PARFUM,
                'top_notes' => 'Lemon Zest, Bergamot, Mint, Artemisia',
                'heart_notes' => 'Lavender, Geranium, Green Notes, Pineapple',
                'base_notes' => 'New Caledonian Sandalwood, Cedar, Amberwood, Tonka Bean',
                'scent_family' => ScentFamily::WOODY,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 22000, 'compare_at' => null, 'stock' => 8],
                    ['size_ml' => 100, 'price' => 29500, 'compare_at' => 32000, 'stock' => 16],
                ],
            ],

            // 5. Coco Mademoiselle
            [
                'brand' => 'chanel',
                'category' => 'women',
                'name' => 'Chanel Coco Mademoiselle',
                'slug' => 'chanel-coco-mademoiselle',
                'short_description' => 'The essence of a bold, free woman in an oriental ambery fragrance with a strong personality.',
                'description' => 'Sparks of fresh and vibrant orange immediately awaken the senses. A clear and sensual heart reveals the transparent accords of Grasse Jasmine and May Rose before unwinding into clean patchouli.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Orange, Bergamot, Mandarin Orange',
                'heart_notes' => 'Turkish Rose, Jasmine, Mimosa, Ylang-Ylang',
                'base_notes' => 'Patchouli, White Musk, Vanilla, Vetiver',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 21000, 'compare_at' => 23000, 'stock' => 10],
                    ['size_ml' => 100, 'price' => 28000, 'compare_at' => 31000, 'stock' => 20],
                ],
            ],

            // 6. Chanel No. 5
            [
                'brand' => 'chanel',
                'category' => 'women',
                'name' => 'Chanel No. 5 Eau de Parfum',
                'slug' => 'chanel-no-5-edp',
                'short_description' => 'The very essence of femininity. An iconic aldehyde floral bouquet.',
                'description' => 'Created in 1921, Chanel No. 5 was the revolutionary first fragrance to feature aldehydes, creating a rich multi-faceted floral bouquet with May rose, neroli, and bourbon vanilla.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Aldehydes, Ylang-Ylang, Neroli, Bergamot, Peach',
                'heart_notes' => 'Iris, Jasmine, Lily-of-the-Valley, May Rose',
                'base_notes' => 'Sandalwood, Vanilla, Oakmoss, Vetiver, Patchouli',
                'scent_family' => ScentFamily::FLORAL,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 21500, 'compare_at' => null, 'stock' => 6],
                    ['size_ml' => 100, 'price' => 29000, 'compare_at' => null, 'stock' => 10],
                ],
            ],

            // 7. Tom Ford Tobacco Vanille
            [
                'brand' => 'tom-ford',
                'category' => 'unisex',
                'name' => 'Tom Ford Tobacco Vanille',
                'slug' => 'tom-ford-tobacco-vanille',
                'short_description' => 'Opulent, warm, and iconic. Reminiscent of an English gentlemans club.',
                'description' => 'Tom Ford reinvents classic tobacco with creamy tonka bean, vanilla, cocoa, dry fruit accords, and sweet wood sap for a modern, opulent, and heady impression.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Tobacco Leaf, Spicy Aromatic Notes',
                'heart_notes' => 'Tonka Bean, Tobacco Blossom, Vanilla, Cacao',
                'base_notes' => 'Dry Fruit Accord, Woody Notes',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 26500, 'compare_at' => 29000, 'stock' => 8],
                    ['size_ml' => 100, 'price' => 36000, 'compare_at' => 39500, 'stock' => 12],
                ],
            ],

            // 8. Tom Ford Oud Wood
            [
                'brand' => 'tom-ford',
                'category' => 'oud-arabian',
                'name' => 'Tom Ford Oud Wood',
                'slug' => 'tom-ford-oud-wood',
                'short_description' => 'Rare, exotic, and distinctive. One of the most precious ingredients in perfumery.',
                'description' => 'Smoky incense-filled temples and a passion for rare, precious oud wood inspire Tom Ford’s groundbreaking composition of exotic rosewood, cardamom, and Chinese pepper.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Rosewood, Cardamom, Chinese Pepper',
                'heart_notes' => 'Oud Wood, Sandalwood, Vetiver',
                'base_notes' => 'Tonka Bean, Vanilla, Amber',
                'scent_family' => ScentFamily::WOODY,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 27000, 'compare_at' => null, 'stock' => 7],
                    ['size_ml' => 100, 'price' => 37000, 'compare_at' => 41000, 'stock' => 11],
                ],
            ],

            // 9. Tom Ford Black Orchid
            [
                'brand' => 'tom-ford',
                'category' => 'unisex',
                'name' => 'Tom Ford Black Orchid Parfum',
                'slug' => 'tom-ford-black-orchid-parfum',
                'short_description' => 'A deeply seductive and luxurious elixir of black truffle and ylang-ylang.',
                'description' => 'Black Orchid Parfum celebrates the impact of the seductive breakthrough with an amplified signature of black truffle, golden rum, and hypnotic black orchid flowers.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::PARFUM,
                'top_notes' => 'Black Truffle, Ylang-Ylang, Blackcurrant, Bergamot',
                'heart_notes' => 'Black Orchid, Floral Accords, Rich Spices, Golden Rum',
                'base_notes' => 'Patchouli, Incense, Amber, Vetiver, Vanilla',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 23000, 'compare_at' => 25000, 'stock' => 10],
                    ['size_ml' => 100, 'price' => 31000, 'compare_at' => 34000, 'stock' => 14],
                ],
            ],

            // 10. Tom Ford Lost Cherry
            [
                'brand' => 'tom-ford',
                'category' => 'women',
                'name' => 'Tom Ford Lost Cherry',
                'slug' => 'tom-ford-lost-cherry',
                'short_description' => 'A full-bodied journey into the once-forbidden; an intoxicating contrast of sweet and tart.',
                'description' => 'Luscious black cherry drenched in cherry liqueur and bitter almond yields to a Turkish rose heart and rich roasted tonka bean base.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Black Cherry, Cherry Liqueur, Bitter Almond',
                'heart_notes' => 'Griotte Syrup, Turkish Rose, Jasmine Sambac',
                'base_notes' => 'Peru Balsam, Roasted Tonka, Sandalwood, Vetiver, Cedar',
                'scent_family' => ScentFamily::GOURMAND,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 28500, 'compare_at' => 32000, 'stock' => 6],
                    ['size_ml' => 100, 'price' => 39000, 'compare_at' => null, 'stock' => 8],
                ],
            ],

            // 11. Creed Aventus
            [
                'brand' => 'creed',
                'category' => 'men',
                'name' => 'Creed Aventus',
                'slug' => 'creed-aventus',
                'short_description' => 'The King of Men’s Fragrances. A legendary blend of pineapple, birch, and oakmoss.',
                'description' => 'Sensual, audacious, and contemporary, Creed Aventus celebrates strength, power, and success. Packed with luscious apple, blackcurrant, pineapple, and rich smoky birch wood.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Pineapple, Bergamot, Blackcurrant, Apple',
                'heart_notes' => 'Birch, Patchouli, Moroccan Jasmine, Rose',
                'base_notes' => 'Musk, Oakmoss, Ambergris, Vanilla',
                'scent_family' => ScentFamily::FRESH,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 28000, 'compare_at' => 31000, 'stock' => 10],
                    ['size_ml' => 100, 'price' => 38000, 'compare_at' => 42000, 'stock' => 15],
                ],
            ],

            // 12. Creed Silver Mountain Water
            [
                'brand' => 'creed',
                'category' => 'unisex',
                'name' => 'Creed Silver Mountain Water',
                'slug' => 'creed-silver-mountain-water',
                'short_description' => 'Inspired by the exhilarating crispness of alpine mountain air.',
                'description' => 'Invigorating and clean, Silver Mountain Water captures the purity of sparkling streams flowing across snow-capped Swiss mountains with green tea, blackcurrant, and sandalwood.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Bergamot, Mandarin Orange',
                'heart_notes' => 'Green Tea, Blackcurrant',
                'base_notes' => 'Musk, Petitgrain, Sandalwood, Galbanum',
                'scent_family' => ScentFamily::FRESH,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 26000, 'compare_at' => null, 'stock' => 8],
                    ['size_ml' => 100, 'price' => 35000, 'compare_at' => 38000, 'stock' => 12],
                ],
            ],

            // 13. Creed Green Irish Tweed
            [
                'brand' => 'creed',
                'category' => 'men',
                'name' => 'Creed Green Irish Tweed',
                'slug' => 'creed-green-irish-tweed',
                'short_description' => 'A true classic gentleman’s fougère evoking lush emerald country estates.',
                'description' => 'Sophisticated, classic, and fresh. Green Irish Tweed blends crisp peppermint and lemon verbena with violet leaves and ambergris for an unmistakably aristocratic impression.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Lemon Verbena, Peppermint',
                'heart_notes' => 'Violet Leaves, Florentine Iris',
                'base_notes' => 'Sandalwood, Ambergris, Cedarwood',
                'scent_family' => ScentFamily::FRESH,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 26000, 'compare_at' => null, 'stock' => 6],
                    ['size_ml' => 100, 'price' => 34000, 'compare_at' => 37000, 'stock' => 9],
                ],
            ],

            // 14. MFK Baccarat Rouge 540
            [
                'brand' => 'maison-francis-kurkdjian',
                'category' => 'unisex',
                'name' => 'Maison Francis Kurkdjian Baccarat Rouge 540',
                'slug' => 'mfk-baccarat-rouge-540',
                'short_description' => 'The world’s most coveted niche scent. Luminous, dense, and intense.',
                'description' => 'Baccarat Rouge 540 lays on the skin like an amber, floral, and woody breeze. Aerial notes of jasmine and radiant saffron carry mineral facets of ambergris and cedarwood.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Jasmine Grandiflorum, Saffron',
                'heart_notes' => 'Bitter Almond, Cedarwood',
                'base_notes' => 'Ambergris, Musky Woody Accord',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 70, 'price' => 29500, 'compare_at' => 33000, 'stock' => 14],
                    ['size_ml' => 200, 'price' => 52000, 'compare_at' => null, 'stock' => 5],
                ],
            ],

            // 15. MFK Grand Soir
            [
                'brand' => 'maison-francis-kurkdjian',
                'category' => 'unisex',
                'name' => 'Maison Francis Kurkdjian Grand Soir',
                'slug' => 'mfk-grand-soir',
                'short_description' => 'An invitation to experience the brilliant, gilded nights of Paris.',
                'description' => 'Dress in your finest attire and polish your look. Lose yourself in an endless night in a bustling Paris where bridges and monuments shine with golden light. Deep amber, vanilla, and benzoin.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Spanish Labdanum',
                'heart_notes' => 'Benzoin from Siam, Lavender Accord',
                'base_notes' => 'Brazilian Tonka Bean, Amber Accord, Vanilla',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 70, 'price' => 27500, 'compare_at' => 30000, 'stock' => 10],
                    ['size_ml' => 200, 'price' => 49000, 'compare_at' => null, 'stock' => 4],
                ],
            ],

            // 16. MFK Oud Satin Mood
            [
                'brand' => 'maison-francis-kurkdjian',
                'category' => 'oud-arabian',
                'name' => 'MFK Oud Satin Mood',
                'slug' => 'mfk-oud-satin-mood',
                'short_description' => 'Flowing silk and rich oriental oud. A sensual curtain of pure luxury.',
                'description' => 'Oud Satin Mood conveys the desire to bring a shimmering Orient to life. Enwrap yourself in woody amber and natural oud from Laos blended with violet and two luxurious roses.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Violet Accord, Geranium',
                'heart_notes' => 'Damask Rose, Centifolia Rose, Oud from Laos',
                'base_notes' => 'Amber, Vanilla Accord, Benzoin',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 70, 'price' => 31000, 'compare_at' => 35000, 'stock' => 8],
                    ['size_ml' => 200, 'price' => 54000, 'compare_at' => null, 'stock' => 3],
                ],
            ],

            // 17. Lattafa Khamrah
            [
                'brand' => 'lattafa',
                'category' => 'unisex',
                'name' => 'Lattafa Khamrah',
                'slug' => 'lattafa-khamrah',
                'short_description' => 'Addictive boozy gourmand of warm cinnamon, sweet dates, and melted praline.',
                'description' => 'One of the most viral perfumes in modern history. Khamrah is a luxurious oriental-spicy unisex fragrance that blends precious spices, woodiness, and warm vanilla praline in an ornate crystal flacon.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Cinnamon, Nutmeg, Bergamot',
                'heart_notes' => 'Dates, Praline, Tuberose, Mahonial',
                'base_notes' => 'Vanilla, Tonka Bean, Benzoin, Myrrh, Amberwood, Akigalawood',
                'scent_family' => ScentFamily::GOURMAND,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 3600, 'compare_at' => 4200, 'stock' => 15],
                    ['size_ml' => 100, 'price' => 5200, 'compare_at' => 6000, 'stock' => 35],
                ],
            ],

            // 18. Lattafa Asad
            [
                'brand' => 'lattafa',
                'category' => 'men',
                'name' => 'Lattafa Asad',
                'slug' => 'lattafa-asad',
                'short_description' => 'Bold masculine oriental spiced elixir with juicy pineapple and dark roasted coffee.',
                'description' => 'Asad by Lattafa is a signature vanilla and spicy fragrance with notes of tobacco, black pepper, and rich coffee. Long-lasting with an incredible sillage that turns heads everywhere.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Black Pepper, Pineapple, Tobacco',
                'heart_notes' => 'Coffee, Patchouli, Iris',
                'base_notes' => 'Amber, Vanilla, Dry Woods, Benzoin, Labdanum',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 3200, 'compare_at' => 3800, 'stock' => 20],
                    ['size_ml' => 100, 'price' => 4800, 'compare_at' => 5500, 'stock' => 40],
                ],
            ],

            // 19. Lattafa Badee Al Oud (Oud for Glory)
            [
                'brand' => 'lattafa',
                'category' => 'oud-arabian',
                'name' => 'Lattafa Badee Al Oud (Oud for Glory)',
                'slug' => 'lattafa-badee-al-oud',
                'short_description' => 'Prestigious smoky oud, lavender, and saffron. True Middle Eastern majesty.',
                'description' => 'A sublime oriental fragrance with strong projection and longevity. Badee Al Oud is celebrated for its deep smoky agarwood notes accented by fresh lavender, nutmeg, and patchouli.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Saffron, Nutmeg, Lavender',
                'heart_notes' => 'Agarwood (Oud), Patchouli',
                'base_notes' => 'Oud, Patchouli, Musk',
                'scent_family' => ScentFamily::WOODY,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 3800, 'compare_at' => 4400, 'stock' => 18],
                    ['size_ml' => 100, 'price' => 5400, 'compare_at' => 6200, 'stock' => 25],
                ],
            ],

            // 20. Lattafa Yara
            [
                'brand' => 'lattafa',
                'category' => 'women',
                'name' => 'Lattafa Yara Pink',
                'slug' => 'lattafa-yara-pink',
                'short_description' => 'Fluffy strawberry milkshake with tropical fruits and powdered vanilla cream.',
                'description' => 'A sweet, comforting fragrance beloved across Addis Ababa. Yara Pink opens with heliotrope and orchid, drying down to velvety vanilla and creamy musk that feels soft and romantic.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Heliotrope, Orchid, Tangerine',
                'heart_notes' => 'Gourmand Accord, Tropical Fruits',
                'base_notes' => 'Vanilla, Sandalwood, Musk',
                'scent_family' => ScentFamily::GOURMAND,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 2900, 'compare_at' => 3400, 'stock' => 25],
                    ['size_ml' => 100, 'price' => 4500, 'compare_at' => 5200, 'stock' => 50],
                ],
            ],

            // 21. Lattafa Nebras
            [
                'brand' => 'lattafa',
                'category' => 'women',
                'name' => 'Lattafa Nebras',
                'slug' => 'lattafa-nebras',
                'short_description' => 'Decadent red berries dipped in warm milk chocolate and caramelized tonka.',
                'description' => 'Nebras is a luscious celebration of red fruits, cacao, and cozy amber. Perfect for cold evenings or special dinners, creating an alluring aura around the wearer.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Red Berries, Mandarin Orange',
                'heart_notes' => 'Vanilla, Cacao, Rose',
                'base_notes' => 'Sugar, Tonka Bean, Amber, Musk',
                'scent_family' => ScentFamily::GOURMAND,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 3900, 'compare_at' => 4600, 'stock' => 18],
                    ['size_ml' => 100, 'price' => 5500, 'compare_at' => 6500, 'stock' => 20],
                ],
            ],

            // 22. Afnan 9 PM
            [
                'brand' => 'afnan',
                'category' => 'men',
                'name' => 'Afnan 9 PM Eau de Parfum',
                'slug' => 'afnan-9-pm',
                'short_description' => 'The ultimate nightlife fragrance. Sweet spiced apple, lavender, and rich vanilla.',
                'description' => '9 PM by Afnan is engineered for evening adventures. Sparkling crisp apple and wild cinnamon burst open before mellowing into comforting vanilla and warm cedarwood with nuclear projection.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Apple, Cinnamon, Wild Lavender, Bergamot',
                'heart_notes' => 'Orange Blossom, Lily-of-the-Valley',
                'base_notes' => 'Vanilla, Tonka Bean, Amber, Patchouli',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 3800, 'compare_at' => 4400, 'stock' => 20],
                    ['size_ml' => 100, 'price' => 5600, 'compare_at' => 6400, 'stock' => 30],
                ],
            ],

            // 23. Afnan Supremacy Not Only Intense
            [
                'brand' => 'afnan',
                'category' => 'men',
                'name' => 'Afnan Supremacy Not Only Intense',
                'slug' => 'afnan-supremacy-not-only-intense',
                'short_description' => 'Extrait de Parfum masterpiece with blackcurrant, smoky oakmoss, and ambergris.',
                'description' => 'A rich, dark, and fruity powerhouse fragrance. Features juicy blackcurrant, bergamot, crisp apple, smoky oakmoss, and patchouli for unmatched versatility and compliment-pulling power.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::PARFUM,
                'top_notes' => 'Blackcurrant, Bergamot, Apple',
                'heart_notes' => 'Oakmoss, Patchouli, Lavender',
                'base_notes' => 'Ambergris, Musk, Saffron',
                'scent_family' => ScentFamily::FRESH,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 100, 'price' => 7200, 'compare_at' => 8200, 'stock' => 22],
                    ['size_ml' => 150, 'price' => 9800, 'compare_at' => 11000, 'stock' => 12],
                ],
            ],

            // 24. Afnan Souvenir Floral Bouquet
            [
                'brand' => 'afnan',
                'category' => 'women',
                'name' => 'Afnan Souvenir Floral Bouquet',
                'slug' => 'afnan-souvenir-floral-bouquet',
                'short_description' => 'Crisp rhubarb, sweet litchi, and Turkish rose in a gorgeous bell flacon.',
                'description' => 'Souvenir Floral Bouquet is an intoxicating celebration of delicate pink florals. Opens with succulent litchi and bergamot leading to velvety peony and warm vanilla cashmere.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Rhubarb, Litchi, Bergamot',
                'heart_notes' => 'Rose, Peony, Vanilla, Lily-of-the-Valley',
                'base_notes' => 'Musk, Cashmeran, Amber',
                'scent_family' => ScentFamily::FLORAL,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 4600, 'compare_at' => 5200, 'stock' => 14],
                    ['size_ml' => 100, 'price' => 6400, 'compare_at' => 7200, 'stock' => 16],
                ],
            ],

            // 25. Armaf Club de Nuit Intense Man
            [
                'brand' => 'armaf',
                'category' => 'men',
                'name' => 'Armaf Club de Nuit Intense Man Pure Parfum',
                'slug' => 'armaf-cdnim-pure-parfum',
                'short_description' => 'Smoky pineapple and birch wood. The world-famous compliment magnet.',
                'description' => 'Club de Nuit Intense Man is an undeniable modern legend. The pure parfum concentration eliminates harsh openings, delivering smooth lemon, smoky birch wood, and luxurious ambergris.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::PARFUM,
                'top_notes' => 'Lemon, Pineapple, Bergamot, Blackcurrant',
                'heart_notes' => 'Birch, Jasmine, Rose',
                'base_notes' => 'Musk, Ambergris, Patchouli, Vanilla',
                'scent_family' => ScentFamily::WOODY,
                'is_featured' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 105, 'price' => 5800, 'compare_at' => 6800, 'stock' => 38],
                    ['size_ml' => 150, 'price' => 7900, 'compare_at' => 8900, 'stock' => 20],
                    ['size_ml' => 200, 'price' => 9900, 'compare_at' => null, 'stock' => 15],
                ],
            ],

            // 26. Armaf Club de Nuit Milestone
            [
                'brand' => 'armaf',
                'category' => 'unisex',
                'name' => 'Armaf Club de Nuit Milestone',
                'slug' => 'armaf-club-de-nuit-milestone',
                'short_description' => 'Marine sea salt, ripe red berries, and sunlit Sicilian citrus in a golden flacon.',
                'description' => 'A royal voyage across summer seas. Salty breeze notes dance across juicy red fruit accords before setting into creamy sandalwood and soft musk.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Sea Notes, Red Fruits, Bergamot',
                'heart_notes' => 'Violet, White Woods, Sandalwood',
                'base_notes' => 'Ambroxan, Musk, Vetiver',
                'scent_family' => ScentFamily::CITRUS,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 3800, 'compare_at' => 4500, 'stock' => 15],
                    ['size_ml' => 105, 'price' => 5200, 'compare_at' => 6000, 'stock' => 25],
                ],
            ],

            // 27. Armaf Club de Nuit Untold
            [
                'brand' => 'armaf',
                'category' => 'unisex',
                'name' => 'Armaf Club de Nuit Untold',
                'slug' => 'armaf-club-de-nuit-untold',
                'short_description' => 'Iridescent saffron, sweet spun amberwood, and sparkling cedarwood crystal.',
                'description' => 'Captivating, airy, yet deeply long-lasting. Untold captures the shimmering sweet amber magic with top notes of saffron and jasmine, supported by cedar and resinous fir balsam.',
                'gender' => Gender::UNISEX,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Saffron, Jasmine',
                'heart_notes' => 'Amberwood, Ambergris',
                'base_notes' => 'Fir Resin, Cedarwood',
                'scent_family' => ScentFamily::ORIENTAL,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 50, 'price' => 4200, 'compare_at' => 4900, 'stock' => 20],
                    ['size_ml' => 105, 'price' => 5900, 'compare_at' => 6900, 'stock' => 30],
                ],
            ],

            // 28. Lattafa Velvet Rose Gift Set
            [
                'brand' => 'lattafa',
                'category' => 'gift-sets',
                'name' => 'Lattafa Velvet Rose Luxury Gift Set',
                'slug' => 'lattafa-velvet-rose-gift-set',
                'short_description' => 'Curated luxury gift set featuring 100ml EDP and 200ml matching body spray.',
                'description' => 'A breathtaking gift ready for presentation in an opulent golden box. Contains full-size Velvet Rose Eau de Parfum accompanied by a scented deodorant spray and travel miniature.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Sweet Rose, Pomegranate',
                'heart_notes' => 'Dry Amber, Rose Essence, Jasmine',
                'base_notes' => 'Oud, White Musk',
                'scent_family' => ScentFamily::FLORAL,
                'is_featured' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 50, 'price' => 5200, 'compare_at' => 6000, 'stock' => 10],
                    ['size_ml' => 100, 'price' => 7800, 'compare_at' => 9200, 'stock' => 15],
                ],
            ],

            // 29. Dior Sauvage Travel Gift Set
            [
                'brand' => 'dior',
                'category' => 'gift-sets',
                'name' => 'Dior Sauvage Eau de Parfum Gift Set',
                'slug' => 'dior-sauvage-edp-gift-set',
                'short_description' => 'Collector holiday gift set with 100ml Sauvage EDP and refillable leather travel spray.',
                'description' => 'An exceptional gift set presented in Dior couture packaging. Includes the iconic 100ml Sauvage Eau de Parfum bottle paired with a pre-filled 10ml travel spray.',
                'gender' => Gender::MEN,
                'concentration' => Concentration::EDP,
                'top_notes' => 'Calabrian Bergamot',
                'heart_notes' => 'Sichuan Pepper, Nutmeg',
                'base_notes' => 'Ambroxan, Vanilla',
                'scent_family' => ScentFamily::FRESH,
                'is_featured' => false,
                'is_new' => true,
                'is_bestseller' => false,
                'variants' => [
                    ['size_ml' => 60, 'price' => 24000, 'compare_at' => 27000, 'stock' => 6],
                    ['size_ml' => 100, 'price' => 32000, 'compare_at' => 35000, 'stock' => 7],
                ],
            ],

            // 30. Lattafa Yara All-Over Body Mist
            [
                'brand' => 'lattafa',
                'category' => 'body-mists',
                'name' => 'Lattafa Yara Hydrating Body Mist',
                'slug' => 'lattafa-yara-body-mist',
                'short_description' => 'Refreshing all-day fragrant mist scented with sweet strawberries and light cream.',
                'description' => 'Light, breezy, and hydrating. Formulated for everyday spritzing after showers or whenever you need an uplifting burst of delicious fruity-vanilla scent.',
                'gender' => Gender::WOMEN,
                'concentration' => Concentration::MIST,
                'top_notes' => 'Tropical Fruit, Tangerine',
                'heart_notes' => 'Heliotrope, Orchid',
                'base_notes' => 'Creamy Vanilla, Musk',
                'scent_family' => ScentFamily::GOURMAND,
                'is_featured' => false,
                'is_new' => false,
                'is_bestseller' => true,
                'variants' => [
                    ['size_ml' => 100, 'price' => 1400, 'compare_at' => 1800, 'stock' => 40],
                    ['size_ml' => 250, 'price' => 2200, 'compare_at' => 2600, 'stock' => 60],
                ],
            ],
        ];

        foreach ($products as $index => $item) {
            $brand = $brands->get($item['brand']);
            $category = $categories->get($item['category']);

            if (! $brand || ! $category) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'short_description' => $item['short_description'],
                    'description' => $item['description'],
                    'gender' => $item['gender'],
                    'concentration' => $item['concentration'],
                    'top_notes' => $item['top_notes'],
                    'heart_notes' => $item['heart_notes'],
                    'base_notes' => $item['base_notes'],
                    'scent_family' => $item['scent_family'],
                    'is_featured' => $item['is_featured'],
                    'is_new' => $item['is_new'],
                    'is_bestseller' => $item['is_bestseller'],
                    'is_active' => true,
                ]
            );

            // Create Variants
            foreach ($item['variants'] as $vIndex => $variantData) {
                $sku = sprintf('%s-%dML', strtoupper(str_replace('-', '', substr($product->slug, 0, 10))) . '-' . $product->id, $variantData['size_ml']);

                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'size_ml' => $variantData['size_ml'],
                    ],
                    [
                        'price' => $variantData['price'],
                        'compare_at_price' => $variantData['compare_at'],
                        'sku' => $sku,
                        'stock' => $variantData['stock'],
                        'is_active' => true,
                    ]
                );
            }

            // Attach Media
            if ($product->getFirstMedia('images') === null) {
                $imagePath = database_path("seeders/images/products/{$product->slug}.jpg");
                PlaceholderImageGenerator::make(
                    $imagePath,
                    800,
                    800,
                    $product->name,
                    $brand->name . ' • ' . $item['concentration']->value,
                    $index
                );

                if (file_exists($imagePath)) {
                    $product->addMedia($imagePath)
                        ->preservingOriginal()
                        ->toMediaCollection('images');
                }
            }
        }
    }
}
