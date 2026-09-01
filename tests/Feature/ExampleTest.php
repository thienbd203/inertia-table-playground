<?php

test('returns a successful response', function () {
    $response = $this->get(route('products.index'));

    $response->assertOk();
});
