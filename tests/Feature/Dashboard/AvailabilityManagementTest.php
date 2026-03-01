<?php

use App\Models\AvailabilityBlock;
use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->user->id]);
});

test('guests are redirected to login', function () {
    $this->get("/{$this->business->handle}/availability")
        ->assertRedirect(route('login'));
});

test('unauthorized users get 403', function () {
    $stranger = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->get("/{$this->business->handle}/availability")
        ->assertForbidden();
});

test('renders availability page with weekly and specific blocks', function () {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/availability")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/availability/index')
            ->has('weeklyBlocks')
            ->has('specificBlocks')
        );
});

test('only shows blocks for current business', function () {
    $otherBusiness = Business::factory()->completed()->create();
    AvailabilityBlock::create([
        'business_id' => $otherBusiness->id,
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);

    AvailabilityBlock::create([
        'business_id' => $this->business->id,
        'day_of_week' => 1,
        'start_time' => '10:00',
        'end_time' => '16:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/availability")
        ->assertInertia(fn ($page) => $page
            ->where('weeklyBlocks.1', fn ($blocks) => count($blocks) === 1 && $blocks[0]['start_time'] === '10:00')
        );
});

test('can create a recurring weekly block', function () {
    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/availability", [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'block_type' => 'available',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('availability_blocks', [
        'business_id' => $this->business->id,
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);
});

test('can create a specific-date block', function () {
    $date = now()->addWeek()->toDateString();

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/availability", [
            'specific_date' => $date,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'block_type' => 'holiday',
            'notes' => 'Bank holiday',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('availability_blocks', [
        'business_id' => $this->business->id,
        'block_type' => 'holiday',
        'notes' => 'Bank holiday',
        'repeat_weekly' => false,
    ]);
});

test('validates start_time before end_time', function () {
    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/availability", [
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '09:00',
            'block_type' => 'available',
        ])
        ->assertSessionHasErrors('end_time');
});

test('validates day_of_week or specific_date is required', function () {
    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/availability", [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'block_type' => 'available',
        ])
        ->assertSessionHasErrors(['day_of_week', 'specific_date']);
});

test('can update a block', function () {
    $block = AvailabilityBlock::create([
        'business_id' => $this->business->id,
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);

    $this->actingAs($this->user)
        ->put("/{$this->business->handle}/availability/{$block->id}", [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'block_type' => 'available',
        ])
        ->assertRedirect();

    $block->refresh();
    expect($block->start_time)->toBe('08:00');
    expect($block->end_time)->toBe('18:00');
});

test('can delete a block', function () {
    $block = AvailabilityBlock::create([
        'business_id' => $this->business->id,
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);

    $this->actingAs($this->user)
        ->delete("/{$this->business->handle}/availability/{$block->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('availability_blocks', ['id' => $block->id]);
});

test('cannot modify blocks from another business', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $block = AvailabilityBlock::create([
        'business_id' => $otherBusiness->id,
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);

    $this->actingAs($this->user)
        ->put("/{$this->business->handle}/availability/{$block->id}", [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'block_type' => 'available',
        ])
        ->assertNotFound();
});
