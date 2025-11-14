<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UsersTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTableTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(UsersTable::class)
            ->assertStatus(200);
    }
}
