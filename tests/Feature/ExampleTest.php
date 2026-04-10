<?php

test('returns a successful response', function () {
    $response = $this->get(route('territory-map'));

    $response->assertOk();
});