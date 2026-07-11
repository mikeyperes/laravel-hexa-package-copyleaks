<?php

namespace Tests\Feature;

use hexa_core\Models\Setting;
use hexa_core\Services\CredentialService;
use hexa_package_copyleaks\Services\CopyleaksService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CopyleaksServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireInstalledPackage("hexawebsystems/laravel-hexa-package-copyleaks", CopyleaksService::class);
        Schema::dropIfExists("settings");
        Schema::create("settings", function (Blueprint $table): void {
            $table->id();
            $table->string("key")->unique();
            $table->text("value")->nullable();
            $table->string("group")->default("general");
            $table->string("type")->default("text");
            $table->string("label")->nullable();
            $table->integer("sort_order")->default(0);
            $table->timestamps();
        });
    }

    public function test_connection_authenticates_and_caches_bearer_token(): void
    {
        $credentials = Mockery::mock(CredentialService::class);
        $credentials->shouldReceive("get")->with("copyleaks", "email")->andReturn("editor@example.com");
        $credentials->shouldReceive("get")->with("copyleaks", "api_key")->andReturn("test-key");
        $this->app->instance(CredentialService::class, $credentials);

        Http::fake([CopyleaksService::LOGIN_URL => Http::response(["access_token" => "bearer-token"], 200)]);

        $result = app(CopyleaksService::class)->testConnection();

        $this->assertTrue($result["success"]);
        $this->assertSame("bearer-token", Setting::getValue("copyleaks_bearer_token"));
        Http::assertSent(function (Request $request): bool {
            return $request->url() === CopyleaksService::LOGIN_URL
                && ($request["email"] ?? null) === "editor@example.com"
                && ($request["key"] ?? null) === "test-key";
        });
    }
}
