<?php

use App\Auth\Protect\SuperAdmin;
use App\Models\User;
use Statamic\Exceptions\ForbiddenHttpException;

beforeEach(function () {
    $this->protector = (new SuperAdmin)->setConfig([
        'driver' => 'super_admin',
        'login_url' => '/login',
        'append_redirect' => true,
    ])->setScheme('super_admin');
});

test('guests are redirected to the login page', function () {
    $this->protector->setUrl('/knowledge-base/test-entry');

    $response = null;

    try {
        $this->protector->protect();
    } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
        $response = $e->getResponse();
    }

    expect($response)->not->toBeNull()
        ->and($response->getTargetUrl())->toContain('/login');
});

test('regular users are denied access', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->protector->setUrl('/knowledge-base/test-entry');

    $this->protector->protect();
})->throws(ForbiddenHttpException::class);

test('super admins can access protected content', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user);

    $this->protector->setUrl('/knowledge-base/test-entry');

    $this->protector->protect();

    expect(true)->toBeTrue();
});
