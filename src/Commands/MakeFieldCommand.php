<?php

declare(strict_types=1);

namespace MoonShine\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

use MoonShine\Fields\Field;

use MoonShine\MoonShine;
use Symfony\Component\Finder\SplFileInfo;

class MakeFieldCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:field';

    protected $description = 'Create field';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $className = text(
            'Class name',
            'CustomField',
            required: true
        );

        $suggestView = str($className)
            ->classBasename()
            ->kebab()
            ->prepend("admin.fields.")
            ->value();

        $view = text(
            'Path to view',
            $suggestView,
            default: $suggestView,
            required: true
        );

        $extends = select(
            'Extends',
            collect(File::files(__DIR__ . '/../Fields'))
                ->mapWithKeys(
                    fn (SplFileInfo $file): array => [
                        $file->getFilenameWithoutExtension() => $file->getFilenameWithoutExtension(),
                    ]
                )
                ->except(['Field', 'Fields', 'FormElement', 'FormElements'])
                ->mapWithKeys(fn ($file): array => [('MoonShine\Fields\\' . $file) => $file])
                ->prepend('Base', Field::class)
                ->toArray(),
            Field::class
        );

        $field = $this->getDirectory() . "/Fields/$className.php";

        $this->copyStub('Field', $field, [
            '{namespace}' => MoonShine::namespace('\Fields'),
            '{view}' => $view,
            '{extend}' => $extends,
            '{extendShort}' => class_basename($extends),
            'DummyField' => $className,
        ]);

        $this->components->info(
            "$className was created: " . str_replace(
                base_path(),
                '',
                $field
            )
        );
    }
}