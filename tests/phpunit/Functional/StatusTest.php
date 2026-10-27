<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Components that describe a value: Badge, Status, Boolean, Tag, Chip, Avatar, ProgressBar, ColorSwatch.
 */
class StatusTest extends TestCase
{
    use RendersTwig;

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testBadgeAndItsComplementRead(): void
    {
        $badge = $this->element('<twig:LleCruditUi:Badge tone="danger" label="visits to plan">12</twig:LleCruditUi:Badge>', 'span');

        self::assertSame('crudit-ui-badge crudit-ui-tone--danger', $badge->getAttribute('class'));
        self::assertSame("12\u{a0}visits to plan", $badge->textContent);
        self::assertSame("\u{a0}visits to plan", $badge->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertSame('crudit-ui-badge crudit-ui-tone--neutral', $this->element('<twig:LleCruditUi:Badge>3 parts</twig:LleCruditUi:Badge>', 'span')->getAttribute('class'));
    }

    public function testStatusOfAWorkflow(): void
    {
        $status = $this->element('<twig:LleCruditUi:Status tone="teal" size="small">Preparation</twig:LleCruditUi:Status>', 'span');

        self::assertSame('crudit-ui-status crudit-ui-tone--teal crudit-ui-status--small', $status->getAttribute('class'));
        self::assertSame('true', $status->querySelector('.crudit-ui-status__dot')?->getAttribute('aria-hidden'));
        self::assertSame('Preparation', $status->textContent);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function booleans(): iterable
    {
        yield 'true' => [':value="true"', 'crudit-ui-bool crudit-ui-bool--yes', 'Yes'];
        yield 'false' => [':value="false"', 'crudit-ui-bool crudit-ui-bool--no', 'No'];
        yield 'null' => [':value="null"', 'crudit-ui-empty-value', '—Not set'];
        yield 'labels of the project' => [':value="true" yes="Active"', 'crudit-ui-bool crudit-ui-bool--yes', 'Active'];
    }

    public function testBooleanReversed(): void
    {
        $yes = $this->element('<twig:LleCruditUi:Boolean :value="true" reverse yes="Blocked"/>', 'span');
        $no = $this->element('<twig:LleCruditUi:Boolean :value="false" :reverse="true"/>', 'span');

        self::assertSame('crudit-ui-bool crudit-ui-bool--yes crudit-ui-bool--reverse', $yes->getAttribute('class'));
        self::assertSame('Blocked', $yes->textContent);
        self::assertSame('crudit-ui-bool crudit-ui-bool--no crudit-ui-bool--reverse', $no->getAttribute('class'));
        self::assertSame('crudit-ui-empty-value', $this->element('<twig:LleCruditUi:Boolean :value="null" reverse/>', 'span')->getAttribute('class'));
    }

    #[DataProvider('booleans')]
    public function testBooleanIsWritten(string $props, string $class, string $text): void
    {
        $boolean = $this->element('<twig:LleCruditUi:Boolean ' . $props . '/>', 'span');

        self::assertSame($class, $boolean->getAttribute('class'));
        self::assertSame($text, $boolean->textContent);
    }

    public function testTagIsAToggleButton(): void
    {
        $tag = $this->element('<twig:LleCruditUi:Tag pressed>Lift</twig:LleCruditUi:Tag>', 'button');
        self::assertSame('true', $tag->getAttribute('aria-pressed'));
        self::assertSame('Lift', $tag->textContent);
        self::assertSame('2lenet--crudit-ui-bundle--button', $tag->getAttribute('data-controller'));

        $tag = $this->element('<twig:LleCruditUi:Tag busy>Lift</twig:LleCruditUi:Tag>', 'button');
        self::assertSame('false', $tag->getAttribute('aria-pressed'));
        self::assertSame('true', $tag->getAttribute('aria-busy'));
        self::assertSame('true', $tag->getAttribute('aria-disabled'));
        self::assertStringContainsString('crudit-ui-icon--spin', (string) $tag->querySelector('svg')?->getAttribute('class'));
    }

    public function testChipIsRemovedByANamedButton(): void
    {
        $chip = $this->element('<twig:LleCruditUi:Chip removeLabel="Remove the filter Status: planned" remove:data-action="filters#remove">Status: <strong>planned</strong></twig:LleCruditUi:Chip>', 'span');

        self::assertSame('Status: planned', $chip->querySelector('.crudit-ui-chip__text')?->textContent);
        $remove = $chip->querySelector('button.crudit-ui-chip__remove');
        self::assertNotNull($remove);
        self::assertSame('filters#remove', $remove->getAttribute('data-action'));
        self::assertSame('Remove the filter Status: planned', $remove->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertFalse($chip->hasAttribute('remove:data-action'));

        self::assertNull($this->element('<twig:LleCruditUi:Chip :removable="false">Archived</twig:LleCruditUi:Chip>', 'span')->querySelector('button'));

        // Without removeLabel, the button is named after the text of the chip.
        $chip = $this->element('<twig:LleCruditUi:Chip>Agency: <strong>Lyon</strong></twig:LleCruditUi:Chip>', 'span');
        self::assertSame('Remove the filter Agency: Lyon', $chip->querySelector('button .crudit-ui-visually-hidden')?->textContent);

        // An apostrophe or an ampersand is read as it is, never as an entity; the blanks of the block are normalised.
        $chip = $this->element("<twig:LleCruditUi:Chip>\n    Agency:\t<strong>{{ agency }}</strong>,  {{ team }}\n</twig:LleCruditUi:Chip>", 'span', ['agency' => "L'Isle-d'Abeau", 'team' => 'R&D']);
        self::assertSame("Remove the filter Agency: L'Isle-d'Abeau, R&D", $chip->querySelector('button .crudit-ui-visually-hidden')?->textContent);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function names(): iterable
    {
        yield 'first and last name' => ['Camille Martin', 'CM'];
        yield 'accents' => ['élodie Ørsted', 'ÉØ'];
        yield 'one word' => ['admin', 'A'];
        yield 'three words, spaces' => ['  Jean  Pierre Dupont ', 'JP'];
    }

    #[DataProvider('names')]
    public function testAvatarShowsTheInitials(string $name, string $initials): void
    {
        $avatar = $this->element('<twig:LleCruditUi:Avatar name="' . $name . '"/>', 'span');

        self::assertSame($initials, $avatar->textContent);
        self::assertSame('true', $avatar->querySelector('span')?->getAttribute('aria-hidden'));
    }

    public function testAvatarSizesAndImpersonation(): void
    {
        $avatar = $this->element('<twig:LleCruditUi:Avatar name="Camille Martin" size="large" impersonated/>', 'span');

        self::assertSame('crudit-ui-avatar crudit-ui-avatar--large crudit-ui-avatar--impersonated', $avatar->getAttribute('class'));
        self::assertStringContainsString('crudit-ui-avatar__flag', (string) $avatar->querySelector('svg')?->getAttribute('class'));
        // The initials are hidden, the impersonation is read.
        self::assertSame('(impersonation)', $avatar->querySelector('.crudit-ui-visually-hidden')?->textContent);
    }

    public function testProgressBarIsNativeNamedAndWritten(): void
    {
        $progress = $this->element('<twig:LleCruditUi:ProgressBar label="Visits done" :value="9" :max="20"/>', 'div');

        $bar = $progress->querySelector('progress');
        self::assertNotNull($bar);
        self::assertSame(['9', '20', '45%'], [$bar->getAttribute('value'), $bar->getAttribute('max'), $bar->getAttribute('aria-valuetext')]);
        self::assertSame('Visits done', $progress->querySelector('#' . $bar->getAttribute('aria-labelledby'))?->textContent);
        self::assertSame('45%', $progress->querySelector('.crudit-ui-progress__value')?->textContent);
        self::assertSame('crudit-ui-progress crudit-ui-progress--primary', $progress->getAttribute('class'));
    }

    public function testProgressBarFollowsTheLocale(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');
        /** @var \Symfony\Component\HttpFoundation\RequestStack $requests */
        $requests = $container->get('request_stack');
        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->setLocale('fr');
        $requests->push($request);

        $html = $this->html($this->render($kernel, '<twig:LleCruditUi:ProgressBar label="Visites" :value="9" :max="20"/>'));

        // A non-breaking space before the sign, as in French.
        self::assertSame("45\u{a0}%", $html->querySelector('progress')?->getAttribute('aria-valuetext'));
    }

    public function testProgressBarDoneOverAndIdOfTheProject(): void
    {
        $done = $this->element('<twig:LleCruditUi:ProgressBar label="Done" :value="20" :max="20"/>', 'div');
        self::assertSame('crudit-ui-progress crudit-ui-progress--success', $done->getAttribute('class'));

        $over = $this->element('<twig:LleCruditUi:ProgressBar id="budget" label="Budget" :value="130" tone="danger" valueText="130 % (over)"/>', 'div');
        self::assertSame('crudit-ui-progress crudit-ui-progress--danger', $over->getAttribute('class'));
        self::assertSame('budget', $over->getAttribute('id'));
        $bar = $over->querySelector('progress');
        // The element stays within max; the real value is read and written.
        self::assertSame(['100', 'budget-label', '130 % (over)'], [$bar?->getAttribute('value'), $bar?->getAttribute('aria-labelledby'), $bar?->getAttribute('aria-valuetext')]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function colors(): iterable
    {
        foreach (['#0077a8', '#07a', '#0077a880', 'rgb(0, 119, 168)', 'rgba(0 119 168 / 50%)', 'hsl(197 100% 33%)', 'hsl(200deg 50% 40%)', 'oklch(70% 0.1 200)', 'teal'] as $color) {
            yield $color => [$color];
        }
    }

    #[DataProvider('colors')]
    public function testColorSwatchDrawsAColour(string $color): void
    {
        $swatch = $this->element('<twig:LleCruditUi:ColorSwatch value="' . $color . '"/>', 'span');

        self::assertSame('crudit-ui-color', $swatch->getAttribute('class'));
        self::assertSame($color, $swatch->querySelector('svg rect')?->getAttribute('fill'));
        self::assertSame('true', $swatch->querySelector('svg')?->getAttribute('aria-hidden'));
        self::assertSame($color, $swatch->querySelector('code')?->textContent);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notColors(): iterable
    {
        yield 'css injection' => ['red; background-image: url(https://example.com/x.png)'];
        yield 'expression' => ['expression(alert(1))'];
        yield 'url' => ['url(https://example.com/x.png)'];
        yield 'function in a function' => ['rgb(var(--x))'];
        yield 'url in a colour function' => ['oklch(url(https://example.com/x.svg))'];
        yield 'quote' => ['red" onload="alert(1)'];
    }

    #[DataProvider('notColors')]
    public function testColorSwatchDrawsNothingElse(string $value): void
    {
        $swatch = $this->element('<twig:LleCruditUi:ColorSwatch value="{{ value }}"/>', 'span', ['value' => $value]);

        self::assertSame('crudit-ui-color crudit-ui-color--invalid', $swatch->getAttribute('class'));
        self::assertFalse($swatch->querySelector('svg rect')?->hasAttribute('fill') ?? true);
        self::assertNull($swatch->querySelector('[style]'));
        self::assertSame($value, $swatch->querySelector('code')?->textContent);
    }

    public function testNoComponentHasAStyleAttribute(): void
    {
        // Strict Content Security Policy (style-src 'self'): the components write no style attribute.
        $templates = glob(\dirname(__DIR__, 3) . '/templates/components/{,*/}*.html.twig', GLOB_BRACE);
        self::assertNotEmpty($templates);
        foreach ($templates as $template) {
            self::assertStringNotContainsString('style=', (string) file_get_contents($template), basename($template));
        }
    }
}
