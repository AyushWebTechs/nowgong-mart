<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Gives demo products and categories without an image a picture: the bundled photo from
 * database/seeders/images when one exists, otherwise a generated (GD) placeholder.
 * Local/dev only — real shops upload photos from the admin panel.
 */
class DemoImageSeeder extends Seeder
{
    private const COLORS = [
        'grocery' => [[255, 183, 77], [230, 126, 34]],
        'dairy' => [[129, 212, 250], [41, 128, 185]],
        'snacks' => [[255, 138, 101], [211, 84, 0]],
        'beverages' => [[165, 214, 167], [39, 174, 96]],
        'ice-cream' => [[244, 143, 177], [194, 24, 91]],
        'household' => [[179, 157, 219], [103, 58, 183]],
    ];

    private const FALLBACK = [[176, 190, 197], [84, 110, 122]];

    private ?string $font = null;

    public function run(): void
    {
        if (! extension_loaded('gd')) {
            $this->command?->warn('GD extension missing — skipping demo images.');

            return;
        }

        $this->font = collect(['C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'])
            ->first(fn ($path) => is_file($path));

        foreach (Category::whereNull('image_path')->get() as $category) {
            $path = "categories/{$category->slug}.png";
            $this->store($path, $this->render($category->name, null, self::COLORS[$category->slug] ?? self::FALLBACK, 200));
            $category->update(['image_path' => $path]);
        }

        foreach (Product::with('category')->whereNull('image_path')->get() as $product) {
            // Prefer a real photo shipped in database/seeders/images (see CREDITS.md there).
            $photo = database_path("seeders/images/products/{$product->slug}.jpg");

            if (is_file($photo)) {
                $path = "products/{$product->slug}.jpg";
                $this->store($path, file_get_contents($photo));
                $product->update(['image_path' => $path]);

                continue;
            }

            $path = "products/{$product->slug}.png";
            $colors = self::COLORS[$product->category?->slug] ?? self::FALLBACK;
            $this->store($path, $this->render($product->name, '₹'.rtrim(rtrim((string) $product->price, '0'), '.').' / '.$product->unit, $colors, 600));
            $product->update(['image_path' => $path]);
        }
    }

    private function store(string $path, string $png): void
    {
        Storage::disk('public')->put($path, $png);
    }

    private function render(string $title, ?string $subtitle, array $colors, int $size): string
    {
        $img = imagecreatetruecolor($size, $size);
        [$from, $to] = $colors;

        // Vertical gradient background.
        for ($y = 0; $y < $size; $y++) {
            $t = $y / $size;
            $c = imagecolorallocate($img, ...array_map(fn ($a, $b) => (int) ($a + ($b - $a) * $t), $from, $to));
            imageline($img, 0, $y, $size, $y, $c);
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $shade = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledellipse($img, (int) ($size * 0.85), (int) ($size * 0.15), (int) ($size * 0.6), (int) ($size * 0.6), $shade);
        imagefilledellipse($img, (int) ($size * 0.1), (int) ($size * 0.95), (int) ($size * 0.5), (int) ($size * 0.5), $shade);

        if (! $this->font) {
            imagestring($img, 5, 10, (int) ($size / 2), substr($title, 0, (int) ($size / 10)), $white);
        } else {
            $fontSize = $size / 14;
            $lines = $this->wrap($title, $fontSize, $size * 0.8);
            $lineHeight = $fontSize * 1.4;
            $y = ($size - count($lines) * $lineHeight) / 2 + $fontSize;

            foreach ($lines as $line) {
                $this->centered($img, $line, $fontSize, (int) $y, $white, $size);
                $y += $lineHeight;
            }

            if ($subtitle) {
                $this->centered($img, $subtitle, $fontSize * 0.7, (int) ($size * 0.88), $white, $size);
            }
        }

        ob_start();
        imagepng($img);
        imagedestroy($img);

        return ob_get_clean();
    }

    private function wrap(string $text, float $fontSize, float $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = trim("$current $word");
            $box = imagettfbbox($fontSize, 0, $this->font, $candidate);

            if ($current !== '' && ($box[2] - $box[0]) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        return [...$lines, $current];
    }

    private function centered($img, string $text, float $fontSize, int $y, int $color, int $size): void
    {
        $box = imagettfbbox($fontSize, 0, $this->font, $text);
        imagettftext($img, $fontSize, 0, (int) (($size - ($box[2] - $box[0])) / 2), $y, $color, $this->font, $text);
    }
}
