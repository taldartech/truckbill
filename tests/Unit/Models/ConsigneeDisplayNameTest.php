<?php

namespace Tests\Unit\Models;

use App\Models\Consignee;
use Tests\TestCase;

class ConsigneeDisplayNameTest extends TestCase
{
    public function test_display_name_includes_city_in_brackets(): void
    {
        $consignee = new Consignee(['name' => 'Vikas Trading Co']);
        $consignee->setAttribute('city_name', 'Barmer');

        $this->assertSame('Vikas Trading Co (Barmer)', $consignee->displayNameWithCity());
    }

    public function test_display_name_omits_brackets_when_city_is_missing(): void
    {
        $consignee = new Consignee(['name' => 'Plain Name Ltd']);

        $this->assertSame('Plain Name Ltd', $consignee->displayNameWithCity());
    }
}
