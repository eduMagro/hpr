<?php

namespace Tests\Feature\Livewire;

use App\Livewire\MaquinasIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class MaquinasIndexTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(MaquinasIndex::class)
            ->assertStatus(200);
    }
}
