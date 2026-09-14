<?php

declare(strict_types=1);

namespace Grav\Plugin\Api\Tests\Unit\Serializers;

use Grav\Plugin\Api\Serializers\PackageSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A package's top-level `name` / `description` come from its own
 * blueprints.yaml, where an author may equally write literal prose or a
 * translation key. The serializer takes an optional translator that resolves
 * keys and returns null for everything else, so literal text survives
 * untouched and an unresolvable key is served as authored rather than
 * humanized into a word (#39).
 */
#[CoversClass(PackageSerializer::class)]
class PackageSerializerTest extends TestCase
{
    /**
     * Translator standing in for TranslatesAdminLabels::resolveTranslationKey():
     * resolves known keys, returns null for anything else.
     */
    private function serializer(array $dictionary = []): PackageSerializer
    {
        return new PackageSerializer(
            static fn (string $value): ?string => $dictionary[$value] ?? null,
        );
    }

    private function package(array $props): object
    {
        return (object) $props;
    }

    #[Test]
    public function description_written_as_a_translation_key_is_translated(): void
    {
        $data = $this->serializer(['MYTHEME.DESCRIPTION' => 'A theme for Grav'])
            ->serialize($this->package(['slug' => 'mytheme', 'description' => 'MYTHEME.DESCRIPTION']));

        self::assertSame('A theme for Grav', $data['description']);
        self::assertSame("<p>A theme for Grav</p>", trim($data['description_html']));
    }

    #[Test]
    public function markdown_is_rendered_from_the_translated_text_not_the_key(): void
    {
        $data = $this->serializer(['MYTHEME.DESCRIPTION' => 'A **fast** theme for [Grav](https://getgrav.org)'])
            ->serialize($this->package(['slug' => 'mytheme', 'description' => 'MYTHEME.DESCRIPTION']));

        self::assertStringContainsString('<strong>fast</strong>', $data['description_html']);
        self::assertStringContainsString('href="https://getgrav.org"', $data['description_html']);
        self::assertStringNotContainsString('MYTHEME.DESCRIPTION', $data['description_html']);
    }

    #[Test]
    public function translated_description_is_still_rendered_in_parsedown_safe_mode(): void
    {
        $data = $this->serializer(['MYTHEME.DESCRIPTION' => 'Nice <script>alert(1)</script> theme'])
            ->serialize($this->package(['slug' => 'mytheme', 'description' => 'MYTHEME.DESCRIPTION']));

        self::assertStringNotContainsString('<script>', $data['description_html']);
        self::assertStringContainsString('&lt;script&gt;', $data['description_html']);
    }

    #[Test]
    public function a_literal_description_passes_through_unchanged(): void
    {
        $data = $this->serializer(['MYTHEME.DESCRIPTION' => 'should not be used'])
            ->serialize($this->package([
                'slug' => 'mytheme',
                'description' => 'A **fast** theme. FAST. SIMPLE.',
            ]));

        self::assertSame('A **fast** theme. FAST. SIMPLE.', $data['description']);
        self::assertStringContainsString('<strong>fast</strong>', $data['description_html']);
    }

    #[Test]
    public function an_unresolvable_key_is_served_as_authored(): void
    {
        // Nothing translates it — better the author's own value than a
        // humanized guess ("Description").
        $data = $this->serializer()
            ->serialize($this->package(['slug' => 'mytheme', 'description' => 'MYTHEME.DESCRIPTION']));

        self::assertSame('MYTHEME.DESCRIPTION', $data['description']);
        self::assertSame('<p>MYTHEME.DESCRIPTION</p>', trim($data['description_html']));
    }

    #[Test]
    public function name_written_as_a_translation_key_is_translated_too(): void
    {
        $data = $this->serializer(['MYTHEME.NAME' => 'My Theme'])
            ->serialize($this->package(['slug' => 'mytheme', 'name' => 'MYTHEME.NAME']));

        self::assertSame('My Theme', $data['name']);
    }

    #[Test]
    public function a_literal_name_passes_through_unchanged(): void
    {
        $data = $this->serializer(['MYTHEME.NAME' => 'should not be used'])
            ->serialize($this->package(['slug' => 'quark', 'name' => 'Quark']));

        self::assertSame('Quark', $data['name']);
    }

    #[Test]
    public function author_name_and_keywords_are_never_translated(): void
    {
        // Proper nouns and tag words: a translator must not be asked about them.
        $seen = [];
        $serializer = new PackageSerializer(
            static function (string $value) use (&$seen): ?string {
                $seen[] = $value;

                return null;
            },
        );

        $serializer->serialize($this->package([
            'slug' => 'mytheme',
            'name' => 'My Theme',
            'description' => 'A theme',
            'author' => (object) ['name' => 'AUTHOR.NAME', 'email' => 'a@b.test'],
            'keywords' => ['THEME.KEYWORD'],
        ]));

        // Description first (it is resolved before the payload is assembled,
        // so the markdown renders from the translation), then name. Nothing else.
        self::assertSame(['A theme', 'My Theme'], $seen);
    }

    #[Test]
    public function without_a_translator_every_value_is_served_verbatim(): void
    {
        $data = (new PackageSerializer())->serialize($this->package([
            'slug' => 'mytheme',
            'name' => 'MYTHEME.NAME',
            'description' => 'MYTHEME.DESCRIPTION',
        ]));

        self::assertSame('MYTHEME.NAME', $data['name']);
        self::assertSame('MYTHEME.DESCRIPTION', $data['description']);
    }

    #[Test]
    public function an_absent_description_stays_null(): void
    {
        $data = $this->serializer(['' => 'nope'])
            ->serialize($this->package(['slug' => 'mytheme']));

        self::assertNull($data['description']);
        self::assertNull($data['description_html']);
    }
}
