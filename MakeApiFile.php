<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeApiFile extends Command
{
    protected $signature = 'make:api {name}';
    protected $description = 'Create API route file and register  in bootstrap/app.php';

    public function handle()
    {
        $name = $this->argument('name');
        $filePath = base_path("routes/{$name}.php");

        if (!File::exists($filePath)) {
            File::put(
                $filePath,
                "<?php\n\nuse Illuminate\Support\Facades\Route;\n\nRoute::get('/" . strtolower($name) . "', function(){ return 'Hello {$name}'; });\n"
            );
            $this->info("Route file created: {$filePath}");
        } else {
            $this->error("File already exists!");
            return;
        }

        $bootstrapPath = base_path('bootstrap/app.php');
        if (!File::exists($bootstrapPath)) {
            $this->warn("bootstrap/app.php not found. Please register the route manually.");
            return;
        }

        $content = File::get($bootstrapPath);

        $pattern = "/withRouting\s*\((.*?)\)/s";

        if (preg_match($pattern, $content, $matches)) {
            $inside = $matches[1];

            if (strpos($inside, "{$name}:") !== false) {
                $insideNew = preg_replace(
                    "/{$name}:\s*__DIR__\s*\.\/\.\.\/routes\/[a-zA-Z0-9_]+\.php/",
                    "{$name}: __DIR__.'/../routes/{$name}.php'",
                    $inside
                );
            } else {
                $insideNew = $inside . "\n    , {$name}: __DIR__.'/../routes/{$name}.php'";
            }

            $content = preg_replace($pattern, "withRouting($insideNew)", $content);
            File::put($bootstrapPath, $content);

            $this->info("Route registered in bootstrap/app.php automatically");
        } else {
            $this->warn("Couldn't find withRouting(...) in bootstrap/app.php. Please add api route manually.");
        }
    }
}