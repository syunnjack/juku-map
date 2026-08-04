<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_google_tags_are_rendered_once_when_configured(): void
    {
        config([
            'services.google.analytics_measurement_id' => 'G-TEST123',
            'services.google.site_verification' => 'verification-token',
        ]);

        $html = view('partials.google')->render();

        $this->assertSame(1, substr_count($html, 'googletagmanager.com/gtag/js'));
        $this->assertSame(1, substr_count($html, "gtag('config'"));
        $this->assertSame(1, substr_count($html, 'google-site-verification'));
        $this->assertStringContainsString('G-TEST123', $html);
        $this->assertStringContainsString('verification-token', $html);
    }

    public function test_google_tags_are_omitted_when_not_configured(): void
    {
        config([
            'services.google.analytics_measurement_id' => null,
            'services.google.site_verification' => null,
        ]);

        $html = view('partials.google')->render();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
        $this->assertStringNotContainsString('google-site-verification', $html);
    }
}
