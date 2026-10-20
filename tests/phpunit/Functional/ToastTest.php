<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * LleCruditUi:Toast and the region LleCruditUi:Toasts (docs/components.md).
 */
class ToastTest extends TestCase
{
    use RendersTwig;

    private const string TOASTS = '2lenet--crudit-ui-bundle--toasts';

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testToast(): void
    {
        $toast = $this->element('<twig:LleCruditUi:Toast tone="success" class="project" data-test="1">The visit is saved.</twig:LleCruditUi:Toast>', 'div');

        self::assertSame('crudit-ui-toast crudit-ui-toast--success project', $toast->getAttribute('class'));
        self::assertSame('toast', $toast->getAttribute('data-' . self::TOASTS . '-target'));
        self::assertSame('1', $toast->getAttribute('data-test'));
        // Not an error: removed after the duration of the region, announced politely.
        self::assertFalse($toast->hasAttribute('data-persistent'));
        self::assertFalse($toast->hasAttribute('data-assertive'));
        // No role: the region announces it (a toast moved with the region would be read again).
        self::assertFalse($toast->hasAttribute('role'));

        self::assertNotNull($toast->querySelector('svg.crudit-ui-toast__icon'));
        // The type is read first, then the message.
        self::assertSame('Success: The visit is saved.', $toast->querySelector('p.crudit-ui-toast__message')?->textContent);
        self::assertSame('Success: ', $toast->querySelector('.crudit-ui-toast__message > .crudit-ui-visually-hidden')?->textContent);

        $close = $toast->querySelector('button.crudit-ui-toast__close');
        self::assertSame('Close the message', $close?->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertSame(self::TOASTS . '#dismiss', $close->getAttribute('data-action'));
    }

    public function testErrorIsPersistentAndAssertive(): void
    {
        $error = $this->element('<twig:LleCruditUi:Toast tone="danger">Refused.</twig:LleCruditUi:Toast>', 'div');
        self::assertSame('true', $error->getAttribute('data-persistent'));
        self::assertSame('true', $error->getAttribute('data-assertive'));
        self::assertSame('Error: Refused.', $error->querySelector('.crudit-ui-toast__message')?->textContent);

        // persistent changes the default, not the announcement.
        $timed = $this->element('<twig:LleCruditUi:Toast tone="danger" :persistent="false">Refused.</twig:LleCruditUi:Toast>', 'div');
        self::assertFalse($timed->hasAttribute('data-persistent'));
        self::assertSame('true', $timed->getAttribute('data-assertive'));

        $kept = $this->element('<twig:LleCruditUi:Toast persistent>Export started.</twig:LleCruditUi:Toast>', 'div');
        self::assertSame('crudit-ui-toast crudit-ui-toast--info', $kept->getAttribute('class'));
        self::assertSame('true', $kept->getAttribute('data-persistent'));
        self::assertFalse($kept->hasAttribute('data-assertive'));
    }

    public function testRegion(): void
    {
        $region = $this->element(<<<'TWIG'
            <twig:LleCruditUi:Toasts :duration="8000" class="project">
                <twig:LleCruditUi:Toast tone="warning">Late.</twig:LleCruditUi:Toast>
            </twig:LleCruditUi:Toasts>
            TWIG, 'div');

        // Its id is the target of a Turbo Stream.
        self::assertSame('crudit-ui-toasts', $region->getAttribute('id'));
        self::assertSame('crudit-ui-toasts project', $region->getAttribute('class'));
        self::assertSame(self::TOASTS, $region->getAttribute('data-controller'));
        self::assertSame('8000', $region->getAttribute('data-' . self::TOASTS . '-duration-value'));
        self::assertSame('4', $region->getAttribute('data-' . self::TOASTS . '-max-value'));
        // Not a live region itself: two hidden ones announce the toasts.
        self::assertFalse($region->hasAttribute('aria-live'));
        self::assertSame('polite', $region->querySelector('[data-' . self::TOASTS . '-target="polite"]')?->getAttribute('aria-live'));
        self::assertSame('assertive', $region->querySelector('[data-' . self::TOASTS . '-target="assertive"]')?->getAttribute('aria-live'));

        // A template per tone, copied by the controller for the toasts of the JavaScript.
        $templates = $region->querySelectorAll('template[data-' . self::TOASTS . '-target="template"]');
        self::assertSame(['info', 'success', 'warning', 'danger'], array_map(static fn (\Dom\Element $template): ?string => $template->getAttribute('data-tone'), iterator_to_array($templates)));
        self::assertStringContainsString('crudit-ui-toast--danger', (string) $templates[3]?->innerHTML);

        $toasts = $region->querySelectorAll('#crudit-ui-toasts > .crudit-ui-toast');
        self::assertCount(1, $toasts);
        self::assertSame('Warning: Late.', $toasts[0]?->querySelector('.crudit-ui-toast__message')?->textContent);
    }

    public function testFlashMessages(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'Saved.');
        $session->getFlashBag()->add('error', 'Refused.');
        $session->getFlashBag()->add('notice', '<b>Started.</b>');
        $session->getFlashBag()->add('custom', 'Other.');
        $request = new Request();
        $request->setSession($session);
        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');
        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');
        $requestStack->push($request);

        $html = $this->html($this->render($kernel, '<twig:LleCruditUi:Toasts/>'));
        $toasts = array_map(
            static fn (\Dom\Element $toast): array => [$toast->getAttribute('class'), $toast->querySelector('.crudit-ui-toast__message')?->textContent],
            iterator_to_array($html->querySelectorAll('#crudit-ui-toasts > .crudit-ui-toast')),
        );
        self::assertSame([
            ['crudit-ui-toast crudit-ui-toast--success', 'Success: Saved.'],
            ['crudit-ui-toast crudit-ui-toast--danger', 'Error: Refused.'],
            // Escaped: a message may hold what a user typed.
            ['crudit-ui-toast crudit-ui-toast--info', 'Information: <b>Started.</b>'],
            ['crudit-ui-toast crudit-ui-toast--info', 'Information: Other.'],
        ], $toasts);
        // Read once.
        self::assertSame([], $session->getFlashBag()->all());

        $session->getFlashBag()->add('success', 'Saved.');
        $html = $this->html($this->render($kernel, '<twig:LleCruditUi:Toasts :flashes="false"/>'));
        self::assertNull($html->querySelector('.crudit-ui-toast'));
        self::assertSame(['Saved.'], $session->getFlashBag()->peek('success'));
    }

    public function testWithoutRequest(): void
    {
        $region = $this->element('<twig:LleCruditUi:Toasts id="toasts"/>', 'div');

        self::assertSame('toasts', $region->getAttribute('id'));
        self::assertNull($region->querySelector('#toasts > .crudit-ui-toast'));
    }
}
