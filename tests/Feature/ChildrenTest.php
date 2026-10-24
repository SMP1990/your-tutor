<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\User;
use App\Policies\ChildPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildrenTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parent = User::factory()->parent()->create();
    }

    public function test_parent_can_add_a_child(): void
    {
        $this->actingAs($this->parent)->get('/dashboard/parent/children/new')->assertOk()->assertSee('Add a child');

        $this->post('/dashboard/parent/children', ['name' => ' Ahmed ', 'grade' => 'FSc Part 1'])
            ->assertRedirect(route('dashboard.parent'))
            ->assertSessionHas('notice', 'Ahmed has been added.');

        $this->assertSame('Ahmed', $this->parent->children()->first()->name);
        $this->get('/dashboard/parent')->assertSee('Ahmed')->assertSee('FSc Part 1');
    }

    public function test_parent_can_edit_and_remove_their_child(): void
    {
        $child = $this->parent->children()->create(['name' => 'Sara', 'grade' => 'Grade 6']);
        $this->actingAs($this->parent);

        $this->get("/dashboard/parent/children/{$child->id}/edit")->assertOk()->assertSee('Edit Sara');
        $this->put("/dashboard/parent/children/{$child->id}", ['name' => 'Sara', 'grade' => 'Grade 7'])->assertRedirect();
        $this->assertSame('Grade 7', $child->fresh()->grade);

        $this->delete("/dashboard/parent/children/{$child->id}")->assertRedirect();
        $this->assertModelMissing($child);
    }

    public function test_parent_cannot_touch_another_parents_child(): void
    {
        $other = User::factory()->parent()->create();
        $child = $other->children()->create(['name' => 'Zara', 'grade' => 'Grade 4']);
        $this->actingAs($this->parent);

        $this->get("/dashboard/parent/children/{$child->id}/edit")->assertForbidden();
        $this->put("/dashboard/parent/children/{$child->id}", ['name' => 'Hacked', 'grade' => 'Grade 4'])->assertForbidden();
        $this->delete("/dashboard/parent/children/{$child->id}")->assertForbidden();

        $this->assertSame('Zara', $child->fresh()->name);
    }

    public function test_other_roles_cannot_add_children(): void
    {
        foreach ([User::factory()->create(), User::factory()->tutor()->create()] as $user) { // student, tutor
            $this->actingAs($user)->post('/dashboard/parent/children', ['name' => 'X', 'grade' => 'Grade 1'])->assertForbidden();
        }
        $this->assertSame(0, Child::count());
    }

    public function test_validation(): void
    {
        $this->actingAs($this->parent)
            ->post('/dashboard/parent/children', ['name' => '', 'grade' => 'University'])
            ->assertSessionHasErrors(['name', 'grade']);
    }

    public function test_a_parent_can_add_at_most_ten_children(): void
    {
        Child::factory()->count(ChildPolicy::MAX_CHILDREN)->create(['parent_id' => $this->parent->id]);
        $this->actingAs($this->parent);

        $this->post('/dashboard/parent/children', ['name' => 'Eleven', 'grade' => 'Grade 1'])->assertForbidden();
        $this->get('/dashboard/parent/children/new')->assertRedirect(route('dashboard.parent'));
    }
}
